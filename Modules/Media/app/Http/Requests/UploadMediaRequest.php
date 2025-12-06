<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxFileSize = (int) settings('max_file_size', 10240);

        $allowedMimes = settings('allowed_mime_types', 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf');
        $mimeTypes = array_map('trim', explode(',', $allowedMimes));

        return [
            'file' => [
                'required',
                'file',
                'max:'.$maxFileSize,
                'mimetypes:'.implode(',', $mimeTypes),
            ],
            'directory' => ['sometimes', 'string', 'max:255'],
            'model_type' => ['sometimes', 'string', 'max:255'],
            'model_id' => ['sometimes', 'string'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxFileSize = (int) settings('max_file_size', 10240);
        $maxFileSizeMB = round($maxFileSize / 1024, 1);

        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.max' => "The file size must not exceed {$maxFileSizeMB}MB.",
            'file.mimetypes' => 'The file type is not allowed. Please upload a supported file format.',
            'directory.string' => 'The directory must be a valid string.',
            'directory.max' => 'The directory name must not exceed 255 characters.',
            'model_type.string' => 'The model type must be a valid string.',
            'model_type.max' => 'The model type must not exceed 255 characters.',
            'model_id.string' => 'The model ID must be a valid UUID.',
        ];
    }
}
