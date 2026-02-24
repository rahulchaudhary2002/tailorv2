@if ($paginator->hasPages())
    @php
        $isLengthAware = method_exists($paginator, 'lastPage');
        $currentPage = (int) $paginator->currentPage();
        $lastPage = $isLengthAware ? (int) $paginator->lastPage() : $currentPage;
        $leadingLimit = 3;
    @endphp

    <nav class="custom-pagination" role="navigation" aria-label="Pagination Navigation">
        <ul class="custom-pagination__list">
            @if ($paginator->onFirstPage())
                <li class="custom-pagination__item">
                    <span class="custom-pagination__disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">&lsaquo;</span>
                </li>
            @else
                <li class="custom-pagination__item">
                    <a class="custom-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            @if ($isLengthAware)
                @for ($page = 1; $page <= min($leadingLimit, $lastPage); $page++)
                    @if ($page === $currentPage)
                        <li class="custom-pagination__item">
                            <span class="custom-pagination__current" aria-current="page">{{ $page }}</span>
                        </li>
                    @else
                        <li class="custom-pagination__item">
                            <a class="custom-pagination__link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                        </li>
                    @endif
                @endfor

                @if ($lastPage > $leadingLimit)
                    @if ($currentPage > ($leadingLimit + 1))
                        <li class="custom-pagination__item">
                            <span class="custom-pagination__disabled" aria-disabled="true">...</span>
                        </li>
                    @endif

                    @for ($page = max($leadingLimit + 1, $currentPage - 1); $page <= min($lastPage - 1, $currentPage + 1); $page++)
                        @if ($page === $currentPage)
                            <li class="custom-pagination__item">
                                <span class="custom-pagination__current" aria-current="page">{{ $page }}</span>
                            </li>
                        @else
                            <li class="custom-pagination__item">
                                <a class="custom-pagination__link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endfor

                    @if ($currentPage < ($lastPage - 2))
                        <li class="custom-pagination__item">
                            <span class="custom-pagination__disabled" aria-disabled="true">...</span>
                        </li>
                    @endif

                    <li class="custom-pagination__item">
                        @if ($currentPage === $lastPage)
                            <span class="custom-pagination__current" aria-current="page">{{ $lastPage }}</span>
                        @else
                            <a class="custom-pagination__link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
                        @endif
                    </li>
                @endif
            @endif

            @if ($paginator->hasMorePages())
                <li class="custom-pagination__item">
                    <a class="custom-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="custom-pagination__item">
                    <span class="custom-pagination__disabled" aria-disabled="true" aria-label="@lang('pagination.next')">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
