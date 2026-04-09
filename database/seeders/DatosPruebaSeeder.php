<?php

namespace Database\Seeders;

use App\Models\CentroSalud;
use App\Models\MetaIne;
use App\Models\Municipio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatosPruebaSeeder extends Seeder
{
    /**
     * Centros de salud de la Red VII Capinota (datos del PDF CAI 2025).
     * Hornoma (id=1) ya existe; los demás se crean.
     */
    private static array $centros = [
        ['nombre' => 'Hospital Capinota',  'codigo_snis' => '300180', 'poblacion_ine' => 12811, 'subsector' => 'Público'],
        ['nombre' => 'C.S. Irpa Irpa',    'codigo_snis' => '300181', 'poblacion_ine' => 4484,  'subsector' => 'Público'],
        ['nombre' => 'C.S. Charamoco',     'codigo_snis' => '300182', 'poblacion_ine' => 1068,  'subsector' => 'Público'],
        ['nombre' => 'C.S. Apillapampa',   'codigo_snis' => '300184', 'poblacion_ine' => 854,   'subsector' => 'Público'],
        ['nombre' => 'C.S. Mollini',       'codigo_snis' => '300185', 'poblacion_ine' => 641,   'subsector' => 'Público'],
        ['nombre' => 'C.I.S. Coboce',      'codigo_snis' => '300186', 'poblacion_ine' => 854,   'subsector' => 'Seguro Social'],
    ];

    // Porcentajes de cada grupo /sobre/ población total (derivados de Hornoma 641)
    private static array $metaPct = [
        'menor_1' => 0.0187, '1_anio' => 0.0187,
        '2_anios' => 0.0203, '3_anios' => 0.0203, '4_anios' => 0.0203,
        '1_4' => 0.0780, 'menor_5' => 0.0967,
        'mayor_5' => 0.9016, '5_9' => 0.1045, '10_14' => 0.1030,
        '15_19' => 0.1030, '20_39' => 0.2793, '40_49' => 0.0983,
        '50_59' => 0.0764, 'mayor_60' => 0.1373,
    ];

    // Efectividad de cobertura por centro (aprox. del PDF 2025)
    private static array $coberturaFactor = [
        'Hospital Capinota' => 0.55,
        'C.S. Irpa Irpa' => 0.50,
        'C.S. Charamoco' => 0.85,
        'C.S. Apillapampa' => 0.15,
        'C.S.A. HORNOMA' => 0.25,
        'C.S. Mollini' => 0.15,
        'C.I.S. Coboce' => 0.40,
    ];

    private int $anio = 2026;

    private array $meses = [1, 2, 3, 4];

    public function run(): void
    {
        $mun = Municipio::where('nombre', 'Capinota')->first();
        if (! $mun) {
            $this->command->error('Municipio Capinota no encontrado.');

            return;
        }

        // 1. Crear centros faltantes
        foreach (self::$centros as $c) {
            CentroSalud::firstOrCreate(
                ['codigo_snis' => $c['codigo_snis']],
                [
                    'municipio_id' => $mun->id,
                    'nombre' => $c['nombre'],
                    'subsector' => $c['subsector'],
                    'red_salud' => 'Capinota',
                    'poblacion_ine' => $c['poblacion_ine'],
                    'activo' => true,
                ]
            );
        }

        // 2. Obtener todos los centros del municipio
        $centros = CentroSalud::where('municipio_id', $mun->id)->get();

        foreach ($centros as $cs) {
            $this->command->info("Seeding datos para: {$cs->nombre} (pop. {$cs->poblacion_ine})");
            $this->seedMetaIne($cs);
            $this->seedPrestaciones($cs);
        }

        $total = DB::table('prest_vacunas')->where('anio', $this->anio)->whereIn('mes', $this->meses)->count();
        $this->command->info("Total registros vacunas: {$total}");
    }

    // ─────────── Meta INE ───────────

    private function seedMetaIne(CentroSalud $cs): void
    {
        MetaIne::where('centro_salud_id', $cs->id)->where('anio', $this->anio)->delete();

        $pop = $cs->poblacion_ine;
        $rows = [];

        // Grupos generales
        foreach (self::$metaPct as $grupo => $pct) {
            $rows[] = ['grupo_etareo' => $grupo, 'sexo' => 'ambos', 'cantidad' => max(1, round($pop * $pct))];
        }

        // Grupos especiales
        $rows[] = ['grupo_etareo' => 'embarazos_esperados', 'sexo' => 'F', 'cantidad' => max(1, round($pop * 0.0234))];
        $rows[] = ['grupo_etareo' => 'partos_esperados', 'sexo' => 'F', 'cantidad' => max(1, round($pop * 0.0187))];
        $rows[] = ['grupo_etareo' => 'nacimientos_esperados', 'sexo' => 'ambos', 'cantidad' => max(1, round($pop * 0.0187))];
        $rows[] = ['grupo_etareo' => 'adolescentes_10_19', 'sexo' => 'ambos', 'cantidad' => max(1, round($pop * 0.2060))];
        $rows[] = ['grupo_etareo' => 'mujeres_menor_20', 'sexo' => 'F', 'cantidad' => max(1, round($pop * 0.1997))];
        $rows[] = ['grupo_etareo' => '7_49', 'sexo' => 'M', 'cantidad' => max(1, round($pop * 0.326))];
        $rows[] = ['grupo_etareo' => '7_49', 'sexo' => 'F', 'cantidad' => max(1, round($pop * 0.321))];
        $rows[] = ['grupo_etareo' => 'mef_15_40', 'sexo' => 'F', 'cantidad' => max(1, round($pop * 0.2387))];
        $rows[] = ['grupo_etareo' => 'dt_7_49', 'sexo' => 'M', 'cantidad' => max(1, round($pop * 0.0156))];
        $rows[] = ['grupo_etareo' => 'dt_7_49', 'sexo' => 'F', 'cantidad' => max(1, round($pop * 0.0156))];

        foreach ($rows as $r) {
            MetaIne::create(array_merge($r, [
                'centro_salud_id' => $cs->id,
                'anio' => $this->anio,
            ]));
        }
    }

    // ─────────── Prestaciones ───────────

    private function seedPrestaciones(CentroSalud $cs): void
    {
        $cob = self::$coberturaFactor[$cs->nombre] ?? 0.30;
        $pop = $cs->poblacion_ine;
        $metaMenor1 = max(1, round($pop * 0.0187)); // ≈ meta < 1 año
        $meta1anio = $metaMenor1;
        $metaMef = max(1, round($pop * 0.2387));
        $metaEmb = max(1, round($pop * 0.0234));
        $metaPartos = max(1, round($pop * 0.0187));

        foreach ($this->meses as $mes) {
            $base = ['centro_salud_id' => $cs->id, 'anio' => $this->anio, 'mes' => $mes];

            // ── Vacunas ──
            $this->seedVacunas($base, $metaMenor1, $meta1anio, $cob);

            // ── Consulta externa ──
            $this->seedConsultas($base, $pop, $cob);

            // ── Micronutrientes ──
            $this->seedMicronutrientes($base, $metaMenor1, $cob);

            // ── Prenatales ──
            $this->seedPrenatales($base, $metaEmb, $cob);

            // ── Partos ──
            $this->seedPartos($base, $metaPartos, $cob, $cs->nombre);

            // ── Puerperio ──
            $this->seedPuerperio($base, $metaPartos, $cob);

            // ── Recién nacidos ──
            $this->seedRecienNacidos($base, $metaPartos, $cob);

            // ── Crecimiento ──
            $this->seedCrecimiento($base, $metaMenor1, $cob);

            // ── Anticoncepción ──
            $this->seedAnticoncepcion($base, $metaMef, $cob);

            // ── Actividades comunidad ──
            $this->seedActividades($base, $cob);

            // ── Referencias ──
            $this->seedReferencias($base, $pop, $cob);

            // ── Odontología ──
            $this->seedOdontologia($base, $pop, $cob);

            // ── Enfermería ──
            $this->seedEnfermeria($base, $pop, $cob);

            // ── Internaciones ──
            $this->seedInternaciones($base, $pop, $cob, $cs->nombre);

            // ── Cáncer ──
            $this->seedCancer($base, $metaMef, $cob);
        }
    }

    private function r(float $base): int
    {
        return max(0, (int) round($base * (0.8 + mt_rand(0, 40) / 100)));
    }

    private function seedVacunas(array $b, int $metaM1, int $meta1, float $cob): void
    {
        $vacunas = [
            // tipo_vacuna, grupo_etareo, factor sobre meta
            ['BCG', 'menor_1', 0.8],
            ['Pentavalente_1', 'menor_1', 1.0], ['Pentavalente_2', 'menor_1', 0.95], ['Pentavalente_3', 'menor_1', 0.90],
            ['IPV_1', 'menor_1', 1.0], ['bOPV_2', 'menor_1', 0.95], ['bOPV_3', 'menor_1', 0.90],
            ['Antirotavirica_1', 'menor_1', 1.0], ['Antirotavirica_2', 'menor_1', 0.95],
            ['Antineumococica_1', 'menor_1', 1.0], ['Antineumococica_2', 'menor_1', 0.95], ['Antineumococica_3', 'menor_1', 0.90],
            ['Influenza_1', 'menor_1', 0.5], ['Influenza_2', 'menor_1', 0.3],
            ['SRP_1', '1_anio', 1.0], ['SRP_2', '1_anio', 0.95],
            ['Antiamarilica', '1_anio', 0.90],
            ['dT_1', '5_9', 0.6], ['dT_2', '5_9', 0.55], ['dT_3', '5_9', 0.50], ['dT_4', '5_9', 0.45], ['dT_5', '5_9', 0.40],
            ['VPH_1', '10_14', 0.7], ['VPH_2', '10_14', 0.65], ['SR', '10_14', 0.60],
        ];

        foreach ($vacunas as [$tipo, $grupo, $factor]) {
            $meta = in_array($grupo, ['menor_1']) ? $metaM1 : (in_array($grupo, ['1_anio']) ? $meta1 : max(1, round($metaM1 * 2)));
            $total = max(0, round($meta * $cob * $factor / count($this->meses)));
            $dm = (int) ceil($total * 0.55);
            $df = $total - $dm;
            $fuera_total = $this->r($total * 0.1);
            $fm = (int) ceil($fuera_total * 0.5);
            $ff = $fuera_total - $fm;

            DB::table('prest_vacunas')->insert(array_merge($b, [
                'tipo_vacuna' => $tipo,
                'grupo_etareo' => $grupo,
                'dentro_m' => $dm, 'dentro_f' => $df,
                'fuera_m' => $fm, 'fuera_f' => $ff,
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    private function seedConsultas(array $b, int $pop, float $cob): void
    {
        $grupos = ['menor_6m', '6m_menor_1', '1_4', '5_9', '10_14', '15_19', '20_39', '40_49', '50_59', 'mayor_60'];
        foreach ($grupos as $g) {
            $base = max(1, round($pop * 0.01 * $cob));
            DB::table('prest_consulta_externa')->insert(array_merge($b, [
                'grupo_etareo' => $g,
                'primera_m' => $this->r($base), 'primera_f' => $this->r($base),
                'nueva_m' => $this->r($base * 0.3), 'nueva_f' => $this->r($base * 0.3),
                'repetida_m' => $this->r($base * 0.5), 'repetida_f' => $this->r($base * 0.5),
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    private function seedMicronutrientes(array $b, int $metaM1, float $cob): void
    {
        $tipos = [
            'hierro_menor_6m' => 0.5, 'hierro_menor_1' => 1.0, 'hierro_1anio' => 0.9, 'hierro_2_5' => 0.8,
            'hierro_embarazadas_completo' => 0.7, 'hierro_puerperas_completo' => 0.6,
            'vitA_puerpera_unica' => 0.5, 'vitA_menor_1_unica' => 0.8,
            'vitA_1anio_1ra' => 0.7, 'vitA_1anio_2da' => 0.5,
            'vitA_2_5_1ra' => 0.6, 'vitA_2_5_2da' => 0.4,
            'zinc_menor_1' => 0.5, 'zinc_1anio' => 0.4,
            'nutribebe_menor_1' => 0.5, 'nutribebe_1anio' => 0.4,
            'nutrimama_embarazada' => 0.4, 'nutrimama_lactancia' => 0.3,
            'carmelo_mayor_60' => 0.2, 'chispitas_6_23m' => 0.4,
        ];

        foreach ($tipos as $tipo => $factor) {
            $val = max(0, round($metaM1 * $cob * $factor));
            DB::table('prest_micronutrientes')->insert(array_merge($b, [
                'tipo' => $tipo,
                'cantidad' => $this->r($val),
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    private function seedPrenatales(array $b, int $metaEmb, float $cob): void
    {
        $controles = ['nueva_1er_trim', 'nueva_2do_trim', 'nueva_3er_trim', 'repetida', 'con_4to_control'];
        $grupos = ['15_19', '20_34', '35_49'];
        $factors = [1.0, 0.8, 0.6, 0.4, 0.3];

        foreach ($controles as $ci => $control) {
            foreach ($grupos as $grupo) {
                $base = max(0, round($metaEmb * $cob * $factors[$ci] / (count($grupos) * count($this->meses))));
                DB::table('prest_prenatales')->insert(array_merge($b, [
                    'tipo_control' => $control,
                    'grupo_etareo' => $grupo,
                    'dentro' => $this->r($base),
                    'fuera' => $this->r($base * 0.15),
                    'created_at' => now(), 'updated_at' => now(),
                ]));
            }
        }
    }

    private function seedPartos(array $b, int $metaPartos, float $cob, string $centro): void
    {
        $esHospital = str_contains($centro, 'Hospital');
        $partoInst = $esHospital ? round($metaPartos * $cob / count($this->meses)) : 0;
        $partoDom = $esHospital ? 0 : max(0, round($metaPartos * $cob * 0.1 / count($this->meses)));

        if ($partoInst > 0 || $partoDom > 0) {
            $tipos = $esHospital
                ? [['vaginal', 'servicio', 'personal_calificado'], ['cesarea', 'servicio', 'personal_calificado']]
                : [['vaginal', 'domicilio', 'partera_capacitada']];

            foreach ($tipos as [$tipo, $lugar, $atendido]) {
                $cant = $tipo === 'cesarea' ? max(0, round($partoInst * 0.25)) : ($esHospital ? $partoInst : $partoDom);
                if ($cant > 0) {
                    DB::table('prest_partos')->insert(array_merge($b, [
                        'tipo' => $tipo, 'lugar' => $lugar, 'atendido_por' => $atendido,
                        'grupo_etareo' => '20_34',
                        'cantidad' => $this->r($cant),
                        'created_at' => now(), 'updated_at' => now(),
                    ]));
                }
            }
        }
    }

    private function seedPuerperio(array $b, int $metaPartos, float $cob): void
    {
        $tipos = ['48h' => 0.8, '7dias' => 0.6, '28dias' => 0.4, '42dias' => 0.3];
        foreach ($tipos as $tipo => $factor) {
            $val = max(0, round($metaPartos * $cob * $factor / count($this->meses)));
            if ($val > 0) {
                DB::table('prest_puerperio')->insert(array_merge($b, [
                    'tipo_control' => $tipo,
                    'cantidad' => $this->r($val),
                    'created_at' => now(), 'updated_at' => now(),
                ]));
            }
        }
    }

    private function seedRecienNacidos(array $b, int $metaPartos, float $cob): void
    {
        $inds = [
            'nacidos_vivos_servicio' => 0.8, 'nacidos_vivos_domicilio' => 0.1,
            'nacidos_vivos_4cpn' => 0.4, 'nacidos_vivos_peso_menor_2500' => 0.05,
            'nacidos_muertos' => 0.005, 'rn_lactancia_inmediata' => 0.7,
            'rn_alojamiento_conjunto' => 0.7, 'rn_corte_tardio_cordon' => 0.6,
            'rn_malformacion_congenita' => 0.01, 'rn_control_48h' => 0.5,
        ];
        foreach ($inds as $ind => $factor) {
            $val = max(0, round($metaPartos * $cob * $factor / count($this->meses)));
            DB::table('prest_recien_nacidos')->insert(array_merge($b, [
                'indicador' => $ind,
                'cantidad' => $this->r(max(0, $val)),
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    private function seedCrecimiento(array $b, int $metaM1, float $cob): void
    {
        $grupos = ['menor_1_dentro', 'menor_1_fuera', '1_menor_2_dentro', '1_menor_2_fuera', '2_menor_5_dentro', '2_menor_5_fuera'];
        foreach ($grupos as $g) {
            $base = max(1, round($metaM1 * $cob * 0.7 / count($this->meses)));
            DB::table('prest_crecimiento')->insert(array_merge($b, [
                'grupo_etareo' => $g,
                'nuevos_m' => $this->r($base), 'nuevos_f' => $this->r($base * 0.9),
                'repetidos_m' => $this->r($base * 0.7), 'repetidos_f' => $this->r($base * 0.6),
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    private function seedAnticoncepcion(array $b, int $metaMef, float $cob): void
    {
        $metodos = ['condon_m', 'condon_f', 'pildora', 'inyectable_mensual', 'inyectable_trimestral', 'diu'];
        foreach ($metodos as $met) {
            $base = max(0, round($metaMef * $cob * 0.02 / count($this->meses)));
            foreach (['nueva', 'continua'] as $tipo) {
                $val = $tipo === 'continua' ? $base * 2 : $base;
                if ($val > 0) {
                    DB::table('prest_anticoncepcion')->insert(array_merge($b, [
                        'metodo' => $met, 'tipo_usuaria' => $tipo,
                        'grupo_etareo' => '20_34',
                        'cantidad' => $this->r($val),
                        'created_at' => now(), 'updated_at' => now(),
                    ]));
                }
            }
        }
    }

    private function seedActividades(array $b, float $cob): void
    {
        $acts = [
            'actividades_con_comunidad' => 3, 'cai_establecimiento' => 1, 'comunidades_en_cai' => 2,
            'familias_nuevas_carpetizadas' => 5, 'familias_seguimiento' => 8,
            'visitas_primeras' => 4, 'visitas_segundas' => 3, 'visitas_terceras' => 2,
            'reuniones_autoridades' => 1, 'reuniones_comites_salud' => 1,
            'actividades_educativas_salud' => 2,
            'pcd_atendidas_establecimiento' => 3, 'pcd_atendidas_comunidad' => 2,
        ];
        foreach ($acts as $tipo => $base) {
            DB::table('prest_actividades_comunidad')->insert(array_merge($b, [
                'tipo_actividad' => $tipo,
                'cantidad' => $this->r($base * $cob * 4),
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    private function seedReferencias(array $b, int $pop, float $cob): void
    {
        $tipos = ['ref_recibida_comunidad', 'ref_recibida_establecimiento', 'ref_enviada',
            'contraref_recibida', 'contraref_enviada', 'pcd_atendida_establecimiento', 'pcd_atendida_comunidad'];
        foreach ($tipos as $tipo) {
            $base = max(0, round($pop * 0.001 * $cob));
            DB::table('prest_referencias')->insert(array_merge($b, [
                'tipo' => $tipo,
                'masculino' => $this->r($base), 'femenino' => $this->r($base),
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    private function seedOdontologia(array $b, int $pop, float $cob): void
    {
        $procs = ['examen_integral_bucal', 'profilaxis_detartraje', 'aplicacion_fluor',
            'obturacion_resina', 'obturacion_ionomero', 'obturacion_amalgama',
            'sellado_fisuras', 'pulpotomia', 'exodoncia', 'emergencia',
            'rayos_x', 'endodoncia', 'protesis'];
        $grupos = ['menor_5', '5_9', '15_19'];

        foreach ($procs as $proc) {
            foreach ($grupos as $grupo) {
                $base = max(0, round($pop * 0.002 * $cob));
                DB::table('prest_odontologia')->insert(array_merge($b, [
                    'procedimiento' => $proc,
                    'grupo_etareo' => $grupo,
                    'masculino' => $this->r($base), 'femenino' => $this->r($base),
                    'created_at' => now(), 'updated_at' => now(),
                ]));
            }
        }
    }

    private function seedEnfermeria(array $b, int $pop, float $cob): void
    {
        $tipos = ['sueros_administrados', 'inyecciones_administradas', 'curaciones',
            'nebulizaciones', 'cirugia_menor', 'cirugia_mayor', 'atencion_emergencia'];
        foreach ($tipos as $tipo) {
            $base = max(0, round($pop * 0.003 * $cob));
            DB::table('prest_enfermeria')->insert(array_merge($b, [
                'tipo' => $tipo,
                'cantidad' => $this->r($base),
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    private function seedInternaciones(array $b, int $pop, float $cob, string $centro): void
    {
        $esHospital = str_contains($centro, 'Hospital');
        $factor = $esHospital ? 0.005 : 0.0005;

        $inds = ['egresos', 'fallecidos', 'dias_estancia_egresos', 'dias_cama_disponible', 'dias_cama_ocupada', 'infecciones_intrahospitalarias'];
        $factores = [1.0, 0.02, 3.0, 30.0, 15.0, 0.01];

        foreach ($inds as $i => $ind) {
            DB::table('prest_internaciones')->insert(array_merge($b, [
                'indicador' => $ind,
                'cantidad' => $this->r($pop * $factor * $cob * $factores[$i]),
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    private function seedCancer(array $b, int $metaMef, float $cob): void
    {
        $inds = [
            'pap_nueva' => 0.05, 'pap_repetida' => 0.03,
            'ivaa_nueva' => 0.04, 'ivaa_repetida' => 0.02, 'ivaa_positivo' => 0.005,
            'crioterapia' => 0.002,
            'examen_mama_nueva' => 0.03, 'examen_mama_repetida' => 0.02, 'mamografia_referida' => 0.01,
        ];
        foreach ($inds as $ind => $factor) {
            DB::table('prest_cancer_prevencion')->insert(array_merge($b, [
                'indicador' => $ind,
                'cantidad' => $this->r($metaMef * $cob * $factor / count($this->meses)),
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    // ─────────── Limpiar ───────────

    public static function limpiar(): void
    {
        $anio = 2026;
        $meses = [1, 2, 3, 4];

        $tablas = [
            'prest_vacunas', 'prest_consulta_externa', 'prest_micronutrientes',
            'prest_prenatales', 'prest_partos', 'prest_puerperio', 'prest_recien_nacidos',
            'prest_crecimiento', 'prest_anticoncepcion', 'prest_actividades_comunidad',
            'prest_referencias', 'prest_odontologia', 'prest_enfermeria',
            'prest_internaciones', 'prest_cancer_prevencion',
        ];

        foreach ($tablas as $t) {
            $del = DB::table($t)->where('anio', $anio)->whereIn('mes', $meses)->delete();
            echo "  {$t}: {$del} registros eliminados\n";
        }

        // Eliminar meta_ine para todos los centros del 2026
        $del = MetaIne::where('anio', $anio)->delete();
        echo "  meta_ine: {$del} registros eliminados\n";

        // Eliminar centros creados (todos menos Hornoma que existía)
        $centrosCreados = CentroSalud::whereIn('codigo_snis', ['300180', '300181', '300182', '300184', '300185', '300186'])->get();
        foreach ($centrosCreados as $cs) {
            echo "  Eliminando centro: {$cs->nombre}\n";
            $cs->delete();
        }

        echo "Limpieza completada.\n";
    }
}
