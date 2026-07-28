<?php

namespace App\Support\Fanlyc;

use App\Models\FanlycZone;
use App\Models\FanlycZoneBranch;
use Illuminate\Support\Facades\DB;

class FanlycZoneResolver
{
    public function zoneForBranch(int $branchId): ?FanlycZone
    {
        $zoneId = FanlycZoneBranch::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->value('fanlyc_zone_id');

        if (! $zoneId) {
            return null;
        }

        return FanlycZone::query()->where('id', $zoneId)->where('is_active', true)->first();
    }

    public function assignBranchToZone(int $branchId, int $zoneId): void
    {
        DB::transaction(function () use ($branchId, $zoneId): void {
            FanlycZoneBranch::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $mapping = FanlycZoneBranch::query()->firstOrNew([
                'branch_id' => $branchId,
                'fanlyc_zone_id' => $zoneId,
            ]);

            $mapping->is_active = true;
            $mapping->save();
        });
    }

    public function unassignBranch(int $mappingId): void
    {
        FanlycZoneBranch::query()->whereKey($mappingId)->update(['is_active' => false]);
    }
}
