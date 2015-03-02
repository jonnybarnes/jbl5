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
		return $this->belongsToMany('Tag');
	}

	/**
	 * Define the relationship with webmentions
	 *
	 * @var array
	 */
	public function webmentions()
	{
		return $this->morphMany('WebMention', 'commentable');
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

	/**
	 * For pagination we want 5 articles by default
	 *
	 * @var string
	 */
	public $per_page = 5;

}
