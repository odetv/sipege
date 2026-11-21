<?php

namespace App\Traits;

use App\Models\UnitSppg;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    /**
     * Boot the trait: otomatis isi unit_sppg_id saat model baru dibuat.
     */
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->unit_sppg_id)) {
                $tenantId = static::resolveCurrentTenantId();
                if ($tenantId) {
                    $model->unit_sppg_id = $tenantId;
                }
            }
        });
    }

    /**
     * Relasi ke Unit SPPG (Tenant).
     */
    public function unitSppg()
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }

    /**
     * Scope query otomatis menyaring data milik tenant saat ini.
     */
    public function scopeForCurrentTenant(Builder $query): Builder
    {
        $tenantId = static::resolveCurrentTenantId();
        if ($tenantId) {
            return $query->where($this->qualifyColumn('unit_sppg_id'), $tenantId);
        }
        return $query;
    }

    /**
     * Scope query untuk tenant tertentu.
     */
    public function scopeForTenant(Builder $query, int|string $tenantId): Builder
    {
        return $query->where($this->qualifyColumn('unit_sppg_id'), $tenantId);
    }

    /**
     * Resolve ID tenant aktif berdasarkan user login atau container binding.
     */
    public static function resolveCurrentTenantId(): ?int
    {
        if (app()->has('current_tenant_id')) {
            return (int) app('current_tenant_id');
        }

        if (Auth::check()) {
            $user = Auth::user();
            if (!empty($user->unit_sppg_id)) {
                return (int) $user->unit_sppg_id;
            }
            if (method_exists($user, 'getCachedUnitSppg')) {
                $unit = $user->getCachedUnitSppg();
                if ($unit) {
                    return (int) $unit->id;
                }
            }
        }

        return null;
    }
}

