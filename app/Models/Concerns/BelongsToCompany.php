<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $id = app(Tenant::class)->id();

            if ($id) {
                $builder->where($builder->qualifyColumn('company_id'), $id);
            } else {
                $builder->whereRaw('1 = 0');
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $model->company_id = app(Tenant::class)->id();
            }

            if (empty($model->company_id)) {
                throw new LogicException('Registro sem empresa: nenhuma empresa ativa no contexto.');
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
