<?php

namespace App\Http\Requests;

use App\Enums\SetAside;
use App\Enums\SolicitationType;
use App\Http\Requests\Concerns\HoldsUploads;
use App\Models\Solicitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSolicitationRequest extends FormRequest
{
    use HoldsUploads;

    public static function heldForm(): string
    {
        return 'solicitation';
    }

    protected function heldFields(): array
    {
        return ['files' => true];
    }

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->restoreHeldUploads();
        $this->merge([
            'email' => strtolower(input_string($this->input('email'))),
            'bond_required' => $this->boolean('bond_required'),
            'naics' => preg_replace('/\D+/', '', input_string($this->input('naics'))) ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source' => ['required', Rule::in(array_keys(Solicitation::SOURCES))],
            'organization' => ['required', 'string', 'max:160'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_role' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'title' => ['required', 'string', 'max:200'],
            'solicitation_number' => ['nullable', 'string', 'max:80'],
            'type' => ['required', Rule::enum(SolicitationType::class)],
            'set_aside' => ['nullable', Rule::enum(SetAside::class)],
            'naics' => ['nullable', 'digits:6'],
            'region' => ['nullable', Rule::in((array) enet('regions'))],
            'location' => ['nullable', 'string', 'max:160'],
            'scope' => ['required', 'string', 'max:5000'],
            'estimated_value' => ['nullable', 'string', 'max:80'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'due_time' => ['required', 'date_format:H:i'],
            'questions_due_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:due_date'],
            'site_visit_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:due_date'],
            'site_visit_time' => ['nullable', 'date_format:H:i'],
            'bond_required' => ['boolean'],
            'documents_url' => ['nullable', 'url:https,http', 'max:300'],
            'files' => ['nullable', 'array', 'max:'.Solicitation::MAX_FILES],
            'files.*' => ['file', 'max:10240', 'mimes:'.implode(',', Solicitation::FILE_TYPES)],
            'message' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $v): void {
            if ($v->errors()->hasAny(['due_date', 'due_time'])) {
                return;
            }
            if ($this->dueAt()->isPast()) {
                $v->errors()->add('due_time', 'That response deadline has already passed (times are Hawaiʻi time, HST).');
            }
        }];
    }

    public function dueAt(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', input_string($this->input('due_date')).' '.input_string($this->input('due_time')), config('app.timezone')) ?: now();
    }

    public function siteVisitAt(): ?Carbon
    {
        $date = input_string($this->input('site_visit_date'));
        if ($date === '') {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i', $date.' '.(input_string($this->input('site_visit_time')) ?: '10:00'), config('app.timezone')) ?: null;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'organization' => 'agency or company',
            'contact_name' => 'contact name',
            'title' => 'project or solicitation title',
            'type' => 'type of request',
            'scope' => 'scope of work',
            'due_date' => 'response date',
            'due_time' => 'response time',
            'questions_due_on' => 'questions deadline',
            'naics' => 'NAICS code',
            'site_visit_date' => 'site visit date',
            'documents_url' => 'link to the documents',
            'files.*' => 'document',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a phone number with its area code, for example (808) 555-0123.',
            'files.max' => 'Attach up to '.Solicitation::MAX_FILES.' files, or send a link to the documents instead.',
            'files.*.max' => 'Each document must be 10 MB or smaller. For larger sets, paste a link (for example to SAM.gov or PIEE) instead.',
            'files.*.mimes' => 'Documents can be PDF, Word, Excel or ZIP files.',
            'files.*.uploaded' => 'A document could not be uploaded. Files must be 10 MB or smaller.',
            'due_date.after_or_equal' => 'The response date cannot be in the past.',
            'source.required' => 'Tell us who is sending the request.',
            'type.required' => 'Choose the type of request.',
            'naics.digits' => 'A NAICS code has 6 digits, for example 562910.',
        ];
    }
}
