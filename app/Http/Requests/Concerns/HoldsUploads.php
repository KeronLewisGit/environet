<?php

namespace App\Http\Requests\Concerns;

use App\Support\HeldUploads;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * For public forms with file inputs: when the form comes back with a validation error, the
 * files that passed are held (App\Support\HeldUploads) and used again if the visitor leaves
 * the input empty next time, so nobody has to attach their certificates or CV twice.
 *
 * @mixin FormRequest
 */
trait HoldsUploads
{
    /** The form's name in the session, e.g. "solicitation". */
    abstract public static function heldForm(): string;

    /**
     * File fields: dot key => whether it takes several files ("files" => true, "cv" => false).
     *
     * @return array<string, bool>
     */
    abstract protected function heldFields(): array;

    /** Call first in prepareForValidation(): put held files back where the input was left empty. */
    protected function restoreHeldUploads(): void
    {
        $files = $this->files->all();
        $changed = false;
        foreach ($this->heldFields() as $field => $multiple) {
            $sent = data_get($files, $field);
            if ($sent instanceof UploadedFile || (is_array($sent) && array_filter($sent) !== [])) {
                continue; // a new choice replaces what was held
            }
            $held = HeldUploads::files(static::heldForm(), $field);
            if ($held !== []) {
                data_set($files, $field, $multiple ? $held : $held[0]);
                $changed = true;
            }
        }
        if ($changed) {
            $this->files->replace($files);
            $this->convertedFiles = null;
        }
    }

    /** Hold every file that passed validation, then redirect back as usual. */
    #[\Override]
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();
        foreach ($this->heldFields() as $field => $multiple) {
            $value = $this->file($field);
            if ($multiple) {
                $ok = $errors->has($field) ? [] : array_values(array_filter(
                    is_array($value) ? $value : [],
                    fn ($f, $i) => ! $errors->has($field.'.'.$i), // anything not a file failed `files.*` already
                    ARRAY_FILTER_USE_BOTH,
                ));
            } else {
                $ok = $value instanceof UploadedFile && ! $errors->has($field) ? [$value] : [];
            }
            HeldUploads::hold(static::heldForm(), $field, $ok);
        }

        parent::failedValidation($validator);
    }

    public function releaseHeldUploads(): void
    {
        HeldUploads::release(static::heldForm());
    }
}
