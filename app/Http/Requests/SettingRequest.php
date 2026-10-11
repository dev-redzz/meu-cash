<?php

namespace App\Http\Requests;

class SettingRequest extends MoneyRequest
{
    protected array $moneyFields = ['initial_balance', 'monthly_goal'];

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:120'],
            'company_document' => ['nullable', 'string', 'max:20'],
            'company_phone' => ['nullable', 'string', 'max:20'],
            'initial_balance' => ['required', 'numeric', 'min:-9999999', 'max:9999999'],
            'monthly_goal' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'due_reminder_days' => ['required', 'integer', 'min:0', 'max:30'],
            'whatsapp_template_lembrete' => ['required', 'string', 'max:1000'],
            'whatsapp_template_vence_hoje' => ['required', 'string', 'max:1000'],
            'whatsapp_template_atrasada' => ['required', 'string', 'max:1000'],
            'whatsapp_template_pagamento' => ['required', 'string', 'max:1000'],
        ];
    }
}
