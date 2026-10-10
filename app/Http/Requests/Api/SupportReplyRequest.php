<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/** Same rules as SupportComponent::sendTicketReply(). */
class SupportReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'বার্তা লিখুন!',
            'message.min'      => 'বার্তা অন্তত ২ অক্ষর লিখুন!',
            'message.max'      => 'বার্তা সর্বোচ্চ ১০০০ অক্ষরের হতে পারে!',
        ];
    }
}
