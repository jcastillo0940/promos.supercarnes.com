<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function index(): View
    {
        return view('admin.operators', [
            'operators' => User::query()->where('role', 'operator')->orderBy('full_name')->get(),
            'modules'   => self::MODULES,
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
}
