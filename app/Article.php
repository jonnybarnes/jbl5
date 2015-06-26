<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use MartinBean\Database\Eloquent\Sluggable;

class Article extends Model
{
    /**
     * We want to turn the titles into slugs
     */
    use Sluggable;
    const DISPLAY_NAME = 'title';
    const SLUG = 'titleurl';

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
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array('deleted');

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
     * Find an article by its slug
     *
     * @param  string
     * @return \App\Article $article
     */
    public static function findBySlug($slug)
    {
        $article = DB::select("select * from $this->table where titleurl = ?", array($slug));
        if (count($article == 0)) {
            return null;
        }
        return $article;
    }
}
