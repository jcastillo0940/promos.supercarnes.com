<?php

namespace App\Models;

use Database\Factories\UserFactory;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'full_name',
        'branch_id',
        'role',
        'admin_modules',
        'cedula',
        'document_type',
        'email',
        'google_id',
        'phone',
        'avatar_path',
        'password',
        'is_active',
        'birthdate',
        'resides_in_panama',
        'is_employee',
        'accepted_terms_at',
        'registration_completed_at',
        'registration_order_key',
        'predictions_completed_at',
        'group_stage_goal_prediction',
        'disqualified_at',
        'disqualification_reason',
        'email_verified_at',
        'email_otp_code_hash',
        'email_otp_expires_at',
        'email_otp_verified_at',
        'last_login_at',
        'entrepreneur_name',
        'entrepreneur_province',
        'nearest_branch_id',
        'entrepreneur_type',
        'entrepreneur_story',
        'entrepreneur_reason',
        'dream_promo_qualified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_otp_expires_at' => 'datetime',
            'email_otp_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'birthdate' => 'date',
            'resides_in_panama' => 'boolean',
            'is_employee' => 'boolean',
            'accepted_terms_at' => 'datetime',
            'registration_completed_at' => 'datetime',
            'predictions_completed_at' => 'datetime',
            'group_stage_goal_prediction' => 'integer',
            'disqualified_at' => 'datetime',
            'dream_promo_qualified_at' => 'datetime',
            'admin_modules' => 'array',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(RegisteredInvoice::class);
    }

    public function fraudFlags(): HasMany
    {
        return $this->hasMany(FraudFlag::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function fanlycInvoices(): HasMany
    {
        return $this->hasMany(FanlycInvoice::class);
    }

    public function fanlycCoupons(): HasMany
    {
        return $this->hasMany(FanlycCoupon::class);
    }

    public function promoPermissions(): HasMany
    {
        return $this->hasMany(OperatorPromoPermission::class, 'operator_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function isSupervisor(): bool
    {
        return in_array($this->role, ['supervisor', 'manager'], true);
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isJury(): bool
    {
        return $this->role === 'jurado';
    }

    public function isFanlycStaff(): bool
    {
        return $this->role === 'staff_fanlyc';
    }

    public function canAccessModule(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isOperator() && is_array($this->admin_modules)) {
            return in_array($module, $this->admin_modules, true);
        }

        return false;
    }

    /**
     * Check a granular permission for a specific promo.
     * Falls back to full access when no row exists in operator_promo_permissions
     * (preserves existing operator access during and after migration).
     */
    public function canDoPromo(string $promoKey, string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        /** @var OperatorPromoPermission|null $record */
        $record = $this->promoPermissions()
            ->where('promo_key', $promoKey)
            ->first();

        if ($record !== null) {
            return (bool) ($record->permissions[$permission] ?? false);
        }

        // Fallback: if the operator has module-level access, allow everything.
        $moduleKey = str_starts_with($promoKey, 'malta-') ? 'malta' : $promoKey;

        return $this->canAccessModule($moduleKey);
    }

    public function firstModuleRoute(): string
    {
        foreach ((array) ($this->admin_modules ?? []) as $module) {
            if ($module === 'malta') {
                $campaign = Campaign::query()
                    ->where('participation_mode', 'product_ranking')
                    ->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 ELSE 4 END")
                    ->first();
                if ($campaign) {
                    return route('admin.campaigns.product-ranking.operations', $campaign);
                }
                continue;
            }

            $map = [
                'saba'          => 'admin.saba',
                'nivea'         => 'admin.nivea',
                'fanlyc'        => 'admin.fanlyc',
                'fonda'         => 'admin.fonda-challenge',
                'entrepreneurs' => 'admin.entrepreneurs',
                'facturas'      => 'admin.invoices',
                'blacklist'     => 'admin.blacklist',
                'audit'         => 'admin.audit',
            ];

            if (isset($map[$module])) {
                return route($map[$module]);
            }
        }

        return route('admin.login');
    }
}
