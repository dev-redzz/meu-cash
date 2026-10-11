<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    use BelongsToCompany;

    protected $table = 'whatsapp_messages';

    protected $fillable = ['customer_id', 'installment_id', 'user_id', 'phone', 'type', 'message', 'channel'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }
}
