<?php

namespace App\Livewire\Concerns;

trait HasCatalogItemValidationRules
{
    protected function catalogItemRules(): array
    {
        return [
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'price'       => 'required|integer|min:1|max:100000',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order'  => 'required|integer|min:0',
        ];
    }

    protected function catalogItemMessages(): array
    {
        return [
            'name.required'  => 'Please enter a name.',
            'name.max'       => 'Name must not exceed 120 characters.',
            'price.required' => 'Please enter a price.',
            'price.min'      => 'Price must be at least ৳1.',
            'image.max'      => 'Image must not exceed 2 MB.',
        ];
    }
}