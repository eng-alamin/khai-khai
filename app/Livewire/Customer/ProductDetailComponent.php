<?php

namespace App\Livewire\Customer;

use App\Models\Product;
use Livewire\Component;

/**
 * Detail page for an admin product ("Featured Products").
 * Add to cart happens here, not on the home page card.
 */
class ProductDetailComponent extends Component
{
    public int $productId;

    public function mount(string $slug): void
    {
        $product = Product::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $this->productId = $product->id;
    }

    /** The cart (CartComponent) listens for this event and enforces the single-source rule. */
    public function addToCart(): void
    {
        $this->dispatch('add-product-to-cart', id: $this->productId);
    }

    public function render()
    {
        $product = Product::query()
            ->active()
            ->with('category:id,name,emoji')
            ->findOrFail($this->productId);

        return view('livewire.customer.product-detail-component', [
                'product' => $product,
            ])
            ->layout('layouts.customer', [
                'title'           => $product->name . ' | KhaiKhai',
                'breadcrumbTitle' => 'Product',
            ]);
    }
}
