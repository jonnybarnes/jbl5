<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Phaza\LaravelPostgis\Geometries\Point;
use Phaza\LaravelPostgis\Geometries\Polygon;
use Phaza\LaravelPostgis\Eloquent\PostgisTrait;

class Place extends Model
{
    use PostgisTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * The attributes that are Postgis geometry objects.
     *
     * @var array
     */
    protected $postgisFields = [Point::class, Polygon::class];

    /**
     * Get all places within a specified distance.
     *
     * @param  float latitude
     * @param  float longitude
     * @param  int maximum distance
     * @todo Check this shit.
     */
    public static function near(float $lat, float $lng, int $distance)
    {
        $point = $lng . ' ' . $lat;
        $distace = $distance ?? 1000;
        $places = DB::select(DB::raw("select
            name,
            ST_AsText(location) AS location,
            ST_Distance(
                ST_GeogFromText('SRID=4326;POINT($point)'),
                location
            ) AS distance
        from places
        where ST_DWithin(
            ST_GeogFromText('SRID=4326;POINT($point)'),
            location,
            $distance
        ) ORDER BY distance"));
        return collect($places);
    }

    /**
     * Convert location to text.
     *
     * @param  text $value
     * @return text
     */
    public function getLocationAttribute($value)
    {
        $result = DB::select(DB::raw("SELECT ST_AsText('$value')"));

        return $result[0]->st_astext;
    }
}
