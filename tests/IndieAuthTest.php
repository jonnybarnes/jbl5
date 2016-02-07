<?php

namespace App\Tests;

use TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IndieAuthTest extends TestCase
{
    protected $appurl;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
    }

    /**
     * Test the beginauth method redirects to the relevant endpoint.
     *
     * @return void
     */
    public function testServiceDiscoversEndpoint()
    {
        $client = \Mockery::mock(\IndieAuth\Client::class)->shouldReceive('discoverAuthorizationEndpoint')
                    ->with($this->appurl)->andReturn('https://indieauth.com/auth')->getMock();
        $service = new \App\Services\IndieAuthService();
        $result = $service->getAuthorizationEndpoint($this->appurl, $client);
        $this->assertSame('https://indieauth.com/auth', $result);
    }
}
