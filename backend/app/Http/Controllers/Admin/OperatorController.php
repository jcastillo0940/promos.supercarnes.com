<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\OperatorPromoPermission;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
            'email'     => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password'  => ['required', 'string', 'min:8'],
            'modules'   => ['required', 'array', 'min:1'],
            'modules.*' => ['required', 'string', Rule::in(array_keys(self::MODULES))],
        ]);

        $operator = User::query()->create([
            'name'               => $validated['full_name'],
            'full_name'          => $validated['full_name'],
            'email'              => $validated['email'],
            'password'           => Hash::make($validated['password']),
            'role'               => 'operator',
            'admin_modules'      => $validated['modules'],
            'is_active'          => true,
            'email_verified_at'  => now(),
        ]);

        $this->savePromoPermissions($operator, $request);

        Audit::log('operator_created', 'user', $operator->id, $request->user(), $request, [
            'email'   => $operator->email,
            'modules' => $validated['modules'],
        ]);

        return back()->with('status', 'Operador creado. Ya puede iniciar sesión en /admin/login.');
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
        $incoming = $request->input('promo_permissions', []);

        if (! is_array($incoming) || $incoming === []) {
            return;
        }

        $allowed = array_keys(self::PERMISSIONS);

        foreach ($incoming as $promoKey => $perms) {
            $promoKey = (string) $promoKey;
            if (! is_array($perms)) {
                continue;
            }

            $permissions = [];
            foreach ($allowed as $perm) {
                $permissions[$perm] = ! empty($perms[$perm]);
            }

            OperatorPromoPermission::query()->updateOrCreate(
                ['operator_id' => $operator->id, 'promo_key' => $promoKey],
                ['permissions' => $permissions],
            );
        }

        // Remove promo keys that are no longer in the submitted list (promo unchecked).
        $operator->promoPermissions()
            ->whereNotIn('promo_key', array_keys($incoming))
            ->delete();
    }
}
