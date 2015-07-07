<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use MartinBean\Database\Eloquent\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    /**
     * We want to turn the titles into slugs
     */
    use Sluggable;
    const DISPLAY_NAME = 'title';
    const SLUG = 'titleurl';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'articles';

    /**
     * Define the relationship with webmentions
     *
     * @var array
     */
    public function webmentions()
    {
        return $this->morphMany('App\WebMention', 'commentable');
    }

    /**
     * We aren't using Eloquent timestamps
     *
     * @var string
     */
    public $timestamps = false;

    /**
     * We shall set a blacklist of non-modifiable model attributes
     *
     * @var array
     */
    protected $guarded = array('id');

    /**
     * Scope a query to only include articles from a particular year/month.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDate($query, $year = null, $month = null)
    {
        if ($year == null) {
            return $query;
        }
        $time = $year;
        if ($month !== null) {
            $time .= '-' . $month;
        }
        $time .= '%';
        return $query->where('updated_at', 'like', $time);
    }
}
