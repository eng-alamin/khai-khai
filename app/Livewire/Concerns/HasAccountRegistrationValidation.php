<?php

namespace App\Livewire\Concerns;

trait HasAccountRegistrationValidation
{
    /**
     * Shared account-info rules used by every registration flow.
     *
     * @param  string|null  $phoneUniqueColumn  e.g. 'users,phone'. Defaults
     *         to the users table, but callers can override if they ever
     *         need to validate uniqueness against a different table.
     */
    protected function accountRegistrationRules(?string $phoneUniqueColumn = 'users,phone'): array
    {
        return [
            'name'                  => 'required|string|min:3|max:100',
            'email'                 => 'required|email|max:150|unique:users,email',
            'phone'                 => "nullable|string|regex:/^01[3-9]\\d{8}$/|unique:{$phoneUniqueColumn}",
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ];
    }

    /**
     * Shared account-info validation messages, matching the rules above.
     */
    protected function accountRegistrationMessages(): array
    {
        return [
            'name.required'       => 'Name is required.',
            'email.required'      => 'Email address is required.',
            'email.email'         => 'Please enter a valid email address.',
            'email.unique'        => 'This email is already in use.',
            'phone.regex'         => 'Please enter a valid Bangladeshi mobile number (01XXXXXXXXX).',
            'phone.unique'        => 'This mobile number is already registered.',
            'password.min'        => 'Password must be at least 8 characters.',
            'password.confirmed'  => 'Passwords do not match.',
        ];
    }
}