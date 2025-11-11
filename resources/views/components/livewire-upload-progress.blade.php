<div x-data="livewireUploadProgress()" x-on:livewire-upload-start="uploading = true; progress = 0"
    x-on:livewire-upload-progress="event => progress = event.detail.progress"
    x-on:livewire-upload-finish="setTimeout(() => { uploading = false; progress = 100 }, 300)"
    x-on:livewire-upload-error="uploading = false; progress = 0"
    class="fixed bottom-4 left-1/2 transform -translate-x-1/2 z-50 w-11/12 max-w-2xl pointer-events-none">
    <!-- Hidden by default (style) so pages without Alpine won't show the progress UI. Alpine/x-cloak will override when present. -->
    <div x-show="uploading" x-cloak style="display: none;"
        class="bg-white/80 dark:bg-gray-800/80 rounded-lg shadow-md p-3 ring-1 ring-black/5">
        <div class="flex items-center justify-between mb-2">
            <div class="text-sm text-gray-700 dark:text-gray-200">Mengunggah file... <span
                    x-text="Math.floor(progress)"></span>%</div>
            <div class="text-xs text-gray-500">Jangan tutup halaman</div>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
            <div :style="`width: ${progress}%`" class="h-2 bg-primary-600 transition-all"></div>
        </div>
    </div>
</div>

<script>
    // Alpine component to handle upload progress and disable submit buttons while uploading.
    document.addEventListener('alpine:init', () => {
        Alpine.data('livewireUploadProgress', () => ({
            uploading: false,
            progress: 0,
            _disabledEls: [],
            init() {
                // watch uploading state and disable/enable submit buttons/actions while uploading
                this.$watch('uploading', (value) => {
                    // selectors targeting Filament form submit buttons and action buttons
                    const selectors = [
                        'form button[type="submit"]',
                        'button[data-action]',
                        '.filament-form button',
                        '.filament-page button[type="submit"]',
                    ];
                    const els = Array.from(document.querySelectorAll(selectors.join(',')));
                    if (value) {
                        // disable and remember previous state
                        els.forEach((el) => {
                            try {
                                el.dataset._prevDisabled = el.disabled ? '1' : '0';
                                el.disabled = true;
                                el.classList.add('opacity-50', 'pointer-events-none');
                                this._disabledEls.push(el);
                            } catch (e) {
                                // ignore
                            }
                        });
                    } else {
                        // restore
                        this._disabledEls.forEach((el) => {
                            try {
                                if (el.dataset._prevDisabled === '1') {
                                    el.disabled = true;
                                } else {
                                    el.disabled = false;
                                }
                                el.classList.remove('opacity-50', 'pointer-events-none');
                                delete el.dataset._prevDisabled;
                            } catch (e) {
                                // ignore
                            }
                        });
                        this._disabledEls = [];
                    }
                });
            },
        }));
    });

    // Client-side guard for Trix file attachments: enforce max size and optionally forbid extensions.
    (function () {
    // Currently allow PPT/PPTX; if you want to forbid some extensions, add them here.
    const FORBIDDEN_EXT = [];
    const MAX_BYTES = 50 * 1024 * 1024; // 50 MB limit to match server-side validation

    document.addEventListener('trix-file-accept', function (event) {
        try {
            const file = event.file;
            const name = (file && file.name) ? file.name : '';
            const size = (file && file.size) ? file.size : 0;
            const ext = name.split('.').pop().toLowerCase();

            // if (FORBIDDEN_EXT.includes(ext)) {
            //     event.preventDefault();
            //     // Let backend validation handle the error display
            //     return;
            // }

            if (size > MAX_BYTES) {
                event.preventDefault();
                // Let backend validation handle the error display under the field
                return;
            }
        } catch (err) {
            // ignore
        }
    });

        // Generic file input fallback: validate file inputs added dynamically
        document.addEventListener('change', function (e) {
            const target = e.target;
            if (! target || target.tagName !== 'INPUT' || target.type !== 'file') return;
            const files = target.files || [];
            for (let i = 0; i < files.length; i++) {
                const f = files[i];
                const ext = (f.name.split('.').pop() || '').toLowerCase();
                if (FORBIDDEN_EXT.includes(ext)) {
                    target.value = null;
                    alert('File PPT/PPTX tidak diizinkan.');
                    return;
                }
                if (f.size > MAX_BYTES) {
                    target.value = null;
                    alert('Ukuran file terlalu besar. Maksimal ' + (MAX_BYTES / (1024*1024)) + ' MB.');
                    return;
                }
            }
        }, true);
    })();
</script>