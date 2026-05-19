<div class="flex items-center gap-0.5 mr-1">
    @foreach(['id' => 'ID', 'en' => 'EN'] as $loc => $label)
        <a
            href="{{ route('locale.switch', $loc) }}"
            title="{{ $loc === 'id' ? 'Bahasa Indonesia' : 'English' }}"
            class="inline-flex items-center justify-center w-8 h-7 text-xs font-semibold rounded
                   transition-colors duration-150
                   {{ app()->getLocale() === $loc
                      ? 'bg-primary-600 text-white shadow-sm'
                      : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700/50' }}"
        >
            {{ $label }}
        </a>
    @endforeach
</div>
