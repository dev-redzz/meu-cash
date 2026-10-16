<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use BelongsToCompany;

    public const DEFAULTS = [
        'initial_balance' => '0',
        'monthly_goal' => '0',
        'due_reminder_days' => '3',
        'weekly_summary' => '1',
        'receipt_terms' => "O COMPRADOR declara ter recebido os produtos descritos neste documento, conferidos e em perfeito estado de funcionamento.\nNas compras parceladas, o COMPRADOR reconhece o saldo em aberto como dívida e se compromete a pagar cada parcela até a data de vencimento indicada.\nO atraso no pagamento pode gerar a cobrança dos valores devidos, conforme combinado entre as partes.\nOs produtos têm a garantia legal prevista no Código de Defesa do Consumidor. A garantia não cobre mau uso, quedas, contato com líquidos ou violação do aparelho.\nEste documento não substitui a nota fiscal.",
        'whatsapp_template_lembrete' => 'Olá, {cliente}! Passando para lembrar que a parcela {parcela} no valor de {valor} vence em {vencimento}. Qualquer dúvida, estou à disposição. {empresa}',
        'whatsapp_template_vence_hoje' => 'Olá, {cliente}! A parcela {parcela} no valor de {valor} vence hoje ({vencimento}). Pode me enviar o comprovante por aqui quando pagar. {empresa}',
        'whatsapp_template_atrasada' => 'Olá, {cliente}! A parcela {parcela} no valor de {valor}, com vencimento em {vencimento}, consta em aberto. Podemos combinar o pagamento? {empresa}',
        'whatsapp_template_pagamento' => 'Olá, {cliente}! Confirmo o recebimento da parcela {parcela} no valor de {valor}. Obrigado! {empresa}',
    ];

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $company = tenant_id();

        $value = $company
            ? Cache::rememberForever("setting.{$company}.{$key}", fn () => static::query()->where('key', $key)->value('value'))
            : null;

        return $value ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('setting.'.tenant_id().'.'.$key);
    }
}
