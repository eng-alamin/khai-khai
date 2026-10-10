<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/** Same rules as SupportComponent::rules(). */
class SupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'     => ['required', 'in:order,payment,delivery,food_quality,account,refund,other'],
            'order_number' => ['nullable', 'string', 'max:20'],
            'subject'      => ['required', 'string', 'min:3', 'max:150'],
            'message'      => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'সমস্যার ধরন বেছে নিন!',
            'subject.required'  => 'বিষয় লিখুন!',
            'message.min'       => 'বিস্তারিত অন্তত ১০ অক্ষর লিখুন!',
        ];
    }
}
