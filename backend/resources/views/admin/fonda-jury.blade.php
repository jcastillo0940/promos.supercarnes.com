@extends('admin.layout')

@section('title', 'Jurados Fonda Challenge')

@section('content')
<div class="page-card">
    <div class="page-title">
        <div>
            <h1>Jurados Fonda Challenge</h1>
            <p>{{ $campaign->name }}</p>
        </div>
    </div>

    <div class="page-section">
        <div class="stack">
            @foreach ($registrations as $registration)
                <div class="page-card" style="padding:16px;">
                    <strong>{{ $registration->fonda_name }}</strong>
                    <p>{{ $registration->dish_name }} · {{ $registration->code }}</p>
                    <form method="POST" action="{{ route('admin.fonda-jury.assign', $registration) }}" style="display:flex; gap:12px; flex-wrap:wrap;">
                        @csrf
                        <input type="number" name="user_id" placeholder="ID jurado" required style="min-width:180px;">
                        <button class="btn btn-red" type="submit">Asignar</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
