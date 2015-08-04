<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['client_url', 'client_name'];

    /**
     * Get the clinet name for an micropub posted note, either the human
     * readable name, or a parsed form of the URL.
     *
     * @param  string  client URL
     * @return string  client name
     */
    public function getClientName($clientURL)
    {
        $clientName = DB::table('clients')->where('client_url', $clientURL)->pluck('client_name');
        if ($clientName) {
            return $clientName;
        }
        $url = parse_url($clientURL);
        if (isset($url['path'])) {
            return $url['host'] . $url['path'];
        }

        return $url['host'];
    }
}
