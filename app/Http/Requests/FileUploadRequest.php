<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) (config('sendspace.max_upload_size') / 1024);

        return [
            'upload_file' => ['required_without:hash', 'array'],
            'upload_file.*' => ['file', "max:{$maxKb}"],
            'hash' => ['nullable', 'array'],
            'hash.*' => ['nullable', 'string'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:200'],
            'recpemail' => ['nullable', 'string'],
            'ownemail' => ['nullable', 'string', 'email'],
        ];
    }
}
