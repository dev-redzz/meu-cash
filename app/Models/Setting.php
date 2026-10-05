<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const DEFAULTS = [
        'company_name' => 'Meu Cash',
        'company_phone' => '',
        'initial_balance' => '0',
        'due_reminder_days' => '3',
        'whatsapp_template_lembrete' => 'Olá, {cliente}! Passando para lembrar que a parcela {parcela} no valor de {valor} vence em {vencimento}. Qualquer dúvida, estou à disposição. {empresa}',
        'whatsapp_template_vence_hoje' => 'Olá, {cliente}! A parcela {parcela} no valor de {valor} vence hoje ({vencimento}). Pode me enviar o comprovante por aqui quando pagar. {empresa}',
        'whatsapp_template_atrasada' => 'Olá, {cliente}! A parcela {parcela} no valor de {valor}, com vencimento em {vencimento}, consta em aberto. Podemos combinar o pagamento? {empresa}',
        'whatsapp_template_pagamento' => 'Olá, {cliente}! Confirmo o recebimento da parcela {parcela} no valor de {valor}. Obrigado! {empresa}',
    ];

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever("setting.{$key}", fn () => static::query()->where('key', $key)->value('value'));

        return $value ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }
}
