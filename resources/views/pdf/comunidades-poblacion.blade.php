<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comunidades y Población — {{ $datos['centro']->nombre ?? 'Centro' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5px; color: #1a1a1a; line-height: 1.3; }

        .header { text-align: center; margin-bottom: 8px; border-bottom: 2px solid #1e40af; padding-bottom: 6px; }
        .header .inst { font-size: 9px; font-weight: bold; color: #1e40af; }
        .header .sub { font-size: 8px; color: #374151; }
        .header .title { font-size: 11px; font-weight: bold; margin-top: 4px; text-transform: uppercase; }
        .header .date { font-size: 7px; color: #6b7280; }

        h2 { font-size: 9px; background: #1e40af; color: #fff; padding: 3px 8px; margin: 10px 0 4px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 2px 3px; text-align: center; font-size: 7px; }
        th { font-weight: bold; font-size: 6.5px; }

        .th-blue { background: #dbeafe; color: #1e3a8a; }
        .th-green { background: #dcfce7; color: #166534; }
        .th-amber { background: #fef3c7; color: #92400e; }
        .th-purple { background: #ede9fe; color: #5b21b6; }
        .th-orange { background: #ffedd5; color: #9a3412; }
        .th-emerald { background: #d1fae5; color: #065f46; }

        .td-blue { background: #eff6ff; }
        .td-green { background: #f0fdf4; }
        .td-amber { background: #fffbeb; }
        .td-purple { background: #faf5ff; }
        .td-orange { background: #fff7ed; }
        .td-emerald { background: #ecfdf5; }

        td.left { text-align: left; }
        .bold { font-weight: bold; }
        .green { color: #059669; }
        .amber { color: #d97706; }
        .red { color: #dc2626; }

        .footer { margin-top: 12px; border-top: 1px solid #d1d5db; padding-top: 8px; }
        .footer-grid { width: 100%; }
        .footer-grid td { border: none; text-align: center; padding: 2px; vertical-align: top; }
        .firma-linea { width: 160px; border-bottom: 1px solid #333; margin: 0 auto 2px; }
        .firma-nombre { font-size: 8px; font-weight: bold; }
        .firma-cargo { font-size: 7px; color: #6b7280; }

        .page-break { page-break-before: always; }
        .zebra-even { background: #f9fafb; }

        .total-row td { font-weight: bold; background: #f3f4f6; border-top: 2px solid #374151; }
    </style>
</head>
<body>

@php
    $centro = $datos['centro'];
    $grupos = $datos['grupos'];
    $municipioNombre = $centro && $centro->municipio ? $centro->municipio->nombre : 'Capinota';
@endphp

{{-- ═══════════════════════ ENCABEZADO INSTITUCIONAL ═══════════════════════ --}}
<div class="header">
    <div class="inst">Coordinación Red VII {{ $municipioNombre }}</div>
    <div class="sub">Jefatura Municipal de Salud {{ $municipioNombre }}</div>
    <div class="sub">{{ $centro->nombre ?? 'Centro de Salud' }}</div>
    <div class="title">Comunidades y Población — {{ date('Y') }}</div>
    <div class="date">Generado el {{ now()->format('d/m/Y H:i') }}</div>
</div>

{{-- ═══════════════════════ TABLA 1: RESUMEN ═══════════════════════ --}}
<h2>Tabla 1 — Resumen por Comunidad</h2>
<table>
    <thead>
        <tr>
            <th rowspan="2" style="text-align:left;">Comunidad</th>
            <th rowspan="2">Km</th>
            <th class="th-blue" colspan="3">Demografía</th>
            <th class="th-green" colspan="{{ count($grupos) }}">Grupos Etáreos</th>
            <th class="th-amber" rowspan="2">Migr.</th>
        </tr>
        <tr>
            <th class="th-blue">Total</th>
            <th class="th-blue">H</th>
            <th class="th-blue">M</th>
            @foreach ($grupos as $label)
                <th class="th-green">{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($datos['filas'] as $i => $fila)
            <tr class="{{ $i % 2 === 0 ? 'zebra-even' : '' }}">
                <td class="left">{{ $fila['comunidad'] }}</td>
                <td>{{ $fila['km'] }}</td>
                <td class="td-blue bold">{{ $fila['total'] }}</td>
                <td class="td-blue">{{ $fila['hombres'] }}</td>
                <td class="td-blue">{{ $fila['mujeres'] }}</td>
                @foreach ($grupos as $key => $label)
                    <td class="td-green">{{ $fila[$key] }}</td>
                @endforeach
                <td class="td-amber">{{ $fila['migrantes'] }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td class="left" colspan="2">TOTAL REAL</td>
            <td class="td-blue">{{ $datos['totales']['total'] }}</td>
            <td class="td-blue">{{ $datos['totales']['hombres'] }}</td>
            <td class="td-blue">{{ $datos['totales']['mujeres'] }}</td>
            @foreach ($grupos as $key => $label)
                <td class="td-green">{{ $datos['totales'][$key] }}</td>
            @endforeach
            <td class="td-amber">{{ $datos['totales']['migrantes'] }}</td>
        </tr>
        <tr>
            <td class="left bold" colspan="2">META INE</td>
            <td class="bold">{{ number_format($datos['metaIne']) }}</td>
            <td colspan="{{ count($grupos) + 3 }}"></td>
        </tr>
        <tr>
            <td class="left bold" colspan="2">DIFERENCIA</td>
            <td class="bold {{ $datos['diferencia'] < 0 ? 'red' : 'green' }}">
                {{ $datos['diferencia'] >= 0 ? '+' : '' }}{{ number_format($datos['diferencia']) }}
                @if ($datos['metaIne'] > 0)
                    ({{ round($datos['totales']['total'] / $datos['metaIne'] * 100, 1) }}%)
                @endif
            </td>
            <td colspan="{{ count($grupos) + 3 }}"></td>
        </tr>
    </tfoot>
</table>

{{-- ═══════════════════════ TABLA 2: DETALLE POR SEXO ═══════════════════════ --}}
<h2>Tabla 2 — Detalle por Sexo y Grupo Etáreo</h2>
<table>
    <thead>
        <tr>
            <th rowspan="2" style="text-align:left;">Comunidad</th>
            @foreach ($grupos as $label)
                <th class="th-green" colspan="2">{{ $label }}</th>
            @endforeach
            <th class="th-amber" colspan="2">Migr.</th>
            <th class="th-blue" colspan="2">Total</th>
        </tr>
        <tr>
            @for ($j = 0; $j < count($grupos); $j++)
                <th class="th-green">H</th>
                <th class="th-green">M</th>
            @endfor
            <th class="th-amber">H</th>
            <th class="th-amber">M</th>
            <th class="th-blue">H</th>
            <th class="th-blue">M</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($datos['detalle'] as $i => $row)
            <tr class="{{ $i % 2 === 0 ? 'zebra-even' : '' }}">
                <td class="left" style="white-space:nowrap;">{{ $row['comunidad'] }}</td>
                @foreach ($grupos as $key => $label)
                    <td class="td-green">{{ $row['datos'][$key]['M'] }}</td>
                    <td class="td-green">{{ $row['datos'][$key]['F'] }}</td>
                @endforeach
                <td class="td-amber">{{ $row['datos']['migrantes']['M'] }}</td>
                <td class="td-amber">{{ $row['datos']['migrantes']['F'] }}</td>
                <td class="td-blue bold">{{ $row['datos']['total']['M'] }}</td>
                <td class="td-blue bold">{{ $row['datos']['total']['F'] }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        @php $dt = $datos['detalleTotales']; @endphp
        <tr class="total-row">
            <td class="left">TOTAL</td>
            @foreach ($grupos as $key => $label)
                <td class="td-green">{{ $dt[$key]['M'] }}</td>
                <td class="td-green">{{ $dt[$key]['F'] }}</td>
            @endforeach
            <td class="td-amber">{{ $dt['migrantes']['M'] }}</td>
            <td class="td-amber">{{ $dt['migrantes']['F'] }}</td>
            <td class="td-blue">{{ $dt['total']['M'] }}</td>
            <td class="td-blue">{{ $dt['total']['F'] }}</td>
        </tr>
    </tfoot>
</table>

{{-- ═══════════════════════ TABLA 3: INE vs REAL ═══════════════════════ --}}
<h2>Tabla 3 — Consolidado Meta INE vs. Población Real</h2>
<table>
    <thead>
        <tr>
            <th rowspan="2" style="text-align:left;">Grupo Etáreo</th>
            <th class="th-purple" colspan="3">Meta INE</th>
            <th class="th-blue" colspan="3">Población Real</th>
            <th class="th-orange" rowspan="2">Diferencia</th>
            <th class="th-emerald" rowspan="2">Cobertura</th>
        </tr>
        <tr>
            <th class="th-purple">H</th>
            <th class="th-purple">M</th>
            <th class="th-purple">Total</th>
            <th class="th-blue">H</th>
            <th class="th-blue">M</th>
            <th class="th-blue">Total</th>
        </tr>
    </thead>
    <tbody>
        @php $sIM=0;$sIF=0;$sIT=0;$sRM=0;$sRF=0;$sRT=0; @endphp
        @foreach ($datos['consolidado'] as $i => $row)
            @php $sIM+=$row['ine_m'];$sIF+=$row['ine_f'];$sIT+=$row['ine_total'];$sRM+=$row['real_m'];$sRF+=$row['real_f'];$sRT+=$row['real_total']; @endphp
            <tr class="{{ $i % 2 === 0 ? 'zebra-even' : '' }}">
                <td class="left">{{ $row['label'] }}</td>
                <td class="td-purple">{{ $row['ine_m'] }}</td>
                <td class="td-purple">{{ $row['ine_f'] }}</td>
                <td class="td-purple bold">{{ $row['ine_total'] }}</td>
                <td class="td-blue">{{ $row['real_m'] }}</td>
                <td class="td-blue">{{ $row['real_f'] }}</td>
                <td class="td-blue bold">{{ $row['real_total'] }}</td>
                <td class="td-orange bold {{ $row['diferencia'] < 0 ? 'red' : 'green' }}">
                    {{ $row['diferencia'] >= 0 ? '+' : '' }}{{ $row['diferencia'] }}
                </td>
                <td class="td-emerald bold {{ $row['cobertura'] >= 100 ? 'green' : ($row['cobertura'] >= 80 ? 'amber' : 'red') }}">
                    {{ $row['cobertura'] }}%
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        @php
            $difT = $sRT - $sIT;
            $cobT = $sIT > 0 ? round($sRT / $sIT * 100, 1) : 0;
        @endphp
        <tr class="total-row">
            <td class="left">TOTAL</td>
            <td class="td-purple">{{ $sIM }}</td>
            <td class="td-purple">{{ $sIF }}</td>
            <td class="td-purple">{{ $sIT }}</td>
            <td class="td-blue">{{ $sRM }}</td>
            <td class="td-blue">{{ $sRF }}</td>
            <td class="td-blue">{{ $sRT }}</td>
            <td class="td-orange {{ $difT < 0 ? 'red' : 'green' }}">{{ $difT >= 0 ? '+' : '' }}{{ $difT }}</td>
            <td class="td-emerald {{ $cobT >= 100 ? 'green' : 'amber' }}">{{ $cobT }}%</td>
        </tr>
    </tfoot>
</table>

{{-- ═══════════════════════ FIRMAS ═══════════════════════ --}}
<div class="footer">
    <table class="footer-grid">
        <tr>
            <td style="width:50%;">
                <div class="firma-linea"></div>
                <div class="firma-nombre">Dr. Eusebio Panozo F.</div>
                <div class="firma-cargo">Médico Responsable</div>
            </td>
            <td style="width:50%;">
                <div class="firma-linea"></div>
                <div class="firma-nombre">Aux. Enf. Alejandrina Fuentes M.</div>
                <div class="firma-cargo">Auxiliar de Enfermería</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
