@props(['paginator'])

@if ($paginator->hasPages())
    <div class="pagination">
        <span>
            Menampilkan {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}
            dari {{ $paginator->total() }}
        </span>

        <div class="pagination__links">
            @if ($paginator->onFirstPage())
                <span class="pagination__page">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Sebelumnya</a>
            @endif

            <span class="pagination__page">Halaman {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya &rsaquo;</a>
            @else
                <span class="pagination__page">Berikutnya</span>
            @endif
        </div>
    </div>
@endif
