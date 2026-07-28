<?php

namespace App\Support;

use App\Models\BlacklistEntry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BlacklistService
{
    public function normalizeCedula(?string $cedula): ?string
    {
        if ($cedula === null || trim($cedula) === '') {
            return null;
        }

        return preg_replace('/[^A-Z0-9-]/', '', strtoupper(trim($cedula))) ?: null;
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        return preg_replace('/[^0-9]/', '', $phone) ?: null;
    }

    private function matchQuery(?string $cedula, ?string $phone, ?int $userId)
    {
        $cedula = $this->normalizeCedula($cedula);
        $phone = $this->normalizePhone($phone);

        if (! $cedula && ! $phone && ! $userId) {
            return null;
        }

        return BlacklistEntry::query()->active()->where(function ($query) use ($cedula, $phone, $userId): void {
            $query->whereRaw('1 = 0');

            if ($userId) {
                $query->orWhere('user_id', $userId);
            }
            if ($cedula) {
                $query->orWhere('cedula', $cedula);
            }
            if ($phone) {
                $query->orWhere('phone', $phone);
            }
        });
    }

    public function findActive(?string $cedula = null, ?string $phone = null, ?int $userId = null): ?BlacklistEntry
    {
        $query = $this->matchQuery($cedula, $phone, $userId);

        return $query?->latest('id')->first();
    }

    public function isBlocked(?string $cedula = null, ?string $phone = null, ?int $userId = null): bool
    {
        return $this->findActive($cedula, $phone, $userId) !== null;
    }

    public function activeEntryForUser(User $user): ?BlacklistEntry
    {
        return $this->findActive($user->cedula, $user->phone, $user->id);
    }

    public function add(array $data, User $admin): BlacklistEntry
    {
        $cedula = $this->normalizeCedula($data['cedula'] ?? null);
        $phone = $this->normalizePhone($data['phone'] ?? null);
        $userId = $data['user_id'] ?? null;
        $reason = trim((string) ($data['reason'] ?? ''));

        if (! $cedula && ! $phone && ! $userId) {
            throw ValidationException::withMessages([
                'cedula' => 'Debes indicar al menos cedula, telefono o una cuenta existente.',
            ]);
        }

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Debes indicar el motivo del bloqueo.',
            ]);
        }

        $user = $userId ? User::query()->find($userId) : null;
        $cedula = $cedula ?? ($user?->cedula ? $this->normalizeCedula($user->cedula) : null);
        $phone = $phone ?? ($user?->phone ? $this->normalizePhone($user->phone) : null);
        $fullName = trim((string) ($data['full_name'] ?? '')) ?: ($user?->full_name ?? $user?->name);

        $existing = $this->findActive($cedula, $phone, $user?->id);
        if ($existing) {
            $existing->forceFill([
                'user_id' => $existing->user_id ?? $user?->id,
                'cedula' => $existing->cedula ?? $cedula,
                'phone' => $existing->phone ?? $phone,
                'full_name' => $fullName ?? $existing->full_name,
                'reason' => $reason,
            ])->save();

            Audit::log('blacklist.entry.updated', 'blacklist_entry', $existing->id, $admin, null, [
                'cedula' => $cedula,
                'phone' => $phone,
                'user_id' => $user?->id,
            ]);

            return $existing;
        }

        $entry = BlacklistEntry::query()->create([
            'user_id' => $user?->id,
            'cedula' => $cedula,
            'phone' => $phone,
            'full_name' => $fullName,
            'status' => 'active',
            'reason' => $reason,
            'created_by_user_id' => $admin->id,
        ]);

        Audit::log('blacklist.entry.created', 'blacklist_entry', $entry->id, $admin, null, [
            'cedula' => $cedula,
            'phone' => $phone,
            'user_id' => $user?->id,
        ]);

        return $entry;
    }

    public function remove(BlacklistEntry $entry, User $admin, ?string $note = null): BlacklistEntry
    {
        $entry->forceFill([
            'status' => 'removed',
            'removed_at' => now(),
            'removed_by_user_id' => $admin->id,
            'removal_note' => $note,
        ])->save();

        Audit::log('blacklist.entry.removed', 'blacklist_entry', $entry->id, $admin, null, [
            'note' => $note,
        ]);

        return $entry;
    }
}
