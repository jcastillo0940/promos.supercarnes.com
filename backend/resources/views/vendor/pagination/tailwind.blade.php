@if ($paginator->hasPages())
<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-top:.75rem;">

    <p style="font-size:.82rem;color:#64748b;margin:0;">
        @if ($paginator->firstItem())
            Mostrando <strong>{{ $paginator->firstItem() }}</strong> a <strong>{{ $paginator->lastItem() }}</strong> de <strong>{{ $paginator->total() }}</strong> resultado(s)
        @else
            {{ $paginator->count() }} resultado(s)
        @endif
    </p>

    <div style="display:flex;gap:.3rem;flex-wrap:wrap;">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="btn btn-gray" style="opacity:.45;cursor:not-allowed;" aria-disabled="true">&#8592;</span>
        @else
            <a class="btn btn-gray" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">&#8592;</a>
        @endif

        {{-- Pages --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="btn btn-gray" style="opacity:.45;cursor:default;">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="btn btn-red" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="btn btn-gray" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a class="btn btn-gray" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">&#8594;</a>
        @else
            <span class="btn btn-gray" style="opacity:.45;cursor:not-allowed;" aria-disabled="true">&#8594;</span>
        @endif

    </div>
</nav>
@endif
