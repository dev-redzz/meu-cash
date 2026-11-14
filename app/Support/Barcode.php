<?php

namespace App\Support;

/**
 * Código de barras Code 128 (conjunto B, e C para trechos numéricos longos) em SVG.
 * Qualquer leitor de código de barras comum lê esse formato.
 */
class Barcode
{
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    /** Sequência de códigos (valores 0-106) com início, dados, dígito verificador e parada. */
    public static function codes(string $text): array
    {
        $text = preg_replace('/[^\x20-\x7E]/', '', $text);
        $codes = [];
        $set = null;
        $i = 0;
        $len = strlen($text);

        while ($i < $len) {
            $digits = strspn($text, '0123456789', $i);
            $useC = $digits >= 4 && ($digits % 2 === 0 || $i + $digits === $len || $digits >= 6);

            if ($useC) {
                $take = $digits - ($digits % 2);
                if ($set !== 'C') {
                    $codes[] = $set === null ? 105 : 99;
                    $set = 'C';
                }
                for ($j = 0; $j < $take; $j += 2) {
                    $codes[] = (int) substr($text, $i + $j, 2);
                }
                $i += $take;

                continue;
            }

            if ($set !== 'B') {
                $codes[] = $set === null ? 104 : 100;
                $set = 'B';
            }
            $codes[] = ord($text[$i]) - 32;
            $i++;
        }

        $sum = $codes[0];
        foreach (array_slice($codes, 1) as $pos => $code) {
            $sum += $code * ($pos + 1);
        }
        $codes[] = $sum % 103;
        $codes[] = 106;

        return $codes;
    }

    public static function svg(string $text, int $height = 40, float $module = 1.0): string
    {
        $x = 10 * $module;
        $rects = '';

        foreach (self::codes($text) as $code) {
            $bar = true;
            foreach (str_split(self::PATTERNS[$code]) as $width) {
                $w = (int) $width * $module;
                if ($bar) {
                    $rects .= '<rect x="'.round($x, 2).'" y="0" width="'.round($w, 2).'" height="'.$height.'"/>';
                }
                $x += $w;
                $bar = ! $bar;
            }
        }

        $total = round($x + 10 * $module, 2);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$total.' '.$height.'" preserveAspectRatio="none" class="barcode" role="img" aria-label="Código de barras '.e($text).'"><rect width="100%" height="100%" fill="#fff"/><g fill="#000">'.$rects.'</g></svg>';
    }

    /** Gera um EAN-13 de uso interno (prefixo 2, reservado para códigos da própria loja). */
    public static function internalEan(int $companyId, int $productId): string
    {
        $base = '2'.str_pad((string) ($companyId % 1000), 3, '0', STR_PAD_LEFT).str_pad((string) $productId, 8, '0', STR_PAD_LEFT);
        $base = substr($base, 0, 12);
        $sum = 0;
        foreach (str_split($base) as $i => $d) {
            $sum += (int) $d * ($i % 2 === 0 ? 1 : 3);
        }

        return $base.((10 - $sum % 10) % 10);
    }
}
