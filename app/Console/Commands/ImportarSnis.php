<?php

namespace App\Console\Commands;

use App\Models\CentroSalud;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class ImportarSnis extends Command
{
    protected $signature = 'snis:importar
        {--ruta=C:\SNIS2026 : Ruta de instalación del SNIS}
        {--anio=2026 : Gestión SNIS a importar}
        {--anio-destino= : Año destino en SIMUES (default = mismo que --anio)}
        {--meses= : Meses a importar separados por coma (default: todos los disponibles)}
        {--limpiar : Eliminar datos existentes del período antes de importar}';

    protected $description = 'Importa datos del SNIS (archivos .mdb) a la base de datos SIMUES';

    private static function dbPassword(): string
    {
        return env('SNIS_DB_PASSWORD', '');
    }

    // ───── G01: CONSULTA EXTERNA (codsubvar 30101VVSS) ─────
    // Variable → grupo_etareo (2026)
    private const CE_GRUPO = [
        '08' => 'mayor_60',
        '09' => '5_9',
        '12' => 'menor_6m',
        '13' => '6m_menor_1',
        '14' => '1_4',
        '15' => '10_14',
        '16' => '15_19',
        '17' => '20_39',
        '18' => '40_49',
        '19' => '50_59',
    ];
    // Subvar → tipo: 01=nuevo, 02=repetido, 03=primera
    private const CE_TIPO = [
        '01' => 'nueva',
        '02' => 'repetida',
        '03' => 'primera',
    ];

    // ───── G02: REFERENCIAS (codsubvar 30102VVSS) ─────
    private const REF_TIPO = [
        '01' => 'ref_enviada',
        '02' => 'ref_recibida_establecimiento',
        '03' => 'contraref_recibida',
        '04' => 'contraref_enviada',
        '05' => 'ref_recibida_comunidad',
        '06' => 'pcd_atendida_establecimiento',
        '07' => 'pcd_atendida_comunidad',
    ];

    // ───── G03: ODONTOLOGIA (codsubvar 30103VVSS) ─────
    private const ODONTO_PROC = [
        '01' => 'primera_consulta',
        '06' => 'restauraciones',
        '08' => 'endodoncias',
        '09' => 'periodoncia',
        '15' => 'exodoncias',
        '19' => 'consulta_nueva',
        '20' => 'consulta_repetida',
        '21' => 'medidas_preventivas',
        '24' => 'cirugia_menor',
        '25' => 'cirugia_mediana',
        '26' => 'fracturas_dentoalveolares',
        '27' => 'TOIT',
        '28' => 'rayos_x',
    ];
    // Subvar → grupo_etareo (SNIS: <5, 5-13, 14-19, 20-59, 60+, emb, post)
    private const ODONTO_GRUPO = [
        '01' => 'menor_5',
        '02' => '5_9',      // SNIS 5-13 → aprox 5_9
        '03' => '15_19',    // SNIS 14-19 → aprox 15_19
        '04' => '20_39',    // SNIS 20-59 → aprox 20_39
        '05' => 'mayor_60',
        // '06' embarazada y '07' postparto no tienen equivalente en enum
    ];

    // ───── G04: PRENATALES (codsubvar 30104VVSS) ─────
    private const PRENATAL_TIPO = [
        '07' => 'nueva_1er_trim',
        '08' => 'nueva_2do_trim',
        '09' => 'nueva_3er_trim',
        '10' => 'repetida',
        '11' => 'con_4to_control',
    ];
    private const PRENATAL_GRUPO = [
        '01' => 'menor_10',   '02' => 'menor_10',
        '03' => '10_14',      '04' => '10_14',
        '05' => '15_19',      '06' => '15_19',
        '07' => '20_34',      '08' => '20_34',
        '09' => '35_49',      '10' => '35_49',
        '11' => '50_mas',     '12' => '50_mas',
    ];

    // ───── G05: ANTICONCEPCION (codsubvar 30105VVSS) ─────
    // Var → [metodo, tipo_usuaria]
    private const ANTICON_MAP = [
        '20' => ['DIU', 'nueva'],
        '21' => ['DIU', 'continua'],
        '23' => ['inyectable_trimestral', 'nueva'],
        '24' => ['inyectable_trimestral', 'continua'],
        '26' => ['condon_masculino', 'nueva'],
        '27' => ['condon_masculino', 'continua'],
        '28' => ['condon_femenino', 'nueva'],
        '29' => ['condon_femenino', 'continua'],
    ];
    private const ANTICON_GRUPO = [
        '01' => '10_14',
        '02' => '15_19',
        '03' => '20_34',
        '04' => '35_49',
        '05' => '50_mas',
        '06' => 'menor_10',
    ];

    // ───── G06: CRECIMIENTO INFANTIL (codsubvar 30106VVSS) ─────
    private const CREC_GRUPO = [
        '04' => 'menor_1_dentro',
        '05' => 'menor_1_fuera',
        '06' => '1_menor_2_dentro',
        '07' => '1_menor_2_fuera',
        '08' => '2_menor_5_dentro',
        '09' => '2_menor_5_fuera',
    ];

    // ───── G07: ENFERMERIA (codsubvar 30107VVSS) ─────
    private const ENF_TIPO = [
        '01' => 'sueros_administrados',
        '02' => 'inyecciones_administradas',
        '03' => 'curaciones',
        '04' => 'nebulizaciones',
    ];

    // ───── G08: MICRONUTRIENTES (codsubvar 30108VVSS) ─────
    private const MICRO_TIPO = [
        '01' => 'hierro_embarazadas_completo',
        '02' => 'hierro_puerperas_completo',
        '04' => 'hierro_2_5',
        '05' => 'hierro_1anio',
        '11' => 'vitA_puerpera_unica',
        '12' => 'vitA_menor_1_unica',
        '13' => 'vitA_1anio_1ra',
        '14' => 'vitA_1anio_2da',
        '15' => 'vitA_1anio_2da',   // alias 2026
        '16' => 'vitA_2_5_1ra',
        '17' => 'vitA_2_5_2da',
        '18' => 'zinc_menor_1',
        '19' => 'zinc_1anio',
        '20' => 'nutribebe_menor_1',
        '21' => 'nutribebe_1anio',
        '22' => 'nutrimama_embarazada',
        '23' => 'nutrimama_lactancia',
        '24' => 'carmelo_mayor_60',
        '25' => 'hierro_menor_1',             // hierro/chispitas 6-23m → same as hierro_menor_1
        '26' => 'hierro_menor_6m',
        '27' => 'hierro_menor_1',
    ];

    // ───── G37: VACUNAS MENORES DE 5 AÑOS (codsubvar 30137VVSS) ─────
    // VV = fila vacuna (01-23), SS = columna grupo etáreo + dentro/fuera
    private const VAC_MENORES5_TIPO = [
        '01' => 'BCG',
        '02' => 'Pentavalente_1', '03' => 'Pentavalente_2', '04' => 'Pentavalente_3',
        '05' => 'Pentavalente_4', '06' => 'Pentavalente_5',
        '07' => 'IPV_1', '08' => 'bOPV_2', '09' => 'IPV_3',
        '10' => 'bOPV_4', '11' => 'bOPV_5',
        '12' => 'Antirotavirica_1', '13' => 'Antirotavirica_2',
        '14' => 'Antineumococica_1', '15' => 'Antineumococica_2', '16' => 'Antineumococica_3',
        '17' => 'Influenza_6_11m_1', '18' => 'Influenza_7_11m_2',
        '19' => 'SRP_1', '20' => 'SRP_2',
        '21' => 'Antiamarilica',
        '22' => 'Influenza_unica_ninos', '23' => 'Influenza_enf_cronicas_ninos',
    ];
    // Subvar: impar=dentro, par=fuera; cada par = un grupo etáreo
    private const VAC_MENORES5_COL = [
        '01' => ['menor_1', true],      '02' => ['menor_1', false],
        '03' => ['12_23m', true],       '04' => ['12_23m', false],
        '05' => ['2_anios', true],      '06' => ['2_anios', false],
        '07' => ['3_anios', true],      '08' => ['3_anios', false],
        '09' => ['4_anios', true],      '10' => ['4_anios', false],
    ];

    // ───── G38: OTRAS VACUNACIONES (codsubvar 30138VVSS) ─────
    private const VAC_OTRAS_TIPO = [
        '01' => 'dT_1', '02' => 'dT_2', '03' => 'dT_3', '04' => 'dT_4', '05' => 'dT_5',
        '06' => 'VPH_1', '07' => 'VPH_2',
        '08' => 'SR',
        '09' => 'Antiamarilica_adultos',
        '10' => 'HepB_salud_1', '11' => 'HepB_salud_2', '12' => 'HepB_salud_3',
        '13' => 'HepB_VIH_1', '14' => 'HepB_VIH_2', '15' => 'HepB_VIH_3',
        '16' => 'HepB_renal_1', '17' => 'HepB_renal_2', '18' => 'HepB_renal_3',
        '19' => 'Influenza_estacional', '20' => 'Influenza_enf_cronicas',
        '21' => 'Influenza_embarazadas', '22' => 'Influenza_personal_salud',
        '23' => 'COVID_1', '24' => 'COVID_2', '25' => 'COVID_3',
        '26' => 'COVID_anual', '27' => 'COVID_unica', '28' => 'COVID_refuerzo',
    ];
    private const VAC_OTRAS_COL = [
        '01' => ['5_9', true],        '02' => ['5_9', false],
        '03' => ['10_anios', true],   '04' => ['10_anios', false],
        '05' => ['11_anios', true],   '06' => ['11_anios', false],
        '07' => ['12_20', true],      '08' => ['12_20', false],
        '09' => ['21_59', true],      '10' => ['21_59', false],
        '11' => ['60_mas', true],     '12' => ['60_mas', false],
    ];

    // ───── G09: ACTIVIDADES COMUNIDAD (codsubvar 30109VVSS) ─────
    private const ACT_TIPO = [
        '01' => 'actividades_con_comunidad',
        '02' => 'cai_establecimiento',
        '03' => 'comunidades_en_cai',
        '04' => 'familias_nuevas_carpetizadas',
        '05' => 'familias_seguimiento',
        '06' => 'actividades_educativas_salud',
        '10' => 'visitas_primeras',
        '11' => 'visitas_segundas',
        '12' => 'visitas_terceras',
        '13' => 'reuniones_autoridades',
        '14' => 'reuniones_comites_salud',
        '15' => 'pcd_atendidas_establecimiento',
        '16' => 'pcd_atendidas_comunidad',
    ];

    private ?PDO $pdoData = null;

    public function handle(): int
    {
        $ruta = rtrim($this->option('ruta'), '\\/');
        $anio = (int) $this->option('anio');
        $anioDestino = $this->option('anio-destino') ? (int) $this->option('anio-destino') : $anio;
        $mesesFiltro = $this->option('meses')
            ? array_map('intval', explode(',', $this->option('meses')))
            : null;

        $this->info("Importando SNIS desde: {$ruta}");
        $this->info("Gestión SNIS: {$anio} → SIMUES año: {$anioDestino}");

        $dataFile = "{$ruta}\\snis{$anio}.mdb";
        if (! file_exists($dataFile)) {
            $this->error("No se encontró: {$dataFile}");
            return self::FAILURE;
        }

        try {
            $this->pdoData = $this->connectMdb($dataFile);
        } catch (\Exception $e) {
            $this->error("Error de conexión: {$e->getMessage()}");
            return self::FAILURE;
        }

        $centrosMap = $this->mapearCentros($anio);
        if (empty($centrosMap)) {
            $this->error('No se encontraron centros de salud mapeados.');
            return self::FAILURE;
        }

        $mesesDisponibles = $this->getMesesDisponibles($centrosMap);
        if ($mesesFiltro) {
            $mesesDisponibles = array_intersect($mesesDisponibles, $mesesFiltro);
        }

        if (empty($mesesDisponibles)) {
            $this->warn('No hay datos para importar en los meses solicitados.');
            return self::SUCCESS;
        }

        $this->info('Meses con datos: ' . implode(', ', $mesesDisponibles));
        $this->info('Centros mapeados: ' . count($centrosMap));
        $this->newLine();

        if ($this->option('limpiar')) {
            $this->limpiarDatos(array_values($centrosMap), $anioDestino, $mesesDisponibles);
        }

        $stats = [
            'consulta_externa' => 0,
            'referencias' => 0,
            'odontologia' => 0,
            'prenatales' => 0,
            'anticoncepcion' => 0,
            'crecimiento' => 0,
            'enfermeria' => 0,
            'micronutrientes' => 0,
            'actividades' => 0,
            'vacunas' => 0,
        ];

        DB::beginTransaction();
        try {
            foreach ($centrosMap as $snisCorr => $centroId) {
                $centro = CentroSalud::find($centroId);
                $this->info("→ {$centro->nombre} (SNIS: {$snisCorr})");

                foreach ($mesesDisponibles as $mes) {
                    $stats['consulta_externa'] += $this->importarG01($snisCorr, $mes, $centroId, $anioDestino);
                    $stats['referencias'] += $this->importarG02($snisCorr, $mes, $centroId, $anioDestino);
                    $stats['odontologia'] += $this->importarG03($snisCorr, $mes, $centroId, $anioDestino);
                    $stats['prenatales'] += $this->importarG04($snisCorr, $mes, $centroId, $anioDestino);
                    $stats['anticoncepcion'] += $this->importarG05($snisCorr, $mes, $centroId, $anioDestino);
                    $stats['crecimiento'] += $this->importarG06($snisCorr, $mes, $centroId, $anioDestino);
                    $stats['enfermeria'] += $this->importarG07($snisCorr, $mes, $centroId, $anioDestino);
                    $stats['micronutrientes'] += $this->importarG08($snisCorr, $mes, $centroId, $anioDestino);
                    $stats['actividades'] += $this->importarG09($snisCorr, $mes, $centroId, $anioDestino);
                    $stats['vacunas'] += $this->importarG37($snisCorr, $mes, $centroId, $anioDestino);
                    $stats['vacunas'] += $this->importarG38($snisCorr, $mes, $centroId, $anioDestino);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error durante la importación: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('═══ Resumen de Importación ═══');
        $this->table(
            ['Tipo', 'Registros'],
            collect($stats)->map(fn ($v, $k) => [str_replace('_', ' ', ucfirst($k)), $v])->values()->toArray()
        );
        $this->info('Total: ' . array_sum($stats));

        return self::SUCCESS;
    }

    private function connectMdb(string $path): PDO
    {
        $dsn = "odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq={$path};Pwd=" . self::dbPassword();

        return new PDO($dsn, '', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function mapearCentros(int $anio): array
    {
        $centrosSicsal = CentroSalud::whereNotNull('codigo_snis')
            ->where('activo', true)
            ->pluck('id', 'codigo_snis')
            ->toArray();

        if (empty($centrosSicsal)) {
            $this->warn('No hay centros de salud con código SNIS en SIMUES.');
            return [];
        }

        $map = [];
        $prefijo = substr((string) $anio, 2, 2);
        foreach ($centrosSicsal as $codigoSnis => $centroId) {
            $corrEstabgest = $prefijo . $codigoSnis;
            $map[$corrEstabgest] = $centroId;
            $this->line("  Mapeado: SNIS {$corrEstabgest} → centro_id={$centroId} (cod={$codigoSnis})");
        }

        return $map;
    }

    private function getMesesDisponibles(array $centrosMap): array
    {
        $corrList = array_keys($centrosMap);
        $placeholders = implode(',', array_fill(0, count($corrList), '?'));

        $stmt = $this->pdoData->prepare(
            "SELECT DISTINCT mes FROM [301_CAB] WHERE corr_estabgest IN ({$placeholders}) AND mes > 0 ORDER BY mes"
        );
        $stmt->execute($corrList);

        return array_column($stmt->fetchAll(), 'mes');
    }

    private function limpiarDatos(array $centroIds, int $anio, array $meses): void
    {
        $tables = [
            'prest_consulta_externa', 'prest_referencias', 'prest_odontologia',
            'prest_prenatales', 'prest_anticoncepcion', 'prest_crecimiento',
            'prest_enfermeria', 'prest_micronutrientes', 'prest_actividades_comunidad',
            'prest_vacunas',
        ];

        foreach ($tables as $table) {
            $deleted = DB::table($table)
                ->whereIn('centro_salud_id', $centroIds)
                ->where('anio', $anio)
                ->whereIn('mes', $meses)
                ->delete();

            if ($deleted > 0) {
                $this->warn("  Limpiado {$table}: {$deleted} registros");
            }
        }
    }

    private function queryGroup(string $table, string $corr, int $mes): array
    {
        try {
            $stmt = $this->pdoData->prepare(
                "SELECT codsubvar, V, M FROM [{$table}] WHERE corr_estabgest = ? AND mes = ?"
            );
            $stmt->execute([$corr, $mes]);

            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    // ───── G01: CONSULTA EXTERNA → prest_consulta_externa ─────

    private function importarG01(string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows = $this->queryGroup('301G01_DAT', $corr, $mes);
        $grouped = [];

        foreach ($rows as $row) {
            $codsubvar = trim($row['codsubvar']);
            $varCode = substr($codsubvar, 5, 2);
            $subCode = substr($codsubvar, 7, 2);

            $grupoEtareo = self::CE_GRUPO[$varCode] ?? null;
            $tipo = self::CE_TIPO[$subCode] ?? null;
            if (! $grupoEtareo || ! $tipo) continue;

            $v = (int) ($row['V'] ?? 0);
            $m = (int) ($row['M'] ?? 0);

            if (! isset($grouped[$grupoEtareo])) {
                $grouped[$grupoEtareo] = ['primera_m' => 0, 'primera_f' => 0, 'nueva_m' => 0, 'nueva_f' => 0, 'repetida_m' => 0, 'repetida_f' => 0];
            }

            $grouped[$grupoEtareo]["{$tipo}_m"] += $v;
            $grouped[$grupoEtareo]["{$tipo}_f"] += $m;
        }

        $count = 0;
        foreach ($grouped as $grupoEtareo => $vals) {
            if (array_sum($vals) === 0) continue;

            DB::table('prest_consulta_externa')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'grupo_etareo' => $grupoEtareo],
                array_merge($vals, ['updated_at' => now()])
            );
            $count++;
        }

        return $count;
    }

    // ───── G02: REFERENCIAS → prest_referencias ─────

    private function importarG02(string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows = $this->queryGroup('301G02_DAT', $corr, $mes);
        $count = 0;

        foreach ($rows as $row) {
            $varCode = substr(trim($row['codsubvar']), 5, 2);
            $tipo = self::REF_TIPO[$varCode] ?? null;
            if (! $tipo) continue;

            $v = (int) ($row['V'] ?? 0);
            $m = (int) ($row['M'] ?? 0);
            if ($v + $m === 0) continue;

            DB::table('prest_referencias')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo' => $tipo],
                ['masculino' => $v, 'femenino' => $m, 'updated_at' => now()]
            );
            $count++;
        }

        return $count;
    }

    // ───── G03: ODONTOLOGIA → prest_odontologia ─────

    private function importarG03(string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows = $this->queryGroup('301G03_DAT', $corr, $mes);
        $count = 0;

        foreach ($rows as $row) {
            $codsubvar = trim($row['codsubvar']);
            $varCode = substr($codsubvar, 5, 2);
            $subCode = substr($codsubvar, 7, 2);

            $proc = self::ODONTO_PROC[$varCode] ?? null;
            $grupo = self::ODONTO_GRUPO[$subCode] ?? null;
            if (! $proc || ! $grupo) continue;

            $v = (int) ($row['V'] ?? 0);
            $m = (int) ($row['M'] ?? 0);
            if ($v + $m === 0) continue;

            DB::table('prest_odontologia')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'procedimiento' => $proc, 'grupo_etareo' => $grupo],
                ['masculino' => $v, 'femenino' => $m, 'updated_at' => now()]
            );
            $count++;
        }

        return $count;
    }

    // ───── G04: PRENATALES → prest_prenatales ─────

    private function importarG04(string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows = $this->queryGroup('301G04_DAT', $corr, $mes);
        $grouped = [];

        foreach ($rows as $row) {
            $codsubvar = trim($row['codsubvar']);
            $varCode = substr($codsubvar, 5, 2);
            $subCode = substr($codsubvar, 7, 2);

            $tipoControl = self::PRENATAL_TIPO[$varCode] ?? null;
            $grupoEtareo = self::PRENATAL_GRUPO[$subCode] ?? null;
            if (! $tipoControl || ! $grupoEtareo) continue;

            $isDentro = ((int) $subCode) % 2 === 1;
            $total = (int) ($row['V'] ?? 0) + (int) ($row['M'] ?? 0);

            $key = "{$tipoControl}|{$grupoEtareo}";
            if (! isset($grouped[$key])) {
                $grouped[$key] = ['dentro' => 0, 'fuera' => 0];
            }

            if ($isDentro) {
                $grouped[$key]['dentro'] += $total;
            } else {
                $grouped[$key]['fuera'] += $total;
            }
        }

        $count = 0;
        foreach ($grouped as $key => $vals) {
            if ($vals['dentro'] + $vals['fuera'] === 0) continue;
            [$tipoControl, $grupoEtareo] = explode('|', $key);

            DB::table('prest_prenatales')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo_control' => $tipoControl, 'grupo_etareo' => $grupoEtareo],
                array_merge($vals, ['updated_at' => now()])
            );
            $count++;
        }

        return $count;
    }

    // ───── G05: ANTICONCEPCION → prest_anticoncepcion ─────

    private function importarG05(string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows = $this->queryGroup('301G05_DAT', $corr, $mes);
        $count = 0;

        foreach ($rows as $row) {
            $codsubvar = trim($row['codsubvar']);
            $varCode = substr($codsubvar, 5, 2);
            $subCode = substr($codsubvar, 7, 2);

            $mapEntry = self::ANTICON_MAP[$varCode] ?? null;
            $grupo = self::ANTICON_GRUPO[$subCode] ?? null;
            if (! $mapEntry || ! $grupo) continue;

            [$metodo, $tipoUsuaria] = $mapEntry;
            $cantidad = (int) ($row['V'] ?? 0);
            if ($cantidad === 0) continue;

            DB::table('prest_anticoncepcion')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'metodo' => $metodo, 'tipo_usuaria' => $tipoUsuaria, 'grupo_etareo' => $grupo],
                ['cantidad' => $cantidad, 'updated_at' => now()]
            );
            $count++;
        }

        return $count;
    }

    // ───── G06: CRECIMIENTO → prest_crecimiento ─────

    private function importarG06(string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows = $this->queryGroup('301G06_DAT', $corr, $mes);
        $grouped = [];

        foreach ($rows as $row) {
            $codsubvar = trim($row['codsubvar']);
            $varCode = substr($codsubvar, 5, 2);
            $subCode = substr($codsubvar, 7, 2);

            $grupoEtareo = self::CREC_GRUPO[$varCode] ?? null;
            if (! $grupoEtareo) continue;

            $v = (int) ($row['V'] ?? 0);
            $m = (int) ($row['M'] ?? 0);

            if (! isset($grouped[$grupoEtareo])) {
                $grouped[$grupoEtareo] = ['nuevos_m' => 0, 'nuevos_f' => 0, 'repetidos_m' => 0, 'repetidos_f' => 0];
            }

            // subvar 01 = nuevo, 02 = repetido
            if ($subCode === '01') {
                $grouped[$grupoEtareo]['nuevos_m'] += $v;
                $grouped[$grupoEtareo]['nuevos_f'] += $m;
            } else {
                $grouped[$grupoEtareo]['repetidos_m'] += $v;
                $grouped[$grupoEtareo]['repetidos_f'] += $m;
            }
        }

        $count = 0;
        foreach ($grouped as $grupoEtareo => $vals) {
            if (array_sum($vals) === 0) continue;

            DB::table('prest_crecimiento')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'grupo_etareo' => $grupoEtareo],
                array_merge($vals, ['updated_at' => now()])
            );
            $count++;
        }

        return $count;
    }

    // ───── G07: ENFERMERIA → prest_enfermeria ─────

    private function importarG07(string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows = $this->queryGroup('301G07_DAT', $corr, $mes);
        $count = 0;

        foreach ($rows as $row) {
            $varCode = substr(trim($row['codsubvar']), 5, 2);
            $tipo = self::ENF_TIPO[$varCode] ?? null;
            if (! $tipo) continue;

            $cantidad = (int) ($row['V'] ?? 0);
            if ($cantidad === 0) continue;

            DB::table('prest_enfermeria')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo' => $tipo],
                ['cantidad' => $cantidad, 'updated_at' => now()]
            );
            $count++;
        }

        return $count;
    }

    // ───── G08: MICRONUTRIENTES → prest_micronutrientes ─────

    private function importarG08(string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows = $this->queryGroup('301G08_DAT', $corr, $mes);
        $grouped = [];

        foreach ($rows as $row) {
            $varCode = substr(trim($row['codsubvar']), 5, 2);
            $tipo = self::MICRO_TIPO[$varCode] ?? null;
            if (! $tipo) continue;

            $cantidad = (int) ($row['V'] ?? 0) + (int) ($row['M'] ?? 0);
            $grouped[$tipo] = ($grouped[$tipo] ?? 0) + $cantidad;
        }

        $count = 0;
        foreach ($grouped as $tipo => $cantidad) {
            if ($cantidad === 0) continue;

            DB::table('prest_micronutrientes')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo' => $tipo],
                ['cantidad' => $cantidad, 'updated_at' => now()]
            );
            $count++;
        }

        return $count;
    }

    // ───── G09: ACTIVIDADES COMUNIDAD → prest_actividades_comunidad ─────

    private function importarG09(string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows = $this->queryGroup('301G09_DAT', $corr, $mes);
        $count = 0;

        foreach ($rows as $row) {
            $varCode = substr(trim($row['codsubvar']), 5, 2);
            $tipo = self::ACT_TIPO[$varCode] ?? null;
            if (! $tipo) continue;

            $cantidad = (int) ($row['V'] ?? 0);
            if ($cantidad === 0) continue;

            DB::table('prest_actividades_comunidad')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo_actividad' => $tipo],
                ['cantidad' => $cantidad, 'updated_at' => now()]
            );
            $count++;
        }

        return $count;
    }

    // ───── G37: VACUNAS MENORES 5 → prest_vacunas ─────

    private function importarG37(string $corr, int $mes, int $centroId, int $anio): int
    {
        return $this->importarVacunas('301G37_DAT', self::VAC_MENORES5_TIPO, self::VAC_MENORES5_COL, $corr, $mes, $centroId, $anio);
    }

    // ───── G38: OTRAS VACUNAS → prest_vacunas ─────

    private function importarG38(string $corr, int $mes, int $centroId, int $anio): int
    {
        return $this->importarVacunas('301G38_DAT', self::VAC_OTRAS_TIPO, self::VAC_OTRAS_COL, $corr, $mes, $centroId, $anio);
    }

    private function importarVacunas(string $table, array $tipoMap, array $colMap, string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows = $this->queryGroup($table, $corr, $mes);
        $grouped = [];

        foreach ($rows as $row) {
            $codsubvar = trim($row['codsubvar']);
            $varCode = substr($codsubvar, 5, 2);
            $subCode = substr($codsubvar, 7, 2);

            $tipoVacuna = $tipoMap[$varCode] ?? null;
            $colDef = $colMap[$subCode] ?? null;
            if (! $tipoVacuna || ! $colDef) continue;

            [$grupoEtareo, $isDentro] = $colDef;
            $v = (int) ($row['V'] ?? 0);
            $m = (int) ($row['M'] ?? 0);

            $key = "{$tipoVacuna}|{$grupoEtareo}";
            if (! isset($grouped[$key])) {
                $grouped[$key] = ['dentro_m' => 0, 'dentro_f' => 0, 'fuera_m' => 0, 'fuera_f' => 0];
            }

            if ($isDentro) {
                $grouped[$key]['dentro_m'] += $v;
                $grouped[$key]['dentro_f'] += $m;
            } else {
                $grouped[$key]['fuera_m'] += $v;
                $grouped[$key]['fuera_f'] += $m;
            }
        }

        $count = 0;
        foreach ($grouped as $key => $vals) {
            if (array_sum($vals) === 0) continue;
            [$tipoVacuna, $grupoEtareo] = explode('|', $key);

            DB::table('prest_vacunas')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo_vacuna' => $tipoVacuna, 'grupo_etareo' => $grupoEtareo],
                array_merge($vals, ['updated_at' => now()])
            );
            $count++;
        }

        return $count;
    }
}
