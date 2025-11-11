@php
use App\Filament\Resources\ArticleResource;
use Illuminate\Support\Facades\Storage;

$disk = $article->attachment_disk ?? 'public';
$storage = Storage::disk($disk);
@endphp
<x-filament::page>
    <div class="flex h-[calc(100vh-4rem)]">
        {{-- Konten Utama --}}
        <div class="flex-1 flex flex-col min-w-0 p-6 space-y-6 overflow-auto">

            {{-- Tombol Kembali dan Edit --}}
            <div class="flex justify-between items-center mb-4">
                <x-filament::button href="{{ ArticleResource::getUrl('index') }}" tag="a" size="sm" variant="ghost"
                    color="gray" class="flex items-center gap-1">
                    <x-heroicon-o-arrow-long-left class="w-4 h-4 flex-shrink-0 inline-block" />
                    <span>Kembali</span>
                </x-filament::button>

                @can('update_articles')
                <x-filament::button tag="a" title="Edit Artikel"
                    href="{{ ArticleResource::getUrl('edit', ['record' => $article]) }}" size="sm" color="primary"
                    class="flex items-center gap-1">
                    <x-heroicon-o-pencil-square class="w-4 h-4 flex-shrink-0 inline-block" />
                    <span>Edit Artikel</span>
                </x-filament::button>
                @endcan
            </div>


            {{-- Artikel Card --}}
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-6 space-y-4">

                {{-- Judul --}}
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $article->title }}</h1>

                {{-- Info --}}
                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1 border rounded-md text-xs px-2 py-1
                                 bg-primary-50 dark:bg-primary-900/20
                                 text-primary-700 dark:text-primary-400
                                 border-primary-200 dark:border-primary-800 shadow-sm">
                        <x-heroicon-m-rectangle-stack class="w-4 h-4 text-primary-500" />
                        {{ $article->category->name ?? $article->category }}
                    </span>

                    <div class="flex items-center gap-1">
                        <x-heroicon-o-eye class="w-4 h-4" /> {{ number_format($article->views) }} Views
                    </div>
                    <div class="flex items-center gap-1">
                        <x-heroicon-o-calendar class="w-4 h-4" /> {{ $article->updated_at->format('d M Y, H:i') }}
                    </div>
                    @if ($article->created_at != $article->updated_at)
                    <div class="flex items-center gap-1">
                        <x-heroicon-o-clock class="w-4 h-4" /> {{ $article->created_at->format('d M Y, H:i') }}
                    </div>
                    @endif
                </div>

                {{-- Tags --}}
                @if (!empty($article->tags))
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach($article->tags as $tag)
                    <span class="flex items-center gap-1 rounded-md text-xs px-2 py-1 shadow-sm border
                                bg-primary-50 dark:bg-primary-900/20
                                text-primary-700 dark:text-primary-300
                                border-primary-200 dark:border-primary-800 transition">
                        <x-filament::icon icon="heroicon-m-tag" class="w-4 h-4 text-primary-500" />
                        {{ $tag }}
                    </span>
                    @endforeach
                </div>
                @endif

                {{-- Engagement --}}
                <div class="flex gap-4 text-sm text-gray-500 dark:text-gray-400">
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

                <hr class="my-4 border-gray-200 dark:border-gray-700">

                {{-- Konten --}}
                <div class="prose dark:prose-invert max-w-none space-y-4">
                    {!! $article->getExcerptText() !!}

                    {{-- Attachment --}}
                    @can('download_articles')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                        @foreach ($article->getAttachments() as $url)
                        @php
                        $filename = basename(parse_url($url, PHP_URL_PATH));
                        $size = '';
                        try {
                        if ($storage->exists('articles/' . $filename)) {
                        $sizeBytes = $storage->size('articles/' . $filename);
                        $size = round($sizeBytes / 1024 / 1024, 2) . ' MB';
                        }
                        } catch (\Throwable $e) {}
                        @endphp

                        <a href="{{ $url }}" target="_blank"
                            class="flex items-center gap-3 p-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition">
                            <div
                                class="flex-shrink-0 w-10 h-10 bg-primary-50 dark:bg-primary-900/20 rounded-full flex items-center justify-center">
                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div class="flex flex-col truncate">
                                <span class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $filename
                                    }}</span>
                                @if($size)
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $size }}</span>
                                @endif
                            </div>
                        </a>
                        @endforeach
                    </div>
                    @endcan
                </div>


                {{-- Footer --}}
                <div class="flex justify-between items-center mt-6">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">
                        Artikel terakhir diperbarui {{ $article->updated_at->format('d M Y, H:i') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>