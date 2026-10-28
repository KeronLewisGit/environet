@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Pagination">
        <p class="text-sm text-ink-3 num">Showing {{ number_format($paginator->firstItem()) }} to {{ number_format($paginator->lastItem()) }} of {{ number_format($paginator->total()) }}</p>
        <div class="flex gap-2">
            @if ($paginator->onFirstPage())
                <span class="a-btn a-btn-ghost opacity-50" aria-disabled="true"><x-icon name="chevron-left" class="h-4 w-4" />Previous</span>
            @else
                <a class="a-btn a-btn-ghost" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-icon name="chevron-left" class="h-4 w-4" />Previous</a>
            @endif
            @if ($paginator->hasMorePages())
                <a class="a-btn a-btn-ghost" href="{{ $paginator->nextPageUrl() }}" rel="next">Next<x-icon name="chevron-right" class="h-4 w-4" /></a>
            @else
                <span class="a-btn a-btn-ghost opacity-50" aria-disabled="true">Next<x-icon name="chevron-right" class="h-4 w-4" /></span>
            @endif
        </div>
    </nav>
@endif
