<?php

namespace App\Helpers;

use App\Models\Persona;

class OmsHelper
{
    /**
     * Tablas simplificadas OMS 2006 de peso-para-talla (z-score -2).
     * Clave = talla en cm (redondeada), valor = peso mínimo normal en kg.
     * Valores por debajo = desnutrición aguda.
     */
    private const PESO_TALLA_MINIMO = [
        45 => 2.0, 50 => 2.6, 55 => 3.4, 60 => 4.4, 65 => 5.5,
        70 => 6.4, 75 => 7.2, 80 => 8.0, 85 => 8.8, 90 => 9.6,
        95 => 10.5, 100 => 11.4, 105 => 12.5, 110 => 13.7, 115 => 15.1,
        120 => 16.6,
    ];

    /**
     * Tablas simplificadas OMS 2006 de talla-para-edad (z-score -2).
     * Clave = edad en meses, valor = talla mínima normal en cm.
     * Valores por debajo = desnutrición crónica.
     */
    private const TALLA_EDAD_MINIMA = [
        0 => 44, 3 => 55, 6 => 61, 9 => 65, 12 => 68,
        18 => 73, 24 => 78, 36 => 85, 48 => 91, 60 => 96,
        72 => 101, 84 => 106, 96 => 111, 108 => 116, 120 => 120,
    ];

    /**
     * Tablas simplificadas OMS 2006 de peso-para-edad (z-score -2).
     * Clave = edad en meses, valor = peso mínimo normal en kg.
     * Valores por debajo = desnutrición global.
     */
    private const PESO_EDAD_MINIMO = [
        0 => 2.5, 3 => 4.5, 6 => 5.9, 9 => 6.9, 12 => 7.5,
        18 => 8.5, 24 => 9.5, 36 => 11.0, 48 => 12.5, 60 => 14.0,
    ];

    /**
     * Clasifica estado nutricional según tablas OMS simplificadas.
     */
    public static function clasificar(Persona $persona, ?float $pesoKg, ?float $tallaCm): string
    {
        if ($pesoKg === null || $tallaCm === null) {
            return 'normal';
        }

        $meses = $persona->edad_meses;

        // 1. Desnutrición aguda: bajo peso para la talla
        $tallaRef = self::interpolate(self::PESO_TALLA_MINIMO, (int) round($tallaCm));
        if ($tallaRef !== null && $pesoKg < $tallaRef) {
            return 'desnutricion_aguda';
        }

        // 2. Desnutrición crónica: baja talla para la edad
        $tallaMinima = self::interpolate(self::TALLA_EDAD_MINIMA, $meses);
        if ($tallaMinima !== null && $tallaCm < $tallaMinima) {
            return 'desnutricion_cronica';
        }

        // 3. Desnutrición global: bajo peso para la edad
        $pesoMinimo = self::interpolate(self::PESO_EDAD_MINIMO, $meses);
        if ($pesoMinimo !== null && $pesoKg < $pesoMinimo) {
            return 'desnutricion_global';
        }

        // 4. Sobrepeso/Obesidad: peso > 120% del esperado para la talla
        if ($tallaRef !== null) {
            $ratio = $pesoKg / $tallaRef;
            if ($ratio > 1.4) {
                return 'obesidad';
            }
            if ($ratio > 1.2) {
                return 'sobrepeso';
            }
        }

        return 'normal';
    }

    /**
     * Interpolación lineal entre los puntos de referencia.
     */
    private static function interpolate(array $table, int $key): ?float
    {
        if (isset($table[$key])) {
            return $table[$key];
        }

        $keys = array_keys($table);
        $lower = null;
        $upper = null;

        foreach ($keys as $k) {
            if ($k <= $key) {
                $lower = $k;
            }
            if ($k >= $key && $upper === null) {
                $upper = $k;
            }
        }

        if ($lower === null || $upper === null || $lower === $upper) {
            return $lower !== null ? $table[$lower] : ($upper !== null ? $table[$upper] : null);
        }

        $ratio = ($key - $lower) / ($upper - $lower);

        return $table[$lower] + ($table[$upper] - $table[$lower]) * $ratio;
    }

    public const CLASIFICACION_LABELS = [
        'normal'                => 'Normal',
        'desnutricion_aguda'    => 'Desnutrición aguda',
        'desnutricion_cronica'  => 'Desnutrición crónica',
        'desnutricion_global'   => 'Desnutrición global',
        'sobrepeso'             => 'Sobrepeso',
        'obesidad'              => 'Obesidad',
    ];

    public const CLASIFICACION_COLORS = [
        'normal'                => 'success',
        'desnutricion_aguda'    => 'danger',
        'desnutricion_cronica'  => 'warning',
        'desnutricion_global'   => 'danger',
        'sobrepeso'             => 'warning',
        'obesidad'              => 'danger',
    ];
}
