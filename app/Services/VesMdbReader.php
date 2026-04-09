<?php

namespace App\Services;

/**
 * Lee tablas de un archivo MDB en formato Jet 3.x (Access 97).
 *
 * Estrategia:
 *   - Windows → llama al script VBScript con DAO.DBEngine.36 (32-bit cscript.exe)
 *   - Linux   → usa mdbtools (mdb-json / mdb-export)
 *
 * Uso:
 *   $reader = new VesMdbReader('C:\ruta\transfer.mdb');
 *   $rows   = $reader->tabla('301G01_DAT05');
 *   $rows   = $reader->tabla('301G01_DAT05', "corr_estabgest='26300183'");
 */
class VesMdbReader
{
    private string $mdbPath;

    /** Ruta al cscript.exe de 32-bit (necesario para DAO.DBEngine.36) */
    private const CSCRIPT32 = 'C:\\Windows\\SysWow64\\cscript.exe';

    /** Ruta al VBScript lector, relativa a base_path() */
    private const VBS_SCRIPT = 'resources/scripts/leer_ves.vbs';

    /** Nombres de tabla permitidos (whitelist para prevenir inyección) */
    private const TABLAS_PERMITIDAS = [
        '301_CAB', '301G01_TMP', '301G02_TMP', '301G03_TMP', '301G04_TMP',
        '301G05_TMP', '301G06_TMP', '301G07_TMP', '301G08_TMP', '301G09_TMP',
        '301G12_TMP', '301G18_TMP', '301G34_TMP', '301G36_TMP', '301G37_TMP',
        '301G38_TMP', 'ESTABL', 'CONSOLIDADO',
        // DAT tables (datos mensuales)
        '301G01_DAT01', '301G01_DAT02', '301G01_DAT03', '301G01_DAT04',
        '301G01_DAT05', '301G01_DAT06', '301G01_DAT07', '301G01_DAT08',
        '301G01_DAT09', '301G01_DAT10', '301G01_DAT11', '301G01_DAT12',
        '301G03_DAT01', '301G03_DAT02', '301G03_DAT03', '301G03_DAT04',
        '301G03_DAT05', '301G03_DAT06', '301G03_DAT07', '301G03_DAT08',
        '301G03_DAT09', '301G03_DAT10', '301G03_DAT11', '301G03_DAT12',
        '301G37_DAT01', '301G37_DAT02', '301G37_DAT03', '301G37_DAT04',
        '301G37_DAT05', '301G37_DAT06', '301G37_DAT07', '301G37_DAT08',
        '301G37_DAT09', '301G37_DAT10', '301G37_DAT11', '301G37_DAT12',
        '301G38_DAT01', '301G38_DAT02', '301G38_DAT03', '301G38_DAT04',
        '301G38_DAT05', '301G38_DAT06', '301G38_DAT07', '301G38_DAT08',
        '301G38_DAT09', '301G38_DAT10', '301G38_DAT11', '301G38_DAT12',
    ];

    public function __construct(string $mdbPath)
    {
        $this->mdbPath = $mdbPath;
    }

    /**
     * Devuelve todos los registros de una tabla como array asociativo.
     *
     * @param  string  $tabla  Nombre exacto de la tabla en el MDB
     * @param  string  $filtroWhere  Cláusula WHERE opcional (sin la palabra WHERE)
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException Si la herramienta de lectura no está disponible
     */
    public function tabla(string $tabla, string $filtroWhere = ''): array
    {
        // Validar nombre de tabla contra whitelist para prevenir inyección
        if (! $this->tablaPermitida($tabla)) {
            throw new \InvalidArgumentException(
                "Tabla '{$tabla}' no está en la lista de tablas permitidas."
            );
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return $this->leerConVbscript($tabla, $filtroWhere);
        }

        return $this->leerConMdbtools($tabla, $filtroWhere);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Windows — VBScript + DAO 3.6
    // ──────────────────────────────────────────────────────────────────────────

    private function leerConVbscript(string $tabla, string $filtroWhere): array
    {
        if (! file_exists(self::CSCRIPT32)) {
            throw new \RuntimeException('No se encontró '.self::CSCRIPT32);
        }

        $vbsPath = base_path(self::VBS_SCRIPT);
        if (! file_exists($vbsPath)) {
            throw new \RuntimeException('No se encontró el script VBScript: '.$vbsPath);
        }

        $args = [
            escapeshellarg(self::CSCRIPT32),
            '//nologo',
            escapeshellarg($vbsPath),
            escapeshellarg($this->mdbPath),
            escapeshellarg($tabla),
        ];

        if ($filtroWhere !== '') {
            $args[] = escapeshellarg($filtroWhere);
        }

        $cmd = implode(' ', $args);
        $output = shell_exec($cmd);

        if ($output === null || $output === '') {
            return [];
        }

        $data = json_decode(trim($output), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'JSON inválido del VBScript: '.json_last_error_msg()."\nPrimeros 500 chars: ".substr($output, 0, 500)
            );
        }

        return $data ?? [];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Linux — mdbtools
    // ──────────────────────────────────────────────────────────────────────────

    private function leerConMdbtools(string $tabla, string $filtroWhere): array
    {
        // mdb-json exporta una tabla a JSON (requiere: apt install mdbtools)
        $cmd = 'mdb-json '.escapeshellarg($this->mdbPath).' '.escapeshellarg($tabla);
        $output = shell_exec($cmd);

        if ($output === null || $output === '') {
            return [];
        }

        // mdb-json devuelve un objeto JSON por línea (ndjson), convertimos a array
        $rows = [];
        foreach (explode("\n", trim($output)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $row = json_decode($line, true);
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        // Aplicar filtro en PHP si hay filtroWhere (mdbtools no soporta WHERE)
        if ($filtroWhere !== '' && ! empty($rows)) {
            $rows = $this->filtrarEnPhp($rows, $filtroWhere);
        }

        return $rows;
    }

    /**
     * Filtro simple en PHP para igualdades: "campo='valor'" o "campo=123"
     * Solo soporta AND de igualdades simples (suficiente para nuestro uso).
     */
    private function filtrarEnPhp(array $rows, string $filtroWhere): array
    {
        // Parsear condiciones: campo='valor' o campo=número
        preg_match_all(
            "/(\w+)\s*=\s*(?:'([^']*)'|(\d+))/",
            $filtroWhere,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            return $rows;
        }

        $condiciones = [];
        foreach ($matches as $m) {
            $condiciones[$m[1]] = $m[2] !== '' ? $m[2] : (int) $m[3];
        }

        return array_filter($rows, function ($row) use ($condiciones) {
            foreach ($condiciones as $campo => $valor) {
                if (! isset($row[$campo])) {
                    return false;
                }
                if (trim((string) $row[$campo]) !== (string) $valor) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Verifica si un nombre de tabla está permitido.
     * Acepta tablas de la whitelist o patrón 301GXX_DATXX (solo alfanumérico + _).
     */
    private function tablaPermitida(string $tabla): bool
    {
        if (in_array($tabla, self::TABLAS_PERMITIDAS, true)) {
            return true;
        }

        // Patrón genérico para tablas DAT de cualquier grupo/mes
        return (bool) preg_match('/^30[1-9]G\d{2}_DAT\d{2}$/', $tabla);
    }
}
