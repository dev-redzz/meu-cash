<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceExpense extends Model
{
    protected $fillable = ['service_id', 'description', 'amount', 'date', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'date' => 'date'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
