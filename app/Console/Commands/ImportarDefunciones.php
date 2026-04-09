<?php

namespace App\Console\Commands;

use App\Models\Comunidad;
use App\Models\Defuncion;
use App\Models\Persona;
use Illuminate\Console\Command;
use PDO;

class ImportarDefunciones extends Command
{
    protected $signature = 'soaps:importar-defunciones
        {--desde=2024 : Año desde el cual importar defunciones}
        {--hasta=2026 : Año hasta el cual importar defunciones}
        {--codestabl=300183 : Código de establecimiento SOAPS}
        {--centro-salud=1 : ID del centro de salud en SIMUES}
        {--todas : Importar todas las gestiones (no solo desde/hasta)}
        {--limpiar : Eliminar defunciones importadas antes de re-importar}
        {--solo-conteo : Solo mostrar conteo sin importar}';

    protected $description = 'Importa defunciones desde SOAPS (MORTALIDAD TODAS CAUSAS) al SIMUES';

    private static function soapsServer(): string { return env('SOAPS_SERVER', '.\SNS'); }
    private static function soapsDb(): string { return env('SOAPS_DB', 'BDestadistica'); }
    private static function soapsUser(): string { return env('SOAPS_USER', 'sa'); }
    private static function soapsPass(): string { return env('SOAPS_PASS', ''); }

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

    public function handle(): int
    {
        $desde = (int) $this->option('desde');
        $hasta = (int) $this->option('hasta');
        $codestabl = $this->option('codestabl');
        $centroSaludId = (int) $this->option('centro-salud');
        $todas = (bool) $this->option('todas');
        $soloConteo = (bool) $this->option('solo-conteo');

        $this->info('Importación de Defunciones SOAPS → SIMUES');
        $this->info($todas ? '  Período: TODAS las gestiones' : "  Período: {$desde} - {$hasta}");
        $this->info("  Centro de salud: {$centroSaludId}");

        try {
            $pdo = $this->connectSoaps();
        } catch (\Exception $e) {
            $this->error("Error de conexión SOAPS: {$e->getMessage()}");
            return self::FAILURE;
        }

        $comunidades = Comunidad::where('centro_salud_id', $centroSaludId)
            ->pluck('id', 'nombre')
            ->toArray();

        // ── 1. Consultar defunciones en SOAPS ──
        $this->newLine();
        $this->info('═══ Consultando SOAPS (MORTALIDAD_TODAS) ═══');

        $filtroAnio = $todas
            ? ''
            : "AND YEAR(ce.Fecha) >= {$desde} AND YEAR(ce.Fecha) <= {$hasta}";

        $defunciones = $pdo->query("
            SELECT ce.Fecha, ce.HC, ce.NOMBRE, ce.DIAGNOS_DESCRIP, ce.CIE_I,
                   ce.zona_res, h.HCL_APPAT, h.HCL_APMAT, h.HCL_NOMBRE,
                   h.HCL_NUMCI, h.HCL_SEXO, h.HCL_FECNAC, h.HCL_DIRECC
            FROM CONSULTA_EXTERNA_2014 ce
            JOIN SE_HC h ON h.HCL_CODIGO = ce.HC
            WHERE ce.MORTALIDAD_TODAS IS NOT NULL
              AND ce.MORTALIDAD_TODAS <> '' AND ce.MORTALIDAD_TODAS <> '0'
              AND ce.codestabl = '{$codestabl}'
              {$filtroAnio}
            ORDER BY ce.Fecha DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->line("  Defunciones encontradas en SOAPS: " . count($defunciones));

        if (empty($defunciones)) {
            $this->info('No hay defunciones para importar.');
            return self::SUCCESS;
        }

        // ── 2. Preparar datos ──
        $importar = [];
        foreach ($defunciones as $d) {
            $apellidos = $this->limpiarTexto(trim($d['HCL_APPAT']) . ' ' . trim($d['HCL_APMAT']));
            $nombres = $this->limpiarTexto($d['HCL_NOMBRE']);
            $sexo = ($d['HCL_SEXO'] == 1) ? 'M' : 'F';
            $fechaNac = substr($d['HCL_FECNAC'], 0, 10);
            $fechaDef = substr($d['Fecha'], 0, 10);
            $ci = $this->limpiarCI($d['HCL_NUMCI']);
            $zona = trim($d['zona_res'] ?? '');
            $direccion = trim($d['HCL_DIRECC'] ?? '');
            $comunidadNombre = $this->resolverComunidad($zona, $direccion);
            $comunidadId = $comunidades[$comunidadNombre] ?? $comunidades['Hornoma'];

            $causa = trim($d['DIAGNOS_DESCRIP'] ?? '');
            $cie = trim($d['CIE_I'] ?? '');
            if ($cie && ! $causa) {
                $causa = $cie;
            } elseif ($cie && $causa) {
                $causa = "{$causa} ({$cie})";
            }

            $importar[] = [
                'hc'               => $d['HC'],
                'centro_salud_id'  => $centroSaludId,
                'nombres'          => $nombres,
                'apellidos'        => $apellidos,
                'fecha_nacimiento' => $fechaNac,
                'sexo'             => $sexo,
                'ci'               => $ci,
                'comunidad_id'     => $comunidadId,
                'comunidad_nombre' => $comunidadNombre,
                'fecha_defuncion'  => $fechaDef,
                'causa_defuncion'  => $causa ?: null,
                'lugar'            => 'domicilio',
            ];
        }

        // Distribución
        $dist = [];
        foreach ($importar as $p) {
            $dist[$p['comunidad_nombre']] = ($dist[$p['comunidad_nombre']] ?? 0) + 1;
        }
        arsort($dist);

        $this->newLine();
        $this->info('═══ Distribución por comunidad ═══');
        $this->table(
            ['Comunidad', 'Defunciones'],
            collect($dist)->map(fn($v, $k) => [$k, $v])->values()->toArray()
        );

        // Detalle
        $this->newLine();
        $this->info('═══ Detalle ═══');
        $headers = ['Fecha Def.', 'Nombre', 'Sexo', 'Fecha Nac.', 'Comunidad', 'Causa'];
        $tableData = [];
        foreach ($importar as $p) {
            $tableData[] = [
                $p['fecha_defuncion'],
                "{$p['apellidos']} {$p['nombres']}",
                $p['sexo'],
                $p['fecha_nacimiento'],
                $p['comunidad_nombre'],
                $p['causa_defuncion'] ?? '-',
            ];
        }
        $this->table($headers, $tableData);

        if ($soloConteo) {
            $this->info('Modo solo conteo. No se importó nada.');
            return self::SUCCESS;
        }

        // ── 3. Importar ──
        $this->newLine();
        $this->info('═══ Importando al SIMUES ═══');

        if ($this->option('limpiar')) {
            $deleted = Defuncion::where('centro_salud_id', $centroSaludId)
                ->where('lugar', 'domicilio')
                ->where('registrado_por', null)
                ->delete();
            $this->warn("  Limpiado: {$deleted} defunciones eliminadas");
        }

        $nuevas = 0;
        $yaExisten = 0;
        $personasDesactivadas = 0;

        foreach ($importar as $data) {
            $hc = $data['hc'];
            $comunidadNombre = $data['comunidad_nombre'];
            unset($data['hc'], $data['comunidad_nombre']);

            // Verificar si ya existe la defunción (por nombre + fecha)
            $existe = Defuncion::where('centro_salud_id', $centroSaludId)
                ->where('apellidos', $data['apellidos'])
                ->where('nombres', $data['nombres'])
                ->where('fecha_defuncion', $data['fecha_defuncion'])
                ->exists();

            if ($existe) {
                $yaExisten++;
                continue;
            }

            // Buscar persona en el censo para vincularla
            $persona = null;
            if ($data['ci']) {
                $persona = Persona::where('centro_salud_id', $centroSaludId)
                    ->where('ci', $data['ci'])
                    ->first();
            }
            if (! $persona) {
                $persona = Persona::where('centro_salud_id', $centroSaludId)
                    ->where('apellidos', $data['apellidos'])
                    ->where('nombres', $data['nombres'])
                    ->first();
            }

            $data['persona_id'] = $persona?->id;

            // Crear defunción (el modelo auto-calcula grupo_etareo y desactiva persona)
            Defuncion::create($data);
            $nuevas++;

            if ($persona && $persona->activo) {
                $personasDesactivadas++;
            }
        }

        $this->newLine();
        $this->info('═══ Resumen ═══');
        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Defunciones en SOAPS', count($defunciones)],
                ['Nuevas registradas', $nuevas],
                ['Ya existían', $yaExisten],
                ['Personas desactivadas del censo', $personasDesactivadas],
                ['Total defunciones en SIMUES', Defuncion::where('centro_salud_id', $centroSaludId)->count()],
                ['Personas activas en censo', Persona::where('centro_salud_id', $centroSaludId)->where('activo', true)->count()],
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

        $dirUp = strtoupper($this->limpiarTexto($direccion));
        $map = [
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
        foreach ($map as $keyword => $comunidad) {
            if (str_contains($dirUp, $keyword)) {
                return $comunidad;
            }
        }

        return 'Hornoma';
    }

    private function limpiarTexto(string $texto): string
    {
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
