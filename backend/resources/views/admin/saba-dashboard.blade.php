@extends('admin.layout')

@section('title', 'Dashboard Saba')
@section('subtitle', 'Emprende tu Sueño')

@php
    $promoTitle    = 'Saba · Emprende tu Sueño';
    $promoKey      = 'saba';
    $dashboardRoute = 'admin.saba.dashboard';
    $listRoute     = 'admin.saba';
@endphp

@include('admin.partials.promo-dashboard-invoice')
