<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInquiryRequest;
use App\Models\Inquiry;
use App\Notifications\InquirySubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class InquiryController extends Controller
{
    public function store(StoreInquiryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $type = $validated['type'];

        $fileFields = $type === 'candidate'
            ? ['qualification_certificate', 'resume', 'id_document']
            : [];

        if ($this->isRecentDuplicate($type, $validated['email'])) {
            Inertia::flash('inquirySubmitted', true);

            return back();
        }

        $storedFiles = [];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $storedFiles[$field] = $request->file($field)->store("inquiries/{$type}", 'local');
            }
        }

        $data = collect($validated)
            ->except(array_merge(['type', 'fax_number', 'cf-turnstile-response'], $fileFields))
            ->merge($storedFiles)
            ->toArray();

        $inquiry = Inquiry::create([
            'type' => $type,
            'data' => $data,
        ]);

        Notification::route('mail', config('mail.ops_address'))
            ->notify(new InquirySubmitted($inquiry));

        Inertia::flash('inquirySubmitted', true);

        return back();
    }

    /**
     * Same email + type within the last hour is treated as an accidental or automated resubmission.
     */
    private function isRecentDuplicate(string $type, string $email): bool
    {
        return Inquiry::query()
            ->where('type', $type)
            ->where('data->email', $email)
            ->where('created_at', '>=', now()->subHour())
            ->exists();
    }
}
