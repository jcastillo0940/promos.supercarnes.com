@extends('admin.layout')

@section('title', 'Dashboard NIVEA')
@section('subtitle', 'Empollerando a Mamá')

@php
    $promoTitle    = 'NIVEA · Empollerando a Mamá';
    $promoKey      = 'nivea';
    $dashboardRoute = 'admin.nivea.dashboard';
    $listRoute     = 'admin.nivea';
@endphp

@include('admin.partials.promo-dashboard-invoice')
