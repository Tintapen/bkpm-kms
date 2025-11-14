@php
use Filament\Facades\Filament;
@endphp
<div class="flex items-center justify-between w-full h-14 px-4 bg-gray-900 text-gray-100">
    <div class="flex items-center gap-2 w-1/3"></div>

    {{-- Tengah: Search bar --}}
    @can('view_articles')
    <div class="flex-1 flex justify-center">
        @livewire('global-search')
    </div>
    @endcan

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