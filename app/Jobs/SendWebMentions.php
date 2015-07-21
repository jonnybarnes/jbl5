<?php

namespace App\Jobs;

use App\Jobs\Job;
use GuzzleHttp\Client;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWebMentions extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $url;
    protected $source;
    protected $client;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($url, $source)
    {
        $this->url = $url;
        $this->source = $source;
        $this->client = new Client();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $endpoint = $this->discoverWebmentionEndpoint($this->url);
        if ($endpoint) {
            $this->client->post($endpoint, [
                'form_params' => [
                    'source' => $this->source,
                    'target' => $this->url,
                ]
            ]);
        }
    }

    /**
     * Discover if a URL has a webmention endpoint
     *
     * @param  string  The URL
     * @return string  The webmention endpoint URL
     */
    private function discoverWebmentionEndpoint($url)
    {
        $endpoint = null;

        $response = $this->client->get($url);
        //check HTTP Headers for webmention endpoint
        $links = \GuzzleHttp\Psr7\parse_header($response->getHeader('Link'));
        foreach ($links as $link) {
            if ($link['rel'] == 'webmention') {
                return trim($link[0], '<>');
            }
        }

        //failed to find a header so parse HTML
        $html = (string) $response->getBody();

        $mf2 = new \Mf2\Parser($html, $url);
        $rels = $mf2->parseRelsAndAlternates();
        if (array_key_exists('webmention', $rels[0])) {
            $endpoint = $rels[0]['webmention'][0];
        } elseif (array_key_exists('http://webmention.org/', $rels[0])) {
            $endpoint = $rels[0]['http://webmention.org/'][0];
        }
        if ($endpoint) {
            if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
                return $endpoint;
            }
            //it must be a relative url, so resolve with php-mf2
            return $mf2->resolveUrl($endpoint);
        }
        return false;
    }
}
