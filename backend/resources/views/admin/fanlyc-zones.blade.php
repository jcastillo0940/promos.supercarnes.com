@extends('admin.layout')

@section('title', 'Fanlyc — Zonas')
@section('subtitle', 'Sucursales por zona')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.fanlyc') }}">Volver a Fanlyc</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('content')
    @if (session('status'))
        <div class="success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Zonas de Fanlyc</h1>
                <p>Asigna que sucursales pertenecen a cada zona. Una sucursal solo puede estar activa en una zona a la vez.</p>
            </div>
        </div>

        @foreach($zones as $zone)
            <div class="page-section" style="border-top:1px solid #e5e7eb;">
                <p class="sidebar-title">{{ $zone->name }} ({{ $zone->code }})</p>

                @if($zone->branches->isEmpty())
                    <div class="empty">Sin sucursales asignadas todavia.</div>
                @else
                    <div class="table-shell">
                        <table>
                            <thead>
                                <tr><th>Sucursal</th><th>Codigo</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                @foreach($zone->branches as $branch)
                                    <tr>
                                        <td>{{ $branch->name }}</td>
                                        <td>{{ $branch->code }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.fanlyc.zones.unassign', $branch->pivot->id) }}" onsubmit="return confirm('¿Quitar esta sucursal de la zona?');">
                                                @csrf
                                                <button class="btn btn-gray" type="submit">Quitar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.fanlyc.zones.assign') }}" class="responsive-actions" style="margin-top:1rem;align-items:end;">
                    @csrf
                    <input type="hidden" name="fanlyc_zone_id" value="{{ $zone->id }}">
                    <div class="field" style="min-width:260px;">
                        <label>Agregar sucursal</label>
                        <select name="branch_id" required>
                            <option value="">Selecciona...</option>
                            @foreach($availableBranches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-red" type="submit">Asignar a {{ $zone->name }}</button>
                </form>
            </div>
        @endforeach
    </div>
@endsection
