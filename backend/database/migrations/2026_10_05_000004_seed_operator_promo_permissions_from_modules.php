<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Permissions given to operators migrated from admin_modules (Operador de facturas template). */
    private const BASE = [
        'view_dashboard'        => true,
        'view_participants'     => true,
        'view_personal_data'    => true,
        'export_data'           => true,
        'view_invoices'         => true,
        'upload_invoice_manual' => true,
        'view_winners'          => true,
        'deliver_prizes'        => false,
        'view_fraud'            => false,
        'modify_config'         => false,
        'activate_pause'        => false,
    ];

    public function up(): void
    {
        $operators = DB::table('users')
            ->where('role', 'operator')
            ->whereNotNull('admin_modules')
            ->get(['id', 'admin_modules']);

        $maltaIds = DB::table('campaigns')
            ->where('participation_mode', 'product_ranking')
            ->pluck('id');

        $now  = now();
        $rows = [];

        foreach ($operators as $operator) {
            $modules = json_decode($operator->admin_modules, true) ?? [];

            foreach ($modules as $module) {
                foreach ($this->promoKeysFor($module, $maltaIds) as $promoKey) {
                    $permissions              = self::BASE;
                    $permissions['deliver_prizes'] = ($module === 'fanlyc');
                    $permissions['view_fraud']     = ($module === 'malta');

                    $rows[] = [
                        'operator_id' => $operator->id,
                        'promo_key'   => $promoKey,
                        'permissions' => json_encode($permissions),
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }
        }

        if ($rows !== []) {
            DB::table('operator_promo_permissions')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        $operators = DB::table('users')
            ->where('role', 'operator')
            ->whereNotNull('admin_modules')
            ->get(['id', 'admin_modules']);

        $maltaIds = DB::table('campaigns')
            ->where('participation_mode', 'product_ranking')
            ->pluck('id');

        foreach ($operators as $operator) {
            $modules  = json_decode($operator->admin_modules, true) ?? [];
            $keys     = [];
            foreach ($modules as $module) {
                $keys = array_merge($keys, $this->promoKeysFor($module, $maltaIds));
            }
            if ($keys !== []) {
                DB::table('operator_promo_permissions')
                    ->where('operator_id', $operator->id)
                    ->whereIn('promo_key', array_unique($keys))
                    ->delete();
            }
        }
    }

    private function promoKeysFor(string $module, \Illuminate\Support\Collection $maltaIds): array
    {
        return match ($module) {
            'malta'         => $maltaIds->map(fn ($id) => "malta-{$id}")->all(),
            'saba', 'nivea', 'fanlyc', 'fonda', 'entrepreneurs' => [$module],
            default         => [],
        };
    }
};
