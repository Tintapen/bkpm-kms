@php
use App\Filament\Resources\ArticleResource;
@endphp

<x-filament-widgets::widget>
    <!-- Stats Cards -->
    @can('ringkasan_aktivitas_admin')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        @php
        $stats = [
        [
        'label' => 'Total Artikel',
        'value' => $this->getTotalArticles(),
        'desc' => 'Jumlah keseluruhan artikel',
        'icon' => 'heroicon-o-document-text',
        ],
        [
        'label' => 'Kategori',
        'value' => $this->getTotalCategory(),
        'desc' => 'Total kategori tersedia',
        'icon' => 'heroicon-m-rectangle-stack',
        ],
        [
        'label' => 'Total Pengguna',
        'value' => $this->getTotalUsers() ?? 0,
        'desc' => 'Total pengguna tersedia',
        'icon' => 'heroicon-o-users',
        ],
        ];
        @endphp

        @foreach($stats as $stat)
        <div class="relative p-6 rounded-xl border text-center shadow-sm transition-all duration-300 group
                            bg-white border-gray-200 hover:shadow-lg
                            dark:bg-gray-800 dark:border-gray-700">

            <div class="absolute top-4 right-4 p-2 rounded-lg bg-gray-100 dark:bg-gray-700 z-10">
                <x-filament::icon icon="{{ $stat['icon'] }}" class="h-5 w-5 text-gray-900 dark:text-white" />
            </div>

            <div
                class="text-3xl font-bold text-gray-900 dark:text-white mb-2 transition-transform group-hover:scale-105">
                {{ $stat['value'] }}
            </div>

            <div class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide">
                {{ $stat['label'] }}
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ $stat['desc'] }}
            </div>
        </div>
        @endforeach
    </div>
    @endcan

    <!-- Articles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-4">
        @php
        $articlesData = [];
        @endphp
        @can('ringkasan_aktivitas_admin')
        @php
        $articlesData[] = [
        'title' => 'Aktivitas Terbaru',
        'color' => 'blue',
        'icon' => 'heroicon-o-clock',
        'activities' => $this->getRecentActivities(), // method baru: log aktivitas admin (tambah/edit artikel, dll)
        ];
        @endphp
        @endcan
        @can('statistik_dashboard_admin')
        @php
        $articlesData[] = [
        'title' => 'Statistik Artikel',
        'color' => 'orange',
        'icon' => 'heroicon-o-chart-bar',
        'articles' => $this->getTopArticles(),
        ];
        @endphp
        @endcan

        @foreach($articlesData as $data)
        <div
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm transition-all duration-300 group hover:shadow-lg">
            <!-- Header -->
            <div
                class="px-6 py-4 border-b bg-{{ $data['color'] }}-500 dark:bg-{{ $data['color'] }}-600 border-{{ $data['color'] }}-600 dark:border-{{ $data['color'] }}-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="{{ $data['icon'] }}" class="h-5 w-5" />
                    {{ $data['title'] }}
                </h3>
                @if(isset($data['articles']))
                <a href="{{ route('filament.admin.resources.articles.index') }}"
                    class="text-sm font-medium text-{{ $data['color'] }}-100 dark:text-{{ $data['color'] }}-200 hover:text-white transition-colors flex items-center gap-1">
                    Lihat Semua
                    <x-filament::icon icon="heroicon-o-arrow-right" class="h-4 w-4" />
                </a>
                @endif
            </div>

            <!-- Content -->
            <div class="p-6 space-y-4">
                @if(isset($data['articles']))
                @foreach($data['articles'] as $index => $article)
                <div
                    class="flex items-start gap-3 p-2 rounded-lg border-b border-gray-200 dark:border-gray-600 transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800 last:border-0 last:pb-0">
                    <div class="flex-1 min-w-0">
                        <h4
                            class="font-semibold text-sm leading-6 text-gray-900 dark:text-white transition-colors duration-200 hover:text-{{ $data['color'] }}-600 dark:hover:text-{{ $data['color'] }}-400">
                            <a href="{{ ArticleResource::getUrl('view', ['record' => $article]) }}" wire-navigate>
                                {{ $article->title }}
                            </a>
                        </h4>
                        <div class="flex justify-between items-center mt-2 text-xs text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-user" class="h-3 w-3" />
                                <span class="dark:text-gray-300">{{ $article->author->name ?? 'Admin' }}</span>
                                <x-filament::icon icon="heroicon-o-calendar" class="h-3 w-3 ml-2" />
                                <span class="dark:text-gray-300">{{ $article->updated_at->format('M d, Y') }}</span>
                            </div>
                            <div
                                class="flex items-center gap-1 font-medium text-{{ $data['color'] }}-600 dark:text-{{ $data['color'] }}-400">
                                <x-filament::icon
                                    icon="{{ $data['color'] == 'orange' ? 'heroicon-o-fire' : 'heroicon-o-eye' }}"
                                    class="h-3 w-3" />
                                <span>{{ $article->views }} views</span>
                            </div>
                        </div>
                        @if($article->category)
                        <div class="flex items-center mt-2">
                            <span
                                class="inline-flex items-center gap-1 border rounded-md text-xs px-2 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border-primary-200 dark:border-primary-800 shadow-sm">
                                <x-filament::icon icon="heroicon-m-rectangle-stack" class="w-4 h-4 text-primary-500" />
                                {{ $article->category->name }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
                @elseif(isset($data['activities']))
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($data['activities'] as $index => $activity)
                    <div
                        class="flex items-center gap-3 py-3 px-2 group hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-all last:border-0">
                        <div class="flex-shrink-0">
                            <span
                                class="inline-flex items-center justify-center rounded-full h-9 w-9 bg-blue-100 dark:bg-blue-900/30">
                                <x-filament::icon icon="heroicon-o-clipboard-document-list"
                                    class="h-5 w-5 text-blue-600" />
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-x-2 text-sm">
                                <span class="font-semibold text-blue-700 dark:text-blue-300">{{ $activity->user->name ??
                                    'Admin' }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ __($activity->action) }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $activity->subject_type
                                    }}</span>
                                @if($activity->description)
                                <span class="text-gray-500 dark:text-gray-400">- {{ $activity->description }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-1">
                                <x-filament::icon icon="heroicon-o-calendar" class="h-3 w-3" />
                                {{ $activity->created_at->format('d M Y H:i') }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</x-filament-widgets::widget>