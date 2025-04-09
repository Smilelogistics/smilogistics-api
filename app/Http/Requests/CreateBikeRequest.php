<?php

namespace App\Http\Requests;

use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateBikeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //'customer_id' => 'nullable|exists:customers,id',
            'user_id' => 'nullable|exists:users,id',
            'branch_id' => 'nullable|exists:branches,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'bike_type' => 'nullable|string|max:255',
            'bike_number' => 'nullable|numeric',
            'bike_office' => 'nullable|string|max:255',
            'make_model' => 'nullable|string|max:255',
            'license_plate_number' => 'nullable|string|max:255',

            'file_path' => [
                'nullable',
                // Allow single file or array
                function ($attribute, $value, $fail) {
                    if (is_array($value)) {
                        foreach ($value as $file) {
                            if (!($file instanceof UploadedFile)) {
                                $fail("$attribute must be a file.");
                                return;
                            }
                        }
                    } elseif (!($value instanceof \Illuminate\Http\UploadedFile)) {
                        $fail("$attribute must be a file.");
                    }
                },
            ],
            'file_path.*' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:150',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }
}
