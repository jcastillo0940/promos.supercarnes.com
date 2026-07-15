@extends('admin.layout')

@section('title', 'Ranking Fonda Challenge')

@section('content')
<div class="page-card">
    <div class="page-title">
        <div>
            <h1>Ranking Fonda Challenge</h1>
            <p>{{ $campaign->name }}</p>
        </div>
    </div>
    <div class="page-section table-shell">
        <table>
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Fonda</th>
                    <th>Plato</th>
                    <th>Puntaje</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $index => $entry)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $entry['fonda_name'] }}</td>
                        <td>{{ $entry['dish_name'] }}</td>
                        <td>{{ number_format($entry['score'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Aún no hay fondas elegibles para ranking.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
