@php
use App\Filament\Resources\MenuResource;
@endphp

<x-filament-widgets::widget>
    <div class="flex flex-row gap-4 w-full min-w-[900px] overflow-x-auto">
        <div
            class="flex flex-row items-center justify-center flex-1 min-w-[220px] p-4 rounded-xl border shadow-sm bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700 h-full min-h-[100px]">
            <span class="flex items-center justify-center h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900 mr-4">
                <x-filament::icon icon="heroicon-o-list-bullet" class="h-7 w-7 text-blue-700 dark:text-blue-400" />
            </span>
            <div class="flex flex-col flex-1 items-center justify-center text-center">
                <div class="text-2xl font-extrabold text-blue-700 dark:text-blue-400">{{ $totalMenus }}</div>
                <div class="text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wide mt-1">Total
                    Menu</div>
            </div>
        </div>
        <div
            class="flex flex-row items-center justify-center flex-1 min-w-[220px] p-4 rounded-xl border shadow-sm bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700 h-full min-h-[100px]">
            <span class="flex items-center justify-center h-12 w-12 rounded-lg bg-green-100 dark:bg-green-900 mr-4">
                <x-filament::icon icon="heroicon-o-check-circle" class="h-7 w-7 text-green-700 dark:text-green-400" />
            </span>
            <div class="flex flex-col flex-1 items-center justify-center text-center">
                <div class="text-2xl font-extrabold text-green-700 dark:text-green-400">{{ $activeMenus }}</div>
                <div class="text-xs font-semibold text-green-700 dark:text-green-400 uppercase tracking-wide mt-1">Menu
                    Aktif</div>
            </div>
        </div>
        <div
            class="flex flex-row items-center justify-center flex-1 min-w-[220px] p-4 rounded-xl border shadow-sm bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700 h-full min-h-[100px]">
            <span class="flex items-center justify-center h-12 w-12 rounded-lg bg-red-100 dark:bg-red-900 mr-4">
                <x-filament::icon icon="heroicon-o-x-circle" class="h-7 w-7 text-red-700 dark:text-red-400" />
            </span>
            <div class="flex flex-col flex-1 items-center justify-center text-center">
                <div class="text-2xl font-extrabold text-red-700 dark:text-red-400">{{ $inactiveMenus }}</div>
                <div class="text-xs font-semibold text-red-700 dark:text-red-400 uppercase tracking-wide mt-1">Menu
                    Nonaktif</div>
            </div>
        </div>
        <div
            class="flex flex-row items-center justify-center flex-1 min-w-[220px] p-4 rounded-xl border shadow-sm bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700 h-full min-h-[100px]">
            <span class="flex items-center justify-center h-12 w-12 rounded-lg bg-indigo-100 dark:bg-indigo-900 mr-4">
                <x-filament::icon icon="heroicon-o-bars-3" class="h-7 w-7 text-indigo-700 dark:text-indigo-400" />
            </span>
            <div class="flex flex-col flex-1 items-center justify-center text-center">
                <div class="text-2xl font-extrabold text-indigo-700 dark:text-indigo-400">{{ $mainMenus }}</div>
                <div class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 uppercase tracking-wide mt-1">
                    Menu Utama</div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>