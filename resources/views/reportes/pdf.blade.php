<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 110px 25px 60px 25px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 12px; color: #222; }
        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #f0f0f0;
            padding: 10px 0;
        }
        .logo { display:flex; align-items:center; gap:12px; }
        .logo img { height:60px; }
        .title { text-align:center; font-size:18px; font-weight:700; color:#111; }
        .meta { text-align:right; font-size:11px; color:#555; }

        .filters {
            margin-top: 6px;
            font-size: 11px;
            color: #333;
        }
        .summary {
            margin: 12px 0;
            padding: 10px;
            border-left: 5px solid #f1c40f; /* amarillo suave acorde a logo */
            background: #fafafa;
            font-weight:600;
        }

        table { width:100%; border-collapse:collapse; margin-top:8px; }
        th, td { padding:8px 6px; border:1px solid #e6e6e6; text-align:left; font-size:11px; }
        thead th { background:#101010; color:#fff; font-weight:700; }
        tbody tr:nth-child(even) { background: #fff; }
        .text-right { text-align:right; }

        .totales {
            margin-top:10px;
            display:flex;
            justify-content:flex-end;
            gap:20px;
            font-weight:700;
        }

        footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            text-align: right;
            font-size: 10px;
            color:#666;
            border-top: 1px solid #eaeaea;
            padding: 6px 10px;
        }

        /* page numbering for dompdf */
        .page:after {
            content: counter(page) " / " counter(pages);
        }

    </style>
</head>
<body>
<header>
    <div class="logo">
        @if(file_exists($logo))
            <img src="{{ $logo }}" alt="logo">
        @endif
    </div>

    <div class="title">{{ $titulo }}</div>

    <div class="meta">
        <div>Generado: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
        <div>Registros: {{ $ventas->count() }}</div>
    </div>
</header>

<main>
    <div style="margin-top:10px;">

        <div class="filters">
            <strong>Filtros aplicados:</strong>
            @foreach($filtros as $f) {{ $f }}@if(!$loop->last) — @endif @endforeach
        </div>

        <div class="summary">
            Total ventas (dinero): ${{ number_format($totalVentas, 0, ',', '.') }}
            &nbsp; | &nbsp;
        </div>
        @if(!empty($productosVendidos))
            <h4 style="margin-top:10px;">Unidades vendidas por producto (solo ventas activas)</h4>

            <table style="margin-bottom:10px;">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="width:15%" class="text-right">Unidades</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productosVendidos as $producto => $cantidad)
                        <tr>
                            <td>{{ $producto }}</td>
                            <td class="text-right">{{ number_format($cantidad, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif


        <table>
            <thead>
                <tr>
                    <th style="width:6%">ID</th>
                    <th style="width:30%">Cliente</th>
                    <th style="width:14%">Fecha</th>
                    <th style="width:12%" class="text-right">Total</th>
                    <th>Método de pago</th>
                    <th style="width:12%">Estado</th>
                    <th style="width:26%">Productos (qty × nombre)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ventas as $v)
                    <tr>
                        <td>{{ $v->id }}</td>
                        <td>{{ $v->cliente ?? '-' }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($v->fecha)->format('d/m/Y H:i') }}
                        </td>
                        <td class="text-right">${{ number_format($v->total, 0, ',', '.') }}</td>
                        <td>{{ ucfirst($v->metodo_pago) }}</td>
                        <td>{{ ucfirst($v->estado) }}</td>
                        <td>
                            @foreach($v->detalles as $d)
                                {{ $d->cantidad }} × {{ $d->producto?->nombre ?? '[producto eliminado]' }}
                                @if(!$loop->last), @endif
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totales">
            <div>Total registros: {{ $ventas->count() }}</div>
            <div>Total (dinero): ${{ number_format($totalVentas, 0, ',', '.') }}</div>
        </div>
    </div>
</main>

<footer>
    Página <span class="page"></span>
</footer>
</body>
</html>
