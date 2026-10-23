<?php

namespace App\Http\Controllers\RecruitmentManager;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    private const CANDIDATE_FIELDS = [
        'email' => 'Email',
        'phone' => 'Phone',
        'date_of_birth' => 'Date of birth',
        'address' => 'Address',
        'city' => 'City',
        'country' => 'Country',
        'education_level' => 'Education level',
        'field_of_study' => 'Field of study',
        'years_of_experience' => 'Years of experience',
        'languages' => 'Languages',
        'career_goals' => 'Career goals',
    ];

    private const RECRUITER_FIELDS = [
        'company_name' => 'Company',
        'industry' => 'Industry',
        'company_size' => 'Company size',
        'website' => 'Website',
        'contact_name' => 'Contact name',
        'designation' => 'Designation',
        'email' => 'Email',
        'phone' => 'Phone',
        'roles_hiring' => 'Roles hiring',
        'positions_count' => 'Positions',
        'required_skills' => 'Required skills',
        'preferred_locations' => 'Preferred locations',
        'heard_about' => 'Heard about us via',
        'hiring_timeline' => 'Hiring timeline',
        'additional_notes' => 'Additional notes',
    ];

    private const DOCUMENTS = [
        'qualification_certificate' => 'Qualification certificate',
        'resume' => 'Resume / CV',
        'id_document' => 'ID document',
    ];

    public function index(Request $request): Response
    {
        $type = $request->query('type') === 'recruiter' ? 'recruiter' : 'candidate';

        $inquiries = Inquiry::query()
            ->where('type', $type)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('app/recruitment-manager/inquiries/index', [
            'type' => $type,
            'counts' => [
                'candidate' => Inquiry::query()->where('type', 'candidate')->count(),
                'recruiter' => Inquiry::query()->where('type', 'recruiter')->count(),
            ],
            'inquiries' => [
                'data' => $inquiries->getCollection()->map(fn (Inquiry $inquiry): array => $this->present($inquiry))->all(),
                'links' => $inquiries->linkCollection(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Inquiry $inquiry): array
    {
        $data = $inquiry->data;
        $isCandidate = $inquiry->type === 'candidate';

        $name = $isCandidate
            ? trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''))
            : (string) ($data['company_name'] ?? '');

        $fields = collect($isCandidate ? self::CANDIDATE_FIELDS : self::RECRUITER_FIELDS)
            ->map(fn (string $label, string $key): array => ['label' => $label, 'value' => $data[$key] ?? null])
            ->filter(fn (array $field): bool => filled($field['value']))
            ->values()
            ->all();

        $documents = $isCandidate
            ? collect(self::DOCUMENTS)
                ->filter(fn (string $label, string $key): bool => filled($data[$key] ?? null))
                ->map(fn (string $label, string $key): array => [
                    'label' => $label,
                    'url' => route('recruitment-manager.inquiries.documents', [$inquiry, $key]),
                ])
                ->values()
                ->all()
            : [];

        return [
            'id' => $inquiry->id,
            'type' => $inquiry->type,
            'name' => $name !== '' ? $name : 'Unknown',
            'subtitle' => $isCandidate ? ($data['email'] ?? null) : ($data['contact_name'] ?? null),
            'submitted_at' => $inquiry->created_at?->toIso8601String(),
            'is_new' => $inquiry->created_at?->isAfter(now()->subDays(2)) ?? false,
            'fields' => $fields,
            'documents' => $documents,
        ];
    }
}
