<?php

namespace App\Livewire\Concerns;

/**
 * Used by the carts when a customer tries to order without any saved address.
 *
 * The customer is sent to the "My Addresses" page instead of a pop-up. A plain
 * page load is used (not wire:navigate) so the map on that page starts clean.
 * The message is shown there as a toast by the customer layout.
 */
trait RedirectsToAddressPage
{
    protected function redirectToAddressPage(): void
    {
        session()->flash('info', 'অর্ডার করতে আগে একটি ডেলিভারি ঠিকানা যোগ করুন।');

        $this->redirect(route('customer.addresses'));
    }
}
