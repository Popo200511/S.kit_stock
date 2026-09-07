<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root domain is the public storefront — guests land there, not on
     * the staff login page.
     */
    public function test_the_application_redirects_guests_to_the_shop(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('shop.index'));
    }
}
