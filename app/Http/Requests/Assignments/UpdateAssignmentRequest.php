<?php

namespace App\Http\Requests\Assignments;

class UpdateAssignmentRequest extends StoreAssignmentRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = $this->assignmentRules();
        unset($rules['batch_id']);

        return $rules;
    }
}
