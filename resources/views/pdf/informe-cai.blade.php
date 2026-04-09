<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe CAI — {{ $datos['encabezado']['centro_nombre'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1a1a1a; line-height: 1.4; }
        h1 { font-size: 14px; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 11px; background: #1e40af; color: #fff; padding: 4px 8px; margin: 12px 0 6px; }
        h3 { font-size: 10px; margin: 8px 0 4px; border-bottom: 1px solid #ccc; padding-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ccc; padding: 3px 5px; text-align: center; }
        th { background: #e5e7eb; font-weight: bold; font-size: 8px; }
        td { font-size: 8px; }
        td.left { text-align: left; }
        .header-table { border: none; margin-bottom: 10px; }
        .header-table td { border: none; text-align: left; padding: 1px 4px; font-size: 9px; }
        .header-table td.label { font-weight: bold; width: 130px; }
        .stats-grid { width: 100%; margin-bottom: 8px; }
        .stats-grid td { border: 1px solid #ddd; padding: 6px; text-align: center; width: 25%; }
        .stats-grid .value { font-size: 16px; font-weight: bold; }
        .stats-grid .desc { font-size: 7px; color: #666; }
        .green { color: #059669; }
        .yellow { color: #d97706; }
        .red { color: #dc2626; }
        .text-right { text-align: right; }
        .section-note { font-size: 8px; color: #666; margin-bottom: 6px; }
        .firma { text-align: center; margin-top: 40px; }
        .firma-linea { width: 200px; border-bottom: 2px solid #333; margin: 0 auto 4px; }
        .firma-nombre { font-size: 10px; font-weight: bold; }
        .firma-cargo { font-size: 8px; color: #666; }
        .page-break { page-break-before: always; }
        .obs-block { margin-bottom: 6px; }
        .obs-mes { font-weight: bold; font-size: 9px; }
        .obs-texto { font-size: 8px; color: #333; }
    </style>
</head>
<body>

@php
    $enc = $datos['encabezado'];
    $mig = $datos['migracion'];
    $censo = $datos['censo'];
    $prest = $datos['prestaciones'];
    $cobertura = $datos['cobertura'];
    $desercion = $datos['desercion'];
    $ceros = $datos['ceros_justificados'];
    $observaciones = $datos['observaciones'];
@endphp

{{-- ENCABEZADO --}}
<h1>INFORME CAI — {{ strtoupper($enc['periodo_nombre']) }}</h1>
<table class="header-table">
    <tr>
        <td class="label">Establecimiento:</td>
        <td>{{ $enc['centro_nombre'] }}</td>
        <td class="label">Código SNIS:</td>
        <td>{{ $enc['codigo_snis'] }}</td>
    </tr>
    <tr>
        <td class="label">Red de Salud:</td>
        <td>{{ $enc['red_salud'] }}</td>
        <td class="label">Municipio:</td>
        <td>{{ $enc['municipio'] }}</td>
    </tr>
    <tr>
        <td class="label">Departamento:</td>
        <td>{{ $enc['departamento'] }}</td>
        <td class="label">Subsector:</td>
        <td>{{ $enc['subsector'] }}</td>
    </tr>
    <tr>
        <td class="label">Responsable:</td>
        <td>{{ $enc['responsable'] }}</td>
        <td class="label">Fecha generación:</td>
        <td>{{ $enc['fecha_generacion'] }}</td>
    </tr>
</table>

{{-- SECCIÓN 1 — MIGRACIÓN --}}
<h2>1. Contexto de Migración</h2>
<table class="stats-grid">
    <tr>
        <td>
            <div class="value">{{ number_format($mig['total_padron']) }}</div>
            <div class="desc">Padrón total</div>
        </td>
        <td>
            <div class="value green">{{ number_format($mig['residentes']) }}</div>
            <div class="desc">Residentes activos</div>
        </td>
        <td>
            <div class="value yellow">{{ number_format($mig['migrantes']) }} ({{ $mig['pct_migrantes'] }}%)</div>
            <div class="desc">Migrantes</div>
        </td>
        <td>
            <div class="value">{{ number_format($mig['mef_activas']) }}</div>
            <div class="desc">MEF activas</div>
        </td>
    </tr>
</table>
<p class="section-note">
    MEF migradas: {{ $mig['mef_migradas'] }} ({{ $mig['pct_mef_migradas'] }}% del total MEF)
    · Hombres migrados: {{ $mig['hombres_migrados'] }}
</p>

{{-- SECCIÓN 2 — CENSO --}}
<h2>2. Censo Poblacional</h2>
<h3>Población por Comunidad</h3>
<table>
    <thead>
        <tr>
            <th>Comunidad</th>
            <th>Dist.</th>
            <th>Total</th>
            <th>H</th>
            <th>M</th>
            <th>&lt; 5a</th>
            <th>Migr.</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($censo['comunidades'] as $com)
            <tr>
                <td class="left">{{ $com['nombre'] }}</td>
                <td>{{ $com['distancia_km'] !== null ? $com['distancia_km'] . ' km' : '—' }}</td>
                <td><strong>{{ $com['total'] }}</strong></td>
                <td>{{ $com['hombres'] }}</td>
                <td>{{ $com['mujeres'] }}</td>
                <td>{{ $com['menor_5'] }}</td>
                <td>{{ $com['migrantes'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Pirámide INE vs. Población Real</h3>
<table>
    <thead>
        <tr>
            <th>Grupo</th>
            <th>INE M</th>
            <th>INE F</th>
            <th>Real M</th>
            <th>Real F</th>
            <th>Dif.</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($censo['piramide'] as $g)
            @php $dif = ($g['real_m'] + $g['real_f']) - ($g['ine_m'] + $g['ine_f']); @endphp
            <tr>
                <td class="left">{{ $g['label'] }}</td>
                <td>{{ $g['ine_m'] }}</td>
                <td>{{ $g['ine_f'] }}</td>
                <td>{{ $g['real_m'] }}</td>
                <td>{{ $g['real_f'] }}</td>
                <td class="{{ $dif >= 0 ? 'green' : 'red' }}">{{ $dif >= 0 ? '+' : '' }}{{ $dif }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- SECCIÓN 3 — PRESTACIONES --}}
<div class="page-break"></div>
<h2>3. Prestaciones Acumuladas del Período</h2>

<h3>Vacunas</h3>
<table>
    <thead>
        <tr>
            <th>Vacuna</th>
            <th>Grupo</th>
            <th>D.M</th>
            <th>D.F</th>
            <th>F.M</th>
            <th>F.F</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($prest['vacunas'] as $v)
            <tr>
                <td class="left">{{ $v['tipo_vacuna'] }}</td>
                <td>{{ $v['grupo_etareo'] }}</td>
                <td>{{ $v['dentro_m'] }}</td>
                <td>{{ $v['dentro_f'] }}</td>
                <td>{{ $v['fuera_m'] }}</td>
                <td>{{ $v['fuera_f'] }}</td>
                <td><strong>{{ $v['total'] }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Micronutrientes</h3>
<table>
    <thead>
        <tr><th>Tipo</th><th>Total</th></tr>
    </thead>
    <tbody>
        @foreach ($prest['micronutrientes'] as $tipo => $total)
            <tr>
                <td class="left">{{ str_replace('_', ' ', ucfirst($tipo)) }}</td>
                <td><strong>{{ $total }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Control de Crecimiento</h3>
<table>
    <thead>
        <tr><th>Grupo</th><th>N.M</th><th>N.F</th><th>R.M</th><th>R.F</th></tr>
    </thead>
    <tbody>
        @foreach ($prest['crecimiento'] as $ge => $c)
            <tr>
                <td class="left">{{ str_replace('_', ' ', $ge) }}</td>
                <td>{{ $c['nuevos_m'] ?? 0 }}</td>
                <td>{{ $c['nuevos_f'] ?? 0 }}</td>
                <td>{{ $c['repetidos_m'] ?? 0 }}</td>
                <td>{{ $c['repetidos_f'] ?? 0 }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Recién Nacidos</h3>
<table>
    <thead>
        <tr><th>Indicador</th><th>Total</th></tr>
    </thead>
    <tbody>
        @foreach ($prest['recien_nacidos'] as $ind => $total)
            <tr>
                <td class="left">{{ str_replace('_', ' ', ucfirst($ind)) }}</td>
                <td><strong>{{ $total }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Puerperio</h3>
<table>
    <thead>
        <tr><th>Control</th><th>Total</th></tr>
    </thead>
    <tbody>
        @foreach ($prest['puerperio'] as $tc => $total)
            <tr>
                <td class="left">{{ $tc }}</td>
                <td><strong>{{ $total }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- SECCIÓN 4 — COBERTURA --}}
<div class="page-break"></div>
<h2>4. Cobertura de Programas</h2>
<table>
    <thead>
        <tr>
            <th>Programa</th>
            <th>Meta INE</th>
            <th>Pob. Real</th>
            <th>Atendidos</th>
            <th>Cob. INE %</th>
            <th>Cob. Real %</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($cobertura as $prog)
            <tr>
                <td class="left">{{ $prog['nombre'] }}</td>
                <td>{{ $prog['meta'] }}</td>
                <td>{{ $prog['real'] }}</td>
                <td><strong>{{ $prog['atendidos'] }}</strong></td>
                <td class="{{ $prog['cob_ine'] >= 80 ? 'green' : ($prog['cob_ine'] >= 50 ? 'yellow' : 'red') }}">{{ $prog['cob_ine'] }}%</td>
                <td class="{{ $prog['cob_real'] >= 80 ? 'green' : ($prog['cob_real'] >= 50 ? 'yellow' : 'red') }}">{{ $prog['cob_real'] }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Tasas de Deserción</h3>
<table>
    <thead>
        <tr><th>Indicador</th><th>1ra Dosis</th><th>Última</th><th>Tasa %</th></tr>
    </thead>
    <tbody>
        @foreach ($desercion as $d)
            <tr>
                <td class="left">{{ $d['indicador'] }}</td>
                <td>{{ $d['primera'] }}</td>
                <td>{{ $d['ultima'] }}</td>
                <td class="{{ $d['tasa'] > 10 ? 'red' : ($d['tasa'] > 0 ? 'yellow' : 'green') }}"><strong>{{ $d['tasa'] }}%</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- SECCIÓN 5 — CEROS JUSTIFICADOS --}}
<h2>5. Ceros Justificados del Período</h2>
@if (count($ceros) > 0)
    <table>
        <thead>
            <tr><th>Mes</th><th>Indicador</th><th>Motivo</th><th>Detalle</th></tr>
        </thead>
        <tbody>
            @foreach ($ceros as $j)
                <tr>
                    <td>{{ $j['mes'] }}</td>
                    <td class="left">{{ str_replace('_', ' ', $j['indicador']) }}</td>
                    <td class="left">{{ ucfirst(str_replace('_', ' ', $j['motivo'])) }}</td>
                    <td class="left">{{ $j['detalle'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p class="section-note">No hay ceros justificados en este período.</p>
@endif

{{-- SECCIÓN 6 — OBSERVACIONES --}}
<h2>6. Observaciones Narrativas</h2>
@if (count($observaciones) > 0)
    @foreach ($observaciones as $obs)
        <div class="obs-block">
            <span class="obs-mes">Mes de {{ $obs['mes'] }}:</span>
            <span class="obs-texto">{{ $obs['texto'] }}</span>
        </div>
    @endforeach
@else
    <p class="section-note">No hay observaciones narrativas en este período.</p>
@endif

{{-- SECCIÓN 7 — FIRMA --}}
<div class="firma">
    <div class="firma-linea"></div>
    <div class="firma-nombre">{{ $enc['responsable'] }}</div>
    <div class="firma-cargo">Responsable — {{ $enc['centro_nombre'] }}</div>
    <br>
    <div class="firma-cargo">{{ $enc['municipio'] }}, {{ $enc['fecha_generacion'] }}</div>
</div>

</body>
</html>
