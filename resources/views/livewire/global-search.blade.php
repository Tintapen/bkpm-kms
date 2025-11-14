<div x-data="{ open: false, active: 0 }" class="relative w-full max-w-md" @click.outside="open = false">
    <!-- INPUT -->
    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Cari artikel, kategori, atau tag…"
        class="w-full h-10 rounded-lg bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600
               pl-4 pr-12 text-sm text-gray-700 dark:text-gray-200 
               placeholder-gray-400 dark:placeholder-gray-500
               focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none shadow-sm"
        @input="open = true" @keydown.escape="open = false" @keydown.arrow-down.prevent="
            if ($refs.list) active = Math.min(active + 1, $refs.list.children.length - 1)
        " @keydown.arrow-up.prevent="
            active = Math.max(active - 1, 0)
        " @keydown.enter.prevent="
            if ($refs.list && $refs.list.children[active]) {
                $refs.list.children[active].click()
            }
        " />

    <!-- DROPDOWN -->
    @if (!empty($results))
    <ul x-show="open" x-transition x-ref="list" class="absolute z-50 mt-2 w-full bg-white dark:bg-gray-800 shadow-lg border
                   border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        @foreach ($results as $i => $result)
        <li class="suggestion px-4 py-2 cursor-pointer text-sm text-gray-700 dark:text-gray-200
                           hover:bg-primary-600 hover:text-white"
            :class="{ 'bg-primary-600 text-white': active === {{ $i }} }" @mouseenter="active = {{ $i }}" @click="
                        open = false; 
                        $wire.selectItem('{{ $result['id'] }}')
                    ">
            {{ $result['label'] }}
        </li>
        @endforeach
    </ul>
    @endif
</div>