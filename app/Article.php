<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model {

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
	 * For pagination we want 5 articles by default
	 *
	 * @var string
	 */
	public $per_page = 5;

	/**
	 * LOOK AT THIS!!!
	 * Find an article by its slug
	 *
	 * @return model
	 */
	public static function findBySlug($slug)
	{
		$article = DB::select("select * from $this->table where titleurl = ?", array($slug));
		if(count($article == 0)) {
			return null;
		} else {
			return $article;
		}
	}

}
