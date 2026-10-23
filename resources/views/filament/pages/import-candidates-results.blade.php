@if ($importResult)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6">
        <div class="fi-section-header px-6 py-4 border-b border-gray-200 dark:border-white/10">
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Import summary
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Created {{ count($importResult['created']) }},
                mapped as paid {{ count($importResult['mapped']) }},
                skipped {{ count($importResult['skipped']) }},
                failed {{ count($importResult['failed']) }}.
                Use the header actions to download results or generate registration codes.
            </p>
        </div>

        @if (count($importResult['created']) > 0)
            <div class="px-6 py-4 overflow-x-auto">
                <h4 class="text-sm font-medium text-gray-950 dark:text-white mb-2">Created</h4>
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="py-1 pr-4">Email</th>
                            <th class="py-1 pr-4">Candidate ID</th>
                            <th class="py-1 pr-4">Temp password</th>
                            <th class="py-1">Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($importResult['created'] as $row)
                            <tr class="border-t border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4">{{ $row['email'] }}</td>
                                <td class="py-2 pr-4 font-mono">{{ $row['candidate_id'] }}</td>
                                <td class="py-2 pr-4 font-mono">{{ $row['temp_password'] }}</td>
                                <td class="py-2">{{ $row['paid_on'] ? 'Paid '.$row['paid_on'].', access until '.$row['expires_at'] : 'Unpaid' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (count($importResult['mapped']) > 0)
            <div class="px-6 py-4 overflow-x-auto border-t border-gray-200 dark:border-white/10">
                <h4 class="text-sm font-medium text-gray-950 dark:text-white mb-2">Existing students mapped as paid</h4>
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="py-1 pr-4">Email</th>
                            <th class="py-1 pr-4">Candidate ID</th>
                            <th class="py-1 pr-4">Paid on</th>
                            <th class="py-1 pr-4">Amount</th>
                            <th class="py-1">Access until</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($importResult['mapped'] as $row)
                            <tr class="border-t border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4">{{ $row['email'] }}</td>
                                <td class="py-2 pr-4 font-mono">{{ $row['candidate_id'] }}</td>
                                <td class="py-2 pr-4">{{ $row['paid_on'] }}</td>
                                <td class="py-2 pr-4">₹{{ number_format($row['amount']) }}</td>
                                <td class="py-2">{{ $row['expires_at'] }}</td>
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
                            @if ($row['email']) ({{ $row['email'] }}) @endif
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
                        <li>Row {{ $row['row'] }} ({{ $row['email'] }}) — {{ $row['reason'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif

@if ($activationCodeResult)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6">
        <div class="fi-section-header px-6 py-4 border-b border-gray-200 dark:border-white/10">
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Registration codes
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Distribute these codes so candidates can unlock their dashboard.
            </p>
        </div>
        <div class="px-6 py-4 overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="text-gray-500 dark:text-gray-400">
                        <th class="py-1 pr-4">Email</th>
                        <th class="py-1 pr-4">Candidate ID</th>
                        <th class="py-1">Registration code</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activationCodeResult as $row)
                        <tr class="border-t border-gray-100 dark:border-white/5">
                            <td class="py-2 pr-4">{{ $row['email'] }}</td>
                            <td class="py-2 pr-4 font-mono">{{ $row['candidate_id'] }}</td>
                            <td class="py-2 font-mono">{{ $row['registration_code'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
