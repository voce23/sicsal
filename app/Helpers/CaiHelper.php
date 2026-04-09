<?php

namespace App\Helpers;

use App\Models\CentroSalud;
use App\Models\Comunidad;
use App\Models\JustificacionCero;
use App\Models\MesCerrado;
use App\Models\MetaIne;
use App\Models\ObservacionMensual;
use App\Models\Persona;
use App\Models\PrestAnticoncepcion;
use App\Models\PrestConsultaExterna;
use App\Models\PrestCrecimiento;
use App\Models\PrestMicronutriente;
use App\Models\PrestParto;
use App\Models\PrestPrenatal;
use App\Models\PrestPuerperio;
use App\Models\PrestRecienNacido;
use App\Models\PrestVacuna;
use App\Models\User;

class CaiHelper
{
    private static array $nombresMeses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public static function getMeses(string $periodo): array
    {
        return match ($periodo) {
            'cai1' => range(1, 4),
            'cai2' => range(1, 8),
            'gestion' => range(1, 12),
            default => range(1, 12),
        };
    }

    public static function getFactorMeta(string $periodo): float
    {
        return match ($periodo) {
            'cai1' => 4 / 12,
            'cai2' => 8 / 12,
            'gestion' => 1.0,
            default => 1.0,
        };
    }

    public static function getNombrePeriodo(string $periodo, int $anio): string
    {
        return match ($periodo) {
            'cai1' => "CAI 1 (Ene–Abr {$anio})",
            'cai2' => "CAI 2 (Ene–Ago {$anio})",
            'gestion' => "Cierre de Gestión {$anio}",
            default => "Gestión {$anio}",
        };
    }

    public static function getEncabezado(int $centroId, string $periodo, int $anio): array
    {
        $centro = CentroSalud::with('municipio')->find($centroId);
        $admin = User::where('centro_salud_id', $centroId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();

        return [
            'centro_nombre' => $centro->nombre ?? '—',
            'codigo_snis' => $centro->codigo_snis ?? '—',
            'red_salud' => $centro->red_salud ?? '—',
            'municipio' => $centro->municipio->nombre ?? '—',
            'departamento' => $centro->municipio->departamento ?? 'Cochabamba',
            'subsector' => $centro->subsector ?? '—',
            'poblacion_ine' => $centro->poblacion_ine ?? 0,
            'responsable' => $admin ? ($admin->name ?? '—') : '—',
            'periodo_nombre' => self::getNombrePeriodo($periodo, $anio),
            'periodo' => $periodo,
            'anio' => $anio,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];
    }

    /* ───── SECCIÓN 1 — Contexto de Migración ───── */
    public static function getMigracion(int $centroId): array
    {
        $base = Persona::where('centro_salud_id', $centroId)->where('activo', true);

        $totalPadron = (clone $base)->count();
        $migrantes = (clone $base)->where('estado', 'migrado')->count();
        $residentes = $totalPadron - $migrantes;

        $mefActivas = (clone $base)->where('sexo', 'F')->where('estado', '!=', 'migrado')
            ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 49')->count();
        $mefMigradas = (clone $base)->where('sexo', 'F')->where('estado', 'migrado')
            ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 49')->count();
        $hombresMigrados = (clone $base)->where('sexo', 'M')->where('estado', 'migrado')->count();

        return [
            'total_padron' => $totalPadron,
            'residentes' => $residentes,
            'migrantes' => $migrantes,
            'pct_migrantes' => $totalPadron > 0 ? round($migrantes / $totalPadron * 100, 1) : 0,
            'mef_activas' => $mefActivas,
            'mef_migradas' => $mefMigradas,
            'pct_mef_migradas' => ($mefActivas + $mefMigradas) > 0
                ? round($mefMigradas / ($mefActivas + $mefMigradas) * 100, 1) : 0,
            'hombres_migrados' => $hombresMigrados,
        ];
    }

    /* ───── SECCIÓN 2 — Censo Poblacional ───── */
    public static function getCenso(int $centroId, int $anio): array
    {
        $metasIne = MetaIne::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->get();

        $comunidades = Comunidad::where('centro_salud_id', $centroId)
            ->where('activo', true)->orderBy('nombre')->get()
            ->map(function ($com) {
                $base = Persona::where('comunidad_id', $com->id)->where('activo', true);
                $total = (clone $base)->count();
                $hombres = (clone $base)->where('sexo', 'M')->count();
                $mujeres = (clone $base)->where('sexo', 'F')->count();
                $menor5 = (clone $base)->where('fecha_nacimiento', '>=', now()->subYears(5))->count();
                $migrantes = (clone $base)->where('estado', 'migrado')->count();

                return [
                    'nombre' => $com->nombre,
                    'distancia_km' => $com->distancia_km,
                    'total' => $total,
                    'hombres' => $hombres,
                    'mujeres' => $mujeres,
                    'menor_5' => $menor5,
                    'migrantes' => $migrantes,
                ];
            })->toArray();

        $gruposPiramide = [
            ['label' => '< 1 año', 'min' => 0, 'max' => 0, 'ine' => 'menor_1'],
            ['label' => '1 año', 'min' => 1, 'max' => 1, 'ine' => '1_anio'],
            ['label' => '2-4 años', 'min' => 2, 'max' => 4, 'ine' => '2_4'],
            ['label' => '5-9 años', 'min' => 5, 'max' => 9, 'ine' => '5_9'],
            ['label' => '10-14 años', 'min' => 10, 'max' => 14, 'ine' => '10_14'],
            ['label' => '15-19 años', 'min' => 15, 'max' => 19, 'ine' => '15_19'],
            ['label' => '20-39 años', 'min' => 20, 'max' => 39, 'ine' => '20_39'],
            ['label' => '40-49 años', 'min' => 40, 'max' => 49, 'ine' => '40_49'],
            ['label' => '50-59 años', 'min' => 50, 'max' => 59, 'ine' => '50_59'],
            ['label' => '≥ 60 años', 'min' => 60, 'max' => 120, 'ine' => 'mayor_60'],
        ];

        $piramide = [];
        foreach ($gruposPiramide as $g) {
            $ineM = $metasIne->where('grupo_etareo', $g['ine'])->where('sexo', 'M')->sum('cantidad');
            $ineF = $metasIne->where('grupo_etareo', $g['ine'])->where('sexo', 'F')->sum('cantidad');
            $ineAmbos = $metasIne->where('grupo_etareo', $g['ine'])->where('sexo', 'ambos')->sum('cantidad');
            if ($ineM === 0 && $ineF === 0 && $ineAmbos > 0) {
                $ineM = intdiv($ineAmbos, 2);
                $ineF = $ineAmbos - $ineM;
            }

            // La pirámide muestra SIEMPRE todos los activos no migrados/fallecidos
            // La verificación no afecta la pirámide — refleja el padrón completo
            $base = Persona::where('centro_salud_id', $centroId)->where('activo', true)
                ->whereNotIn('estado', ['migrado', 'fallecido']);
            $realM = (clone $base)->where('sexo', 'M')
                ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN ? AND ?', [$g['min'], $g['max']])
                ->count();
            $realF = (clone $base)->where('sexo', 'F')
                ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN ? AND ?', [$g['min'], $g['max']])
                ->count();

            $piramide[] = [
                'label' => $g['label'],
                'ine_m' => $ineM, 'ine_f' => $ineF,
                'real_m' => $realM, 'real_f' => $realF,
            ];
        }

        // Total INE: suma solo los grupos de la pirámide (evita doble conteo por grupos solapados)
        $gruposPiramideKeys = array_column($gruposPiramide, 'ine');
        $metaIneTotal = $metasIne->filter(function ($m) use ($gruposPiramideKeys) {
            return in_array($m->grupo_etareo, $gruposPiramideKeys);
        })->sum('cantidad');

        return [
            'comunidades' => $comunidades,
            'piramide' => $piramide,
            'meta_ine_total' => $metaIneTotal,
        ];
    }

    /* ───── SECCIÓN 3 — Prestaciones Acumuladas ───── */
    public static function getPrestaciones(int $centroId, int $anio, array $meses): array
    {
        // Consulta externa totales
        $consultaExterna = PrestConsultaExterna::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('grupo_etareo,
                SUM(primera_m) as primera_m, SUM(primera_f) as primera_f,
                SUM(nueva_m) as nueva_m, SUM(nueva_f) as nueva_f,
                SUM(repetida_m) as repetida_m, SUM(repetida_f) as repetida_f')
            ->groupBy('grupo_etareo')->get()
            ->keyBy('grupo_etareo')->toArray();

        // Vacunas acumuladas
        $vacunas = PrestVacuna::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('tipo_vacuna, grupo_etareo,
                SUM(dentro_m + dentro_f + fuera_m + fuera_f) as total,
                SUM(dentro_m) as dm, SUM(dentro_f) as df,
                SUM(fuera_m) as fm, SUM(fuera_f) as ff')
            ->groupBy('tipo_vacuna', 'grupo_etareo')->get()
            ->map(fn ($r) => [
                'tipo_vacuna' => $r->tipo_vacuna,
                'grupo_etareo' => $r->grupo_etareo,
                'total' => $r->total,
                'dentro_m' => $r->dm, 'dentro_f' => $r->df,
                'fuera_m' => $r->fm, 'fuera_f' => $r->ff,
            ])->toArray();

        // Micronutrientes
        $micronutrientes = PrestMicronutriente::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('tipo, SUM(cantidad) as total')
            ->groupBy('tipo')->pluck('total', 'tipo')->toArray();

        // Prenatales
        $prenatales = PrestPrenatal::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('tipo_control, grupo_etareo, SUM(dentro) as dentro, SUM(fuera) as fuera')
            ->groupBy('tipo_control', 'grupo_etareo')->get()->toArray();

        // Partos
        $partos = PrestParto::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('tipo, lugar, atendido_por, grupo_etareo, SUM(cantidad) as total')
            ->groupBy('tipo', 'lugar', 'atendido_por', 'grupo_etareo')->get()->toArray();

        // Puerperio
        $puerperio = PrestPuerperio::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('tipo_control, SUM(cantidad) as total')
            ->groupBy('tipo_control')->pluck('total', 'tipo_control')->toArray();

        // Crecimiento
        $crecimiento = PrestCrecimiento::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('grupo_etareo,
                SUM(nuevos_m) as nuevos_m, SUM(nuevos_f) as nuevos_f,
                SUM(repetidos_m) as repetidos_m, SUM(repetidos_f) as repetidos_f')
            ->groupBy('grupo_etareo')->get()->keyBy('grupo_etareo')->toArray();

        // Recién nacidos
        $recienNacidos = PrestRecienNacido::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('indicador, SUM(cantidad) as total')
            ->groupBy('indicador')->pluck('total', 'indicador')->toArray();

        // Anticoncepción
        $anticoncepcion = PrestAnticoncepcion::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('metodo, tipo_usuaria, SUM(cantidad) as total')
            ->groupBy('metodo', 'tipo_usuaria')->get()->toArray();

        return [
            'consulta_externa' => $consultaExterna,
            'vacunas' => $vacunas,
            'micronutrientes' => $micronutrientes,
            'prenatales' => $prenatales,
            'partos' => $partos,
            'puerperio' => $puerperio,
            'crecimiento' => $crecimiento,
            'recien_nacidos' => $recienNacidos,
            'anticoncepcion' => $anticoncepcion,
        ];
    }

    /* ───── SECCIÓN 4 — Cobertura de Programas ───── */
    public static function getCobertura(int $centroId, int $anio, string $periodo): array
    {
        $meses = self::getMeses($periodo);
        // La Meta INE es siempre el dato ANUAL del INE (sin escalar por período).
        // El CAI mide cuánto se logró en los meses del período vs la meta anual.

        $metasIne = MetaIne::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->get();

        $programas = [];

        // — Vacunas —
        // Meta = nacimientos esperados en el AÑO (dato INE completo, no se divide)
        $metaMenor1 = $metasIne->where('grupo_etareo', 'nacimientos_esperados')->sum('cantidad');
        // Pob. Real: padrón completo activo no migrado/fallecido
        $baseCobertura = Persona::where('centro_salud_id', $centroId)->where('activo', true)
            ->whereNotIn('estado', ['migrado', 'fallecido']);
        $realMenor1 = (clone $baseCobertura)->where('fecha_nacimiento', '>', now()->subYear())->count();

        $vacunasIndicadores = [
            'BCG' => ['BCG'],
            'Pentavalente 1ra' => ['Pentavalente_1'],
            'Pentavalente 2da' => ['Pentavalente_2'],
            'Pentavalente 3ra' => ['Pentavalente_3'],
            'IPV 1ra' => ['IPV_1'],
            'bOPV 2da' => ['bOPV_2'],
            'bOPV 3ra' => ['IPV_3'],
            'Antirotavírica 1ra' => ['Antirotavirica_1'],
            'Antirotavírica 2da' => ['Antirotavirica_2'],
            'Antineumocócica 1ra' => ['Antineumococica_1'],
            'Antineumocócica 2da' => ['Antineumococica_2'],
            'Antineumocócica 3ra' => ['Antineumococica_3'],
            'Influenza 1ra' => ['Influenza_6_11m_1'],
            'Influenza 2da' => ['Influenza_7_11m_2'],
            'SRP 1ra' => ['SRP_1'],
            'Antiamarílica' => ['Antiamarilica'],
        ];

        foreach ($vacunasIndicadores as $nombre => $tipos) {
            $atendidos = PrestVacuna::where('centro_salud_id', $centroId)
                ->where('anio', $anio)->whereIn('mes', $meses)
                ->whereIn('tipo_vacuna', $tipos)
                ->selectRaw('SUM(dentro_m+dentro_f+fuera_m+fuera_f) as t')->value('t') ?? 0;
            $programas[] = self::buildFilaCobertura($nombre, $metaMenor1, $realMenor1, $atendidos);
        }

        // — Prenatal —
        $metaPrenatal = $metasIne->where('grupo_etareo', 'embarazos_esperados')->sum('cantidad');
        $mefActivas = (clone $baseCobertura)->where('sexo', 'F')
            ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 49')->count();
        $atPrenatal = PrestPrenatal::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('SUM(dentro+fuera) as t')->value('t') ?? 0;
        $programas[] = self::buildFilaCobertura('Prenatal', $metaPrenatal, $mefActivas, $atPrenatal);

        // — Partos —
        $metaParto = $metasIne->where('grupo_etareo', 'partos_esperados')->sum('cantidad');
        $atParto = PrestParto::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)->sum('cantidad');
        $programas[] = self::buildFilaCobertura('Partos', $metaParto, $mefActivas, $atParto);

        // — Crecimiento —
        // Meta = menores de 5 años proyectados por INE (menor_5 del Excel)
        $metaCrecim = $metasIne->where('grupo_etareo', 'menor_5')->sum('cantidad');
        $menores5 = (clone $baseCobertura)->where('fecha_nacimiento', '>=', now()->subYears(5))->count();
        $atCrecim = PrestCrecimiento::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->selectRaw('SUM(nuevos_m+nuevos_f+repetidos_m+repetidos_f) as t')->value('t') ?? 0;
        $programas[] = self::buildFilaCobertura('Crecimiento', $metaCrecim, $menores5, $atCrecim);

        // ── Micronutrientes — cada programa/grupo etáreo en su propia fila ────
        $nMeses = count($meses);

        $sumMicro = fn (array $tipos) => (int) PrestMicronutriente::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->whereIn('tipo', $tipos)->sum('cantidad');

        // Metas INE por grupo etáreo exacto (del Excel — cada año independiente)
        $ineM1  = $metasIne->where('grupo_etareo', 'menor_1')->sum('cantidad');
        $ine1a  = $metasIne->where('grupo_etareo', '1_anio')->sum('cantidad');
        $ine2_4 = $metasIne->where('grupo_etareo', '2_4')->sum('cantidad');
        $ineM60 = $metasIne->where('grupo_etareo', 'mayor_60')->sum('cantidad');

        // Pob. real del padrón por grupo
        // Para abuelos: usar todos los activos no migrados (igual que la pirámide)
        // Incluye fallecidos recientes porque Carmelo se entrega antes del registro de defunción
        $realM60 = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)->where('estado', '!=', 'migrado')
            ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) >= 60')->count();

        // ── HIERRO (1 dosis/año, cada grupo etáreo independiente) ──────────
        // 6-11 meses → meta = menor_1
        $programas[] = self::buildFilaCobertura(
            'Hierro 6-11 meses',
            $ineM1, 0,
            $sumMicro(['hierro_menor_1'])
        );
        // 1 año → meta = 1_anio
        $programas[] = self::buildFilaCobertura(
            'Hierro 1 año',
            $ine1a, 0,
            $sumMicro(['hierro_1anio'])
        );
        // 2-4 años → meta = 2_4 (grupo agregado del Excel)
        $programas[] = self::buildFilaCobertura(
            'Hierro 2-4 años',
            $ine2_4, 0,
            $sumMicro(['hierro_2_5'])
        );

        // ── VITAMINA A (2 dosis/año, cada grupo/dosis independiente) ────────
        // <1 año: 1 dosis única
        $programas[] = self::buildFilaCobertura(
            'Vitamina A <1 año (única)',
            $ineM1, 0,
            $sumMicro(['vitA_menor_1_unica'])
        );
        // 1 año: 1ra dosis
        $programas[] = self::buildFilaCobertura(
            'Vitamina A 1 año — 1ra dosis',
            $ine1a, 0,
            $sumMicro(['vitA_1anio_1ra'])
        );
        // 1 año: 2da dosis
        $programas[] = self::buildFilaCobertura(
            'Vitamina A 1 año — 2da dosis',
            $ine1a, 0,
            $sumMicro(['vitA_1anio_2da'])
        );
        // 2-4 años: 1ra dosis (meta = 2_4 agrupado)
        $programas[] = self::buildFilaCobertura(
            'Vitamina A 2-4 años — 1ra dosis',
            $ine2_4, 0,
            $sumMicro(['vitA_2_5_1ra'])
        );
        // 2-4 años: 2da dosis
        $programas[] = self::buildFilaCobertura(
            'Vitamina A 2-4 años — 2da dosis',
            $ine2_4, 0,
            $sumMicro(['vitA_2_5_2da'])
        );

        // ── NUTRIBEBÉ (mensual) ──────────────────────────────────────────────
        // Meta = número de niños del grupo INE (cuántos niños debo atender cada mes)
        // Atendidos = promedio mensual entregado en el período
        // Así: si doy a 6 de 12 niños cada mes → 50% de cobertura
        $nutri6_11Total = $sumMicro(['nutribebe_menor_1']);
        $nutri1aTotal   = $sumMicro(['nutribebe_1anio']);

        // 6-11 meses: meta = mitad del grupo menor_1 (aprox. los que ya tienen ≥6m)
        $metaNutri6_11 = (int) round($ineM1 / 2);
        $programas[] = self::buildFilaCobertura(
            'Nutribebé 6-11 meses (prom. mensual)',
            $metaNutri6_11, 0,
            $nMeses > 0 ? (int) round($nutri6_11Total / $nMeses) : 0
        );
        // 1 año: meta = 1_anio
        $programas[] = self::buildFilaCobertura(
            'Nutribebé 1 año (prom. mensual)',
            $ine1a, 0,
            $nMeses > 0 ? (int) round($nutri1aTotal / $nMeses) : 0
        );

        // ── CARMELO >60 años (mensual) ───────────────────────────────────────
        // Meta = mayor_60 del INE (cuántos abuelos debo atender cada mes)
        // Atendidos = promedio mensual entregado en el período
        // Nota: puede haber más abuelos que la meta INE (migración de retorno)
        $carmeloTotal = $sumMicro(['carmelo_mayor_60']);
        $programas[] = self::buildFilaCobertura(
            'Carmelo >60 años (prom. mensual)',
            $ineM60, $realM60,
            $nMeses > 0 ? (int) round($carmeloTotal / $nMeses) : 0
        );

        return $programas;
    }

    /* ───── Deserción (indicadores CAI) ───── */
    public static function getDesercion(int $centroId, int $anio, array $meses): array
    {
        $sumVacuna = fn (string $tipo) => PrestVacuna::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->where('tipo_vacuna', $tipo)
            ->selectRaw('SUM(dentro_m+dentro_f+fuera_m+fuera_f) as t')->value('t') ?? 0;

        $penta1 = $sumVacuna('Pentavalente_1');
        $penta3 = $sumVacuna('Pentavalente_3');
        $srp1 = $sumVacuna('SRP_1');
        $srp2 = $sumVacuna('SRP_2');

        return [
            [
                'indicador' => 'Pentavalente 1ra → 3ra',
                'primera' => $penta1, 'ultima' => $penta3,
                'tasa' => $penta1 > 0 ? round(($penta1 - $penta3) / $penta1 * 100, 1) : 0,
            ],
            [
                'indicador' => 'SRP 1ra → 2da',
                'primera' => $srp1, 'ultima' => $srp2,
                'tasa' => $srp1 > 0 ? round(($srp1 - $srp2) / $srp1 * 100, 1) : 0,
            ],
        ];
    }

    /* ───── SECCIÓN 5 — Ceros Justificados ───── */
    public static function getCerosJustificados(int $centroId, int $anio, array $meses): array
    {
        return JustificacionCero::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->orderBy('mes')->orderBy('indicador')
            ->get()
            ->map(fn ($j) => [
                'mes' => self::$nombresMeses[$j->mes] ?? $j->mes,
                'indicador' => $j->indicador,
                'motivo' => $j->motivo,
                'detalle' => $j->detalle,
            ])->toArray();
    }

    /* ───── SECCIÓN 6 — Observaciones Narrativas ───── */
    public static function getObservaciones(int $centroId, int $anio, array $meses): array
    {
        return ObservacionMensual::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->whereNotNull('texto')->where('texto', '!=', '')
            ->orderBy('mes')
            ->get()
            ->map(fn ($o) => [
                'mes' => self::$nombresMeses[$o->mes] ?? $o->mes,
                'texto' => $o->texto,
            ])->toArray();
    }

    /* ───── Estado de meses cerrados ───── */
    public static function getMesesCerrados(int $centroId, int $anio, array $meses): array
    {
        $cerrados = MesCerrado::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->whereIn('mes', $meses)
            ->pluck('mes')->toArray();

        return collect($meses)->map(fn ($m) => [
            'mes' => self::$nombresMeses[$m] ?? $m,
            'cerrado' => in_array($m, $cerrados),
        ])->toArray();
    }

    /* ───── Datos completos para informe ───── */
    public static function getDatosInforme(int $centroId, string $periodo, int $anio): array
    {
        $meses = self::getMeses($periodo);

        return [
            'encabezado' => self::getEncabezado($centroId, $periodo, $anio),
            'migracion' => self::getMigracion($centroId),
            'censo' => self::getCenso($centroId, $anio),
            'prestaciones' => self::getPrestaciones($centroId, $anio, $meses),
            'cobertura' => self::getCobertura($centroId, $anio, $periodo),
            'desercion' => self::getDesercion($centroId, $anio, $meses),
            'ceros_justificados' => self::getCerosJustificados($centroId, $anio, $meses),
            'observaciones' => self::getObservaciones($centroId, $anio, $meses),
            'meses_cerrados' => self::getMesesCerrados($centroId, $anio, $meses),
        ];
    }

    /* ───── Helper privado ───── */
    private static function buildFilaCobertura(string $nombre, int $meta, int $real, int $atendidos): array
    {
        return [
            'nombre' => $nombre,
            'meta' => $meta,
            'real' => $real,
            'atendidos' => $atendidos,
            'cob_ine' => $meta > 0 ? round($atendidos / $meta * 100, 1) : 0,
            'cob_real' => $real > 0 ? round($atendidos / $real * 100, 1) : 0,
        ];
    }
}
