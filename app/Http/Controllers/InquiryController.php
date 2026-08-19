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

        $storedFiles = [];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $storedFiles[$field] = $request->file($field)->store("inquiries/{$type}", 'public');
            }
        }

        $data = collect($validated)
            ->except(array_merge(['type'], $fileFields))
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
}
