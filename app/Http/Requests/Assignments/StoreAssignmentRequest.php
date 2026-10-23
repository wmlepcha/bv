<?php

namespace App\Http\Requests\Assignments;

use App\Enums\AssignmentQuestionType;
use App\Enums\AssignmentTargetScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isTeacher() || $user->isTrainingManager());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->assignmentRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function assignmentRules(): array
    {
        $questionRules = [
            'questions.*.type' => ['required', Rule::enum(AssignmentQuestionType::class)],
            'questions.*.prompt' => ['required', 'string', 'max:5000'],
            'questions.*.points' => ['nullable', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['required_if:questions.*.type,mcq', 'array', 'min:2'],
            'questions.*.options.*' => ['string', 'max:500'],
            'questions.*.correct_index' => ['required_if:questions.*.type,mcq', 'integer', 'min:0'],
            'questions.*.template' => ['required_if:questions.*.type,fill_in_blank', 'string', 'max:5000'],
            'questions.*.hints' => ['nullable', 'array'],
            'questions.*.hints.*' => ['string', 'max:200'],
            'questions.*.model_answers' => ['nullable', 'array'],
            'questions.*.model_answers.*' => ['array'],
            'questions.*.model_answers.*.*' => ['string', 'max:200'],
            'questions.*.tokens' => ['required_if:questions.*.type,word_reorder', 'array', 'min:2'],
            'questions.*.tokens.*' => ['string', 'max:200'],
            'questions.*.model_sentence' => ['nullable', 'string', 'max:5000'],
            'questions.*.url' => ['required_if:questions.*.type,link', 'nullable', 'url', 'max:2000'],
            'questions.*.media' => ['nullable', 'file', 'max:20480'],
            'questions.*.existing_media_path' => ['nullable', 'string', 'max:500'],
            'questions.*.media_original_name' => ['nullable', 'string', 'max:255'],
        ];

        $sectionQuestionRules = [];
        foreach ($questionRules as $key => $rules) {
            $sectionQuestionRules[str_replace('questions.', 'sections.*.questions.', $key)] = $rules;
        }

        return [
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
            'target_type' => ['required', Rule::enum(AssignmentTargetScope::class)],
            'student_ids' => ['required_if:target_type,student', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
            'sections' => ['required_without:questions', 'array', 'min:1'],
            'sections.*.label' => ['required', 'string', 'max:40'],
            'sections.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.instructions' => ['nullable', 'string', 'max:5000'],
            'sections.*.questions' => ['required', 'array', 'min:1'],
            'questions' => ['required_without:sections', 'array', 'min:1'],
            ...$questionRules,
            ...$sectionQuestionRules,
            'audio' => ['nullable', 'file', 'max:20480', 'mimes:mp3,wav,m4a,ogg'],
            'remove_audio' => ['nullable', 'boolean'],
            'publish' => ['nullable', 'boolean'],
            'sections_json' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'audio.max' => 'Audio must be 20MB or smaller.',
            'audio.mimes' => 'Audio must be an MP3, WAV, M4A, or OGG file.',
            'questions.*.media.max' => 'PDF and audio files must be 20MB or smaller.',
            'sections.*.questions.*.media.max' => 'PDF and audio files must be 20MB or smaller.',
            'questions.*.options.array' => 'Multiple-choice questions need at least two answer options.',
            'questions.*.options.min' => 'Multiple-choice questions need at least two answer options.',
            'questions.*.options.required_if' => 'Multiple-choice questions need at least two answer options.',
            'sections.*.questions.*.options.array' => 'Multiple-choice questions need at least two answer options.',
            'sections.*.questions.*.options.min' => 'Multiple-choice questions need at least two answer options.',
            'sections.*.questions.*.options.required_if' => 'Multiple-choice questions need at least two answer options.',
            'questions.*.options.*.string' => 'Option text is invalid.',
            'sections.*.questions.*.options.*.string' => 'Option text is invalid.',
            'questions.*.tokens.array' => 'Word-reorder items need at least two words.',
            'questions.*.tokens.min' => 'Word-reorder items need at least two words.',
            'questions.*.tokens.required_if' => 'Word-reorder items need at least two words.',
            'sections.*.questions.*.tokens.array' => 'Word-reorder items need at least two words.',
            'sections.*.questions.*.tokens.min' => 'Word-reorder items need at least two words.',
            'sections.*.questions.*.tokens.required_if' => 'Word-reorder items need at least two words.',
            'student_ids.array' => 'Select at least one student.',
            'student_ids.required_if' => 'Select at least one student.',
            'sections.array' => 'Add at least one worksheet section.',
            'sections.required_without' => 'Add at least one worksheet section.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateMediaItems($validator, $this->input('sections'), 'sections');
            $this->validateMediaItems($validator, $this->input('questions'), 'questions');
        });
    }

    private function validateMediaItems(Validator $validator, mixed $items, string $prefix): void
    {
        if (! is_array($items)) {
            return;
        }

        if ($prefix === 'sections') {
            foreach ($items as $sectionIndex => $section) {
                if (! is_array($section)) {
                    continue;
                }

                $questions = $section['questions'] ?? null;

                if (! is_array($questions)) {
                    continue;
                }

                foreach ($questions as $questionIndex => $question) {
                    $this->validateSingleMediaItem(
                        $validator,
                        $question,
                        "sections.{$sectionIndex}.questions.{$questionIndex}",
                    );
                }
            }

            return;
        }

        foreach ($items as $questionIndex => $question) {
            $this->validateSingleMediaItem(
                $validator,
                $question,
                "questions.{$questionIndex}",
            );
        }
    }

    private function validateSingleMediaItem(Validator $validator, mixed $question, string $path): void
    {
        if (! is_array($question)) {
            return;
        }

        $type = $question['type'] ?? null;

        if ($type === AssignmentQuestionType::Link->value) {
            $url = trim((string) ($question['url'] ?? ''));

            if ($url === '') {
                $validator->errors()->add("{$path}.url", 'A link URL is required.');
            }

            return;
        }

        $mediaTypes = [
            AssignmentQuestionType::Pdf->value,
            AssignmentQuestionType::Image->value,
            AssignmentQuestionType::Audio->value,
        ];

        if (! in_array($type, $mediaTypes, true)) {
            return;
        }

        $hasUpload = $this->hasFile("{$path}.media");
        $existingPath = $question['existing_media_path'] ?? null;
        $hasExisting = is_string($existingPath) && $existingPath !== '';

        if (! $hasUpload && ! $hasExisting) {
            $validator->errors()->add("{$path}.media", 'Upload a file for this item.');
        }

        if (! $hasUpload) {
            return;
        }

        $file = $this->file("{$path}.media");
        $extension = strtolower((string) $file?->getClientOriginalExtension());

        $allowed = match ($type) {
            AssignmentQuestionType::Pdf->value => ['pdf'],
            AssignmentQuestionType::Image->value => ['jpg', 'jpeg', 'png', 'webp'],
            AssignmentQuestionType::Audio->value => ['mp3', 'wav', 'm4a', 'ogg'],
            default => [],
        };

        if ($extension === '' || ! in_array($extension, $allowed, true)) {
            $validator->errors()->add("{$path}.media", match ($type) {
                AssignmentQuestionType::Pdf->value => 'Only PDF files are allowed.',
                AssignmentQuestionType::Image->value => 'Only JPG, PNG, and WEBP images are allowed.',
                AssignmentQuestionType::Audio->value => 'Only MP3, WAV, M4A, and OGG audio files are allowed.',
                default => 'Invalid media file type.',
            });
        }

        $maxKb = match ($type) {
            AssignmentQuestionType::Image->value => 10240,
            default => 20480,
        };

        if ($file !== null && $file->getSize() > $maxKb * 1024) {
            $validator->errors()->add(
                "{$path}.media",
                $type === AssignmentQuestionType::Image->value
                    ? 'Images must be 10MB or smaller.'
                    : 'Files must be 20MB or smaller.',
            );
        }
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('sections_json') && is_string($this->input('sections_json'))) {
            $decoded = json_decode($this->string('sections_json')->toString(), true);

            if (is_array($decoded)) {
                $this->merge([
                    'sections' => $decoded,
                ]);
            }
        }

        if (! $this->has('student_ids')) {
            $this->merge(['student_ids' => []]);
        }

        if ($this->has('publish')) {
            $this->merge([
                'publish' => $this->boolean('publish'),
            ]);
        }

        if ($this->has('sections') && is_array($this->input('sections'))) {
            $payload = $this->all();
            unset($payload['questions'], $payload['sections_json']);
            $this->replace($payload);
        }
    }

    /**
     * Validated assignment data with nested question media UploadedFile instances merged in.
     *
     * @return array<string, mixed>
     */
    public function assignmentPayload(): array
    {
        $data = $this->validated();
        unset($data['sections_json']);

        if (isset($data['sections']) && is_array($data['sections'])) {
            foreach ($data['sections'] as $sectionIndex => $section) {
                if (! is_array($section) || ! isset($section['questions']) || ! is_array($section['questions'])) {
                    continue;
                }

                foreach ($section['questions'] as $questionIndex => $question) {
                    $file = $this->file("sections.{$sectionIndex}.questions.{$questionIndex}.media");

                    if ($file !== null) {
                        $data['sections'][$sectionIndex]['questions'][$questionIndex]['media'] = $file;
                    }
                }
            }
        }

        if (isset($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as $questionIndex => $question) {
                $file = $this->file("questions.{$questionIndex}.media");

                if ($file !== null) {
                    $data['questions'][$questionIndex]['media'] = $file;
                }
            }
        }

        return $data;
    }
}
