<div class="border-t border-gray-200 dark:border-gray-700">
    <ul class="pb-4 space-y-1 text-sm text-gray-900">
        @foreach ($categories as $cat)
        @include('livewire.partials.cat-node', ['node' => $cat, 'level' => 0])
        @endforeach
    </ul>
</div>