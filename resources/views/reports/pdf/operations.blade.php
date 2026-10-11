<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:18px 20px}body{font-family:DejaVu Sans,sans-serif;color:#26384d;font-size:8px}h1{margin:0;color:#173f75;font-size:16px}.head{display:flex;justify-content:space-between;border-bottom:3px solid #173f75;padding-bottom:8px;margin-bottom:10px}.doc{text-align:right}.doc strong{display:block;color:#173f75;font-size:11px}table{width:100%;border-collapse:collapse;table-layout:auto}th{padding:5px;background:#173f75;color:#fff;text-align:left;font-size:7px}td{padding:4px;border:1px solid #d4dbe3;vertical-align:top}tbody tr:nth-child(even){background:#f8fafc}.number{text-align:right}.empty{text-align:center;padding:16px;color:#718096}.footer{margin-top:8px;color:#748397;font-size:7px}
</style></head><body>
<div class="head"><div><h1>{{ $title }}</h1><span>{{ $from->format('d/m/Y') }} s.d. {{ $to->format('d/m/Y') }}</span></div><div class="doc"><strong>{{ config('app.name') }}</strong><span>Dicetak {{ now()->format('d/m/Y H:i') }}</span></div></div>
@php
$moneyColumns = match($type) {
    'purchases' => [4,5,6,7,8],
    'shifts' => [4,5,6,7],
    'expenses' => [4],
    'sale_returns' => [5,6,7],
    'purchase_returns' => [5],
    'expiry' => [8],
    default => [],
};
$quantityColumns = match($type) {
    'movements' => [7,8,9],
    'expiry' => [5,6],
    'opnames' => [4,5,6],
    default => [],
};
@endphp
<table><thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead><tbody>@forelse($rows as $row)<tr>@foreach($row as $index=>$value)<td class="{{ in_array($index,$moneyColumns,true)||in_array($index,$quantityColumns,true)?'number':'' }}">@if(in_array($index,$moneyColumns,true) && is_numeric($value)){{ number_format((float)$value,0,',','.') }}@elseif(in_array($index,$quantityColumns,true) && is_numeric($value)){{ \App\Support\Number::quantity((float)$value) }}@else{{ $value??'-' }}@endif</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headings) }}" class="empty">Tidak ada data.</td></tr>@endforelse</tbody></table><div class="footer">Dokumen dibuat otomatis dari laporan operasional sistem.</div></body></html>
