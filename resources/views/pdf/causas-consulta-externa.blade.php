<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; color: #212121; }

    .header {
        background: #004D40;
        color: white;
        padding: 8px 12px;
        margin-bottom: 6px;
    }
    .header h1 { font-size: 13pt; font-weight: bold; margin-bottom: 2px; }
    .header p  { font-size: 8pt; color: #B2DFDB; }

    .info-bar {
        background: #E0F2F1;
        color: #004D40;
        font-size: 8pt;
        padding: 4px 12px;
        margin-bottom: 10px;
        border-left: 4px solid #00897B;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    thead tr.head-grupo {
        background: #00897B;
        color: white;
        font-size: 7pt;
        font-weight: bold;
        text-align: center;
    }
    thead tr.head-sub {
        background: #00897B;
        color: white;
        font-size: 7pt;
        font-weight: bold;
        text-align: center;
    }

    th, td { border: 1px solid #B0BEC5; padding: 3px 4px; }
    td.diag { text-align: left; }
    td.num  { text-align: center; }

    tr.par  td { background: #ffffff; }
    tr.impar td { background: #f5f5f5; }
    tr.total-row td {
        background: #004D40;
        color: white;
        font-weight: bold;
        text-align: center;
        font-size: 8pt;
    }
    tr.total-row td.diag { text-align: left; }

    .rank { width: 18px; text-align: center; font-weight: bold; color: #00897B; }
    .pct  { font-weight: bold; }
    .total-col { font-weight: bold; background: #E0F2F1 !important; color: #004D40; }

    .footer {
        font-size: 7pt;
        color: #757575;
        text-align: right;
        margin-top: 8px;
        border-top: 1px solid #ECEFF1;
        padding-top: 4px;
    }
</style>
</head>
<body>

<div class="header">
    <h1>10 Principales Causas de Consulta Externa</h1>
    <p>{{ $datos['centro'] }}  ·  {{ $datos['periodo_label'] }}</p>
</div>

<div class="info-bar">
    Total de consultas registradas en el período: <strong>{{ number_format($datos['grand_total']) }}</strong>
    &nbsp;|&nbsp; Masculino: <strong>{{ number_format($datos['grand_total_m']) }}</strong>
    &nbsp;|&nbsp; Femenino: <strong>{{ number_format($datos['grand_total_f']) }}</strong>
</div>

@php
$grupos = [
    'menor_6m'   => '<6m',
    '6m_menor_1' => '6m-1a',
    '1_4'        => '1-4a',
    '5_9'        => '5-9a',
    '10_14'      => '10-14a',
    '15_19'      => '15-19a',
    '20_39'      => '20-39a',
    '40_49'      => '40-49a',
    '50_59'      => '50-59a',
    'mayor_60'   => '≥60a',
];
$grupoCols = array_keys($grupos);
@endphp

<table>
    <thead>
        <tr class="head-grupo">
            <th rowspan="2" style="width:18px;">N°</th>
            <th rowspan="2" style="text-align:left; min-width:120px;">Diagnóstico</th>
            @foreach($grupos as $g => $label)
                <th colspan="2" style="font-size:6.5pt;">{{ $label }}</th>
            @endforeach
            <th rowspan="2" style="width:28px;">Tot. M</th>
            <th rowspan="2" style="width:28px;">Tot. F</th>
            <th rowspan="2" style="width:30px;">Total</th>
            <th rowspan="2" style="width:26px;">%</th>
        </tr>
        <tr class="head-sub">
            @foreach($grupos as $g => $label)
                <th style="width:16px; font-size:6pt;">M</th>
                <th style="width:16px; font-size:6pt;">F</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($datos['causas'] as $i => $causa)
            <tr class="{{ $i % 2 === 0 ? 'par' : 'impar' }}">
                <td class="num rank">{{ $causa['rank'] }}</td>
                <td class="diag">{{ $causa['diagnostico'] }}</td>
                @foreach($grupoCols as $g)
                    <td class="num" style="font-size:6.5pt;">{{ $causa['grupos'][$g]['m'] ?? 0 }}</td>
                    <td class="num" style="font-size:6.5pt;">{{ $causa['grupos'][$g]['f'] ?? 0 }}</td>
                @endforeach
                <td class="num">{{ $causa['total_m'] }}</td>
                <td class="num">{{ $causa['total_f'] }}</td>
                <td class="num total-col">{{ $causa['total'] }}</td>
                <td class="num pct">{{ $causa['porcentaje'] }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="25" style="text-align:center; padding:12px; color:#757575;">
                    Sin datos registrados para el período seleccionado.
                </td>
            </tr>
        @endforelse

        {{-- Fila vacías hasta completar 10 --}}
        @for($i = count($datos['causas']); $i < 10; $i++)
            <tr class="{{ $i % 2 === 0 ? 'par' : 'impar' }}">
                <td class="num rank" style="color:#B0BEC5;">{{ $i + 1 }}</td>
                <td class="diag" style="color:#B0BEC5;">—</td>
                @foreach($grupoCols as $g)
                    <td></td><td></td>
                @endforeach
                <td></td><td></td><td></td><td></td>
            </tr>
        @endfor

        {{-- Fila TOTAL --}}
        <tr class="total-row">
            <td></td>
            <td class="diag">TOTAL</td>
            @foreach($grupoCols as $g)
                <td>{{ array_sum(array_map(fn($c) => $c['grupos'][$g]['m'] ?? 0, $datos['causas'])) }}</td>
                <td>{{ array_sum(array_map(fn($c) => $c['grupos'][$g]['f'] ?? 0, $datos['causas'])) }}</td>
            @endforeach
            <td>{{ $datos['grand_total_m'] }}</td>
            <td>{{ $datos['grand_total_f'] }}</td>
            <td>{{ $datos['grand_total'] }}</td>
            <td>100%</td>
        </tr>
    </tbody>
</table>

<div class="footer">
    Generado el {{ now()->format('d/m/Y H:i') }} · SIMUES — Sistema de Información Municipal de Establecimientos de Salud
</div>

</body>
</html>
