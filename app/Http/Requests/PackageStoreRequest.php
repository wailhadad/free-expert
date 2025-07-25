<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackageStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'term' => 'required',
            'price' => 'required',
            'number_of_service_add' => 'required',
            'number_of_service_featured' => 'required',
            'number_of_form_add' => 'required',
            'live_chat_status' => 'required',
            'qr_builder_status' => 'required',
            'qr_code_save_limit' => 'required_if:qr_builder_status,1',
            'recommended' => 'required',
            'status' => 'required',
            'trial_days' => 'required_if:is_trial,1',
        ];
    }
    public function messages(): array
    {
        return [
            'trial_days.required_if' => 'Trial days is required',
            'qr_code_save_limit.required_if' => 'QR code save limit feild is required'
        ];
    }
}
