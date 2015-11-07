<?php

namespace App;

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
}
