<?php

namespace App\Console\Commands;

use App\Models\Comunidad;
use App\Models\Persona;
use Illuminate\Console\Command;
use PDO;

class ImportarCenso extends Command
{
    protected $signature = 'soaps:importar-censo
        {--anio=2025 : Año base del censo (se incluye ene-mar del año siguiente)}
        {--codestabl=300183 : Código de establecimiento SOAPS}
        {--centro-salud=1 : ID del centro de salud en SIMUES}
        {--limpiar : Eliminar personas existentes antes de importar}
        {--solo-conteo : Solo mostrar conteo sin importar}';

    protected $description = 'Importa el censo de pacientes del SOAPS al SIMUES (personas atendidas en la gestión)';

    private static function soapsServer(): string { return env('SOAPS_SERVER', '.\SNS'); }
    private static function soapsDb(): string { return env('SOAPS_DB', 'BDestadistica'); }
    private static function soapsUser(): string { return env('SOAPS_USER', 'sa'); }
    private static function soapsPass(): string { return env('SOAPS_PASS', ''); }

    // Mapeo SE_ZONA.zon_descripcion → comunidad SIMUES (nombre normalizado)
    // Los que no coincidan se intentan resolver por HCL_DIRECC
    private const ZONA_COMUNIDAD = [
        'HORNOMA'      => 'Hornoma',
        'HUAYCHOMA'    => 'Huaychoma',
        'VILLCABAMBA'  => 'Villcabamba',
        'COCOMA'       => 'Cocoma',
        'TOCOHALLA'    => 'Tocohalla',
        'CHALLAVILQUE' => 'Challavilque',
        'CALACAJA'     => 'Calacaja',
        'SIQUIMIRANI'  => 'Siquimirani',
    ];

    // Palabras clave en HCL_DIRECC para resolver "sin zona"
    private const DIRECC_COMUNIDAD = [
        'HORNOMA'       => 'Hornoma',
        'HUAYCHOMA'     => 'Huaychoma',
        'HHUAYCHOMA'    => 'Huaychoma',
        'VILLCABAMBA'   => 'Villcabamba',
        'VILLCABAM'     => 'Villcabamba',
        'COCOMA'        => 'Cocoma',
        'TOCOHALLA'     => 'Tocohalla',
        'CHALLAVILQUE'  => 'Challavilque',
        'CHALLAVILLQUE' => 'Challavilque',
        'CALACAJA'      => 'Calacaja',
        'CALCAJA'       => 'Calacaja',
        'CALA CAJA'     => 'Calacaja',
        'SIQUIMIRANI'   => 'Siquimirani',
        'HIORNOMA'      => 'Hornoma',
    ];

    public function handle(): int
    {
        $anio = (int) $this->option('anio');
        $anioSig = $anio + 1;
        $codestabl = $this->option('codestabl');
        $centroSaludId = (int) $this->option('centro-salud');
        $soloConteo = (bool) $this->option('solo-conteo');

        $this->info("Importación de Censo SOAPS → SIMUES");
        $this->info("  Período: {$anio} + ene-mar {$anioSig}");
        $this->info("  Centro de salud: {$centroSaludId}");

        try {
            $pdo = $this->connectSoaps();
        } catch (\Exception $e) {
            $this->error("Error de conexión SOAPS: {$e->getMessage()}");
            return self::FAILURE;
        }

        // Cargar comunidades SIMUES
        $comunidades = Comunidad::where('centro_salud_id', $centroSaludId)
            ->pluck('id', 'nombre')
            ->toArray();

        if (empty($comunidades)) {
            $this->error("No hay comunidades para el centro de salud {$centroSaludId}");
            return self::FAILURE;
        }

        $this->info('  Comunidades SIMUES: ' . implode(', ', array_keys($comunidades)));

        // ── 1. Pacientes con consulta en el período ──
        $this->newLine();
        $this->info('═══ Consultando SOAPS ═══');

        $pacientes = $pdo->query("
            SELECT DISTINCT h.HCL_CODIGO, h.HCL_APPAT, h.HCL_APMAT, h.HCL_NOMBRE,
                   h.HCL_NUMCI, h.HCL_SEXO, h.HCL_FECNAC, h.HCL_CodCSB,
                   h.zon_codigo, z.zon_descripcion, h.HCL_DIRECC, h.HCL_SUMI,
                   h.HCL_FECHA
            FROM SE_HC h
            LEFT JOIN SE_ZONA z ON z.zon_codigo = h.zon_codigo
                AND z.emp_codigo = h.Emp_Codigo
            WHERE h.HCL_CODIGO IN (
                SELECT DISTINCT HCL_CODIGO FROM SE_DATOS
                WHERE (YEAR(DAT_FECHA) = {$anio})
                   OR (YEAR(DAT_FECHA) = {$anioSig} AND MONTH(DAT_FECHA) <= 3)
            )
            AND h.codestabl = '{$codestabl}'
            ORDER BY h.HCL_APPAT, h.HCL_APMAT, h.HCL_NOMBRE
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->line("  Pacientes con consulta: " . count($pacientes));

        // ── 2. Fallecidos ──
        $fallecidos = $pdo->query("
            SELECT dfa_NombreFallecido, dfa_ApellidoPaterno, dfa_ApellidoMaterno
            FROM Tbl_datosFallecido
            WHERE dfa_Gestion >= {$anio}
              AND dfa_EstadoReg = 'A'
        ")->fetchAll(PDO::FETCH_ASSOC);

        $fallecidosNorm = [];
        foreach ($fallecidos as $f) {
            $key = $this->normalizarNombre(
                $f['dfa_ApellidoPaterno'],
                $f['dfa_ApellidoMaterno'],
                $f['dfa_NombreFallecido']
            );
            $fallecidosNorm[$key] = true;
        }
        $this->line("  Fallecidos (gestión ≥{$anio}): " . count($fallecidosNorm));

        // ── 3. Mapear y clasificar ──
        $importar = [];
        $sinComunidad = [];
        $excluidos = 0;

        foreach ($pacientes as $p) {
            // Verificar si es fallecido
            $nombreNorm = $this->normalizarNombre(
                $p['HCL_APPAT'], $p['HCL_APMAT'], $p['HCL_NOMBRE']
            );
            if (isset($fallecidosNorm[$nombreNorm])) {
                $excluidos++;
                continue;
            }

            // Resolver comunidad
            $comunidadNombre = $this->resolverComunidad(
                trim($p['zon_descripcion'] ?? ''),
                trim($p['HCL_DIRECC'] ?? '')
            );

            $comunidadId = $comunidades[$comunidadNombre] ?? null;

            if (! $comunidadId) {
                $sinComunidad[] = "{$p['HCL_APPAT']} {$p['HCL_APMAT']} {$p['HCL_NOMBRE']} (dir: {$p['HCL_DIRECC']})";
                // Asignar a Hornoma como default (centro de salud principal)
                $comunidadId = $comunidades['Hornoma'];
            }

            $importar[] = [
                'hcl_codigo'       => $p['HCL_CODIGO'],
                'centro_salud_id'  => $centroSaludId,
                'comunidad_id'     => $comunidadId,
                'nombres'          => $this->limpiarTexto($p['HCL_NOMBRE']),
                'apellidos'        => trim($this->limpiarTexto($p['HCL_APPAT']) . ' ' . $this->limpiarTexto($p['HCL_APMAT'])),
                'fecha_nacimiento' => substr($p['HCL_FECNAC'], 0, 10),
                'sexo'             => ($p['HCL_SEXO'] == 1) ? 'M' : 'F',
                'ci'               => $this->limpiarCI($p['HCL_NUMCI']),
                'tipo_seguro'      => ($p['HCL_SUMI'] === 'S') ? 'SUS' : 'ninguno',
                'estado'           => 'residente',
                'fecha_registro'   => substr($p['HCL_FECHA'], 0, 10),
                'observaciones'    => "Código Seguro: {$p['HCL_CodCSB']}",
            ];
        }

        $this->line("  Excluidos (fallecidos): {$excluidos}");
        $this->line("  A importar: " . count($importar));

        // Distribución por comunidad
        $dist = [];
        foreach ($importar as $p) {
            $comNombre = array_search($p['comunidad_id'], $comunidades) ?: '?';
            $dist[$comNombre] = ($dist[$comNombre] ?? 0) + 1;
        }
        arsort($dist);

        $this->newLine();
        $this->info('═══ Distribución por comunidad ═══');
        $this->table(
            ['Comunidad', 'Pacientes'],
            collect($dist)->map(fn ($v, $k) => [$k, $v])->values()->toArray()
        );

        if (! empty($sinComunidad)) {
            $this->warn("Sin comunidad asignada (→ Hornoma): " . count($sinComunidad));
            if ($this->getOutput()->isVerbose()) {
                foreach ($sinComunidad as $s) $this->line("    {$s}");
            }
        }

        if ($soloConteo) {
            $this->info('Modo solo conteo. No se importó nada.');
            return self::SUCCESS;
        }

        // ── 4. Importar ──
        $this->newLine();
        $this->info('═══ Importando al SIMUES ═══');

        if ($this->option('limpiar')) {
            $deleted = Persona::where('centro_salud_id', $centroSaludId)
                ->where('observaciones', 'LIKE', 'Código Seguro:%')
                ->delete();
            $this->warn("  Limpiado: {$deleted} personas eliminadas");
        }

        $nuevos = 0;
        $actualizados = 0;
        $omitidos = 0;

        foreach ($importar as $data) {
            $hclCodigo = $data['hcl_codigo'];
            unset($data['hcl_codigo']);

            // Buscar duplicado por CI o por nombre+fecha_nacimiento
            $existente = null;
            if ($data['ci']) {
                $existente = Persona::where('centro_salud_id', $centroSaludId)
                    ->where('ci', $data['ci'])
                    ->first();
            }
            if (! $existente) {
                $existente = Persona::where('centro_salud_id', $centroSaludId)
                    ->where('apellidos', $data['apellidos'])
                    ->where('nombres', $data['nombres'])
                    ->where('fecha_nacimiento', $data['fecha_nacimiento'])
                    ->first();
            }

            if ($existente) {
                // Actualizar comunidad y tipo seguro si cambió
                $existente->update([
                    'comunidad_id' => $data['comunidad_id'],
                    'tipo_seguro'  => $data['tipo_seguro'],
                    'activo'       => true,
                    'observaciones' => $data['observaciones'],
                ]);
                $actualizados++;
            } else {
                $data['activo'] = true;
                Persona::create($data);
                $nuevos++;
            }
        }

        $this->newLine();
        $this->info('═══ Resumen ═══');
        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Pacientes en SOAPS', count($pacientes)],
                ['Excluidos (fallecidos)', $excluidos],
                ['Nuevos insertados', $nuevos],
                ['Actualizados', $actualizados],
                ['Total en SIMUES', Persona::where('centro_salud_id', $centroSaludId)->count()],
            ]
        );

        return self::SUCCESS;
    }

    private function connectSoaps(): PDO
    {
        $dsn = sprintf(
            'odbc:Driver={SQL Server};Server=%s;Database=%s;Uid=%s;Pwd=%s',
            self::soapsServer(), self::soapsDb(), self::soapsUser(), self::soapsPass()
        );

        return new PDO($dsn, '', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function resolverComunidad(string $zona, string $direccion): string
    {
        $zonaUp = strtoupper(trim($zona));
        if (isset(self::ZONA_COMUNIDAD[$zonaUp])) {
            return self::ZONA_COMUNIDAD[$zonaUp];
        }

        // Intentar resolver por dirección
        $dirUp = strtoupper($this->limpiarTexto($direccion));
        foreach (self::DIRECC_COMUNIDAD as $keyword => $comunidad) {
            if (str_contains($dirUp, $keyword)) {
                return $comunidad;
            }
        }

        return 'Hornoma'; // Default
    }

    private function normalizarNombre(string $paterno, string $materno, string $nombre): string
    {
        return strtoupper(trim("{$paterno}|{$materno}|{$nombre}"));
    }

    private function limpiarTexto(string $texto): string
    {
        // Eliminar caracteres basura del ODBC (|�, |O�, etc.)
        $texto = preg_replace('/\|.*$/', '', $texto);
        return mb_convert_encoding(trim($texto), 'UTF-8', 'UTF-8');
    }

    private function limpiarCI(?string $ci): ?string
    {
        $ci = trim($ci ?? '');
        if ($ci === '' || $ci === '0') {
            return null;
        }

        return $ci;
    }
}
