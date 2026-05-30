@if ($paginator->hasPages())
    <ul class="custom-pagination">
        
        {{-- Previous --}}
        <li>
            <button 
                @if ($paginator->onFirstPage()) disabled @endif
                wire:click="previousPage"
            >
                ‹
            </button>
        </li>

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <li>
                        <button 
                            wire:click="gotoPage({{ $page }})"
                            class="{{ $page == $paginator->currentPage() ? 'active' : '' }}"
                        >
                            {{ $page }}
                        </button>
                    </li>
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        <li>
            <button 
                @if (!$paginator->hasMorePages()) disabled @endif
                wire:click="nextPage"
            >
                ›
            </button>
        </li>

    </ul>
@endif