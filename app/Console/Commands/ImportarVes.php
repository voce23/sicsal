<?php

namespace App\Console\Commands;

use App\Models\CentroSalud;
use App\Services\VesMdbReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importa un archivo .ves del SNIS (backup mensual) al SIMUES.
 *
 * El .ves es un archivo Cabinet (.cab) que contiene:
 *   - auditoria.text  → hash MD5
 *   - transfer.sql    → base de datos Access 97 (Jet 3.x)
 *
 * Uso:
 *   php artisan snis:importar-ves --archivo="C:\ruta\M30701FORM_26_01al01.ves"
 *   php artisan snis:importar-ves --archivo="..." --limpiar
 */
class ImportarVes extends Command
{
    protected $signature = 'snis:importar-ves
        {--archivo=  : Ruta completa al archivo .ves}
        {--limpiar   : Eliminar datos existentes del mes antes de importar}
        {--solo-centro= : Importar solo un centro (código SNIS, ej: 300183)}';

    protected $description = 'Importa un archivo .ves del SNIS (backup mensual consolidado) al SIMUES';

    // ── Sufijos de tablas en el MDB del .ves ─────────────────────────────────
    // Tablas de formulario: 301_CAB05, 301G01_DAT05, etc.
    private const SUFIJO_FORM = '05';
    // Tablas de referencia: EstabGest2005, municipio2005, t_transfer2005, etc.
    private const SUFIJO_REF = '2005';

    // ══════════════════════════════════════════════════════════════════════════
    //  MAPEO codsubvar → campos SIMUES
    //  Formato codsubvar: FFFF GG VV SS
    //    FFFF = código formulario (301x)
    //    GG   = grupo (G01..G38)
    //    VV   = variable (fila)
    //    SS   = subvariable (columna: grupo etáreo, dentro/fuera, tipo)
    // ══════════════════════════════════════════════════════════════════════════

    private const CE_GRUPO = [
        '08' => 'mayor_60',   '09' => '5_9',       '12' => 'menor_6m',
        '13' => '6m_menor_1', '14' => '1_4',        '15' => '10_14',
        '16' => '15_19',      '17' => '20_39',       '18' => '40_49',
        '19' => '50_59',
    ];
    private const CE_TIPO = ['01' => 'nueva', '02' => 'repetida', '03' => 'primera'];

    private const REF_TIPO = [
        '01' => 'ref_enviada',                    '02' => 'ref_recibida_establecimiento',
        '03' => 'contraref_recibida',             '04' => 'contraref_enviada',
        '05' => 'ref_recibida_comunidad',         '06' => 'pcd_atendida_establecimiento',
        '07' => 'pcd_atendida_comunidad',
    ];

    private const ODONTO_PROC = [
        '01' => 'primera_consulta',   '06' => 'restauraciones',
        '08' => 'endodoncias',        '09' => 'periodoncia',
        '15' => 'exodoncias',         '19' => 'consulta_nueva',
        '20' => 'consulta_repetida',  '21' => 'medidas_preventivas',
        '24' => 'cirugia_menor',      '25' => 'cirugia_mediana',
        '26' => 'fracturas_dentoalveolares', '27' => 'TOIT', '28' => 'rayos_x',
    ];
    private const ODONTO_GRUPO = [
        '01' => 'menor_5', '02' => '5_9', '03' => '15_19',
        '04' => '20_39',   '05' => 'mayor_60',
    ];

    private const PRENATAL_TIPO = [
        '07' => 'nueva_1er_trim',  '08' => 'nueva_2do_trim', '09' => 'nueva_3er_trim',
        '10' => 'repetida',        '11' => 'con_4to_control',
    ];
    private const PRENATAL_GRUPO = [
        '01' => 'menor_10', '02' => 'menor_10', '03' => '10_14', '04' => '10_14',
        '05' => '15_19',    '06' => '15_19',    '07' => '20_34', '08' => '20_34',
        '09' => '35_49',    '10' => '35_49',    '11' => '50_mas','12' => '50_mas',
    ];

    // Form 301 filas 74-97 (anticoncepción):
    // Se omiten VV de cantidades (condones entregados, ciclos, implantes insertados, DIU retirados).
    // inyectable_mensual no existe en Form 301 — se ingresa manualmente.
    private const ANTICON_MAP = [
        '20' => ['DIU',                   'nueva'],
        '21' => ['DIU',                   'continua'],
        '23' => ['inyectable_trimestral', 'nueva'],
        '24' => ['inyectable_trimestral', 'continua'],
        '26' => ['condon_masculino',      'nueva'],
        '27' => ['condon_masculino',      'continua'],
        '28' => ['condon_femenino',       'nueva'],
        '29' => ['condon_femenino',       'continua'],
        '32' => ['pildora',               'nueva'],
        '33' => ['pildora',               'continua'],
        '35' => ['implante_subdermic',    'nueva'],
        '36' => ['implante_subdermic',    'continua'],
        '41' => ['metodos_naturales',     'nueva'],
        '42' => ['metodos_naturales',     'continua'],
        '43' => ['AQV_femenino',          'nueva'],
        '46' => ['AQV_masculino',         'nueva'],
        '51' => ['pildora_emergencia',    'nueva'],
    ];
    private const ANTICON_GRUPO = [
        '01' => '10_14', '02' => '15_19', '03' => '20_34',
        '04' => '35_49', '05' => '50_mas','06' => 'menor_10',
    ];

    private const CREC_GRUPO = [
        '04' => 'menor_1_dentro', '05' => 'menor_1_fuera',
        '06' => '1_menor_2_dentro','07' => '1_menor_2_fuera',
        '08' => '2_menor_5_dentro','09' => '2_menor_5_fuera',
    ];

    private const ENF_TIPO = [
        '01' => 'sueros_administrados', '02' => 'inyecciones_administradas',
        '03' => 'curaciones',           '04' => 'nebulizaciones',
    ];

    // G12 = INTERNACIONES (Form 301 filas 141-151)
    // Solo se mapean los indicadores que existen en el ENUM de prest_internaciones.
    // VV=04 y VV=05 (fallecidos <48h y ≥48h) se suman en 'fallecidos'.
    // VV=07+08 (días cama ocupada maternidad+otros) se suman en 'dias_cama_ocupada'.
    // VV=09+10 (días cama disponible maternidad+otros) se suman en 'dias_cama_disponible'.
    // VV=01,02,06 (ingresos, contrarreferidos) no tienen columna en SIMUES — se omiten.
    private const INT_TIPO = [
        '03' => 'egresos',
        '04' => 'fallecidos',
        '05' => 'fallecidos',
        '07' => 'dias_cama_ocupada',
        '08' => 'dias_cama_ocupada',
        '09' => 'dias_cama_disponible',
        '10' => 'dias_cama_disponible',
    ];

    // G18 = RECIÉN NACIDOS (Form 301 filas 113-132)
    // VV: 01=nacidos_vivos, 02=nacidos_4cpn, 03=peso_<2500g,
    //     06=por_personal_salud, 07=por_partera, 08=por_otros,
    //     09=nacidos_muertos, 16=malformacion_congenita,
    //     17=apego_precoz (lactancia inmediata), 18=corte_tardio_cordon,
    //     19=alojamiento_conjunto, 20=control_48h
    private const RN_TIPO = [
        '01' => 'nacidos_vivos_total',
        '02' => 'nacidos_vivos_4cpn',
        '03' => 'nacidos_vivos_peso_menor_2500',
        '06' => 'nacidos_vivos_personal_salud',
        '07' => 'nacidos_vivos_partera',
        '08' => 'nacidos_vivos_otros',
        '09' => 'nacidos_muertos',
        '16' => 'rn_malformacion_congenita',
        '17' => 'rn_lactancia_inmediata',
        '18' => 'rn_corte_tardio_cordon',
        '19' => 'rn_alojamiento_conjunto',
        '20' => 'rn_control_48h',
    ];

    // Mapeo codsubvar GG=08 (variable VV) → tipo en prest_micronutrientes
    // Orden igual al formulario 301 filas AE7-AE27
    private const MICRO_TIPO = [
        '01' => 'hierro_embarazadas_completo', // AE7  Embarazadas hierro
        '02' => 'hierro_puerperas_completo',   // AE8  Puérperas hierro
        '03' => 'hierro_menor_6m',             // AE9  <6 meses hierro (desde 4m)
        '04' => 'hierro_2_5',                  // AE12 2 a <5 años hierro
        '05' => 'hierro_1anio',                // AE11 1 año hierro
        '11' => 'vitA_puerpera_unica',         // AE13 Puérperas vitamina A
        '12' => 'vitA_menor_1_unica',          // AE14 <1 año vitamina A única
        '13' => 'vitA_1anio_1ra',              // AE15 1 año vitamina A 1ra
        '14' => 'vitA_1anio_2da',              // AE16 1 año vitamina A 2da
        '15' => 'vitA_1anio_2da',              // duplicado SNIS — mismo tipo
        '16' => 'vitA_2_5_1ra',               // AE17 2-<5 años vitamina A 1ra
        '17' => 'vitA_2_5_2da',               // AE18 2-<5 años vitamina A 2da
        '18' => 'zinc_menor_1',               // AE19 <1 año zinc (talla baja)
        '19' => 'zinc_1anio',                 // AE20 1 año zinc (talla baja)
        '20' => 'nutribebe_menor_1',          // AE21 <1 año Nutribebé
        '21' => 'nutribebe_1anio',            // AE22 1 año Nutribebé
        '22' => 'nutrimama_embarazada',       // AE26 Embarazadas Nutrimamá
        '23' => 'nutrimama_lactancia',        // AE27 Lactancia Nutrimamá
        '24' => 'carmelo_mayor_60',           // AE25 Adultos >60 Carmelo
        '25' => 'hierro_menor_1',             // hierro/chispitas 6-23m → same as hierro_menor_1
        '26' => 'hierro_menor_6m',            // AE9  <6 meses hierro
        '27' => 'hierro_menor_1',             // AE10 <1 año hierro
        '28' => 'lactancia_inmediata',        // AE23 RN lactancia materna inmediata
        '29' => 'lactancia_exclusiva_6m',     // AE24 6 meses lactancia exclusiva
    ];

    private const ACT_TIPO = [
        '01' => 'actividades_con_comunidad',     '02' => 'cai_establecimiento',
        '03' => 'comunidades_en_cai',            '04' => 'familias_nuevas_carpetizadas',
        '05' => 'familias_seguimiento',          '06' => 'actividades_educativas_salud',
        '10' => 'visitas_primeras',              '11' => 'visitas_segundas',
        '12' => 'visitas_terceras',              '13' => 'reuniones_autoridades',
        '14' => 'reuniones_comites_salud',       '15' => 'pcd_atendidas_establecimiento',
        '16' => 'pcd_atendidas_comunidad',
    ];

    private const VAC_MENORES5_TIPO = [
        '01' => 'BCG',
        '02' => 'Pentavalente_1',    '03' => 'Pentavalente_2',    '04' => 'Pentavalente_3',
        '05' => 'Pentavalente_4',    '06' => 'Pentavalente_5',
        '07' => 'IPV_1',             '08' => 'bOPV_2',            '09' => 'IPV_3',
        '10' => 'bOPV_4',            '11' => 'bOPV_5',
        '12' => 'Antirotavirica_1',  '13' => 'Antirotavirica_2',
        '14' => 'Antineumococica_1', '15' => 'Antineumococica_2', '16' => 'Antineumococica_3',
        '17' => 'Influenza_6_11m_1', '18' => 'Influenza_7_11m_2',
        '19' => 'SRP_1',             '20' => 'SRP_2',
        '21' => 'Antiamarilica',
        '22' => 'Influenza_unica_ninos', '23' => 'Influenza_enf_cronicas_ninos',
    ];
    private const VAC_MENORES5_COL = [
        '01' => ['menor_1',  true],  '02' => ['menor_1',  false],
        '03' => ['12_23m',   true],  '04' => ['12_23m',   false],
        '05' => ['2_anios',  true],  '06' => ['2_anios',  false],
        '07' => ['3_anios',  true],  '08' => ['3_anios',  false],
        '09' => ['4_anios',  true],  '10' => ['4_anios',  false],
    ];

    private const VAC_OTRAS_TIPO = [
        '01' => 'dT_1', '02' => 'dT_2', '03' => 'dT_3', '04' => 'dT_4', '05' => 'dT_5',
        '06' => 'VPH_1', '07' => 'VPH_2', '08' => 'SR',
        '09' => 'Antiamarilica_adultos',
        '10' => 'HepB_salud_1', '11' => 'HepB_salud_2', '12' => 'HepB_salud_3',
        '13' => 'HepB_VIH_1',   '14' => 'HepB_VIH_2',   '15' => 'HepB_VIH_3',
        '16' => 'HepB_renal_1', '17' => 'HepB_renal_2', '18' => 'HepB_renal_3',
        '19' => 'Influenza_estacional',     '20' => 'Influenza_enf_cronicas',
        '21' => 'Influenza_embarazadas',    '22' => 'Influenza_personal_salud',
        '23' => 'COVID_1', '24' => 'COVID_2', '25' => 'COVID_3',
        '26' => 'COVID_anual', '27' => 'COVID_unica', '28' => 'COVID_refuerzo',
    ];
    private const VAC_OTRAS_COL = [
        '01' => ['5_9',     true],  '02' => ['5_9',     false],
        '03' => ['10_anios',true],  '04' => ['10_anios',false],
        '05' => ['11_anios',true],  '06' => ['11_anios',false],
        '07' => ['12_20',   true],  '08' => ['12_20',   false],
        '09' => ['21_59',   true],  '10' => ['21_59',   false],
        '11' => ['60_mas',  true],  '12' => ['60_mas',  false],
    ];

    // ─────────────────────────────────────────────────────────────────────────

    private VesMdbReader $reader;
    private array $stats = [];

    public function handle(): int
    {
        $archivoVes = $this->option('archivo');

        if (! $archivoVes) {
            $this->error('Debe indicar --archivo con la ruta del .ves');
            return self::FAILURE;
        }

        // ── 1. Extraer el .ves ─────────────────────────────────────────────
        $mdbPath = $this->extraerVes($archivoVes);
        if (! $mdbPath) {
            return self::FAILURE;
        }

        // ── 2. Leer metadatos del .ves ─────────────────────────────────────
        $this->reader = new VesMdbReader($mdbPath);

        $metadatos = $this->leerMetadatos();
        if (! $metadatos) {
            return self::FAILURE;
        }

        $anio = $metadatos['anio'];
        $mes  = $metadatos['mes'];

        $this->info("Municipio: {$metadatos['municipio']} | Año: {$anio} | Mes: {$mes}");
        $this->newLine();

        // ── 3. Mapear centros SNIS → SIMUES ───────────────────────────────
        $centrosMap = $this->mapearCentros($anio);
        if (empty($centrosMap)) {
            $this->error('No se encontraron centros de salud mapeados. Verifique que los centros tengan "código SNIS" configurado.');
            return self::FAILURE;
        }

        // Filtrar por --solo-centro si se indicó
        $soloCentro = $this->option('solo-centro');
        if ($soloCentro) {
            $prefijo   = substr((string) $anio, -2);
            $corrFiltro = $prefijo . $soloCentro;
            if (! isset($centrosMap[$corrFiltro])) {
                $this->error("No se encontró el centro con código SNIS {$soloCentro} mapeado.");
                return self::FAILURE;
            }
            $centrosMap = [$corrFiltro => $centrosMap[$corrFiltro]];
        }

        // ── 4. Limpiar si se pidió ─────────────────────────────────────────
        if ($this->option('limpiar')) {
            $this->limpiarDatos(array_values($centrosMap), $anio, $mes);
        }

        // ── 5. Importar por centro ─────────────────────────────────────────
        $this->stats = array_fill_keys([
            'consulta_externa','referencias','odontologia','prenatales',
            'anticoncepcion','crecimiento','enfermeria','micronutrientes',
            'actividades','vacunas','recien_nacidos','internaciones',
        ], 0);

        DB::beginTransaction();
        try {
            foreach ($centrosMap as $snisCorr => $centroId) {
                $centro = CentroSalud::find($centroId);
                $this->info("  → {$centro->nombre} (SNIS: {$snisCorr})");

                $this->stats['consulta_externa']  += $this->importarG01($snisCorr, $mes, $centroId, $anio);
                $this->stats['referencias']        += $this->importarG02($snisCorr, $mes, $centroId, $anio);
                $this->stats['odontologia']        += $this->importarG03($snisCorr, $mes, $centroId, $anio);
                $this->stats['prenatales']         += $this->importarG04($snisCorr, $mes, $centroId, $anio);
                $this->stats['anticoncepcion']     += $this->importarG05($snisCorr, $mes, $centroId, $anio);
                $this->stats['crecimiento']        += $this->importarG06($snisCorr, $mes, $centroId, $anio);
                $this->stats['enfermeria']         += $this->importarG07($snisCorr, $mes, $centroId, $anio);
                $this->stats['micronutrientes']    += $this->importarG08($snisCorr, $mes, $centroId, $anio);
                $this->stats['actividades']        += $this->importarG09($snisCorr, $mes, $centroId, $anio);
                $this->stats['vacunas']            += $this->importarG37($snisCorr, $mes, $centroId, $anio);
                $this->stats['vacunas']            += $this->importarG38($snisCorr, $mes, $centroId, $anio);
                $this->stats['internaciones']      += $this->importarG12($snisCorr, $mes, $centroId, $anio);
                $this->stats['recien_nacidos']     += $this->importarG18($snisCorr, $mes, $centroId, $anio);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error durante la importación: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            // Limpiar MDB temporal
            @unlink($mdbPath);
        }

        $this->newLine();
        $this->info('══════ Resumen ══════');
        $this->table(
            ['Tipo de dato', 'Registros'],
            collect($this->stats)->map(
                fn ($v, $k) => [str_replace('_', ' ', ucfirst($k)), $v]
            )->values()->toArray()
        );
        $this->info('Total: ' . array_sum($this->stats) . ' registros importados.');

        return self::SUCCESS;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  EXTRACCIÓN DEL .VES
    // ══════════════════════════════════════════════════════════════════════════

    public function extraerVes(string $vesPath): string|false
    {
        if (! file_exists($vesPath)) {
            $this->error("No se encontró el archivo: {$vesPath}");
            return false;
        }

        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sicsal_ves_' . uniqid();
        mkdir($tmpDir, 0700, true);

        $mdbDest = $tmpDir . DIRECTORY_SEPARATOR . 'transfer.mdb';

        if (PHP_OS_FAMILY === 'Windows') {
            $expandExe = 'C:\\Windows\\System32\\expand.exe';
            $cmd = sprintf(
                '"%s" "%s" -F:* "%s"',
                $expandExe,
                $vesPath,
                $tmpDir
            );
            exec($cmd, $output, $code);

            if ($code !== 0 || ! file_exists($tmpDir . DIRECTORY_SEPARATOR . 'transfer.sql')) {
                $this->error("Error extrayendo el .ves (código {$code}).");
                return false;
            }

            // Renombrar transfer.sql → transfer.mdb
            rename($tmpDir . DIRECTORY_SEPARATOR . 'transfer.sql', $mdbDest);
        } else {
            // Linux: usa cabextract
            $cmd = sprintf('cabextract -d %s %s 2>&1', escapeshellarg($tmpDir), escapeshellarg($vesPath));
            exec($cmd, $output, $code);

            if ($code !== 0 || ! file_exists($tmpDir . DIRECTORY_SEPARATOR . 'transfer.sql')) {
                $this->error("Error extrayendo el .ves. ¿Está instalado 'cabextract'?");
                return false;
            }

            rename($tmpDir . DIRECTORY_SEPARATOR . 'transfer.sql', $mdbDest);
        }

        $this->line("  Extraído: {$mdbDest}");
        return $mdbDest;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  METADATOS DEL .VES
    // ══════════════════════════════════════════════════════════════════════════

    private function leerMetadatos(): array|false
    {
        $r = self::SUFIJO_REF;

        // Leer municipio desde municipio2005
        $munis = $this->reader->tabla("municipio{$r}");
        if (empty($munis)) {
            $this->error('No se encontraron datos en la tabla municipio' . $r);
            return false;
        }
        $municipio = trim($munis[0]['nommunicip'] ?? 'Desconocido');

        // El año más confiable es idgestion de EstabGest2005 (ej: 2026)
        $estab = $this->reader->tabla('EstabGest' . $r);
        $anio  = (int) ($estab[0]['idgestion'] ?? 0);

        // Si idgestion tiene formato fecha YYYYMMDD, extraer solo el año
        if ($anio > 9999) {
            $anio = (int) substr((string) $anio, 0, 4);
        }

        if ($anio === 0) {
            $anio = (int) date('Y');
        }

        // El mes viene de la tabla CAB del formulario 301
        $cab = $this->reader->tabla('301_CAB' . self::SUFIJO_FORM);
        $mes = (int) ($cab[0]['mes'] ?? 0);

        if ($mes === 0 || $anio === 0) {
            $this->error('No se pudo determinar el año/mes del archivo .ves');
            return false;
        }

        return compact('anio', 'mes', 'municipio');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  MAPEO CENTROS SNIS → SIMUES
    // ══════════════════════════════════════════════════════════════════════════

    private function mapearCentros(int $anio): array
    {
        $centrosSicsal = CentroSalud::whereNotNull('codigo_snis')
            ->where('activo', true)
            ->pluck('id', 'codigo_snis')
            ->toArray();

        if (empty($centrosSicsal)) {
            return [];
        }

        // Leer establecimientos del .ves para confirmar qué hay
        $establecimientos = $this->reader->tabla('EstabGest' . self::SUFIJO_REF);
        $enVes = [];
        foreach ($establecimientos as $e) {
            $enVes[trim($e['codestabl'] ?? '')] = trim($e['nomestabl'] ?? '');
        }

        $prefijo = substr((string) $anio, -2);
        $map     = [];

        foreach ($centrosSicsal as $codigoSnis => $centroId) {
            $corrEstabgest = $prefijo . $codigoSnis;

            if (isset($enVes[$codigoSnis])) {
                $map[$corrEstabgest] = $centroId;
                $this->line("  Mapeado: {$enVes[$codigoSnis]} → centro_id={$centroId}");
            } else {
                $this->warn("  Sin datos en .ves: código SNIS {$codigoSnis}");
            }
        }

        return $map;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  LIMPIEZA
    // ══════════════════════════════════════════════════════════════════════════

    private function limpiarDatos(array $centroIds, int $anio, int $mes): void
    {
        $tablas = [
            'prest_consulta_externa', 'prest_referencias', 'prest_odontologia',
            'prest_prenatales', 'prest_anticoncepcion', 'prest_crecimiento',
            'prest_enfermeria', 'prest_micronutrientes', 'prest_actividades_comunidad',
            'prest_vacunas', 'prest_internaciones', 'prest_recien_nacidos',
            'causas_consulta_externa', 'prest_ile',
        ];

        foreach ($tablas as $tabla) {
            $eliminados = DB::table($tabla)
                ->whereIn('centro_salud_id', $centroIds)
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->delete();

            if ($eliminados > 0) {
                $this->warn("  Limpiado {$tabla}: {$eliminados} filas");
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HELPERS DE CONSULTA AL MDB
    // ══════════════════════════════════════════════════════════════════════════

    /** Devuelve filas de una tabla G?? del formulario 301, filtradas por corr+mes */
    private function queryGrupo(string $grupo, string $corr, int $mes): array
    {
        $tabla = "301{$grupo}_DAT" . self::SUFIJO_FORM;
        return $this->reader->tabla(
            $tabla,
            "corr_estabgest='{$corr}' AND mes={$mes}"
        );
    }

    private function v(array $row, string $campo): int
    {
        return (int) ($row[$campo] ?? 0);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  IMPORTADORES POR GRUPO
    // ══════════════════════════════════════════════════════════════════════════

    // ── G01: CONSULTA EXTERNA ─────────────────────────────────────────────────
    private function importarG01(string $corr, int $mes, int $centroId, int $anio): int
    {
        $rows    = $this->queryGrupo('G01', $corr, $mes);
        $grouped = [];

        foreach ($rows as $row) {
            $cs  = trim($row['codsubvar'] ?? '');
            $var = substr($cs, 5, 2);
            $sub = substr($cs, 7, 2);
            $grp = self::CE_GRUPO[$var]  ?? null;
            $tip = self::CE_TIPO[$sub]   ?? null;
            if (! $grp || ! $tip) continue;

            if (! isset($grouped[$grp])) {
                $grouped[$grp] = ['primera_m' => 0, 'primera_f' => 0, 'nueva_m' => 0, 'nueva_f' => 0, 'repetida_m' => 0, 'repetida_f' => 0];
            }
            $grouped[$grp]["{$tip}_m"] += $this->v($row, 'V');
            $grouped[$grp]["{$tip}_f"] += $this->v($row, 'M');
        }

        $count = 0;
        foreach ($grouped as $grp => $vals) {
            if (array_sum($vals) === 0) continue;
            DB::table('prest_consulta_externa')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'grupo_etareo' => $grp],
                array_merge($vals, ['updated_at' => now()])
            );
            $count++;
        }
        return $count;
    }

    // ── G02: REFERENCIAS ──────────────────────────────────────────────────────
    private function importarG02(string $corr, int $mes, int $centroId, int $anio): int
    {
        $count = 0;
        foreach ($this->queryGrupo('G02', $corr, $mes) as $row) {
            $var  = substr(trim($row['codsubvar'] ?? ''), 5, 2);
            $tipo = self::REF_TIPO[$var] ?? null;
            if (! $tipo) continue;
            $v = $this->v($row, 'V');
            $m = $this->v($row, 'M');
            if ($v + $m === 0) continue;
            DB::table('prest_referencias')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo' => $tipo],
                ['masculino' => $v, 'femenino' => $m, 'updated_at' => now()]
            );
            $count++;
        }
        return $count;
    }

    // ── G03: ODONTOLOGIA ──────────────────────────────────────────────────────
    private function importarG03(string $corr, int $mes, int $centroId, int $anio): int
    {
        $count = 0;
        foreach ($this->queryGrupo('G03', $corr, $mes) as $row) {
            $cs   = trim($row['codsubvar'] ?? '');
            $var  = substr($cs, 5, 2);
            $sub  = substr($cs, 7, 2);
            $proc = self::ODONTO_PROC[$var]  ?? null;
            $grp  = self::ODONTO_GRUPO[$sub] ?? null;
            if (! $proc || ! $grp) continue;
            $v = $this->v($row, 'V');
            $m = $this->v($row, 'M');
            if ($v + $m === 0) continue;
            DB::table('prest_odontologia')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'procedimiento' => $proc, 'grupo_etareo' => $grp],
                ['masculino' => $v, 'femenino' => $m, 'updated_at' => now()]
            );
            $count++;
        }
        return $count;
    }

    // ── G04: PRENATALES ───────────────────────────────────────────────────────
    private function importarG04(string $corr, int $mes, int $centroId, int $anio): int
    {
        $grouped = [];
        foreach ($this->queryGrupo('G04', $corr, $mes) as $row) {
            $cs   = trim($row['codsubvar'] ?? '');
            $var  = substr($cs, 5, 2);
            $sub  = substr($cs, 7, 2);
            $tipo = self::PRENATAL_TIPO[$var]  ?? null;
            $grp  = self::PRENATAL_GRUPO[$sub] ?? null;
            if (! $tipo || ! $grp) continue;
            $isDentro = ((int) $sub) % 2 === 1;
            $total    = $this->v($row, 'V') + $this->v($row, 'M');
            $key = "{$tipo}|{$grp}";
            if (! isset($grouped[$key])) $grouped[$key] = ['dentro' => 0, 'fuera' => 0];
            $grouped[$key][$isDentro ? 'dentro' : 'fuera'] += $total;
        }

        $count = 0;
        foreach ($grouped as $key => $vals) {
            if ($vals['dentro'] + $vals['fuera'] === 0) continue;
            [$tipo, $grp] = explode('|', $key);
            DB::table('prest_prenatales')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo_control' => $tipo, 'grupo_etareo' => $grp],
                array_merge($vals, ['updated_at' => now()])
            );
            $count++;
        }
        return $count;
    }

    // ── G05: ANTICONCEPCION ───────────────────────────────────────────────────
    private function importarG05(string $corr, int $mes, int $centroId, int $anio): int
    {
        $count = 0;
        foreach ($this->queryGrupo('G05', $corr, $mes) as $row) {
            $cs    = trim($row['codsubvar'] ?? '');
            $var   = substr($cs, 5, 2);
            $sub   = substr($cs, 7, 2);
            $entry = self::ANTICON_MAP[$var]   ?? null;
            $grp   = self::ANTICON_GRUPO[$sub] ?? null;
            if (! $entry || ! $grp) continue;
            [$metodo, $tipoU] = $entry;
            $cantidad = $this->v($row, 'V');
            if ($cantidad === 0) continue;
            DB::table('prest_anticoncepcion')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'metodo' => $metodo, 'tipo_usuaria' => $tipoU, 'grupo_etareo' => $grp],
                ['cantidad' => $cantidad, 'updated_at' => now()]
            );
            $count++;
        }
        return $count;
    }

    // ── G06: CRECIMIENTO ──────────────────────────────────────────────────────
    private function importarG06(string $corr, int $mes, int $centroId, int $anio): int
    {
        $grouped = [];
        foreach ($this->queryGrupo('G06', $corr, $mes) as $row) {
            $cs  = trim($row['codsubvar'] ?? '');
            $var = substr($cs, 5, 2);
            $sub = substr($cs, 7, 2);
            $grp = self::CREC_GRUPO[$var] ?? null;
            if (! $grp) continue;
            if (! isset($grouped[$grp])) $grouped[$grp] = ['nuevos_m' => 0, 'nuevos_f' => 0, 'repetidos_m' => 0, 'repetidos_f' => 0];
            if ($sub === '01') {
                $grouped[$grp]['nuevos_m']    += $this->v($row, 'V');
                $grouped[$grp]['nuevos_f']    += $this->v($row, 'M');
            } else {
                $grouped[$grp]['repetidos_m'] += $this->v($row, 'V');
                $grouped[$grp]['repetidos_f'] += $this->v($row, 'M');
            }
        }

        $count = 0;
        foreach ($grouped as $grp => $vals) {
            if (array_sum($vals) === 0) continue;
            DB::table('prest_crecimiento')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'grupo_etareo' => $grp],
                array_merge($vals, ['updated_at' => now()])
            );
            $count++;
        }
        return $count;
    }

    // ── G07: ENFERMERIA ───────────────────────────────────────────────────────
    private function importarG07(string $corr, int $mes, int $centroId, int $anio): int
    {
        $count = 0;
        foreach ($this->queryGrupo('G07', $corr, $mes) as $row) {
            $var  = substr(trim($row['codsubvar'] ?? ''), 5, 2);
            $tipo = self::ENF_TIPO[$var] ?? null;
            if (! $tipo) continue;
            $cantidad = $this->v($row, 'V');
            if ($cantidad === 0) continue;
            DB::table('prest_enfermeria')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo' => $tipo],
                ['cantidad' => $cantidad, 'updated_at' => now()]
            );
            $count++;
        }
        return $count;
    }

    // ── G08: MICRONUTRIENTES ──────────────────────────────────────────────────
    private function importarG08(string $corr, int $mes, int $centroId, int $anio): int
    {
        $grouped = [];
        foreach ($this->queryGrupo('G08', $corr, $mes) as $row) {
            $var  = substr(trim($row['codsubvar'] ?? ''), 5, 2);
            $tipo = self::MICRO_TIPO[$var] ?? null;
            if (! $tipo) continue;
            $grouped[$tipo] = ($grouped[$tipo] ?? 0) + $this->v($row, 'V') + $this->v($row, 'M');
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

    // ── G09: ACTIVIDADES COMUNIDAD ────────────────────────────────────────────
    private function importarG09(string $corr, int $mes, int $centroId, int $anio): int
    {
        $count = 0;
        foreach ($this->queryGrupo('G09', $corr, $mes) as $row) {
            $var  = substr(trim($row['codsubvar'] ?? ''), 5, 2);
            $tipo = self::ACT_TIPO[$var] ?? null;
            if (! $tipo) continue;
            $cantidad = $this->v($row, 'V');
            if ($cantidad === 0) continue;
            DB::table('prest_actividades_comunidad')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo_actividad' => $tipo],
                ['cantidad' => $cantidad, 'updated_at' => now()]
            );
            $count++;
        }
        return $count;
    }

    // ── G37 + G38: VACUNAS ────────────────────────────────────────────────────
    private function importarG37(string $corr, int $mes, int $centroId, int $anio): int
    {
        return $this->importarGrupoVacunas('G37', self::VAC_MENORES5_TIPO, self::VAC_MENORES5_COL, $corr, $mes, $centroId, $anio);
    }

    private function importarG38(string $corr, int $mes, int $centroId, int $anio): int
    {
        return $this->importarGrupoVacunas('G38', self::VAC_OTRAS_TIPO, self::VAC_OTRAS_COL, $corr, $mes, $centroId, $anio);
    }

    private function importarGrupoVacunas(string $grupo, array $tipoMap, array $colMap, string $corr, int $mes, int $centroId, int $anio): int
    {
        $grouped = [];
        foreach ($this->queryGrupo($grupo, $corr, $mes) as $row) {
            $cs  = trim($row['codsubvar'] ?? '');
            $var = substr($cs, 5, 2);
            $sub = substr($cs, 7, 2);
            $tip = $tipoMap[$var]  ?? null;
            $col = $colMap[$sub]   ?? null;
            if (! $tip || ! $col) continue;
            [$grp, $isDentro] = $col;
            $key = "{$tip}|{$grp}";
            if (! isset($grouped[$key])) $grouped[$key] = ['dentro_m' => 0, 'dentro_f' => 0, 'fuera_m' => 0, 'fuera_f' => 0];
            if ($isDentro) {
                $grouped[$key]['dentro_m'] += $this->v($row, 'V');
                $grouped[$key]['dentro_f'] += $this->v($row, 'M');
            } else {
                $grouped[$key]['fuera_m'] += $this->v($row, 'V');
                $grouped[$key]['fuera_f'] += $this->v($row, 'M');
            }
        }

        $count = 0;
        foreach ($grouped as $key => $vals) {
            if (array_sum($vals) === 0) continue;
            [$tip, $grp] = explode('|', $key);
            DB::table('prest_vacunas')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'tipo_vacuna' => $tip, 'grupo_etareo' => $grp],
                array_merge($vals, ['updated_at' => now()])
            );
            $count++;
        }
        return $count;
    }

    // ── G12: INTERNACIONES ────────────────────────────────────────────────────
    // Form 301 filas 141-151: egresos, fallecidos, días cama.
    // V = Varones, M = Mujeres. Varios VV pueden sumar al mismo indicador ENUM.
    private function importarG12(string $corr, int $mes, int $centroId, int $anio): int
    {
        $grouped = [];
        foreach ($this->queryGrupo('G12', $corr, $mes) as $row) {
            $var  = substr(trim($row['codsubvar'] ?? ''), 5, 2);
            $tipo = self::INT_TIPO[$var] ?? null;
            if (! $tipo) continue;
            $grouped[$tipo] = ($grouped[$tipo] ?? 0) + $this->v($row, 'V') + $this->v($row, 'M');
        }

        $count = 0;
        foreach ($grouped as $tipo => $cantidad) {
            if ($cantidad === 0) continue;
            DB::table('prest_internaciones')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'indicador' => $tipo],
                ['cantidad' => $cantidad, 'updated_at' => now()]
            );
            $count++;
        }
        return $count;
    }

    // ── G18: RECIÉN NACIDOS ───────────────────────────────────────────────────
    // Form 301 filas 113-132: nacidos vivos, nacidos muertos, indicadores RN.
    // V = Varones, M = Mujeres (cantidad total = V + M)
    // Nota: partos (G10/G11) no existen en el MDB cuando no hay datos registrados.
    private function importarG18(string $corr, int $mes, int $centroId, int $anio): int
    {
        $grouped = [];
        foreach ($this->queryGrupo('G18', $corr, $mes) as $row) {
            $var  = substr(trim($row['codsubvar'] ?? ''), 5, 2);
            $tipo = self::RN_TIPO[$var] ?? null;
            if (! $tipo) continue;
            $grouped[$tipo] = ($grouped[$tipo] ?? 0) + $this->v($row, 'V') + $this->v($row, 'M');
        }

        $count = 0;
        foreach ($grouped as $tipo => $cantidad) {
            if ($cantidad === 0) continue;
            // nacidos_vivos_total → lo guardamos como nacidos_vivos_servicio
            // (desglose por proveedor en VV=06/07/08 si existen)
            $indicador = $tipo === 'nacidos_vivos_total' ? 'nacidos_vivos_servicio' : $tipo;
            DB::table('prest_recien_nacidos')->updateOrInsert(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'indicador' => $indicador],
                ['cantidad' => $cantidad, 'updated_at' => now()]
            );
            $count++;
        }
        return $count;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Devuelve stats para uso programático (Filament page)
    // ══════════════════════════════════════════════════════════════════════════
    public function getStats(): array
    {
        return $this->stats;
    }
}
