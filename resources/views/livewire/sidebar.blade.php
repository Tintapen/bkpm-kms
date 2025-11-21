<div x-data>
    {{-- COLLAPSED SIDEBAR --}}
    <ul x-show="!$store.sidebar.isOpen" x-collapse x-cloak class="flex flex-col gap-y-3 px-2 py-2">

        @if (isset($leafCategories))
        @foreach ($leafCategories as $cat)
        @php
        $isActive = ($selectedCategoryId ?? null) == $cat->id;
        @endphp

        <li class="fi-sidebar-item">
            <a wire:navigate wire:click="redirectToCategory('{{ $cat->name }}')" {{-- TOOLTIP --}}
                x-data="{ tooltip: false }" x-effect="
                            tooltip = $store.sidebar.isOpen
                                ? false
                                : {
                                    content: '{{ $cat->name }}',
                                    placement: document.dir === 'rtl' ? 'left' : 'right',
                                    theme: $store.theme,
                            }
                    " x-tooltip.html="tooltip" class="fi-sidebar-item-button
                            relative flex items-center justify-center
                            rounded-xl px-2 py-2 h-12 w-12 cursor-pointer
                            transition duration-150

                            hover:bg-gray-100 dark:hover:bg-white/5
                            {{ $isActive ? 'bg-gray-100 shadow-sm ring-1 ring-gray-200' : '' }}
                    ">

                <x-heroicon-o-folder class="h-6 w-6 transition-colors duration-150" style="color:
                                {{ $isActive 
                                    ? '#2563eb'
                                    : '#64748b'
                                }};
                            " />
            </a>
        </li>
        @endforeach
        @endif
    </ul>

    {{-- EXPANDED SIDEBAR --}}
    <div x-show="$store.sidebar.isOpen" x-collapse x-cloak class="fi-sidebar-group mt-4">

        <ul class="fi-sidebar-group-items flex flex-col gap-y-1 px-2 text-sm">
            @foreach ($categories as $cat)
            @include('livewire.partials.cat-node', [
            'node' => $cat,
            'level' => 0,
            'open' => $open,
            'selectedCategoryId' => $selectedCategoryId,
            ])
            @endforeach
        </ul>
    </div>
</div>