{{-- Status filter tabs with counts. $cases (enum cases), $counts, $current, $route, $extra (other query params), $all (label for "all") --}}
<nav class="tag-row" aria-label="Filter by status">
    <a href="{{ route($route, array_filter($extra ?? [])) }}" class="tag" @if(! $current) aria-current="true" @endif>{{ $all ?? 'All' }} <span class="count">{{ $counts->sum() }}</span></a>
    @foreach ($cases as $case)
        <a href="{{ route($route, array_filter(($extra ?? []) + ['status' => $case->value])) }}" class="tag" @if($current === $case->value) aria-current="true" @endif>{{ $case->label() }} <span class="count">{{ $counts[$case->value] ?? 0 }}</span></a>
    @endforeach
</nav>
