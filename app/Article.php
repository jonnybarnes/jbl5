<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use MartinBean\Database\Eloquent\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    /*
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
    protected $dates = ['deleted_at'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'articles';

    /**
     * Define the relationship with webmentions.
     *
     * @var array
     */
    public function webmentions()
    {
        return $this->morphMany('App\WebMention', 'commentable');
    }

    /**
     * We shall set a blacklist of non-modifiable model attributes.
     *
     * @var array
     */
    protected $guarded = ['id'];

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
        $start = $year . '-01-01 00:00:00';
        $end = ($year + 1) . '-01-01 00:00:00';
        if (($month !== null) && ($month !== '12')) {
            $start = $year . '-' . $month . '-01 00:00:00';
            $end = $year . '-' . ($month + 1) . '-01 00:00:00';
        }
        if ($month === '12') {
            $start = $year . '-12-01 00:00:00';
            //$end as above
        }

        return $query->where([
            ['updated_at', '>=', $start],
            ['updated_at', '<', $end],
        ]);
    }
}
