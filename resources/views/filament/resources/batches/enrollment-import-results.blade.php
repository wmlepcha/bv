@if ($importResult)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6 mb-6">
        <div class="fi-section-header px-6 py-4 border-b border-gray-200 dark:border-white/10">
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Enrollment import summary
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Enrolled {{ count($importResult['enrolled']) }},
                skipped {{ count($importResult['skipped']) }},
                failed {{ count($importResult['failed']) }}.
            </p>
        </div>

        @if (count($importResult['enrolled']) > 0)
            <div class="px-6 py-4 overflow-x-auto">
                <h4 class="text-sm font-medium text-gray-950 dark:text-white mb-2">Enrolled</h4>
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="py-1 pr-4">Row</th>
                            <th class="py-1 pr-4">Candidate ID</th>
                            <th class="py-1">Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($importResult['enrolled'] as $row)
                            <tr class="border-t border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4">{{ $row['row'] }}</td>
                                <td class="py-2 pr-4 font-mono">{{ $row['candidate_id'] }}</td>
                                <td class="py-2">{{ $row['name'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (count($importResult['failed']) > 0)
            <div class="px-6 py-4 overflow-x-auto border-t border-gray-200 dark:border-white/10">
                <h4 class="text-sm font-medium text-danger-600 dark:text-danger-400 mb-2">Failed</h4>
                <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($importResult['failed'] as $row)
                        <li>
                            Row {{ $row['row'] }}
                            @if ($row['candidate_id'])
                                <span class="font-mono">({{ $row['candidate_id'] }})</span>
                            @endif
                            — {{ $row['errors'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (count($importResult['skipped']) > 0)
            <div class="px-6 py-4 overflow-x-auto border-t border-gray-200 dark:border-white/10">
                <h4 class="text-sm font-medium text-gray-950 dark:text-white mb-2">Skipped</h4>
                <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($importResult['skipped'] as $row)
                        <li>
                            Row {{ $row['row'] }}
                            <span class="font-mono">({{ $row['candidate_id'] }})</span>
                            — {{ $row['reason'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
