<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Note extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'notes';

	/**
	 * Define the relationship with tags
	 *
	 * @var array
	 */
	public function tags()
	{
		return $this->belongsToMany('App\Tag');
	}

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
	 * We shall set a blacklist of non-modifiable model attributes
	 *
	 * @var array
	 */
	protected $guarded = array('id');

}
