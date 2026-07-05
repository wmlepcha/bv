<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInquiryRequest;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
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

        Inquiry::create([
            'type' => $type,
            'data' => $data,
        ]);

        Inertia::flash('inquirySubmitted', true);

        return back();
    }
}
