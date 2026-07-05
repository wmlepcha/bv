@php
    $path = $getState();
    $hasFile = filled($path);
    $title = $title ?? $entry->getLabel();
    $filename = $filename ?? ($hasFile ? \App\Support\InquiryPresenter::fileLabel($path) : null);
    $url = $url ?? ($hasFile ? \App\Support\InquiryPresenter::fileUrl($path) : null);
    $previewType = $previewType ?? ($hasFile ? \App\Support\InquiryPresenter::filePreviewType($path) : null);
    $extension = $extension ?? ($hasFile ? \App\Support\InquiryPresenter::fileExtension($path) : null);
    $downloadUrl = $downloadUrl ?? null;
    $previewId = 'inquiry-document-preview-' . md5($entry->getName() . ($filename ?? 'empty'));
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @if (! $hasFile)
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/5">
            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $title }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ \App\Support\InquiryPresenter::placeholder() }}</p>
        </div>
    @else
        <div
            x-data="{ previewOpen: false }"
            class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5"
        >
            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <div @class([
                        'flex h-11 w-11 shrink-0 items-center justify-center rounded-lg',
                        'bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400' => $extension === 'PDF',
                        'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => in_array($extension, ['DOC', 'DOCX'], true),
                        'bg-info-50 text-info-600 dark:bg-info-500/10 dark:text-info-400' => in_array($extension, ['PNG', 'JPG', 'JPEG', 'WEBP', 'GIF'], true),
                        'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' => ! in_array($extension, ['PDF', 'DOC', 'DOCX', 'PNG', 'JPG', 'JPEG', 'WEBP', 'GIF'], true),
                    ])>
                        @if ($previewType === 'image')
                            <x-filament::icon icon="heroicon-o-photo" class="h-5 w-5" />
                        @elseif ($previewType === 'pdf')
                            <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5" />
                        @else
                            <x-filament::icon icon="heroicon-o-document" class="h-5 w-5" />
                        @endif
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $title }}</p>
                            @if ($extension)
                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                    {{ $extension }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400" title="{{ $filename }}">
                            {{ $filename }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @if ($previewType)
                        <x-filament::button
                            color="gray"
                            size="sm"
                            type="button"
                            x-on:click="previewOpen = ! previewOpen"
                            x-bind:aria-expanded="previewOpen"
                            x-bind:aria-controls="{{ json_encode($previewId) }}"
                            :icon="'heroicon-m-eye'"
                        >
                            <span x-text="previewOpen ? 'Hide preview' : 'View'"></span>
                        </x-filament::button>
                    @else
                        <x-filament::button
                            color="gray"
                            size="sm"
                            type="button"
                            disabled
                            :icon="'heroicon-m-eye-slash'"
                        >
                            Preview unavailable
                        </x-filament::button>
                    @endif

                    <x-filament::button
                        tag="a"
                        :href="$downloadUrl"
                        color="primary"
                        size="sm"
                        :icon="'heroicon-m-arrow-down-tray'"
                    >
                        Download
                    </x-filament::button>
                </div>
            </div>

            @if ($previewType)
                <div
                    id="{{ $previewId }}"
                    x-show="previewOpen"
                    x-collapse
                    x-cloak
                    class="border-t border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-black/20"
                >
                    <div class="p-4">
                        @if ($previewType === 'image')
                            <img
                                src="{{ $url }}"
                                alt="{{ $title }}"
                                class="mx-auto max-h-[28rem] w-auto max-w-full rounded-lg border border-gray-200 object-contain dark:border-white/10"
                            />
                        @elseif ($previewType === 'pdf')
                            <iframe
                                src="{{ $url }}"
                                title="{{ $title }}"
                                class="h-[28rem] w-full rounded-lg border border-gray-200 bg-white dark:border-white/10"
                            ></iframe>
                        @endif
                    </div>
                </div>
            @elseif (in_array($extension, ['DOC', 'DOCX'], true))
                <div class="border-t border-gray-200 px-4 py-3 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                    Word documents can be downloaded and opened locally. In-browser preview is not available for this file type.
                </div>
            @endif
        </div>
    @endif
</x-dynamic-component>
