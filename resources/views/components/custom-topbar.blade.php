@php
use Filament\Facades\Filament;
@endphp
<div class="flex items-center justify-between w-full h-14 px-4 bg-gray-900 text-gray-100">
    <div class="flex items-center gap-2 w-1/3"></div>

    {{-- Tengah: Search bar --}}
    {{-- <div class="flex-1 flex justify-center">
        <div class="relative w-full max-w-md">
            <input type="text" placeholder="Cari berdasarkan artikel, kategori, atau tag…" class="w-full h-10 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-700
                pl-4 pr-12 text-sm text-gray-900 dark:text-white
                focus:ring-2 focus:ring-primary-500 focus:outline-none" />
        </div>
    </div> --}}

    {{-- Kanan: Lonceng + User Menu --}}
    <div class="flex items-center gap-4 w-1/3 justify-end">
        @livewire('notification-bell', [], key('notification-bell'))
        {!! Filament::renderHook('panels::user-menu') !!}
    </div>
</div>


<x-livewire-upload-progress />
@vite('resources/js/app.js')

<style>
    .fi-topbar {
        border-bottom: none !important;
        box-shadow: none !important;
    }
</style>