<?php

namespace App;

use Normalizer;
use Illuminate\Database\Eloquent\Model;
use Jonnybarnes\UnicodeTools\UnicodeTools;
use League\CommonMark\CommonMarkConverter;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMedia;

class Note extends Model implements HasMedia
{
    use SoftDeletes;
    use HasMediaTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'notes';

    /**
     * Define the relationship with tags.
     *
     * @var array
     */
    public function tags()
    {
        return $this->belongsToMany('App\Tag');
    }

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
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * A mutator to ensure that in-reply-to is always non-empty or null.
     *
     * @param  string  value
     * @return string
     */
    public function setInReplyToAttribute($value)
    {
        $this->attributes['in_reply_to'] = empty($value) ? null : $value;
    }

    /**
     * Normalize the note to Unicode FORM C.
     *
     * @param  string  $value
     * @return string
     */
    public function setNoteAttribute($value)
    {
        $this->attributes['note'] = normalizer_normalize($value, Normalizer::FORM_C);
    }

    /**
     * Pre-process notes for web-view.
     *
     * @param  string
     * @return string
     */
    public function getNoteAttribute($value)
    {
        $unicode = new UnicodeTools();
        $codepoints = $unicode->convertUnicodeCodepoints($value);
        $markdown = new CommonMarkConverter();
        $transformed = $markdown->convertToHtml($codepoints);

        return $transformed;
    }
}
