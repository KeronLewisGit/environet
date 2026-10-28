<?php

namespace App\Http\Requests;

use App\Enums\DocumentKind;
use App\Enums\SetAside;
use App\Enums\VendorType;
use App\Http\Requests\Concerns\HoldsUploads;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
{
    use HoldsUploads;

    public static function heldForm(): string
    {
        return 'teaming';
    }

    protected function heldFields(): array
    {
        return collect(DocumentKind::cases())->mapWithKeys(fn (DocumentKind $k) => ['docs.'.$k->value.'.file' => false])->all();
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
            // UEIs and CAGE codes are written with spaces or in lower case; keep the characters.
            'uei' => format_uei(input_string($this->input('uei'))) ?: null,
            'cage' => format_uei(input_string($this->input('cage'))) ?: null,
        ]);
    }

    public function vendorType(): ?VendorType
    {
        return VendorType::tryFrom(input_string($this->input('type')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = $this->vendorType();
        $required = $type?->requiredDocuments() ?? [DocumentKind::Liability];
        $rules = [
            'type' => ['required', Rule::enum(VendorType::class)],
            'company_name' => ['required', 'string', 'max:160'],
            'trading_as' => ['nullable', 'string', 'max:160'],
            // A SAM Unique Entity ID is 12 letters and digits; teaming partners and subcontractors need one.
            'uei' => [$type?->needsUei() ? 'required' : 'nullable', 'string', 'size:12', 'regex:/^[A-Z0-9]{12}$/'],
            'cage' => ['nullable', 'string', 'size:5', 'regex:/^[A-Z0-9]{5}$/'],
            'naics' => ['nullable', 'string', 'max:200', 'regex:/^\s*\d{6}(?:\s*[,;\s]\s*\d{6})*\s*$/'],
            'set_asides' => ['nullable', 'array'],
            'set_asides.*' => [Rule::in(array_map(fn (SetAside $s) => $s->value, SetAside::declarable()))],
            'year_established' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'address' => ['required', 'string', 'max:240'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:40'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_role' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'trades' => ['required', 'array', 'min:1', 'max:21'],
            'trades.*' => [Rule::in(array_keys((array) enet('trades')))],
            'regions_served' => ['nullable', 'array'],
            'regions_served.*' => [Rule::in((array) enet('regions'))],
            'employees' => ['nullable', Rule::in(array_keys(Vendor::EMPLOYEE_BANDS))],
            'bonding' => ['nullable', 'string', 'max:120'],
            'experience' => ['nullable', 'string', 'max:3000'],
            'message' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
        ];
        foreach (DocumentKind::cases() as $kind) {
            $must = in_array($kind, $required, true);
            $k = 'docs.'.$kind->value;
            $rules[$k.'.file'] = [$must ? 'required' : 'nullable', 'file', 'max:5120', 'mimes:'.implode(',', Vendor::FILE_TYPES)];
            $rules[$k.'.number'] = ['nullable', 'string', 'max:80'];
            $rules[$k.'.expires_on'] = $kind->expires()
                ? [$must ? 'required' : 'nullable', 'required_with:'.$k.'.file', 'date_format:Y-m-d', 'after:today']
                : ['nullable', 'date_format:Y-m-d'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attrs = [
            'company_name' => 'company name',
            'uei' => 'Unique Entity ID (UEI)',
            'cage' => 'CAGE code',
            'naics' => 'NAICS codes',
            'state' => 'state or territory',
            'contact_name' => 'contact name',
            'year_established' => 'year established',
            'trades' => 'type of work',
        ];
        foreach (DocumentKind::cases() as $kind) {
            $attrs['docs.'.$kind->value.'.file'] = $kind->label();
            $attrs['docs.'.$kind->value.'.expires_on'] = $kind->short().' expiry date';
        }

        return $attrs;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $messages = [
            'uei.required' => 'Teaming partners and subcontractors need a SAM Unique Entity ID (UEI). It is free at SAM.gov.',
            'uei.size' => 'A UEI has 12 letters and digits, for example EEWLLJUH4SC9.',
            'uei.regex' => 'A UEI has 12 letters and digits, for example EEWLLJUH4SC9.',
            'cage.size' => 'A CAGE code has 5 letters and digits.',
            'cage.regex' => 'A CAGE code has 5 letters and digits.',
            'naics.regex' => 'List 6-digit NAICS codes separated by commas, for example 562910, 238910.',
            'phone.regex' => 'Enter a phone number with its area code, for example (808) 555-0123.',
            'trades.required' => 'Choose at least one type of work.',
            'consent.accepted' => 'Please confirm the declaration to send your application.',
        ];
        foreach (DocumentKind::cases() as $kind) {
            $k = 'docs.'.$kind->value;
            $messages[$k.'.file.required'] = 'Upload your '.lcfirst($kind->label()).'.';
            $messages[$k.'.file.max'] = 'The '.$kind->short().' file must be 5 MB or smaller.';
            $messages[$k.'.file.mimes'] = 'The '.$kind->short().' must be a PDF, JPG or PNG.';
            $messages[$k.'.file.uploaded'] = 'The '.$kind->short().' could not be uploaded. Files must be 5 MB or smaller.';
            $messages[$k.'.expires_on.after'] = 'This '.$kind->short().' has expired. Upload the current one.';
            $messages[$k.'.expires_on.required'] = 'Enter the date the '.$kind->short().' expires.';
            $messages[$k.'.expires_on.required_with'] = 'Enter the date the '.$kind->short().' expires.';
        }

        return $messages;
    }
}
