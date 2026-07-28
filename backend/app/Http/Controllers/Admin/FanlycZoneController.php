<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FanlycZone;
use App\Models\FanlycZoneBranch;
use App\Support\Audit;
use App\Support\Fanlyc\FanlycZoneResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FanlycZoneController extends Controller
{
    public function index(): View
    {
        $zones = FanlycZone::query()
            ->orderBy('sort_order')
            ->with(['branches' => fn ($query) => $query->wherePivot('is_active', true)])
            ->get();

        $assignedBranchIds = FanlycZoneBranch::query()->where('is_active', true)->pluck('branch_id');

        $availableBranches = Branch::query()
            ->where('is_active', true)
            ->whereNotIn('id', $assignedBranchIds)
            ->orderBy('name')
            ->get();

        return view('admin.fanlyc-zones', [
            'zones' => $zones,
            'availableBranches' => $availableBranches,
        ]);
    }

    public function assignBranch(Request $request, FanlycZoneResolver $resolver): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'fanlyc_zone_id' => ['required', 'integer', 'exists:fanlyc_zones,id'],
        ]);

        $resolver->assignBranchToZone((int) $validated['branch_id'], (int) $validated['fanlyc_zone_id']);

        Audit::log('fanlyc.zone_branch.assigned', 'fanlyc_zone_branch', null, $request->user(), $request, $validated);

        return back()->with('status', 'Sucursal asignada a la zona.');
    }

    public function unassignBranch(Request $request, FanlycZoneBranch $mapping, FanlycZoneResolver $resolver): RedirectResponse
    {
        $resolver->unassignBranch($mapping->id);

        Audit::log('fanlyc.zone_branch.unassigned', 'fanlyc_zone_branch', $mapping->id, $request->user(), $request, [
            'branch_id' => $mapping->branch_id,
            'fanlyc_zone_id' => $mapping->fanlyc_zone_id,
        ]);

        return back()->with('status', 'Sucursal quitada de la zona.');
    }
}
