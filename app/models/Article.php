<?php

class Article extends Eloquent {

    use Conner\Tagging\TaggableTrait;
    
    const ARTWORKS_DIR = '/images/clanky/';

    public static $rules = array(
        'name' => 'required',
        'text' => 'required',
        );

    // public function items()
 //    {
 //        return $this->belongsToMany('Item', 'collection_item', 'collection_id', 'item_id');
 //    }

	public function category()
    {
        return $this->belongsTo('Category');
    }

    public function getUrl()
    {
    	return URL::to('clanok/' . $this->attributes['slug']);
    }

    public function getShortTextAttribute($string, $length = 160)
    {
        $string = strip_tags($string);
        $string = $string;
        $string = substr($string, 0, $length);
        return substr($string, 0, strrpos($string, ' ')) . " ...";
    }

    public function getHeaderImage($full=false) {
        $relative_path = self::ARTWORKS_DIR . $this->attributes['main_image'];
        $path = ($full) ? public_path() . $relative_path : $relative_path;
        return $path;
    }

    public function getThumbnailImage($full=false) {
        $preview_image = substr($this->attributes['main_image'], 0, strrpos($this->attributes['main_image'], ".")); //zmaze priponu
        $preview_image .= '.thumbnail.jpg';
        $relative_path = self::ARTWORKS_DIR . $preview_image;
        $full_path = public_path() . $relative_path;
        if (!file_exists($full_path)) {
            Image::make($this->getHeaderImage(true))->fit(600, 250)->save($full_path);
        }
        return $relative_path;
    }

    public function getPublishedDateAttribute($value) {        
        return Carbon::parse($value)->format('d. m. Y'); //Change the format to whichever you desire
    }

    public function getTitleColorAttribute($value) {        
        return (!empty($value)) ? $value : '#fff';
    }

    public function getTitleShadowAttribute($value) {        
        return (!empty($value)) ? $value : '#777';
    }

    public function scopePublished($query)
    {
        return $query->where('publish', '=', 1);
    }

    public function scopePromoted($query)
    {
        return $query->where('promote', '=', 1);
    }

}