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
     * Test the getAuthorizationEndpoint calls the correct service methods,
     * though these methods are actually mocked.
     *
     * @return void
     */
    public function testIndieAuthServiceDiscoversEndpoint()
    {
        $client = \Mockery::mock(\IndieAuth\Client::class)
                    ->shouldReceive('normalizeMeURL')
                    ->andReturn($this->appurl)
                    ->shouldReceive('discoverAuthorizationEndpoint')
                    ->with($this->appurl)->andReturn('https://indieauth.com/auth')->getMock();
        $service = new \App\Services\IndieAuthService();
        $result = $service->getAuthorizationEndpoint($this->appurl, $client);
        $this->assertSame('https://indieauth.com/auth', $result);
    }

    /**
     * Test that the Service build the correct redirect URL.
     *
     * @return void
     */
    public function testIndieAuthServiceBuildRedirectURL()
    {
        $client = new \IndieAuth\Client();
        $service = new \App\Services\IndieAuthService();
        $result = $service->buildAuthorizationURL(
            'https://indieauth.com/auth',
            $this->appurl,
            $client
        );
        $this->assertSame(
            'https://indieauth.com/auth?me=',
            substr($result, 0, 30)
        );
    }

    /**
     * Now we test the `beginauth` method fails and returns a redirect to the
     * client.
     *
     * @return void
     */
    public function testIndieAuthControllerFailsThenReturnsToClient()
    {
        $response = $this->call('GET', $this->appurl . '/beginauth', ['me' => $this->appurl]);
        var_dump($response->status());
        $this->assertSame($this->appurl . '/notes/new', $response->headers->get('Location'));
    }
}
