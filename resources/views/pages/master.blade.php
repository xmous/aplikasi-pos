@extends('layouts.pos')

@php
    $masterTitles = [
        'categories' => 'Kategori',
        'units' => 'Satuan',
        'suppliers' => 'Supplier',
        'customers' => 'Pelanggan & Member',
        'warehouses' => 'Gudang',
    ];
@endphp

@section('title', $masterTitles[$type] ?? 'Master Data')
@section('body_class', 'pos-master-page')
@section('content')
    <livewire:master-data-manager :type="$type" />
@endsection
