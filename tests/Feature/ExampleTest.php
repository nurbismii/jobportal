<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_login_page_renders_for_guests()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
