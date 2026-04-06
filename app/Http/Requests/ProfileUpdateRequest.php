<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        $shouldSyncSubscriptionPreference = $this->exists('is_subscribed')
            || $this->exists('time_zone')
            || $this->exists('phone')
            || $this->exists('address')
            || $this->exists('blurb')
            || $this->exists('first_name')
            || $this->exists('last_name')
            || $this->hasFile('photo');

        $this->attributes->set('sync_subscription_preference', $shouldSyncSubscriptionPreference);

        $prepared = [];

        if ($this->filled('name') && ! $this->filled('first_name') && ! $this->filled('last_name')) {
            [$firstName, $lastName] = User::splitName($this->string('name')->toString());

            $prepared['first_name'] = $firstName;
            $prepared['last_name'] = $lastName;
        }

        foreach (['first_name', 'last_name', 'email', 'phone', 'address', 'time_zone', 'blurb'] as $field) {
            if (! array_key_exists($field, $prepared) && ! $this->exists($field)) {
                $prepared[$field] = $user->{$field};
            }
        }

        $this->merge($prepared);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email' => [
                'required', 
                'string', 
                'lowercase', 
                'email', 
                'max:255', 
                Rule::unique(User::class)->ignore($this->user()->id)
            ],
            'photo' => ['nullable', 'image', 'max:8192'],
            'blurb' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_subscribed' => ['sometimes', 'boolean'],
            'time_zone' => ['required', 'string', 'timezone'],
        ];
    }
}
