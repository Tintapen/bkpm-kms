@php
$isActive = ($selectedCategoryId ?? null) == $node->id;
$isOpen = in_array($node->id, $open ?? []);
@endphp

<li>
    <div class="flex items-center justify-between px-2 py-2 rounded-lg transition-all duration-150 cursor-pointer
            hover:bg-gray-100 dark:hover:bg-white/5" style="margin-left: {{ ($level ?? 0) * 20 }}px;
               background: {{ $isActive ? '#f0f4ff' : '' }};
               border: {{ $isActive ? '1px solid #d9e6ff' : '' }};" @if($node->children->isEmpty())
        wire:click="redirectToCategory('{{ $node->name }}')"
        wire:navigate
        @else
        wire:click.prevent="toggle({{ $node->id }})"
        @endif
        >
        <div class="flex items-center gap-2 w-full">
            {{-- ICON --}}
            <span class="w-6 flex items-center justify-center">
                <x-heroicon-o-folder class="transition-colors duration-150" style="color: {{ $isActive ? '#2E66DB'
                        : ($isOpen ? '#3b82f6'
                        : '#64748b') }};
                           width: 1.6rem; height: 1.6rem;" />
            </span>

            {{-- TEXT --}}
            <span class="truncate transition-colors duration-150" style="font-weight: {{ $isActive ? '600' : '500' }};
                       color: {{ $isActive ? '#2E66DB'
                            : ($isOpen ? '#1d4ed8'
                            : '#1e293b') }};">
                {{ $node->name }}
            </span>

            {{-- COUNT HANYA UNTUK LEVEL 0 --}}
            @if(($level ?? 0) === 0)
            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                           bg-primary-100 dark:bg-primary-900/30
                           text-primary-700 dark:text-primary-300
                           border border-primary-200 dark:border-primary-700">
                {{ $node->getTotalArticlesRecursive() }}
            </span>
            @endif
        </div>

        {{-- CHEVRON --}}
        @if($node->children->isNotEmpty())
        <span class="w-4 h-4 flex items-center justify-center transition-transform duration-200">
            @if($isOpen)
            <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400" />
            @else
            <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400" />
            @endif
        </span>
        @endif

    </div>

    {{-- CHILDREN --}}
    @if($node->children->isNotEmpty() && $isOpen)
    <ul class="mt-1 space-y-1" style="margin-left: {{ ($level ?? 0) * 20 }}px;">
        @foreach ($node->children as $child)
        @include('livewire.partials.cat-node', [
        'node' => $child,
        'level' => ($level ?? 0) + 1,
        ])
        @endforeach
    </ul>
    @endif
</li>