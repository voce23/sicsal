<?php

namespace App\Console\Commands;

use App\Models\CausaConsultaExterna;
use App\Models\CentroSalud;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class ImportarCausasConsulta extends Command
{
    protected $signature = 'soaps:importar-causas
        {--servidor=.\\SNS : Instancia SQL Server del SOAPS}
        {--db=BDestadistica : Base de datos SOAPS}
        {--usuario=sa : Usuario SQL Server}
        {--password= : Contraseña SQL Server (default: env SOAPS_PASS)}
        {--anio=2026 : Gestión a importar}
        {--meses= : Meses separados por coma (default: todos)}
        {--top=10 : Cantidad de principales causas}
        {--limpiar : Eliminar datos existentes antes de importar}';

    protected $description = 'Importa las 10 principales causas de consulta externa desde SOAPS a SIMUES';

    private ?PDO $soaps = null;

    // Mapeo grupo etáreo a partir de meses totales de vida
    private function getGrupoFromMeses(int $totalMeses): string
    {
        if ($totalMeses < 6) {
            return 'menor_6m';
        }
        if ($totalMeses < 12) {
            return '6m_menor_1';
        }
        if ($totalMeses < 60) {
            return '1_4';
        }
        if ($totalMeses < 120) {
            return '5_9';
        }
        if ($totalMeses < 180) {
            return '10_14';
        }
        if ($totalMeses < 240) {
            return '15_19';
        }
        if ($totalMeses < 480) {
            return '20_39';
        }
        if ($totalMeses < 600) {
            return '40_49';
        }
        if ($totalMeses < 720) {
            return '50_59';
        }

        return 'mayor_60';
    }

    public function handle(): int
    {
        $servidor = trim($this->option('servidor')) ?: env('SOAPS_SERVER', '.\\SNS');
        $db = trim($this->option('db')) ?: env('SOAPS_DB', 'BDestadistica');
        $usuario = trim($this->option('usuario')) ?: env('SOAPS_USER', 'sa');
        $password = trim($this->option('password')) ?: env('SOAPS_PASS', '');
        $anio = (int) $this->option('anio');
        $top = (int) $this->option('top');
        $mesesFiltro = $this->option('meses')
            ? array_map('intval', explode(',', $this->option('meses')))
            : null;

        $this->info("Conectando a SOAPS: {$servidor} / {$db}");

        try {
            $dsn = "odbc:Driver={SQL Server};Server={$servidor};Database={$db};UID={$usuario};PWD={$password};";
            $this->soaps = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\Exception $e) {
            $this->error('Error de conexión SOAPS: '.$e->getMessage());

            return self::FAILURE;
        }

        // Obtener todos los centros de salud con código SNIS → codestabl SOAPS (6 dígitos finales)
        $centros = CentroSalud::whereNotNull('codigo_snis')->where('activo', true)->get();
        if ($centros->isEmpty()) {
            $this->error('No hay centros de salud con código SNIS configurados.');

            return self::FAILURE;
        }

        // Obtener meses disponibles en SOAPS para el año
        $mesesDisponibles = $this->getMesesDisponibles($anio, $centros->pluck('codigo_snis')->toArray());
        if ($mesesFiltro) {
            $mesesDisponibles = array_values(array_intersect($mesesDisponibles, $mesesFiltro));
        }

        if (empty($mesesDisponibles)) {
            $this->warn("No hay datos en SOAPS para gestión {$anio}.");

            return self::SUCCESS;
        }

        $this->info('Meses con datos: '.implode(', ', $mesesDisponibles));

        $totalImportados = 0;

        DB::beginTransaction();
        try {
            foreach ($centros as $centro) {
                // El codestabl en SOAPS son los últimos 6 dígitos del código SNIS
                $codestabl = (int) substr($centro->codigo_snis, -6);

                // Verificar si hay datos de este establecimiento en SOAPS
                $stmt = $this->soaps->prepare(
                    'SELECT COUNT(*) as cnt FROM se_consulta_externa
                     WHERE codestabl = ? AND YEAR(Fecha) = ?
                     AND CIE_I IS NOT NULL AND LEN(RTRIM(CIE_I)) > 0'
                );
                $stmt->execute([$codestabl, $anio]);
                $row = $stmt->fetch();
                if ((int) $row['cnt'] === 0) {
                    $this->line("  {$centro->nombre}: sin datos en SOAPS, omitido.");

                    continue;
                }

                $this->info("→ {$centro->nombre} (SOAPS codestabl: {$codestabl})");

                foreach ($mesesDisponibles as $mes) {
                    $n = $this->importarMes($centro->id, $codestabl, $anio, $mes, $top);
                    $this->line("    Mes {$mes}: {$n} registros importados");
                    $totalImportados += $n;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error durante la importación: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Total registros insertados/actualizados: {$totalImportados}");

        return self::SUCCESS;
    }

    private function getMesesDisponibles(int $anio, array $codigosSnis): array
    {
        // Convertir códigos SNIS (8 dígitos) a codestabl SOAPS (6 dígitos finales)
        $codestablList = array_map(fn ($c) => (int) substr($c, -6), $codigosSnis);
        if (empty($codestablList)) {
            return [];
        }

        $placeholders = implode(',', $codestablList);
        $stmt = $this->soaps->prepare(
            "SELECT DISTINCT MONTH(Fecha) as mes FROM se_consulta_externa
             WHERE codestabl IN ({$placeholders}) AND YEAR(Fecha) = ?
             ORDER BY mes"
        );
        $stmt->execute([$anio]);

        return array_column($stmt->fetchAll(), 'mes');
    }

    private function importarMes(int $centroId, int $codestabl, int $anio, int $mes, int $top): int
    {
        // Obtener todos los registros del mes (incluyendo sin CIE).
        // Los sin CIE se tratan como Z76.8 (Carmelo / actividad preventiva no codificada).
        // El diccionario CIE10 se carga aparte para evitar el tipo TEXT de SQL Server.
        $stmtCie = $this->soaps->query(
            'SELECT CIE_ALFA, CAST(CIE_DESCRIPCION AS varchar(500)) as desc10 FROM SE_CIE10'
        );
        $cie10 = [];
        foreach ($stmtCie->fetchAll() as $row) {
            $cie10[trim($row['CIE_ALFA'])] = $row['desc10'];
        }

        // Nota: columna "Años" usa ñ (chr 241 latin-1) — se usa DATEDIFF como alternativa
        // Sin CIE + ≥60 años → Z76.8 (Carmelo). Sin CIE + <60 años → excluir (curaciones, etc.)
        $stmtAll = $this->soaps->prepare("
            SELECT
                CASE
                    WHEN CIE_I IS NULL OR LEN(RTRIM(CIE_I)) = 0 THEN 'Z76.8'
                    ELSE CIE_I
                END as cie,
                Sexo,
                DATEDIFF(month, HCL_FECNAC, Fecha) as total_meses
            FROM se_consulta_externa
            WHERE codestabl = ? AND MONTH(Fecha) = ? AND YEAR(Fecha) = ?
                AND NOT (
                    (CIE_I IS NULL OR LEN(RTRIM(CIE_I)) = 0)
                    AND DATEDIFF(month, HCL_FECNAC, Fecha) < 720
                )
        ");
        $stmtAll->execute([$codestabl, $mes, $anio]);
        $allRecords = $stmtAll->fetchAll();

        if (empty($allRecords)) {
            return 0;
        }

        $grupos = array_keys(CausaConsultaExterna::$grupos);

        // Acumular en PHP: por CIE → grupo → M/F
        $conteos = [];
        foreach ($allRecords as $d) {
            $cie = trim($d['cie'] ?? 'Z76.8') ?: 'Z76.8';
            $g = $this->getGrupoFromMeses(max(0, (int) ($d['total_meses'] ?? 0)));
            $sexo = $d['Sexo'];
            if (! isset($conteos[$cie])) {
                $conteos[$cie] = array_fill_keys($grupos, ['m' => 0, 'f' => 0]);
            }
            if ($sexo === 'Masculino') {
                $conteos[$cie][$g]['m']++;
            } else {
                $conteos[$cie][$g]['f']++;
            }
        }

        // Ordenar por total descendente y tomar top-N
        $totales = [];
        foreach ($conteos as $cie => $gs) {
            $t = 0;
            foreach ($gs as $v) {
                $t += $v['m'] + $v['f'];
            }
            $totales[$cie] = $t;
        }
        arsort($totales);
        $top10 = array_slice($totales, 0, $top, true);

        // 3. Limpiar registros existentes del mes
        CausaConsultaExterna::where('centro_salud_id', $centroId)
            ->where('mes', $mes)->where('anio', $anio)
            ->delete();

        // 4. Insertar top-N diagnósticos
        $n = 0;
        $posicion = 1;
        foreach ($top10 as $cie => $_) {
            $rawDiag = $cie10[$cie] ?? $cie;
            // SOAPS devuelve Latin-1/Windows-1252 → convertir a UTF-8 para MySQL
            $diag = mb_detect_encoding($rawDiag, ['UTF-8'], true)
                ? $rawDiag
                : mb_convert_encoding($rawDiag, 'UTF-8', 'Windows-1252');

            foreach ($grupos as $g) {
                $m = $conteos[$cie][$g]['m'] ?? 0;
                $f = $conteos[$cie][$g]['f'] ?? 0;
                if ($m === 0 && $f === 0) {
                    continue;
                }

                CausaConsultaExterna::create([
                    'centro_salud_id' => $centroId,
                    'mes' => $mes,
                    'anio' => $anio,
                    'posicion' => $posicion,
                    'diagnostico' => $diag,
                    'grupo_etareo' => $g,
                    'masculino' => $m,
                    'femenino' => $f,
                ]);
                $n++;
            }
            $posicion++;
        }

        return $n;
    }
}
