<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OperatorInvitation;
use App\Models\Campaign;
use App\Models\OperatorPromoPermission;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OperatorController extends Controller
{
    const MODULES = [
        'saba'          => 'Saba · Emprende tu Sueño',
        'nivea'         => 'NIVEA · Empollerando a Mamá',
        'malta'         => 'Malta Vigor',
        'fanlyc'        => 'Fanlyc',
        'fonda'         => 'Fonda Challenge',
        'entrepreneurs' => 'Del sueño al puesto',
        'facturas'      => 'Facturas y Ganadores',
        'blacklist'     => 'Blacklist',
        'audit'         => 'Auditoría',
    ];

    const PERMISSIONS = [
        'view_dashboard'        => 'Ver dashboard',
        'view_participants'     => 'Ver participantes',
        'view_personal_data'    => 'Ver datos personales',
        'export_data'           => 'Exportar datos',
        'view_invoices'         => 'Ver facturas',
        'upload_invoice_manual' => 'Subir factura manual',
        'view_winners'          => 'Ver ganadoras',
        'deliver_prizes'        => 'Entregar premios',
        'view_fraud'            => 'Ver fraude',
        'modify_config'         => 'Modificar configuración',
        'activate_pause'        => 'Activar/pausar',
    ];

    const TEMPLATES = [
        'readonly'    => ['view_dashboard' => true, 'view_participants' => true, 'view_personal_data' => false, 'export_data' => false, 'view_invoices' => false, 'upload_invoice_manual' => false, 'view_winners' => false, 'deliver_prizes' => false, 'view_fraud' => false, 'modify_config' => false, 'activate_pause' => false],
        'invoice_op'  => ['view_dashboard' => true, 'view_participants' => true, 'view_personal_data' => true,  'export_data' => true,  'view_invoices' => true,  'upload_invoice_manual' => true,  'view_winners' => true,  'deliver_prizes' => false, 'view_fraud' => false, 'modify_config' => false, 'activate_pause' => false],
        'full'        => ['view_dashboard' => true, 'view_participants' => true, 'view_personal_data' => true,  'export_data' => true,  'view_invoices' => true,  'upload_invoice_manual' => true,  'view_winners' => true,  'deliver_prizes' => true,  'view_fraud' => true,  'modify_config' => true,  'activate_pause' => true],
    ];

    /** Promo-modules that support granular permissions (keyed by module). */
    const PROMO_MODULES = ['saba', 'nivea', 'malta', 'fanlyc', 'fonda', 'entrepreneurs'];

    public function index(): View
    {
        $operators = User::query()
            ->where('role', 'operator')
            ->orderBy('full_name')
            ->with('promoPermissions')
            ->get();

        $maltaCampaigns = Campaign::query()
            ->where('participation_mode', 'product_ranking')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status']);

        return view('admin.operators', [
            'operators'     => $operators,
            'modules'       => self::MODULES,
            'permissions'   => self::PERMISSIONS,
            'templates'     => self::TEMPLATES,
            'promoModules'  => self::PROMO_MODULES,
            'maltaCampaigns'=> $maltaCampaigns,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email'     => ['required', 'email', 'max:150'],
            'password'  => ['nullable', 'string', 'min:8'],
            'modules'   => ['required', 'array', 'min:1'],
            'modules.*' => ['required', 'string', Rule::in(array_keys(self::MODULES))],
        ]);

        $existing = User::query()->whereRaw('LOWER(email) = ?', [Str::lower($validated['email'])])->first();

        if ($existing && in_array($existing->role, ['admin', 'operator', 'supervisor'])) {
            throw ValidationException::withMessages(['email' => 'Este correo ya pertenece a una cuenta de backoffice ('.$existing->role.').']);
        }

        $sendInvite = blank($validated['password'] ?? null);
        $promoted   = false;

        if ($existing) {
            // Promote existing client account to operator
            $existing->forceFill([
                'full_name'         => $validated['full_name'],
                'name'              => $validated['full_name'],
                'role'              => 'operator',
                'admin_modules'     => $validated['modules'],
                'is_active'         => true,
                'email_verified_at' => $existing->email_verified_at ?? now(),
            ]);
            if (! $sendInvite) {
                $existing->forceFill(['password' => Hash::make($validated['password'])]);
            }
            $existing->save();
            $operator = $existing;
            $promoted = true;
        } else {
            $operator = User::query()->create([
                'name'               => $validated['full_name'],
                'full_name'          => $validated['full_name'],
                'email'              => $validated['email'],
                'password'           => Hash::make($validated['password'] ?? Str::random(32)),
                'role'               => 'operator',
                'admin_modules'      => $validated['modules'],
                'is_active'          => true,
                'email_verified_at'  => now(),
            ]);
        }

        $this->savePromoPermissions($operator, $request);

        Audit::log($promoted ? 'operator_promoted_from_client' : 'operator_created', 'user', $operator->id, $request->user(), $request, [
            'email'   => $operator->email,
            'modules' => $validated['modules'],
        ]);

        if ($sendInvite) {
            try {
                $token    = Password::broker()->createToken($operator);
                $resetUrl = url(route('admin.password.reset', ['token' => $token, 'email' => $operator->email], false));
                Mail::to($operator->email)->send(new OperatorInvitation($operator, $resetUrl));
            } catch (\Throwable $e) {
                report($e);
            }

            $verb = $promoted ? 'promovido a operador' : 'creado';
            return back()->with('status', "Operador {$verb}. Se envió un correo a {$operator->email} con el enlace para crear su contraseña.");
        }

        $verb = $promoted ? 'promovido a operador' : 'creado';
        return back()->with('status', "Operador {$verb}. Ya puede iniciar sesión en /admin/login.");
    }

    public function updateModules(Request $request, User $operator): RedirectResponse
    {
        abort_unless($operator->role === 'operator', 404);

        $validated = $request->validate([
            'modules'   => ['required', 'array', 'min:1'],
            'modules.*' => ['required', 'string', Rule::in(array_keys(self::MODULES))],
        ]);

        $operator->update(['admin_modules' => $validated['modules']]);

        $this->savePromoPermissions($operator, $request);

        Audit::log('operator_modules_updated', 'user', $operator->id, $request->user(), $request, [
            'modules' => $validated['modules'],
        ]);

        return back()->with('status', "Módulos de {$operator->full_name} actualizados.");
    }

    public function resendInvite(Request $request, User $operator): RedirectResponse
    {
        abort_unless($operator->role === 'operator', 404);

        try {
            $token    = Password::broker()->createToken($operator);
            $resetUrl = url(route('admin.password.reset', ['token' => $token, 'email' => $operator->email], false));
            Mail::to($operator->email)->send(new OperatorInvitation($operator, $resetUrl));
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['status' => 'No se pudo enviar el correo. Revisa la configuración de mail.']);
        }

        Audit::log('operator_invite_resent', 'user', $operator->id, $request->user(), $request, [
            'email' => $operator->email,
        ]);

        return back()->with('status', "Enlace de acceso reenviado a {$operator->email}.");
    }

    public function toggleStatus(Request $request, User $operator): RedirectResponse
    {
        abort_unless($operator->role === 'operator', 404);

        $operator->update(['is_active' => ! $operator->is_active]);

        Audit::log('operator_status_toggled', 'user', $operator->id, $request->user(), $request, [
            'is_active' => $operator->is_active,
        ]);

        return back()->with('status', $operator->is_active ? 'Operador activado.' : 'Operador desactivado.');
    }

    /** Upsert operator_promo_permissions from the promo_permissions[promoKey][permName] inputs. */
    private function savePromoPermissions(User $operator, Request $request): void
    {
        $incoming   = is_array($request->input('promo_permissions')) ? $request->input('promo_permissions') : [];
        $allowed    = array_keys(self::PERMISSIONS);
        $denyAll    = array_fill_keys($allowed, false);
        $written    = [];

        // For every promo module the operator is assigned to, ensure a permission row exists.
        // If the admin submitted explicit permissions, use those; otherwise write deny-all.
        // This prevents the canDoPromo() fallback from granting implicit full access.
        $assignedPromos = array_intersect((array) ($operator->admin_modules ?? []), self::PROMO_MODULES);
        foreach ($assignedPromos as $promoKey) {
            $perms = $denyAll;
            if (isset($incoming[$promoKey]) && is_array($incoming[$promoKey])) {
                foreach ($allowed as $perm) {
                    $perms[$perm] = ! empty($incoming[$promoKey][$perm]);
                }
            }
            OperatorPromoPermission::query()->updateOrCreate(
                ['operator_id' => $operator->id, 'promo_key' => $promoKey],
                ['permissions' => $perms],
            );
            $written[] = $promoKey;
        }

        // Handle extra keys in $incoming (e.g., malta-{campaign_id}) not in PROMO_MODULES.
        foreach ($incoming as $promoKey => $perms) {
            $promoKey = (string) $promoKey;
            if (in_array($promoKey, $written, true) || ! is_array($perms)) {
                continue;
            }
            $permissions = $denyAll;
            foreach ($allowed as $perm) {
                $permissions[$perm] = ! empty($perms[$perm]);
            }
            OperatorPromoPermission::query()->updateOrCreate(
                ['operator_id' => $operator->id, 'promo_key' => $promoKey],
                ['permissions' => $permissions],
            );
            $written[] = $promoKey;
        }

        // Remove rows for promo keys that are no longer assigned or submitted.
        $operator->promoPermissions()
            ->whereNotIn('promo_key', $written)
            ->delete();
    }
}
