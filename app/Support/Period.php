<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class Period
{
    public const OPTIONS = [
        'hoje' => 'Hoje',
        '7dias' => 'Últimos 7 dias',
        'mes' => 'Este mês',
        'mes_anterior' => 'Mês anterior',
        'ano' => 'Este ano',
        'personalizado' => 'Personalizado',
    ];

    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly string $key,
    ) {
    }

    public static function fromRequest(Request $request, string $default = 'mes'): self
    {
        $key = $request->input('periodo', $default);
        $today = Carbon::today();

        return match ($key) {
            'hoje' => new self($today->copy(), $today->copy()->endOfDay(), $key),
            '7dias' => new self($today->copy()->subDays(6), $today->copy()->endOfDay(), $key),
            'mes_anterior' => new self($today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth(), $key),
            'ano' => new self($today->copy()->startOfYear(), $today->copy()->endOfYear(), $key),
            'personalizado' => self::custom($request),
            default => new self($today->copy()->startOfMonth(), $today->copy()->endOfMonth(), 'mes'),
        };
    }

    private static function custom(Request $request): self
    {
        try {
            $start = Carbon::parse($request->input('inicio', Carbon::today()->startOfMonth()))->startOfDay();
            $end = Carbon::parse($request->input('fim', Carbon::today()))->endOfDay();
        } catch (\Throwable) {
            return new self(Carbon::today()->startOfMonth(), Carbon::today()->endOfMonth(), 'mes');
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return new self($start, $end, 'personalizado');
    }

    public function label(): string
    {
        if ($this->key !== 'personalizado') {
            return self::OPTIONS[$this->key].' ('.$this->start->format('d/m/Y').' a '.$this->end->format('d/m/Y').')';
        }

        return $this->start->format('d/m/Y').' a '.$this->end->format('d/m/Y');
    }

    public function query(): array
    {
        return ['periodo' => $this->key, 'inicio' => $this->start->toDateString(), 'fim' => $this->end->toDateString()];
    }

    public function startDate(): string
    {
        return $this->start->toDateString();
    }

    public function endDate(): string
    {
        return $this->end->toDateString();
    }
}
