@extends('admin.layout')

@section('title', 'Jurado Fonda Challenge')
@section('subtitle', $isAdmin ? 'Asignación de jurados' : 'Evaluación')

@section('topbar-actions')
    @if($isAdmin)
        <a class="topbar-action hide-mobile" href="{{ route('admin.fonda-challenge') }}">Fonda Challenge</a>
        <a class="topbar-action hide-mobile" href="{{ route('admin.jurors') }}">Jurados</a>
    @endif
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
                <h1>{{ $isAdmin ? 'Asignar jurados' : 'Fondas a evaluar' }}</h1>
                <p>{{ $campaign->name }}</p>
            </div>
        </div>

        <div class="page-section">
            @if ($registrations->isEmpty())
                <div class="empty">
                    @if($isAdmin)
                        No hay fondas aprobadas todavía para asignar jurado.
                    @else
                        Todavía no tienes fondas asignadas.
                    @endif
                </div>
            @else
                <div class="stack">
                    @foreach ($registrations as $registration)
                        @php
                            $registrationAssignments = $assignments->where('registration_id', $registration->id);
                            $myAssignment = $isAdmin ? null : $registrationAssignments->first();
                            $myEvaluation = $myAssignment ? $evaluations->firstWhere('assignment_id', $myAssignment->id) : null;
                        @endphp
                        <div class="page-card" style="padding:16px;">
                            <strong>{{ $registration->fonda_name }}</strong>
                            <p style="margin:.25rem 0; color:#64748b;">{{ $registration->dish_name }} · {{ $registration->code }}</p>

                            @if($isAdmin)
                                <div class="table-shell" style="margin:.5rem 0;">
                                    @if($registrationAssignments->isEmpty())
                                        <span class="badge badge-gray">Sin jurado asignado</span>
                                    @else
                                        @foreach($registrationAssignments as $assignment)
                                            @php $score = $evaluations->firstWhere('assignment_id', $assignment->id); @endphp
                                            <span class="badge {{ $score ? 'badge-green' : 'badge-yellow' }}">
                                                Jurado #{{ $assignment->user_id }}{{ $score ? ' · '.$score->final_score : ' · pendiente' }}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                                @if($jurors->isNotEmpty())
                                    <form method="POST" action="{{ route('admin.fonda-jury.assign', $registration) }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                                        @csrf
                                        <select name="user_id" required style="min-width:220px; min-height:44px; border-radius:10px; border:1px solid #cbd5e1; padding:0 .6rem;">
                                            <option value="">Selecciona jurado</option>
                                            @foreach($jurors as $juror)
                                                <option value="{{ $juror->id }}">{{ $juror->full_name ?? $juror->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-red" type="submit">Asignar</button>
                                    </form>
                                @else
                                    <p class="small-note">Crea jurados primero en <a href="{{ route('admin.jurors') }}">Jurados</a>.</p>
                                @endif
                            @else
                                @if($myEvaluation)
                                    <p><span class="badge badge-green">Evaluado · {{ $myEvaluation->final_score }}</span></p>
                                @endif
                                <form method="POST" action="{{ route('admin.fonda-jury.evaluate', $myAssignment) }}" class="form-grid" style="grid-template-columns: repeat(5, minmax(0,1fr)); margin-top:.5rem;">
                                    @csrf
                                    <div class="field">
                                        <label>Sabor (1-10)</label>
                                        <input type="number" name="sabor" min="1" max="10" step="0.1" value="{{ $myEvaluation->sabor ?? '' }}" required>
                                    </div>
                                    <div class="field">
                                        <label>Técnica (1-10)</label>
                                        <input type="number" name="tecnica" min="1" max="10" step="0.1" value="{{ $myEvaluation->tecnica ?? '' }}" required>
                                    </div>
                                    <div class="field">
                                        <label>Presentación (1-10)</label>
                                        <input type="number" name="presentacion" min="1" max="10" step="0.1" value="{{ $myEvaluation->presentacion ?? '' }}" required>
                                    </div>
                                    <div class="field">
                                        <label>Originalidad (1-10)</label>
                                        <input type="number" name="originalidad" min="1" max="10" step="0.1" value="{{ $myEvaluation->originalidad ?? '' }}" required>
                                    </div>
                                    <div class="field">
                                        <label>Uso del producto (1-10)</label>
                                        <input type="number" name="uso_producto" min="1" max="10" step="0.1" value="{{ $myEvaluation->uso_producto ?? '' }}" required>
                                    </div>
                                    <div class="field" style="grid-column: 1 / -1;">
                                        <label>Comentario (opcional)</label>
                                        <input type="text" name="commentary" value="{{ $myEvaluation->commentary ?? '' }}" maxlength="2000">
                                    </div>
                                    <div class="responsive-actions" style="align-self:end;">
                                        <button class="btn btn-red" type="submit">{{ $myEvaluation ? 'Actualizar evaluación' : 'Guardar evaluación' }}</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
