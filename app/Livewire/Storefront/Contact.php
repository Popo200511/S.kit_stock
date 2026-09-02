<?php

namespace App\Livewire\Storefront;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Contact extends Component
{
    public function render()
    {
        return view('livewire.storefront.contact')->layout('components.layouts.storefront', [
            'title' => 'ติดต่อ/ที่ตั้งร้าน · '.config('shop.name'),
            'description' => 'ช่องทางติดต่อและที่ตั้งร้าน '.config('shop.name'),
            'canonical' => route('shop.contact'),
        ]);
    }
}
