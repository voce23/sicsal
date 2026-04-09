<?php

use App\Livewire\Blog;
use App\Livewire\CausasConsulta;
use App\Livewire\ComunidadesPoblacion;
use App\Livewire\PostView;
use App\Livewire\InformeCAI;
use App\Livewire\Poblacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Rutas públicas — sin autenticación
Route::get('/', function () {
    $anio = (int) date('Y');
    $centrosIds = [38, 39, 40, 41, 42, 1];   // orden de presentación

    // ── Población total del municipio (sin superposición: menor_5 + mayor_5) ──
    $poblacionTotal = DB::table('metas_ine')
        ->whereIn('centro_salud_id', $centrosIds)
        ->where('anio', $anio)
        ->whereIn('grupo_etareo', ['menor_5', 'mayor_5'])
        ->where('sexo', 'ambos')
        ->sum('cantidad');

    // ── Config de grupos para la pirámide ──
    $gruposConfig = [
        ['label' => '<1 año', 'ine' => ['menor_1'],                              'min' => -1,  'max' => 0],
        ['label' => '1-4',   'ine' => ['1_anio','2_anios','3_anios','4_anios'], 'min' => 1,   'max' => 4],
        ['label' => '5-9',   'ine' => ['5_9'],                                  'min' => 5,   'max' => 9],
        ['label' => '10-14', 'ine' => ['10_14'],                                'min' => 10,  'max' => 14],
        ['label' => '15-19', 'ine' => ['15_19'],                                'min' => 15,  'max' => 19],
        ['label' => '20-39', 'ine' => ['20_39'],                                'min' => 20,  'max' => 39],
        ['label' => '40-49', 'ine' => ['40_49'],                                'min' => 40,  'max' => 49],
        ['label' => '50-59', 'ine' => ['50_59'],                                'min' => 50,  'max' => 59],
        ['label' => '60+',   'ine' => ['mayor_60'],                             'min' => 60,  'max' => 150],
    ];
    $labels = array_column($gruposConfig, 'label');

    // ── Datos INE en bulk (1 sola query) ──
    $todasMetas = DB::table('metas_ine')
        ->whereIn('centro_salud_id', $centrosIds)
        ->where('anio', $anio)
        ->where('sexo', 'ambos')
        ->get()
        ->groupBy('centro_salud_id');

    // ── Personas reales en bulk (1 sola query) — solo centros con padrón ──
    $todasPersonas = DB::table('personas')
        ->whereIn('centro_salud_id', $centrosIds)
        ->where('activo', true)
        ->whereNotNull('fecha_nacimiento')
        ->select('centro_salud_id', 'sexo', DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) AS edad'))
        ->get()
        ->groupBy('centro_salud_id');

    // ── Centros ordenados (con coordenadas para el mapa) ──
    $centrosDb = DB::table('centros_salud')
        ->whereIn('id', $centrosIds)
        ->get()
        ->keyBy('id');

    // ── Construir pirámides ──
    $piramides = [];
    foreach ($centrosIds as $cid) {
        $metasCentro = $todasMetas->get($cid, collect())->keyBy('grupo_etareo');

        $ineM = []; $ineF = [];
        foreach ($gruposConfig as $g) {
            $total = 0;
            foreach ($g['ine'] as $ge) {
                $total += $metasCentro->get($ge)?->cantidad ?? 0;
            }
            $ineM[] = (int) ceil($total / 2);
            $ineF[] = (int) floor($total / 2);
        }

        $personasCentro = $todasPersonas->get($cid, collect());
        $tieneReal      = $personasCentro->isNotEmpty();

        $realM = []; $realF = [];
        if ($tieneReal) {
            foreach ($gruposConfig as $g) {
                $realM[] = $personasCentro->filter(fn($p) =>
                    $p->sexo === 'M' && (
                        $g['min'] === -1
                            ? $p->edad < 1
                            : ($p->edad >= $g['min'] && $p->edad <= $g['max'])
                    )
                )->count();
                $realF[] = $personasCentro->filter(fn($p) =>
                    $p->sexo === 'F' && (
                        $g['min'] === -1
                            ? $p->edad < 1
                            : ($p->edad >= $g['min'] && $p->edad <= $g['max'])
                    )
                )->count();
            }
        }

        $centro = $centrosDb->get($cid);
        $piramides[] = [
            'id'        => $cid,
            'nombre'    => $centro?->nombre ?? "Centro $cid",
            'totalIne'  => array_sum($ineM) + array_sum($ineF),
            'labels'    => $labels,
            'ineM'      => $ineM,
            'ineF'      => $ineF,
            'tieneReal' => $tieneReal,
            'realM'     => $realM,
            'realF'     => $realF,
        ];
    }

    // ── Centros con coordenadas para el mapa ──
    $centrosMapas = $centrosDb->filter(fn($c) => $c->latitud && $c->longitud)
        ->map(fn($c) => [
            'id'      => $c->id,
            'nombre'  => $c->nombre,
            'snis'    => $c->codigo_snis,
            'lat'     => (float) $c->latitud,
            'lng'     => (float) $c->longitud,
        ])->values();

    return view('public.inicio', compact('poblacionTotal', 'piramides', 'centrosMapas', 'centrosDb'));
})->name('home');

Route::get('/blog', Blog::class)->name('blog');
Route::get('/blog/{slug}', PostView::class)->name('blog.post');

// Rutas protegidas — requieren autenticación y aprobación del admin
Route::middleware(['auth', 'aprobado'])->group(function () {
    Route::redirect('dashboard', '/admin')->name('dashboard');
    Route::get('/comunidades', ComunidadesPoblacion::class)->name('comunidades');
    Route::get('/poblacion', Poblacion::class)->name('poblacion');
    Route::get('/cai', InformeCAI::class)->name('cai');
    Route::get('/causas-consulta', CausasConsulta::class)->name('causas-consulta');
});

// Página de espera tras registro (auth pero sin aprobación)
Route::middleware(['auth'])->get('/pendiente', function () {
    if (auth()->user()->activo) {
        return redirect('/');
    }
    return view('public.pendiente');
})->name('pendiente');

require __DIR__.'/settings.php';
