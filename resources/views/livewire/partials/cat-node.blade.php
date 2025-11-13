@php
use App\Filament\Resources\ArticleResource;
@endphp
<li>
    <div class="flex items-center justify-between px-2 py-2 rounded-lg text-[1.15rem] category-node transition-all duration-150"
        @if(($selectedCategoryId ?? null) == $node->id)
            style="background: #f8fafc; border: 1px solid #e5e7eb; color: #2563eb; font-weight: 600; margin-left: {{ ($level ?? 0) * 20 }}px; cursor:pointer;"
        @else
            style="margin-left: {{ ($level ?? 0) * 20 }}px; cursor:pointer;"
        @endif
        @if($node->children->isEmpty())
        wire:click="redirectToCategory('{{ $node->name }}')"
        wire:navigate
        style="margin-left: {{ ($level ?? 0) * 20 }}px; cursor:pointer;"
        @else
        wire:click.prevent="toggle({{ $node->id }})"
        style="margin-left: {{ ($level ?? 0) * 20 }}px; cursor:pointer;"
        @endif
        >
        <div class="flex items-center gap-2 w-full">
            <span class="w-6 flex-shrink-0 flex items-center justify-center">
                <x-heroicon-o-folder
                    style="color: {{ ($selectedCategoryId ?? null) == $node->id ? '#2563eb' : (in_array($node->id, $open) ? '#3b82f6' : '#64748b') }}; display: inline; font-size:1.6rem; width:2.1rem; height:2.1rem;"
                    class="transition-colors duration-150" />
            </span>
            <span style="font-weight: {{ ($selectedCategoryId ?? null) == $node->id ? '600' : '500' }}; color: {{ ($selectedCategoryId ?? null) == $node->id ? '#2563eb' : (in_array($node->id, $open) ? '#1d4ed8' : '#1e293b') }};" class="font-medium w-full">
                {{ $node->name }}
            </span>
            @if(($level ?? 0) === 0)
            <span
                class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-700">
                {{ $node->getTotalArticlesRecursive() }}
            </span>
            @endif
        </div>

        @if($node->children->isNotEmpty())
        <span
            class="w-4 h-4 flex items-center justify-center transition-transform duration-200 {{ in_array($node->id, $open) ? 'rotate-90' : '' }}">
            <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400" />
        </span>
        @endif
    </div>

    {{-- Recursive children --}}
    @if($node->children->isNotEmpty() && in_array($node->id, $open))
    <ul style="margin-left: {{ ($level ?? 0) * 20 }}px;" class="mt-1 space-y-1">
        @foreach ($node->children as $child)
        @include('livewire.partials.cat-node', [
        'node' => $child,
        'level' => ($level ?? 0) + 1,
        ])
        @endforeach
    </ul>
    @endif
</li>