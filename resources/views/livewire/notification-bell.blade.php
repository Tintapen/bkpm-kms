<div x-data="{ open: @entangle('openDropdown') }" class="relative" x-cloak>
    <!-- Tombol Bell -->
    <button @click="open = !open" class="relative focus:outline-none">
        <x-filament::icon icon="heroicon-o-bell"
            class="w-6 h-6 text-gray-500 dark:text-gray-400 hover:text-primary-500 transition" />

        @if($newArticlesCount > 0)
        <span style="
    position: absolute;
    top: -6px;
    right: -6px;
    width: 20px;
    height: 20px;
    background-color: #dc2626; /* merah-600 */
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: bold;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
">
            {{ $newArticlesCount }}
        </span>
        @endif
    </button>

    <!-- Dropdown -->
    <div x-show="open" @click.outside="open = false" x-transition
        style="position: absolute; right: 0; margin-top: 0.5rem; width: 400px; max-height: 16rem; background-color: white; color: black; border-radius: 0.5rem; box-shadow: 0 10px 15px rgba(0,0,0,0.1); overflow-y: auto; z-index: 50;"
        class="dark:bg-gray-800">

        <div style="padding: 0.5rem; border-bottom: 1px solid #e5e7eb; font-weight: 600;" class="dark:border-gray-700">
            Notifikasi Terbaru ({{ $newArticlesCount }})
        </div>

        <div style="max-height: 16rem; overflow-y: auto;">
            @forelse($notifications as $note)
            <div wire:click="markAsReadAndGo('{{ $note->id }}', '{{ $note->data['article_id'] ?? '' }}')"
                style="padding: 0.5rem; border-radius: 0.375rem; cursor: pointer; margin-bottom: 0.25rem;"
                onmouseover="this.style.backgroundColor='#f3f4f6';"
                onmouseout="this.style.backgroundColor='transparent';" class="dark:hover:bg-gray-700">
                <p style="font-size: 0.875rem; color: #1f2937;" class="dark:text-gray-200">
                    {{ $note->data['message'] ?? 'Artikel baru!' }}
                </p>
                <span style="font-size: 0.75rem; color: #6b7280;" class="dark:text-gray-400">
                    {{ $note->created_at->diffForHumans() }}
                </span>
            </div>
            @empty
            <div style="padding: 0.5rem; font-size: 0.875rem; color: #6b7280;" class="dark:text-gray-400">
                Belum ada notifikasi
            </div>
            @endforelse
        </div>
    </div>
</div>