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
                    <span>Ubah Artikel</span>
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                        <div x-data="{ show: false, fileUrl: '', fileType: '', fileName: '' }" class="space-y-3">
                            @foreach ($article->getAttachments() as $url)
                            @php
                            $filename = basename(parse_url($url, PHP_URL_PATH));
                            $size = '';
                            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            try {
                            if ($storage->exists('articles/' . $filename)) {
                            $sizeBytes = $storage->size('articles/' . $filename);
                            $size = round($sizeBytes / 1024 / 1024, 2) . ' MB';
                            }
                            } catch (\Throwable $e) {}
                            @endphp
                            <div
                                class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow hover:shadow-md transition flex flex-col gap-3 items-start">
                                <div class="flex items-center gap-3 w-full">
                                    <!-- Icon file type -->
                                    <div
                                        class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center bg-gray-100 dark:bg-gray-800">
                                        @if(in_array($ext, ['pdf']))
                                        <!-- Stylish PDF Icon -->
                                        <svg class="w-8 h-8" viewBox="0 0 48 48" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <rect width="48" height="48" rx="12" fill="#F87171" />
                                            <path d="M16 16H32V32H16V16Z" fill="#fff" />
                                            <path d="M20 20H28V28H20V20Z" fill="#F87171" />
                                            <text x="24" y="34" text-anchor="middle" font-size="10"
                                                font-family="Arial, Helvetica, sans-serif" fill="#fff"
                                                font-weight="bold">PDF</text>
                                        </svg>
                                        @else
                                        <!-- Modern Document Icon -->
                                        <svg class="w-8 h-8" viewBox="0 0 48 48" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <rect width="48" height="48" rx="12" fill="#CBD5E1" />
                                            <path d="M16 16H32V32H16V16Z" fill="#fff" />
                                            <path d="M20 20H28V28H20V20Z" fill="#60A5FA" />
                                            <text x="24" y="34" text-anchor="middle" font-size="10"
                                                font-family="Arial, Helvetica, sans-serif" fill="#2563eb"
                                                font-weight="bold">DOC</text>
                                        </svg>
                                        @endif
                                    </div>
                                    <!-- File info -->
                                    <div class="flex-grow min-w-0 max-w-full">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{
                                            $filename }}</div>
                                        @if($size)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $size }}</div>
                                        @endif
                                    </div>
                                </div>
                                <!-- Action buttons below -->
                                <div class="flex gap-2 w-full mt-2 flex-col sm:flex-row">
                                    <button type="button"
                                        style="display:flex;align-items:center;gap:0.25rem;font-size:0.75rem;padding:0.25rem 0.9rem;border-radius:0.375rem;background:#22c55e;color:#fff;border:1px solid #22c55e;transition:all .2s;width:100%;max-width:140px;justify-content:center;"
                                        onmouseover="this.style.background='#16a34a';this.style.color='#fff'"
                                        onmouseout="this.style.background='#22c55e';this.style.color='#fff'"
                                        @click="show = true; fileUrl = '{{ $url }}'; fileType = '{{ $ext }}'; fileName = '{{ $filename }}'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Preview
                                    </button>
                                    @can('download_articles')
                                    <a href="{{ $url }}" target="_blank"
                                        style="display:flex;align-items:center;gap:0.25rem;font-size:0.75rem;padding:0.25rem 0.9rem;border-radius:0.375rem;background:#2563eb;color:#fff;border:1px solid #2563eb;transition:all .2s;text-decoration:none;width:100%;max-width:140px;justify-content:center;"
                                        onmouseover="this.style.background='#1d4ed8';this.style.color='#fff'"
                                        onmouseout="this.style.background='#2563eb';this.style.color='#fff'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                                        </svg>
                                        Download
                                    </a>
                                    @endcan
                                </div>
                            </div>
                            @endforeach

                            <!-- Modal Preview File -->
                            <div x-show="show" x-cloak
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                                <div
                                    class="bg-white dark:bg-gray-900 rounded-xl shadow-lg max-w-2xl w-full p-6 pt-14 relative">
                                    <div class="flex items-center justify-between mb-4 gap-2">
                                        <div class="font-semibold text-lg text-gray-900 dark:text-gray-100 truncate"
                                            x-text="fileName"></div>
                                        <button @click="show = false"
                                            class="flex items-center justify-center w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-primary-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <template x-if="['jpg','jpeg','png','gif','webp','bmp'].includes(fileType)">
                                        <img :src="fileUrl" alt="Preview" class="max-h-96 mx-auto rounded" />
                                    </template>
                                    <template x-if="['pdf'].includes(fileType)">
                                        <iframe :src="fileUrl" class="w-full h-96 rounded" frameborder="0"></iframe>
                                    </template>
                                    <template x-if="['mp4','webm','ogg'].includes(fileType)">
                                        <video :src="fileUrl" controls class="w-full max-h-96 rounded"></video>
                                    </template>
                                    <!-- CSV preview as text -->
                                    <template x-if="['csv'].includes(fileType)">
                                        <iframe :src="fileUrl" class="w-full h-96 rounded bg-white"
                                            frameborder="0"></iframe>
                                    </template>
                                    <!-- Office files preview with Google Docs Viewer (hanya jika url http/https) -->
                                    <template
                                        x-if="['doc','docx','xls','xlsx','ppt','pptx'].includes(fileType) && (fileUrl.startsWith('http://') || fileUrl.startsWith('https://'))">
                                        <iframe
                                            :src="'https://docs.google.com/gview?url=' + encodeURIComponent(fileUrl) + '&embedded=true'"
                                            class="w-full h-96 rounded bg-white" frameborder="0"></iframe>
                                    </template>
                                    @can('download_articles')
                                    <template
                                        x-if="['doc','docx','xls','xlsx','ppt','pptx'].includes(fileType) && !(fileUrl.startsWith('http://') || fileUrl.startsWith('https://'))">
                                        <div class="text-gray-500 dark:text-gray-300 text-center py-8">
                                            File Office hanya bisa dipreview jika file dapat diakses publik (URL
                                            http/https).<br>
                                            <a :href="fileUrl" target="_blank"
                                                style="color:#2563eb;text-decoration:none;"
                                                onmouseover="this.style.textDecoration='underline'"
                                                onmouseout="this.style.textDecoration='none'">Download file</a>
                                        </div>
                                    </template>
                                    @endcan
                                    <template
                                        x-if="!['jpg','jpeg','png','gif','webp','bmp','pdf','mp4','webm','ogg','csv','doc','docx','xls','xlsx','ppt','pptx'].includes(fileType)">
                                        <div class="text-gray-500 dark:text-gray-300 text-center py-8">
                                            Tidak dapat preview file ini.
                                        </div>
                                    </template>
                                    @can('download_articles')
                                    <div class="mt-6 text-center">
                                        <a :href="fileUrl" target="_blank"
                                            style="color:#2563eb;text-decoration:none;font-size:15px;"
                                            onmouseover="this.style.textDecoration='underline'"
                                            onmouseout="this.style.textDecoration='none'">Download file</a>
                                    </div>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
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