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
                <div class="prose dark:prose-invert max-w-none trix-content" id="excerptContent">
                    {!! $article->excerpt !!}
                </div>

                {{-- Footer --}}
                <div class="flex justify-between items-center mt-6">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">
                        Artikel terakhir diperbarui {{ $article->updated_at->format('d M Y, H:i') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Modal Preview -->
        <div id="modalPreview"
            class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-3xl md:max-w-4xl lg:max-w-5xl xl:max-w-6xl flex flex-col overflow-hidden border border-gray-200 dark:border-gray-700"
                style="height:80vh; min-height:400px; max-height:95vh;">
                <div
                    class="flex items-center justify-between px-4 md:px-6 py-3 md:py-4 border-b bg-gray-50 dark:bg-gray-800">
                    <div id="modalTitle"
                        class="font-semibold text-base md:text-lg text-gray-900 dark:text-gray-100 truncate"></div>
                    <button onclick="closePreview()"
                        class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 hover:text-black dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 p-2 md:p-4 overflow-auto bg-gray-50 dark:bg-gray-900 flex flex-col">
                    <div id="previewContent" class="flex-1 w-full h-full flex items-center justify-center"></div>
                </div>
                <div class="px-4 md:px-6 py-3 md:py-4 border-t bg-gray-50 dark:bg-gray-800 text-center">
                    <a id="downloadLink" href="#" target="_blank" class="text-blue-600 hover:underline text-sm">
                        Download file
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>

@push('scripts')
<script>
    function renderTrixAttachments() {
        const figures = document.querySelectorAll("figure[data-trix-attachment]");

        // Ambil URL download dari backend jika hanya satu attachment
        let backendDownloadUrl = null;
        if (window.backendAttachmentUrl) {
            backendDownloadUrl = window.backendAttachmentUrl;
        }

        figures.forEach(fig => {
            const data = JSON.parse(fig.dataset.trixAttachment);
            const url = data.url || data.href;
            const filename = data.filename || "Attachment";
            const sizeKB = data.filesize
                ? (data.filesize / 1024).toFixed(1) + " KB"
                : "";

            const ext = filename.split('.').pop().toLowerCase();

            // Remove default spacing
            fig.style.margin = "0";

            // Gunakan backendDownloadUrl jika ada, jika tidak fallback ke url lama
            const downloadUrl = backendDownloadUrl || url;

            // Build the custom card UI
            let iconHtml = '';
            if (ext === "pdf") {
                iconHtml = `<svg class="w-8 h-8" viewBox="0 0 48 48" fill="none">
                    <rect width="48" height="48" rx="12" fill="#F87171" />
                    <path d="M16 16H32V32H16V16Z" fill="#fff" />
                    <path d="M20 20H28V28H20V20Z" fill="#F87171" />
                    <text x="24" y="34" text-anchor="middle" font-size="10" fill="#fff" font-weight="bold">PDF</text>
                </svg>`;
            } else if (["xls","xlsx","csv"].includes(ext)) {
                iconHtml = `<svg class="w-8 h-8" viewBox="0 0 48 48" fill="none">
                    <rect width="48" height="48" rx="12" fill="#34D399" />
                    <path d="M16 16H32V32H16V16Z" fill="#fff" />
                    <path d="M20 20H28V28H20V20Z" fill="#34D399" />
                    <text x="24" y="34" text-anchor="middle" font-size="10" fill="#fff" font-weight="bold">XLS</text>
                </svg>`;
            } else if (["jpg","jpeg","png"].includes(ext)) {
                iconHtml = `<svg class="w-8 h-8" viewBox="0 0 48 48" fill="none">
                    <rect width="48" height="48" rx="12" fill="#FBBF24" />
                    <circle cx="24" cy="24" r="10" fill="#fff" />
                    <circle cx="24" cy="24" r="6" fill="#FBBF24" />
                    <text x="24" y="40" text-anchor="middle" font-size="10" fill="#fff" font-weight="bold">IMG</text>
                </svg>`;
            } else if (["ppt","pptx"].includes(ext)) {
                iconHtml = `<svg class="w-8 h-8" viewBox="0 0 48 48" fill="none">
                    <rect width="48" height="48" rx="12" fill="#F59E42" />
                    <path d="M16 16H32V32H16V16Z" fill="#fff" />
                    <path d="M20 20H28V28H20V20Z" fill="#F59E42" />
                    <text x="24" y="34" text-anchor="middle" font-size="10" fill="#fff" font-weight="bold">PPT</text>
                </svg>`;
            } else {
                iconHtml = `<svg class="w-8 h-8" viewBox="0 0 48 48" fill="none">
                    <rect width="48" height="48" rx="12" fill="#CBD5E1" />
                    <path d="M16 16H32V32H16V16Z" fill="#fff" />
                    <path d="M20 20H28V28H20V20Z" fill="#60A5FA" />
                    <text x="24" y="34" text-anchor="middle" font-size="10" fill="#2563eb" font-weight="bold">DOC</text>
                </svg>`;
            }

            fig.innerHTML = `
                <div class="border rounded-xl p-4 bg-white dark:bg-gray-800 shadow-sm
                            flex flex-col gap-3 w-full max-w-md mb-4">

                    <div class="flex items-center gap-3 w-full">

                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center bg-gray-100 dark:bg-gray-800">
                            ${iconHtml}
                        </div>

                        <div class="flex-grow min-w-0 max-w-full">
                            <div class="font-semibold text-gray-900 dark:text-gray-100 truncate">${filename}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${sizeKB} KB</div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 mt-2">                        
                        
                        <!-- PREVIEW BUTTON -->
                        <button type="button"
                            onclick="openPreview('${downloadUrl}', '${filename}')"
                            class="flex items-center justify-center gap-1 text-xs font-medium px-4 py-2 rounded-md w-full text-white"
                            style="background:#22c55e; text-decoration:none;">
                            <x-heroicon-o-eye class="w-4 h-4" />
                            Preview
                        </button>

                        <!-- DOWNLOAD -->
                        <a href="${downloadUrl}" download="${filename}"
                            class="flex items-center justify-center gap-1 text-xs font-medium px-4 py-2 rounded-md w-full text-white"
                            style="background:#2563eb; text-decoration:none;"
                            onmouseover="this.style.background='#1d4ed8'"
                            onmouseout="this.style.background='#2563eb'">

                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                                class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 10.5l4.5 4.5 4.5-4.5M12 3v12" />
                            </svg>

                            Download
                        </a>
                    </div>
                </div>
            `;
        });
    }

    // SPA events
    document.addEventListener("livewire:navigated", () => setTimeout(renderTrixAttachments, 60));
    document.addEventListener("DOMContentLoaded", () => setTimeout(renderTrixAttachments, 80));

    // Set window.backendAttachmentUrl dari backend jika hanya satu attachment
    @php
        $attachments = $article->getAttachments();
        if (count($attachments) === 1) {
            echo 'window.backendAttachmentUrl = ' . json_encode($article->getFirstAttachmentUrl()) . ";\n";
        }
    @endphp

    function openPreview(url, title) {
        document.getElementById("modalTitle").innerText = title;
        const ext = title.split('.').pop().toLowerCase();
        const previewContent = document.getElementById("previewContent");
        let html = '';
        if (["jpg","jpeg","png","gif","webp","bmp","svg"].includes(ext)) {
            html = `<img src="${url}" alt="${title}" style="max-width:100%; max-height:65vh; border-radius:12px; box-shadow:0 2px 8px #0002; background:#fff;" />`;
        } else if (["mp3","wav","ogg","aac","m4a"].includes(ext)) {
            html = `<audio controls style="width:100%; max-width:600px;"><source src="${url}">Browser tidak mendukung audio.</audio>`;
        } else if (["mp4","webm","ogg","mov","mkv"].includes(ext)) {
            html = `<video controls style="max-width:100%; max-height:65vh; border-radius:12px; background:#000;"><source src="${url}">Browser tidak mendukung video.</video>`;
        } else if (ext === "pdf") {
            html = `<iframe src="${url}" frameborder="0" style="width:100%; height:70vh; min-height:300px; max-height:80vh; border-radius:12px; background:#fff;"></iframe>`;
        } else {
            // Google Docs Viewer for other docs (doc, docx, xls, xlsx, ppt, pptx, etc)
            const gview = `https://docs.google.com/gview?url=${encodeURIComponent(url)}&embedded=true`;
            html = `<iframe src="${gview}" frameborder="0" style="width:100%; height:70vh; min-height:300px; max-height:80vh; border-radius:12px; background:#fff;"></iframe>`;
        }
        previewContent.innerHTML = html;

        // Set download link in modal
        let downloadUrl = url;
        if (window.backendAttachmentUrl) {
            downloadUrl = window.backendAttachmentUrl;
        }
        const downloadLink = document.getElementById("downloadLink");
        if (downloadLink) {
            downloadLink.href = downloadUrl;
            downloadLink.setAttribute('download', title);
        }

        document.getElementById("modalPreview").classList.remove("hidden");
    }

    function closePreview() {
        document.getElementById("modalPreview").classList.add("hidden");
        document.getElementById("previewContent").innerHTML = "";
    }

    function linkifyExcerpt() {
        const excerptDiv = document.getElementById('excerptContent');
        if (!excerptDiv) return;
        const urlRegex = /(https?:\/\/[\w\-._~:/?#[\]@!$&'()*+,;=%]+)|(www\.[\w\-._~:/?#[\]@!$&'()*+,;=%]+)/gi;

        function linkifyNode(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                if (urlRegex.test(node.textContent)) {
                    const html = node.textContent.replace(urlRegex, function(url) {
                        let href = url;
                        if (!href.match(/^https?:\/\//)) href = 'http://' + href;
                        return `<a href='${href}' style='text-decoration:underline' target='_blank' rel='noopener'>${url}</a>`;
                    });
                    const span = document.createElement('span');
                    span.innerHTML = html;
                    node.parentNode.replaceChild(span, node);
                }
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                Array.from(node.childNodes).forEach(linkifyNode);
            }
        }

        linkifyNode(excerptDiv);

        // Ensure all <a> tags inside excerptContent open in new tab and are blue/underlined inline
        const allLinks = excerptDiv.querySelectorAll('a');
        allLinks.forEach(link => {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener');
        });
    }
    
    document.addEventListener("DOMContentLoaded", linkifyExcerpt);
    document.addEventListener("livewire:navigated", () => setTimeout(linkifyExcerpt, 60));
</script>
@endpush