@php
use App\Filament\Resources\ArticleResource;
@endphp

<x-filament::page>
    <div class="space-y-6">
        {{-- Upload Progress (untuk create/edit) --}}
        @includeWhen(
        request()->routeIs([
        'filament.admin.resources.articles.create',
        'filament.admin.resources.articles.edit',
        ]),
        'components.livewire-upload-progress'
        )

        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Temukan, jelajahi, dan kelola konten pengetahuan Anda di sini.
            </p>

            @can('create_articles')
            <x-filament::button tag="a" href="{{ ArticleResource::getUrl('create') }}">
                Tambah Artikel
            </x-filament::button>
            @endcan
        </div>

        {{-- Search --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm">
            <input type="text" wire:model.live="search" placeholder="Cari berdasarkan artikel, kategori, atau tag…"
                class="w-full rounded-full border border-gray-300 dark:border-gray-700
                    bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100
                    placeholder-gray-400 dark:placeholder-gray-500
                    focus:outline-none focus:ring-2 focus:ring-primary-500
                    px-5 py-3 transition" />
        </div>

        {{-- Grid Artikel --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse ($this->articles as $article)
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700
                        bg-gray-50 dark:bg-gray-900 p-4 shadow-sm transition hover:shadow-md">

                {{-- Header --}}
                <div class="flex justify-between items-start mb-2">
                    <span class="inline-flex items-center gap-1 border rounded-md text-xs px-2 py-1
                                    bg-primary-50 dark:bg-primary-900/20
                                    text-primary-700 dark:text-primary-400
                                    border-primary-200 dark:border-primary-800 shadow-sm">
                        <x-filament::icon icon="heroicon-m-rectangle-stack" class="w-4 h-4 text-primary-500" />
                        {{ $article->category->name ?? $article->category }}
                    </span>

                    <div class="flex gap-2">
                        {{-- Download --}}
                        @can('download_articles')
                        @php
                        $firstUrl = $article->getFirstAttachmentUrl();
                        $downloadFilename = $firstUrl ? basename(parse_url($firstUrl, PHP_URL_PATH)) : null;
                        @endphp
                        @if($firstUrl && $downloadFilename)
                        <x-filament::icon-button icon="heroicon-o-arrow-down-tray" tag="a" title="Download Dokumen"
                            href="{{ url('/download/attachment/' . urlencode($downloadFilename)) }}" target="_blank"
                            rel="noopener noreferrer" color="success" />
                        @endif
                        @endcan

                        {{-- Edit --}}
                        @can('update_articles')
                        <x-filament::icon-button icon="heroicon-o-pencil-square" tag="a" title="Edit Artikel"
                            href="{{ ArticleResource::getUrl('edit', ['record' => $article]) }}" />
                        @endcan

                        {{-- Hapus --}}
                        @can('delete_articles')
                        <x-filament::icon-button icon="heroicon-o-trash" color="danger" title="Hapus Artikel"
                            x-on:click="$wire.set('articleIdToDelete', {{ $article->id }}); $dispatch('open-modal', { id: 'confirm-delete' })" />
                        @endcan
                    </div>
                </div>

                {{-- Judul --}}
                <h3 class="font-semibold text-lg">
                    <a href="{{ ArticleResource::getUrl('view', ['record' => $article]) }}"
                        class="text-gray-800 dark:text-gray-200 hover:text-primary-600 dark:hover:text-primary-400 transition"
                        wire-navigate>
                        {{ $article->title }}
                    </a>
                </h3>

                {{-- Excerpt --}}
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">
                    {!! Str::limit($article->getExcerptText() ?? 'Lorem ipsum dolor sit amet...', 60) !!}
                </p>

                {{-- Tags --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($article->tags ?? [] as $tag)
                    <span class="flex items-center gap-1 rounded-md text-xs px-2 py-1 shadow-sm border
                                bg-primary-50 dark:bg-primary-900/20
                                text-primary-700 dark:text-primary-300
                                border-primary-200 dark:border-primary-800 transition">
                        <x-filament::icon icon="heroicon-m-tag" class="w-4 h-4 text-primary-500" />
                        {{ $tag }}
                    </span>
                    @endforeach
                </div>

                {{-- Info --}}
                <div class="flex justify-between items-center text-gray-400 dark:text-gray-500 text-xs">
                    <div class="flex items-center gap-1">
                        <x-heroicon-o-user class="w-4 h-4" />
                        {{ $article->author->name ?? 'Admin' }}
                    </div>

                    <div>Updated {{ $article->updated_at->format('Y-m-d') }}</div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-eye class="w-4 h-4" />
                            {{ $article->views ?? 0 }}
                        </div>

                        {{-- Likes --}}
                        <button wire:click="like({{ $article->id }})"
                            class="group flex items-center gap-1 text-gray-500 dark:text-gray-400 transition-colors duration-200"
                            title="Sukai Artikel Ini">
                            <x-heroicon-o-hand-thumb-up
                                class="w-4 h-4 text-gray-500 dark:text-gray-400
                                        group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200" />
                            <span
                                class="group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200">
                                {{ $article->likes ?? 0 }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center text-gray-400 dark:text-gray-500 py-10">
                Tidak ada artikel ditemukan.
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div>
            {{ $this->articles->links() }}
        </div>
    </div>

    <x-filament::modal id="confirm-delete" width="md">
        <div class="text-lg font-semibold mb-2">Hapus Artikel</div>
        <p>Apakah Anda yakin ingin menghapus artikel ini?</p>

        <div class="mt-4 flex justify-end gap-2">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'confirm-delete' })">
                Batal
            </x-filament::button>

            <x-filament::button color="danger" wire:click="deleteArticle"
                x-on:click="$dispatch('close-modal', { id: 'confirm-delete' })">
                Ya, Hapus
            </x-filament::button>
        </div>
    </x-filament::modal>
</x-filament::page>