@extends('admin.layout')

@section('content')
<div class="admin-page">
    <h1>Fonda Challenge</h1>
    <p>Revisión y operación del módulo.</p>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fonda</th>
                    <th>Responsable</th>
                    <th>Cédula</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registrations as $registration)
                    <tr>
                        <td>{{ $registration->code }}</td>
                        <td>{{ $registration->fonda_name }}</td>
                        <td>{{ $registration->full_name }}</td>
                        <td>{{ $registration->cedula }}</td>
                        <td>{{ $registration->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Aún no hay inscripciones.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
