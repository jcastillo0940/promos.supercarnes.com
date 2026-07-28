<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlacklistEntry;
use App\Models\User;
use App\Support\BlacklistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlacklistController extends Controller
{
    public function __construct(private readonly BlacklistService $blacklist)
    {
    }

    public function index(Request $request): View
    {
        $query = BlacklistEntry::query()->with(['user', 'createdBy', 'removedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->orderByRaw("status = 'active' desc");
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $normalizedCedula = $this->blacklist->normalizeCedula($term);
            $normalizedPhone = $this->blacklist->normalizePhone($term);

            $query->where(function ($query) use ($term, $normalizedCedula, $normalizedPhone): void {
                $query->where('full_name', 'like', "%{$term}%")
                    ->orWhere('reason', 'like', "%{$term}%");

                if ($normalizedCedula) {
                    $query->orWhere('cedula', 'like', "%{$normalizedCedula}%");
                }
                if ($normalizedPhone) {
                    $query->orWhere('phone', 'like', "%{$normalizedPhone}%");
                }
            });
        }

        $entries = $query->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.blacklist', [
            'entries' => $entries,
            'activeCount' => BlacklistEntry::query()->active()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'cedula' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:30'],
            'full_name' => ['nullable', 'string', 'max:180'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->blacklist->add($validated, $request->user());

        return back()->with('status', 'Persona agregada a la blacklist. Quedara bloqueada de todas las promociones.');
    }

    public function destroy(Request $request, BlacklistEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'removal_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->blacklist->remove($entry, $request->user(), $validated['removal_note'] ?? null);

        return back()->with('status', 'Persona removida de la blacklist.');
    }
}
