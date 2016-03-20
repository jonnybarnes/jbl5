<?php

namespace App\Tests;

use TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MicropubClientTest extends TestCase
{
    protected $appurl;
    protected $controller;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
        $this->controller = new \App\Http\Controllers\MicropubClientController();
    }

    /**
     * Test the client gets shown for an unauthorised request.
     *
     * @return void
     */
    public function testClientPageUnauthorised()
    {
        $this->visit($this->appurl . '/notes/new')
             ->see('IndieAuth');
    }

    public function testClientPageRecentAuth()
    {
        $this->withSession(['me' => 'https://example.com'])
             ->visit($this->appurl . '/notes/new')
             ->see('https://example.com');
    }
}
