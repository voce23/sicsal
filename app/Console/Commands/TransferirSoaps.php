<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;

class TransferirSoaps extends Command
{
    protected $signature = 'soaps:transferir-snis
        {--mes= : Mes a transferir (1-12). Si no se indica, transfiere todos los meses disponibles}
        {--anio=2025 : Año a transferir}
        {--ruta-snis=C:\SNIS2025 : Ruta de instalación del SNIS}
        {--codestabl=300183 : Código de establecimiento SOAPS}
        {--limpiar : Eliminar datos existentes del período antes de insertar}
        {--solo-consulta : Transferir solo consulta externa (G01)}
        {--solo-odontologia : Transferir solo odontología (G03)}
        {--solo-302 : Transferir solo 302A (Vigilancia Epidemiológica Semanal)}
        {--solo-305 : Transferir solo 305/302B (Vigilancia Epidemiológica Mensual)}
        {--formulario= : Formularios a transferir: 301,302,305 (separados por coma, default: todos}';

    protected $description = 'Transfiere datos del SOAPS (SQL Server) al SNIS (Access .mdb)';

    private static function snisPassword(): string
    {
        return env('SNIS_DB_PASSWORD', '');
    }

    private static function soapsServer(): string
    {
        return env('SOAPS_SERVER', '.\SNS');
    }

    private static function soapsDb(): string
    {
        return env('SOAPS_DB', 'BDestadistica');
    }

    private static function soapsUser(): string
    {
        return env('SOAPS_USER', 'sa');
    }

    private static function soapsPass(): string
    {
        return env('SOAPS_PASS', '');
    }

    // ══════════ G01 Consulta Externa ══════════
    // SNIS 2025 codvariabl → grupo etáreo
    // codsubvar format: 30101XXYY where XX=codvariabl, YY=subvar
    private const G01_EDAD_VAR = [
        'menor_6m' => '12', // DAT_FNANIO=0, DAT_FNMES<6
        '6m_menor1' => '13', // DAT_FNANIO=0, DAT_FNMES>=6
        '1_4' => '14', // DAT_FNANIO 1-4
        '5_9' => '09', // DAT_FNANIO 5-9
        '10_14' => '15', // DAT_FNANIO 10-14
        '15_19' => '16', // DAT_FNANIO 15-19
        '20_39' => '17', // DAT_FNANIO 20-39
        '40_49' => '18', // DAT_FNANIO 40-49
        '50_59' => '19', // DAT_FNANIO 50-59
        '60_mas' => '08', // DAT_FNANIO >= 60
    ];

    // DAT_RECONSULTA → SNIS subvar
    // 0=No(Nuevo), 1=Si(Repetido), 3=vacío(Nuevo)
    private const G01_RECONSULTA_SUB = [
        '0' => '01', // Nuevo
        '1' => '02', // Repetido
        '3' => '01', // Sin dato → Nuevo
    ];

    // ══════════ G03 Odontología ══════════
    // SOAPS se_odontologia fields → SNIS G03 codvariabl
    private const G03_CAMPOS_VAR = [
        'PRIMERA_CONSULTA' => '01', // Primera consulta
        'RESINA_FOTOCURABLE' => '06', // Restauraciones (agrupado)
        'RESINA_AUTOCURABLE' => '06',
        'AMALGAMA' => '06',
        'IONOMERO' => '06',
        'PRAT' => '06',
        'PULPOTOMIA' => '08', // Endodoncia
        'TRATAMIENTO_CONDUCTO' => '08',
        'TARTRECTOMIA' => '09', // Periodoncia
        'GINGIVECTOMIA' => '09',
        'EXODONCIA_PERMANENTE' => '15', // Cirugía Bucal menor
        'EXODONCIA_TEMPORAL' => '15',
        'ALVEOLITIS' => '15',
        'ABSCESO_AGUDO' => '15',
        'MAXILO_FACIAL' => '24', // Cirugía Bucomaxilofacial Menor
        'FLUORINIZACION_DENTRO' => '21', // Medidas Preventivas
        'FLUORINIZACION_FUERA' => '21',
        'PROFILAXIS' => '21',
        'SELLANTES_DENTRO' => '21',
        'SELLANTES_FUERA' => '21',
        'CARIOSTATICOS_DENTRO' => '21',
        'CARIOSTATICOS_FUERA' => '21',
        'SALUD_ORAL_DENTRO' => '21',
        'SALUD_ORAL_FUERA' => '21',
        'RADIOGRAFIA_ORDENADA' => '28', // Rayos X Dental
        'RADIOGRAFIA_REALIZADA' => '28',
    ];

    // G03 subvar por edad (calculada desde HCL_FECNAC)
    // 01=<5, 02=5-13, 03=14-19, 04=20-59, 05=60+, 06=Emb, 07=Post
    // MUJER_EMBARAZADA field determines 06 vs normal age

    // ══════════ 302A Vigilancia Epidemiológica Semanal ══════════
    // CIE-10 → [grupo SNIS, variable SNIS]
    // Orden importa: la primera coincidencia gana (para CIE_I; CIE_II/III se evalúan independientemente)
    // Patrones: regex aplicados al código CIE-10 (mayúsculas, trimmed)
    private const CIE_302_MAP = [
        // G06 - Enfermedades prevalentes (las más frecuentes primero)
        ['grp' => '06', 'var' => '10', 'rx' => '/^A0[0-9]/'],       // EDA (A00-A09)
        ['grp' => '06', 'var' => '11', 'rx' => '/^J(0[0-6]|2[0-2]|3[0-1])/'], // IRA sin neumonía
        ['grp' => '06', 'var' => '12', 'rx' => '/^J1[2-8]/'],       // Neumonía (J12-J18)
        ['grp' => '06', 'var' => '13', 'rx' => '/^(W5[345]|Z20\.3)/'], // Exposición rábica
        ['grp' => '06', 'var' => '14', 'rx' => '/^X20/'],           // Mordedura serpientes
        ['grp' => '06', 'var' => '15', 'rx' => '/^X2[1-5]/'],       // Arácnidos

        // G01 - Enfermedades de notificación inmediata
        ['grp' => '01', 'var' => '11', 'rx' => '/^B0[56]/'],                  // Sarampión/Rubéola
        ['grp' => '01', 'var' => '12', 'rx' => '/^P35\.0/'],                  // SRC
        ['grp' => '01', 'var' => '13', 'rx' => '/^A37/'],                     // Tos ferina
        ['grp' => '01', 'var' => '14', 'rx' => '/^A36/'],                     // Difteria
        ['grp' => '01', 'var' => '15', 'rx' => '/^(A80|G61)/'],               // Polio/PFA
        ['grp' => '01', 'var' => '16', 'rx' => '/^A95/'],                     // Fiebre amarilla
        ['grp' => '01', 'var' => '17', 'rx' => '/^A91/'],                     // Dengue Grave
        ['grp' => '01', 'var' => '18', 'rx' => '/^A96\.1/'],                  // Fiebre Hemorrágica Boliviana
        ['grp' => '01', 'var' => '19', 'rx' => '/^A20/'],                     // Peste
        ['grp' => '01', 'var' => '20', 'rx' => '/^A39\.0/'],                  // Meningocócica
        ['grp' => '01', 'var' => '21', 'rx' => '/^A00/'],                     // Cólera
        ['grp' => '01', 'var' => '22', 'rx' => '/^B33\.4/'],                  // Hanta
        ['grp' => '01', 'var' => '24', 'rx' => '/^A82/'],                     // Rabia humana
        ['grp' => '01', 'var' => '25', 'rx' => '/^J(09|10|11)/'],             // Influenza
        ['grp' => '01', 'var' => '26', 'rx' => '/^A27/'],                     // Leptospirosis
        ['grp' => '01', 'var' => '27', 'rx' => '/^A92\.0/'],                  // Chikungunya
        ['grp' => '01', 'var' => '28', 'rx' => '/^A92\.8/'],                  // Zika
        ['grp' => '01', 'var' => '29', 'rx' => '/^A97\.1/'],                  // Dengue con alarma
        ['grp' => '01', 'var' => '30', 'rx' => '/^(A90|A97\.0)/'],            // Dengue sin alarma
        ['grp' => '01', 'var' => '31', 'rx' => '/^G0[0-3]/'],                 // Otras Meningitis
        ['grp' => '01', 'var' => '32', 'rx' => '/^D59\.3/'],                  // SHU
        ['grp' => '01', 'var' => '33', 'rx' => '/^U07/'],                     // COVID-19
        ['grp' => '01', 'var' => '34', 'rx' => '/^U10/'],                     // SIM-COVID
        ['grp' => '01', 'var' => '35', 'rx' => '/^(T50|Y59)/'],               // ESAVI
        ['grp' => '01', 'var' => '36', 'rx' => '/^B04/'],                     // Mpox
        ['grp' => '01', 'var' => '37', 'rx' => '/^A93\.0/'],                  // Oropouche

        // G04 - Inmunoprevenibles
        ['grp' => '04', 'var' => '05', 'rx' => '/^A3[345]/'],       // Tétanos
        ['grp' => '04', 'var' => '06', 'rx' => '/^B15/'],           // Hepatitis A
        ['grp' => '04', 'var' => '07', 'rx' => '/^B16/'],           // Hepatitis B
        ['grp' => '04', 'var' => '08', 'rx' => '/^B26/'],           // Parotiditis
        ['grp' => '04', 'var' => '09', 'rx' => '/^B01/'],           // Varicela
        ['grp' => '04', 'var' => '10', 'rx' => '/^B1[789]/'],       // Otras hepatitis (B17-B19)
        ['grp' => '04', 'var' => '11', 'rx' => '/^(B17\.1|B18\.2)/'], // Hepatitis C

        // G05 - ITS
        ['grp' => '05', 'var' => '09', 'rx' => '/^A50/'],           // Sífilis congénita
        ['grp' => '05', 'var' => '10', 'rx' => '/^O98\.1/'],        // Sífilis embarazada
        ['grp' => '05', 'var' => '08', 'rx' => '/^A5[0-3]|^A57|^A60/'], // Úlcera genital
        ['grp' => '05', 'var' => '12', 'rx' => '/^A54/'],           // Gonorrea
        ['grp' => '05', 'var' => '11', 'rx' => '/^A5[456]/'],       // Flujo uretral/vaginal
        ['grp' => '05', 'var' => '13', 'rx' => '/^A63/'],           // Verruga genital
        ['grp' => '05', 'var' => '15', 'rx' => '/^(B2[0-4]|Z21)/'], // VIH

        // G07 - TB/Lepra
        ['grp' => '07', 'var' => '12', 'rx' => '/^A17/'],           // TB meníngea
        ['grp' => '07', 'var' => '13', 'rx' => '/^Y43/'],           // RAFA
        ['grp' => '07', 'var' => '15', 'rx' => '/^A30\.[345]/'],    // Lepra paucibacilar
        ['grp' => '07', 'var' => '16', 'rx' => '/^A30\.[012]/'],    // Lepra multibacilar

        // G08 - Violencia y Accidentes
        ['grp' => '08', 'var' => '05', 'rx' => '/^Y07/'],           // Violencia familiar
        ['grp' => '08', 'var' => '08', 'rx' => '/^V/'],             // Hechos de tránsito

        // G13 - Intoxicaciones
        ['grp' => '13', 'var' => '07', 'rx' => '/^T60\.0/'],        // Organofosforados
        ['grp' => '13', 'var' => '08', 'rx' => '/^T60\.4/'],        // Halogenados
        ['grp' => '13', 'var' => '09', 'rx' => '/^T60\.3/'],        // Herbicida/fungicida
        ['grp' => '13', 'var' => '10', 'rx' => '/^Y18/'],           // Otros plaguicidas
        ['grp' => '13', 'var' => '06', 'rx' => '/^Y1[0-9]/'],       // Otras intoxicaciones
        ['grp' => '13', 'var' => '05', 'rx' => '/^A05/'],           // ETA

        // G17 - Enfermedades tropicales
        ['grp' => '17', 'var' => '01', 'rx' => '/^B55\.1/'],        // Leishmaniasis cutánea
        ['grp' => '17', 'var' => '05', 'rx' => '/^B55\.2/'],        // Leishmaniasis mucocutánea
        ['grp' => '17', 'var' => '06', 'rx' => '/^B55\.0/'],        // Leishmaniasis visceral
        ['grp' => '17', 'var' => '02', 'rx' => '/^B57\.0/'],        // Chagas congénito
        ['grp' => '17', 'var' => '07', 'rx' => '/^B57\.1/'],        // Chagas agudo
        ['grp' => '17', 'var' => '04', 'rx' => '/^B5[0-4]/'],       // Malaria
    ];

    // ══════════ 305 Vigilancia Epidemiológica Mensual ══════════
    // CIE-10 → [grupo SNIS, variable SNIS]
    private const CIE_305_MAP = [
        // G02 - Enfermedades crónicas no transmisibles
        ['grp' => '02', 'var' => '01', 'rx' => '/^E10/'],           // Diabetes Tipo 1
        ['grp' => '02', 'var' => '02', 'rx' => '/^E11/'],           // Diabetes Tipo 2
        ['grp' => '02', 'var' => '03', 'rx' => '/^O24/'],           // Diabetes Gestacional
        ['grp' => '02', 'var' => '04', 'rx' => '/^E66/'],           // Obesidad
        ['grp' => '02', 'var' => '05', 'rx' => '/^M(05|06|13|17|18)/'], // Artritis
        ['grp' => '02', 'var' => '06', 'rx' => '/^I1[0-5]/'],       // HTA
        ['grp' => '02', 'var' => '07', 'rx' => '/^I21/'],           // IAM
        ['grp' => '02', 'var' => '08', 'rx' => '/^I50/'],           // IC
        ['grp' => '02', 'var' => '09', 'rx' => '/^C53/'],           // Cáncer cérvix
        ['grp' => '02', 'var' => '10', 'rx' => '/^C50/'],           // Cáncer mama
        ['grp' => '02', 'var' => '11', 'rx' => '/^C61/'],           // Cáncer próstata
        ['grp' => '02', 'var' => '12', 'rx' => '/^C9[1-5]/'],       // Leucemias
        ['grp' => '02', 'var' => '13', 'rx' => '/^C/'],             // Otros cánceres
        ['grp' => '02', 'var' => '14', 'rx' => '/^J4[56]/'],        // Asma
        ['grp' => '02', 'var' => '15', 'rx' => '/^J44/'],           // EPOC
        ['grp' => '02', 'var' => '16', 'rx' => '/^E03/'],           // Hipotiroidismo
        ['grp' => '02', 'var' => '17', 'rx' => '/^N18/'],           // Enf. Renal (general)
        ['grp' => '02', 'var' => '21', 'rx' => '/^E78/'],           // Dislipidemia
        ['grp' => '02', 'var' => '22', 'rx' => '/^I6/'],            // ACV (I60-I69)

        // G03 - Salud Mental
        ['grp' => '03', 'var' => '01', 'rx' => '/^F3[23]/'],        // Depresión
        ['grp' => '03', 'var' => '02', 'rx' => '/^F4[01]/'],        // Ansiedad
        ['grp' => '03', 'var' => '03', 'rx' => '/^F2/'],            // Psicosis/Esquizofrenia
        ['grp' => '03', 'var' => '04', 'rx' => '/^G40/'],           // Epilepsia
        ['grp' => '03', 'var' => '05', 'rx' => '/^F50/'],           // Trastornos alimenticios
        ['grp' => '03', 'var' => '06', 'rx' => '/^F91/'],           // Trastornos de conducta
        ['grp' => '03', 'var' => '07', 'rx' => '/^F31/'],           // Bipolar
        ['grp' => '03', 'var' => '08', 'rx' => '/^F0[0-3]/'],       // Demencia
        ['grp' => '03', 'var' => '09', 'rx' => '/^F84/'],           // Autismo
        ['grp' => '03', 'var' => '10', 'rx' => '/^F53/'],           // Mental perinatal
        ['grp' => '03', 'var' => '11', 'rx' => '/^F1[0-5]/'],       // Drogas estimulantes
        ['grp' => '03', 'var' => '12', 'rx' => '/^F1[0-9]/'],       // Drogas depresoras

        // G04 - Neurodegenerativas
        ['grp' => '04', 'var' => '01', 'rx' => '/^G30/'],           // Alzheimer
        ['grp' => '04', 'var' => '02', 'rx' => '/^G20/'],           // Parkinson
    ];

    // 302 subvar de edad (10 grupos): SS
    // 01=<6m, 02=6m-<1a, 03=1-4, 04=5-9, 05=10-14, 06=15-19, 07=20-39, 08=40-49, 09=50-59, 10=60+
    // 305 G02/G03 subvar (20 = 10 edades × 2 sexos): odd=M, even=F
    // 305 V=Nuevos, M=En Control

    private ?PDO $pdoSoaps = null;

    private ?PDO $pdoSnis = null;

    public function handle(): int
    {
        $anio = (int) $this->option('anio');
        $mesFiltro = $this->option('mes') ? (int) $this->option('mes') : null;
        $rutaSnis = rtrim($this->option('ruta-snis'), '\\/');
        $codestabl = $this->option('codestabl');
        $limpiar = (bool) $this->option('limpiar');

        $prefijo = substr((string) $anio, 2, 2);
        $corrEstabgest = $prefijo.$codestabl;

        // Determinar formularios a transferir
        $formularios = $this->resolverFormularios();

        $this->info('Transfiriendo SOAPS → SNIS');
        $this->info("  SOAPS: codestabl={$codestabl} | SNIS: corr_estabgest={$corrEstabgest}");
        $this->info("  Año: {$anio} | Mes: ".($mesFiltro ?? 'todos'));
        $this->info('  Formularios: '.implode(', ', $formularios));

        $dataFile = "{$rutaSnis}\\snis{$anio}.mdb";
        if (! file_exists($dataFile)) {
            $this->error("No se encontró {$dataFile}");

            return self::FAILURE;
        }

        try {
            $this->pdoSoaps = $this->connectSoaps();
            $this->pdoSnis = $this->connectSnis($dataFile);
        } catch (\Exception $e) {
            $this->error("Error de conexión: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info('Conexiones establecidas correctamente.');
        $this->newLine();

        $stats = [];

        // ── 301 Consulta Externa + Odontología ──
        if (in_array('301', $formularios)) {
            $stats = array_merge($stats, $this->transferir301($corrEstabgest, $anio, $mesFiltro, $limpiar));
        }

        // ── 302 Vigilancia Epidemiológica Semanal ──
        if (in_array('302', $formularios)) {
            $n = $this->transferir302Semanal($corrEstabgest, $anio, $mesFiltro, $limpiar);
            $stats['302_semanal'] = $n;
        }

        // ── 305 Vigilancia Epidemiológica Mensual ──
        if (in_array('305', $formularios)) {
            $n = $this->transferir305Mensual($corrEstabgest, $anio, $mesFiltro, $limpiar);
            $stats['305_mensual'] = $n;
        }

        $this->newLine();
        $this->info('═══ Resumen de Transferencia ═══');
        $this->table(
            ['Grupo', 'Registros insertados'],
            collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->toArray()
        );

        $total = array_sum($stats);
        $this->info("Total registros transferidos: {$total}");

        return self::SUCCESS;
    }

    private function resolverFormularios(): array
    {
        if ($this->option('formulario')) {
            return array_map('trim', explode(',', $this->option('formulario')));
        }
        if ($this->option('solo-302')) {
            return ['302'];
        }
        if ($this->option('solo-305')) {
            return ['305'];
        }
        if ($this->option('solo-consulta') || $this->option('solo-odontologia')) {
            return ['301'];
        }

        return ['301', '302', '305'];
    }

    // ───────── 301: Consulta + Odontología (wrapper) ─────────

    private function transferir301(string $corr, int $anio, ?int $mesFiltro, bool $limpiar): array
    {
        $meses = $mesFiltro ? [$mesFiltro] : range(1, 12);
        $mesesConDatos = $this->getMesesConDatos($anio, $meses);

        if (empty($mesesConDatos)) {
            $this->warn('301: No hay datos en SOAPS para el período.');

            return ['G01_consulta' => 0, 'G03_odontologia' => 0];
        }

        $this->info('301: Meses con datos: '.implode(', ', $mesesConDatos));
        $stats = ['G01_consulta' => 0, 'G03_odontologia' => 0];

        foreach ($mesesConDatos as $mes) {
            $this->newLine();
            $this->info("═══ 301 Mes {$mes} ═══");
            $this->asegurarCabecera($corr, $mes, $anio);

            if (! $this->option('solo-odontologia')) {
                if ($limpiar) {
                    $this->limpiarTabla('301G01_DAT', $corr, $mes, $anio);
                }
                $n = $this->transferirConsultaExterna($corr, $mes, $anio);
                $stats['G01_consulta'] += $n;
                $this->line("  G01 Consulta Externa: {$n} registros");
            }

            if (! $this->option('solo-consulta')) {
                if ($limpiar) {
                    $this->limpiarTabla('301G03_DAT', $corr, $mes, $anio);
                }
                $n = $this->transferirOdontologia($corr, $mes, $anio);
                $stats['G03_odontologia'] += $n;
                $this->line("  G03 Odontología: {$n} registros");
            }
        }

        return $stats;
    }

    // ───────── Conexiones ─────────

    private function connectSoaps(): PDO
    {
        $dsn = sprintf(
            'odbc:Driver={SQL Server};Server=%s;Database=%s;Uid=%s;Pwd=%s',
            self::soapsServer(),
            self::soapsDb(),
            self::soapsUser(),
            self::soapsPass()
        );

        return new PDO($dsn, '', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function connectSnis(string $path): PDO
    {
        $dsn = "odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq={$path};Pwd=".self::snisPassword();

        return new PDO($dsn, '', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    // ───────── Meses con datos ─────────

    private function getMesesConDatos(int $anio, array $meses): array
    {
        $placeholders = implode(',', $meses);
        $stmt = $this->pdoSoaps->query(
            "SELECT DISTINCT MONTH(DAT_FECHA) as mes
             FROM SE_DATOS
             WHERE YEAR(DAT_FECHA) = {$anio}
               AND MONTH(DAT_FECHA) IN ({$placeholders})
               AND FOR_CODIGO IN (1, 7)
             ORDER BY mes"
        );

        return array_column($stmt->fetchAll(), 'mes');
    }

    // ───────── Cabecera 301_CAB ─────────

    private function asegurarCabecera(string $corr, int $mes, int $anio): void
    {
        $stmt = $this->pdoSnis->prepare(
            'SELECT COUNT(*) as c FROM [301_CAB] WHERE corr_estabgest = ? AND mes = ?'
        );
        $stmt->execute([$corr, $mes]);
        $count = (int) $stmt->fetchColumn();

        if ($count === 0) {
            // Obtener datos del médico responsable desde SOAPS
            $stmt2 = $this->pdoSoaps->query(
                'SELECT TOP 1 p.pperNombre, p.pperDocIde, p.pperCodPer
                 FROM perPersona p
                 WHERE p.pperSwMedi = 1'
            );
            $medico = $stmt2->fetch();

            $nombre = $medico ? trim($medico['pperNombre']) : 'RESPONSABLE';
            $ci = $medico ? trim($medico['pperDocIde']) : '';
            $idUsuario = 80; // Usuario por defecto del SNIS

            $fecha = "{$anio}-{$mes}-01";

            $this->pdoSnis->prepare(
                'INSERT INTO [301_CAB] (corr_estabgest, mes, sem, codestado, nombre, fecha, ci, fechasis, idUsuario)
                 VALUES (?, ?, 0, 0, ?, ?, ?, ?, ?)'
            )->execute([$corr, $mes, $nombre, $fecha, $ci, $fecha, $idUsuario]);

            $this->line("  → Cabecera 301_CAB creada para mes {$mes}");
        }
    }

    // ───────── Limpiar datos existentes ─────────

    private function limpiarTabla(string $tabla, string $corr, int $mes, int $anio): void
    {
        $stmt = $this->pdoSnis->prepare(
            "DELETE FROM [{$tabla}] WHERE corr_estabgest = ? AND mes = ? AND idgestion = ?"
        );
        $stmt->execute([$corr, $mes, $anio]);
        $deleted = $stmt->rowCount();

        if ($deleted > 0) {
            $this->warn("  → Limpiado {$tabla}: {$deleted} registros eliminados");
        }
    }

    // ───────── G01: Consulta Externa ─────────

    private function transferirConsultaExterna(string $corr, int $mes, int $anio): int
    {
        // Agregar datos del SOAPS por grupo etáreo y reconsulta
        $stmt = $this->pdoSoaps->query("
            SELECT
                CASE
                    WHEN DAT_FNANIO = 0 AND DAT_FNMES < 6 THEN '12'
                    WHEN DAT_FNANIO = 0 AND DAT_FNMES >= 6 THEN '13'
                    WHEN DAT_FNANIO >= 1 AND DAT_FNANIO <= 4 THEN '14'
                    WHEN DAT_FNANIO >= 5 AND DAT_FNANIO <= 9 THEN '09'
                    WHEN DAT_FNANIO >= 10 AND DAT_FNANIO <= 14 THEN '15'
                    WHEN DAT_FNANIO >= 15 AND DAT_FNANIO <= 19 THEN '16'
                    WHEN DAT_FNANIO >= 20 AND DAT_FNANIO <= 39 THEN '17'
                    WHEN DAT_FNANIO >= 40 AND DAT_FNANIO <= 49 THEN '18'
                    WHEN DAT_FNANIO >= 50 AND DAT_FNANIO <= 59 THEN '19'
                    WHEN DAT_FNANIO >= 60 THEN '08'
                    ELSE NULL
                END AS var_code,
                CASE DAT_RECONSULTA
                    WHEN 0 THEN '01'
                    WHEN 1 THEN '02'
                    ELSE '01'
                END AS sub_code,
                SUM(CASE WHEN DAT_SEXO = '1' THEN 1 ELSE 0 END) AS V,
                SUM(CASE WHEN DAT_SEXO = '2' THEN 1 ELSE 0 END) AS M
            FROM SE_DATOS
            WHERE FOR_CODIGO = 1
              AND YEAR(DAT_FECHA) = {$anio}
              AND MONTH(DAT_FECHA) = {$mes}
            GROUP BY
                CASE
                    WHEN DAT_FNANIO = 0 AND DAT_FNMES < 6 THEN '12'
                    WHEN DAT_FNANIO = 0 AND DAT_FNMES >= 6 THEN '13'
                    WHEN DAT_FNANIO >= 1 AND DAT_FNANIO <= 4 THEN '14'
                    WHEN DAT_FNANIO >= 5 AND DAT_FNANIO <= 9 THEN '09'
                    WHEN DAT_FNANIO >= 10 AND DAT_FNANIO <= 14 THEN '15'
                    WHEN DAT_FNANIO >= 15 AND DAT_FNANIO <= 19 THEN '16'
                    WHEN DAT_FNANIO >= 20 AND DAT_FNANIO <= 39 THEN '17'
                    WHEN DAT_FNANIO >= 40 AND DAT_FNANIO <= 49 THEN '18'
                    WHEN DAT_FNANIO >= 50 AND DAT_FNANIO <= 59 THEN '19'
                    WHEN DAT_FNANIO >= 60 THEN '08'
                    ELSE NULL
                END,
                CASE DAT_RECONSULTA
                    WHEN 0 THEN '01'
                    WHEN 1 THEN '02'
                    ELSE '01'
                END
            HAVING
                CASE
                    WHEN DAT_FNANIO = 0 AND DAT_FNMES < 6 THEN '12'
                    WHEN DAT_FNANIO = 0 AND DAT_FNMES >= 6 THEN '13'
                    WHEN DAT_FNANIO >= 1 AND DAT_FNANIO <= 4 THEN '14'
                    WHEN DAT_FNANIO >= 5 AND DAT_FNANIO <= 9 THEN '09'
                    WHEN DAT_FNANIO >= 10 AND DAT_FNANIO <= 14 THEN '15'
                    WHEN DAT_FNANIO >= 15 AND DAT_FNANIO <= 19 THEN '16'
                    WHEN DAT_FNANIO >= 20 AND DAT_FNANIO <= 39 THEN '17'
                    WHEN DAT_FNANIO >= 40 AND DAT_FNANIO <= 49 THEN '18'
                    WHEN DAT_FNANIO >= 50 AND DAT_FNANIO <= 59 THEN '19'
                    WHEN DAT_FNANIO >= 60 THEN '08'
                    ELSE NULL
                END IS NOT NULL
        ");

        $insertStmt = $this->pdoSnis->prepare(
            'INSERT INTO [301G01_DAT] (idgestion, corr_estabgest, mes, sem, codsubvar, V, M)
             VALUES (?, ?, ?, 0, ?, ?, ?)'
        );

        $count = 0;
        while ($row = $stmt->fetch()) {
            $codsubvar = '30101'.$row['var_code'].$row['sub_code'];
            $v = (int) $row['V'];
            $m = (int) $row['M'];

            if ($v + $m === 0) {
                continue;
            }

            // Verificar si ya existe para evitar duplicados
            $check = $this->pdoSnis->prepare(
                'SELECT COUNT(*) FROM [301G01_DAT] WHERE idgestion = ? AND corr_estabgest = ? AND mes = ? AND codsubvar = ?'
            );
            $check->execute([$anio, $corr, $mes, $codsubvar]);

            if ((int) $check->fetchColumn() > 0) {
                // Actualizar
                $this->pdoSnis->prepare(
                    'UPDATE [301G01_DAT] SET V = ?, M = ? WHERE idgestion = ? AND corr_estabgest = ? AND mes = ? AND codsubvar = ?'
                )->execute([$v, $m, $anio, $corr, $mes, $codsubvar]);
            } else {
                $insertStmt->execute([$anio, $corr, $mes, $codsubvar, $v, $m]);
            }

            $count++;
        }

        return $count;
    }

    // ───────── G03: Odontología ─────────

    private function transferirOdontologia(string $corr, int $mes, int $anio): int
    {
        // Obtener datos individuales de odontología del SOAPS
        $stmt = $this->pdoSoaps->query("
            SELECT Fecha, Sexo, HCL_FECNAC, MUJER_EMBARAZADA,
                PRIMERA_CONSULTA, EXODONCIA_PERMANENTE, EXODONCIA_TEMPORAL,
                ALVEOLITIS, ABSCESO_AGUDO, AMALGAMA, RESINA_FOTOCURABLE,
                RESINA_AUTOCURABLE, IONOMERO, PRAT, TARTRECTOMIA,
                GINGIVECTOMIA, MAXILO_FACIAL, PULPOTOMIA, TRATAMIENTO_CONDUCTO,
                FLUORINIZACION_DENTRO, FLUORINIZACION_FUERA, PROFILAXIS,
                SELLANTES_DENTRO, SELLANTES_FUERA, CARIOSTATICOS_DENTRO,
                CARIOSTATICOS_FUERA, SALUD_ORAL_DENTRO, SALUD_ORAL_FUERA,
                RADIOGRAFIA_ORDENADA, RADIOGRAFIA_REALIZADA, Reconsulta
            FROM se_odontologia
            WHERE YEAR(Fecha) = {$anio} AND MONTH(Fecha) = {$mes}
        ");

        // Acumular por codsubvar (var + subvar_edad)
        $acumulado = []; // codsubvar => ['V' => n, 'M' => n]

        while ($row = $stmt->fetch()) {
            $sexo = trim($row['Sexo'] ?? '');
            $esMasc = ($sexo === 'Masculino');
            $esEmbarazada = ! empty(trim($row['MUJER_EMBARAZADA'] ?? ''));

            // Calcular edad para determinar subvar
            $subEdad = $this->calcularSubvarEdadOdonto($row['HCL_FECNAC'], $row['Fecha'], $esEmbarazada);

            // Determinar si es consulta nueva o repetida
            $esReconsulta = (trim($row['Reconsulta'] ?? '') === 'Si');

            // Para cada campo de procedimiento, acumular en la variable SNIS correspondiente
            foreach (self::G03_CAMPOS_VAR as $campo => $varCode) {
                $valor = (int) ($row[$campo] ?? 0);
                if ($valor <= 0) {
                    continue;
                }

                $codsubvar = '30103'.$varCode.$subEdad;

                if (! isset($acumulado[$codsubvar])) {
                    $acumulado[$codsubvar] = ['V' => 0, 'M' => 0];
                }

                if ($esMasc) {
                    $acumulado[$codsubvar]['V'] += $valor;
                } else {
                    $acumulado[$codsubvar]['M'] += $valor;
                }
            }

            // Consulta Nueva (var 19) / Repetida (var 20)
            $varConsulta = $esReconsulta ? '20' : '19';
            $codsubvar = '30103'.$varConsulta.$subEdad;

            if (! isset($acumulado[$codsubvar])) {
                $acumulado[$codsubvar] = ['V' => 0, 'M' => 0];
            }

            if ($esMasc) {
                $acumulado[$codsubvar]['V']++;
            } else {
                $acumulado[$codsubvar]['M']++;
            }
        }

        // Insertar/actualizar en SNIS
        $count = 0;
        foreach ($acumulado as $codsubvar => $vals) {
            if ($vals['V'] + $vals['M'] === 0) {
                continue;
            }

            $check = $this->pdoSnis->prepare(
                'SELECT COUNT(*) FROM [301G03_DAT] WHERE idgestion = ? AND corr_estabgest = ? AND mes = ? AND codsubvar = ?'
            );
            $check->execute([$anio, $corr, $mes, $codsubvar]);

            if ((int) $check->fetchColumn() > 0) {
                $this->pdoSnis->prepare(
                    'UPDATE [301G03_DAT] SET V = ?, M = ? WHERE idgestion = ? AND corr_estabgest = ? AND mes = ? AND codsubvar = ?'
                )->execute([$vals['V'], $vals['M'], $anio, $corr, $mes, $codsubvar]);
            } else {
                $this->pdoSnis->prepare(
                    'INSERT INTO [301G03_DAT] (corr_estabgest, mes, sem, idgestion, codsubvar, V, M)
                     VALUES (?, ?, 0, ?, ?, ?, ?)'
                )->execute([$corr, $mes, $anio, $codsubvar, $vals['V'], $vals['M']]);
            }

            $count++;
        }

        return $count;
    }

    private function calcularSubvarEdadOdonto(?string $fechaNac, string $fechaAtencion, bool $esEmbarazada): string
    {
        if ($esEmbarazada) {
            return '06'; // Embarazada
        }

        if (! $fechaNac) {
            return '04'; // Default: 20-59 (más común)
        }

        try {
            $nacimiento = new \DateTime($fechaNac);
            $atencion = new \DateTime($fechaAtencion);
            $edad = $nacimiento->diff($atencion)->y;
        } catch (\Exception) {
            return '04';
        }

        return match (true) {
            $edad < 5 => '01', // Menores de 5
            $edad <= 13 => '02', // 5 a 13
            $edad <= 19 => '03', // 14 a 19
            $edad <= 59 => '04', // 20 a 59
            default => '05', // 60+
        };
    }

    // ───────── 302: Vigilancia Epidemiológica Semanal ─────────

    private function transferir302Semanal(string $corr, int $anio, ?int $mesFiltro, bool $limpiar): int
    {
        $this->newLine();
        $this->info('═══ 302A Vigilancia Epidemiológica Semanal ═══');

        $whereMonth = $mesFiltro ? " AND MONTH(Fecha) = {$mesFiltro}" : '';

        $aniosCol = "A\xf1os"; // Años en Latin-1
        $sql = "SELECT Fecha, CIE_I, CIE_II, CIE_III, CAUSA_EXTERNA,
                       [{$aniosCol}] as anios, Meses as meses_edad,
                       DAT_SEXO, Reconsulta
                FROM SE_CONSULTA_EXTERNA
                WHERE YEAR(Fecha) = {$anio}{$whereMonth}";

        $rows = $this->pdoSoaps->query($sql)->fetchAll();

        $this->line('  Consultas en SOAPS: '.count($rows));

        // Acumular: [sem => [codsubvar => [V, M]]]
        $datos = [];
        $matchCount = 0;

        foreach ($rows as $row) {
            $fecha = $row['Fecha'];
            $sem = (int) date('W', strtotime($fecha));
            $sexo = (int) $row['DAT_SEXO']; // 1=M, 2=F
            $aniosEdad = (int) ($row['anios'] ?? 0);
            $mesesEdad = (int) ($row['meses_edad'] ?? 0);

            // Buscar coincidencias CIE en todas las posiciones
            $matched = [];
            foreach (['CIE_I', 'CIE_II', 'CIE_III', 'CAUSA_EXTERNA'] as $campo) {
                $cie = strtoupper(trim($row[$campo] ?? ''));
                if ($cie === '' || $cie === 'Z76.8') {
                    continue;
                }

                $m = $this->matchCie(self::CIE_302_MAP, $cie);
                if ($m) {
                    $key = $m['grp'].$m['var'];
                    if (! isset($matched[$key])) {
                        $matched[$key] = $m;
                    }
                }
            }

            if (empty($matched)) {
                continue;
            }
            $matchCount++;

            $edadSub = $this->edadSubvar302($aniosEdad, $mesesEdad);

            foreach ($matched as $m) {
                $grp = str_pad($m['grp'], 2, '0', STR_PAD_LEFT);
                $var = str_pad($m['var'], 2, '0', STR_PAD_LEFT);
                $codsubvar = "302{$grp}{$var}{$edadSub}";

                if (! isset($datos[$sem][$codsubvar])) {
                    $datos[$sem][$codsubvar] = ['V' => 0, 'M' => 0];
                }

                if ($sexo === 1) {
                    $datos[$sem][$codsubvar]['V']++;
                } else {
                    $datos[$sem][$codsubvar]['M']++;
                }
            }
        }

        $this->line("  Con diagnóstico epidemiológico: {$matchCount}");
        $this->line('  Semanas con datos: '.count($datos));

        // Escribir al SNIS
        $count = 0;
        ksort($datos);

        foreach ($datos as $sem => $subvars) {
            // El mes se determina por el jueves de la semana ISO (convención SNIS)
            $jueves = new \DateTime;
            $jueves->setISODate($anio, $sem, 4);
            $mes = (int) $jueves->format('n');

            $this->asegurarCabecera302($corr, $sem, $mes, $anio);

            if ($limpiar) {
                $grupos = [];
                foreach (array_keys($subvars) as $csv) {
                    $grupos[substr($csv, 3, 2)] = true;
                }
                foreach (array_keys($grupos) as $g) {
                    $this->limpiarPorSemana("302G{$g}_DAT", $corr, $sem, $anio);
                }
            }

            foreach ($subvars as $codsubvar => $vals) {
                if ($vals['V'] + $vals['M'] === 0) {
                    continue;
                }

                $grp = substr($codsubvar, 3, 2);
                // Solo escribir a la tabla de grupo (302_DAT es query no actualizable en Access)
                $this->upsertDato("302G{$grp}_DAT", $anio, $corr, $mes, $sem, $codsubvar, $vals['V'], $vals['M']);
                $count++;
            }

            $this->line("  Sem {$sem} (mes {$mes}): ".count($subvars).' registros');
        }

        return $count;
    }

    // ───────── 305: Vigilancia Epidemiológica Mensual ─────────

    private function transferir305Mensual(string $corr, int $anio, ?int $mesFiltro, bool $limpiar): int
    {
        $this->newLine();
        $this->info('═══ 305 Vigilancia Epidemiológica Mensual ═══');

        $whereMonth = $mesFiltro ? " AND MONTH(Fecha) = {$mesFiltro}" : '';

        $aniosCol = "A\xf1os"; // Años en Latin-1
        $sql = "SELECT Fecha, CIE_I, CIE_II, CIE_III,
                       [{$aniosCol}] as anios, Meses as meses_edad,
                       DAT_SEXO, Reconsulta
                FROM SE_CONSULTA_EXTERNA
                WHERE YEAR(Fecha) = {$anio}{$whereMonth}";

        $rows = $this->pdoSoaps->query($sql)->fetchAll();

        $this->line('  Consultas en SOAPS: '.count($rows));

        // Acumular: [mes => [codsubvar => [V, M]]]
        // V = Caso Nuevo (Reconsulta != 'Si'), M = En Control (Reconsulta = 'Si')
        $datos = [];
        $matchCount = 0;

        foreach ($rows as $row) {
            $mes = (int) date('n', strtotime($row['Fecha']));
            $sexo = (int) $row['DAT_SEXO']; // 1=M, 2=F
            $aniosEdad = (int) ($row['anios'] ?? 0);
            $mesesEdad = (int) ($row['meses_edad'] ?? 0);
            $esNuevo = (strtolower(trim($row['Reconsulta'] ?? '')) !== 'si');

            $matched = [];
            foreach (['CIE_I', 'CIE_II', 'CIE_III'] as $campo) {
                $cie = strtoupper(trim($row[$campo] ?? ''));
                if ($cie === '' || $cie === 'Z76.8') {
                    continue;
                }

                $m = $this->matchCie(self::CIE_305_MAP, $cie);
                if ($m) {
                    $key = $m['grp'].$m['var'];
                    if (! isset($matched[$key])) {
                        $matched[$key] = $m;
                    }
                }
            }

            if (empty($matched)) {
                continue;
            }
            $matchCount++;

            foreach ($matched as $m) {
                $grp = str_pad($m['grp'], 2, '0', STR_PAD_LEFT);
                $var = str_pad($m['var'], 2, '0', STR_PAD_LEFT);

                // G02,G03,G04,G08,G09 → 20 subvars (edad×sexo)
                // G01,G05,G06,G07,G10 → 10 subvars (edad)
                $numSub = in_array($grp, ['02', '03', '04', '08', '09']) ? 20 : 10;

                if ($numSub === 20) {
                    $sub = $this->edadSubvar305_20($aniosEdad, $mesesEdad, $sexo);
                } else {
                    $sub = $this->edadSubvar302($aniosEdad, $mesesEdad);
                }

                $codsubvar = "305{$grp}{$var}{$sub}";

                if (! isset($datos[$mes][$codsubvar])) {
                    $datos[$mes][$codsubvar] = ['V' => 0, 'M' => 0];
                }

                if ($esNuevo) {
                    $datos[$mes][$codsubvar]['V']++;
                } else {
                    $datos[$mes][$codsubvar]['M']++;
                }
            }
        }

        $this->line("  Con diagnóstico crónico/mental: {$matchCount}");
        $this->line('  Meses con datos: '.count($datos));

        // Escribir al SNIS
        $count = 0;
        ksort($datos);

        foreach ($datos as $mes => $subvars) {
            $this->asegurarCabecera305($corr, $mes, $anio);

            if ($limpiar) {
                $grupos = [];
                foreach (array_keys($subvars) as $csv) {
                    $grupos[substr($csv, 3, 2)] = true;
                }
                foreach (array_keys($grupos) as $g) {
                    $this->limpiarPorMes305("305G{$g}_DAT", $corr, $mes, $anio);
                }
            }

            foreach ($subvars as $codsubvar => $vals) {
                if ($vals['V'] + $vals['M'] === 0) {
                    continue;
                }

                $grp = substr($codsubvar, 3, 2);
                // Solo escribir a la tabla de grupo (305_DAT es query no actualizable en Access)
                $this->upsertDato("305G{$grp}_DAT", $anio, $corr, $mes, 0, $codsubvar, $vals['V'], $vals['M']);
                $count++;
            }

            $this->line("  Mes {$mes}: ".count($subvars).' registros');
        }

        return $count;
    }

    // ───────── Helpers: CIE-10 matching ─────────

    private function matchCie(array $map, string $cie): ?array
    {
        foreach ($map as $entry) {
            if (preg_match($entry['rx'], $cie)) {
                return $entry;
            }
        }

        return null;
    }

    // ───────── Helpers: subvar de edad ─────────

    private function edadSubvar302(int $anios, int $meses): string
    {
        return match (true) {
            $anios == 0 && $meses < 6 => '01',
            $anios == 0 => '02',
            $anios <= 4 => '03',
            $anios <= 9 => '04',
            $anios <= 14 => '05',
            $anios <= 19 => '06',
            $anios <= 39 => '07',
            $anios <= 49 => '08',
            $anios <= 59 => '09',
            default => '10',
        };
    }

    private function edadSubvar305_20(int $anios, int $meses, int $sexo): string
    {
        // 20 subvars: impar=Masculino, par=Femenino
        // Pares: 01/02=<6m, 03/04=6-11m, 05/06=1-4, 07/08=5-9,
        //        09/10=10-14, 11/12=15-19, 13/14=20-39, 15/16=40-49, 17/18=50-59, 19/20=60+
        $base = match (true) {
            $anios == 0 && $meses < 6 => 1,
            $anios == 0 => 3,
            $anios <= 4 => 5,
            $anios <= 9 => 7,
            $anios <= 14 => 9,
            $anios <= 19 => 11,
            $anios <= 39 => 13,
            $anios <= 49 => 15,
            $anios <= 59 => 17,
            default => 19,
        };

        $sub = ($sexo === 2) ? $base + 1 : $base;

        return str_pad((string) $sub, 2, '0', STR_PAD_LEFT);
    }

    // ───────── Helpers: cabeceras 302/305 ─────────

    private function asegurarCabecera302(string $corr, int $sem, int $mes, int $anio): void
    {
        $stmt = $this->pdoSnis->prepare(
            'SELECT COUNT(*) FROM [302_CAB] WHERE corr_estabgest = ? AND sem = ?'
        );
        $stmt->execute([$corr, $sem]);

        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $medico = $this->obtenerMedico();
        $fecha = sprintf('%d-%02d-01', $anio, $mes);

        $this->pdoSnis->prepare(
            'INSERT INTO [302_CAB] (corr_estabgest, mes, sem, codestado, nombre, fecha, ci, fechasis, idUsuario)
             VALUES (?, ?, ?, 0, ?, ?, ?, ?, 80)'
        )->execute([$corr, $mes, $sem, $medico['nombre'], $fecha, $medico['ci'], $fecha]);

        $this->line("  → Cabecera 302_CAB: sem {$sem}, mes {$mes}");
    }

    private function asegurarCabecera305(string $corr, int $mes, int $anio): void
    {
        $stmt = $this->pdoSnis->prepare(
            'SELECT COUNT(*) FROM [305_CAB] WHERE corr_estabgest = ? AND mes = ?'
        );
        $stmt->execute([$corr, $mes]);

        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $medico = $this->obtenerMedico();
        $fecha = sprintf('%d-%02d-01', $anio, $mes);

        $this->pdoSnis->prepare(
            'INSERT INTO [305_CAB] (corr_estabgest, mes, sem, codestado, nombre, fecha, ci, fechasis, idUsuario)
             VALUES (?, ?, 0, 0, ?, ?, ?, ?, 80)'
        )->execute([$corr, $mes, $medico['nombre'], $fecha, $medico['ci'], $fecha]);

        $this->line("  → Cabecera 305_CAB: mes {$mes}");
    }

    private function obtenerMedico(): array
    {
        static $medico = null;
        if ($medico !== null) {
            return $medico;
        }

        $stmt = $this->pdoSoaps->query(
            'SELECT TOP 1 pperNombre, pperDocIde FROM perPersona WHERE pperSwMedi = 1'
        );
        $row = $stmt->fetch();

        return $medico = [
            'nombre' => $row ? trim($row['pperNombre']) : 'RESPONSABLE',
            'ci' => $row ? trim($row['pperDocIde']) : '',
        ];
    }

    // ───────── Helpers: upsert + limpiar ─────────

    private function upsertDato(string $tabla, int $anio, string $corr, int $mes, int $sem, string $codsubvar, int $v, int $m): void
    {
        $check = $this->pdoSnis->prepare(
            "SELECT COUNT(*) FROM [{$tabla}] WHERE idgestion = ? AND corr_estabgest = ? AND sem = ? AND codsubvar = ?"
        );
        $check->execute([$anio, $corr, $sem, $codsubvar]);

        if ((int) $check->fetchColumn() > 0) {
            $this->pdoSnis->prepare(
                "UPDATE [{$tabla}] SET V = ?, M = ? WHERE idgestion = ? AND corr_estabgest = ? AND sem = ? AND codsubvar = ?"
            )->execute([$v, $m, $anio, $corr, $sem, $codsubvar]);
        } else {
            $this->pdoSnis->prepare(
                "INSERT INTO [{$tabla}] (idgestion, corr_estabgest, mes, sem, codsubvar, V, M)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([$anio, $corr, $mes, $sem, $codsubvar, $v, $m]);
        }
    }

    private function limpiarPorSemana(string $tabla, string $corr, int $sem, int $anio): void
    {
        $stmt = $this->pdoSnis->prepare(
            "DELETE FROM [{$tabla}] WHERE corr_estabgest = ? AND sem = ? AND idgestion = ?"
        );
        $stmt->execute([$corr, $sem, $anio]);
        $deleted = $stmt->rowCount();

        if ($deleted > 0) {
            $this->warn("  → Limpiado {$tabla} sem {$sem}: {$deleted} registros");
        }
    }

    private function limpiarPorMes305(string $tabla, string $corr, int $mes, int $anio): void
    {
        $stmt = $this->pdoSnis->prepare(
            "DELETE FROM [{$tabla}] WHERE corr_estabgest = ? AND mes = ? AND idgestion = ?"
        );
        $stmt->execute([$corr, $mes, $anio]);
        $deleted = $stmt->rowCount();

        if ($deleted > 0) {
            $this->warn("  → Limpiado {$tabla} mes {$mes}: {$deleted} registros");
        }
    }
}
