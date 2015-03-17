<?php
use Fadion\Bouncy\BouncyTrait;

class Authority extends Eloquent {

	use BouncyTrait;

	protected $table = 'authorities';

    const ARTWORKS_DIR = '/images/autori/';
    const ES_TYPE = 'authorities';

	// protected $indexName = 'webumenia';
    protected $typeName = self::ES_TYPE;

	public static $filterable = array(
		'role',
		'nationality',
	);

	protected $fillable = array(
		'id',
		'type',
		'type_organization',
		'name',
		'sex',
		'biography',
		'birth_place',
		'birth_date',
		'birth_year',
		'death_place',
		'death_date',
		'death_year',
	);

	protected $with = array('roles', 'nationalities', 'names');

	protected $guarded = array();

	public static $rules = array(
		'name' => 'required',
		);

	public $incrementing = false;


	// ELASTIC SEARCH INDEX
	
	public static function boot()
	{
	    parent::boot();

	    static::created(function($item)
	    {
	        $item->index();
	    });

	    static::updated(function($item)
	    {
	        $item->index();

	    });

		static::deleting(function($item) {
			$image = $item->getImagePath(true); // fullpath, disable no image
			if ($image) {
				@unlink($image); 
			}
        });	    

	    static::deleted(function($item)
	    {
	        Elastic::delete([
	        	'index' => Config::get('app.elasticsearch.index'),
	        	'type' => self::ES_TYPE,
	        	'id' => $item->id,
        	]);
	    });
	}
	

	public function nationalities()
    {
        return $this->belongsToMany('Nationality')->withPivot('prefered');
    }

	public function roles()
    {
        return $this->hasMany('AuthorityRole');
    }

	public function names()
    {
        return $this->hasMany('AuthorityName');
    }

	public function events()
    {
        return $this->hasMany('AuthorityEvent');
    }

	public function relationships()
    {
        return $this->hasMany('AuthorityRelationship');
    }

	public function items()
    {
        return $this->belongsToMany('Item');
    }

    public function links()
    {
        return $this->morphMany('Link', 'linkable');
    }    

	public function getFormatedNameAttribute()
    {
        return preg_replace('/^([^,]*),\s*(.*)$/', '$2 $1', $this->name);
    }


    public function getPlacesAttribute()
    {
        $places = array_merge([
        	$this->birth_place, 
        	$this->death_place
        	], $this->events->lists('place'));

        return array_values(array_filter(array_unique($places)));
    }    


	public function getImagePath($full=false) {
		
		return self::getImagePathForId($this->id, $this->attributes['has_image'], $this->attributes['sex'], $full);
		// : self::ARTWORKS_DIR . "no-image.jpg";;

	}

	public function getDetailUrl() {
		return URL::to('autor/' . $this->id);
	}

	public static function getImagePathForId($id, $has_image, $sex = 'male', $full = false, $resize = false)
	{
		if (!$has_image && !$full) return self::getNoImage($sex);

		$levels = 1;
	    $dirsPerLevel = 100;

	    $transformedWorkArtID = hashcode((string)$id);
		$workArtIdInt = abs(intval32bits($transformedWorkArtID));
	    $tmpValue = $workArtIdInt;
	    $dirsInLevels = array();

	    $galleryDir = substr($id, 4, 3);

	    for ($i = 0; $i < $levels; $i++) {
	            $dirsInLevels[$i] = $tmpValue % $dirsPerLevel;
	            $tmpValue = $tmpValue / $dirsPerLevel;
	    }

	    $path = implode("/", $dirsInLevels);

		// adresar obrazkov workartu sa bude volat presne ako id, kde je ':' nahradena '_'
		$trans = array(":" => "_", " " => "_");
	    $file = strtr($id, $trans);

	    $relative_path = self::ARTWORKS_DIR . "$galleryDir/$path/$file/";
	    $full_path =  public_path() . $relative_path;

	    // ak priecinky este neexistuju - vytvor ich
	    if ($full && !file_exists($full_path)) {
	    	mkdir($full_path, 0777, true);
	    }

	    // dd($full_path . "$file.jpeg");
	    if ($full) {
	    	$result_path = $full_path . "$file.jpeg";
	    } else {
		    if (file_exists($full_path . "$file.jpeg")) {
		    	$result_path =  $relative_path . "$file.jpeg";

		    	if ($resize) {
		    		if (!file_exists($full_path . "$file.$resize.jpeg")) {
		    			$img = Image::make( $full_path . "$file.jpeg")->fit($resize)->sharpen(7);
		    			$img->save($full_path . "$file.$resize.jpeg");		    			
		    		}
		    		$result_path = $relative_path . "$file.$resize.jpeg";
		    	}
		    } else {
		    	$result_path =  self::ARTWORKS_DIR . "no-image.jpg";
		    }
	    }

		return $result_path;
	}

	private static function getNoImage($sex = 'male') {
		$filename = 'no-image-' . $sex . '.jpeg';
		return self::ARTWORKS_DIR . $filename;
	}

	public  function index() {
		if ($this->attributes['type'] != 'person') {
			return false;
		}
        $client = new Elasticsearch\Client();
        $data = [
        	'identifier' => $this->attributes['id'],
        	'name' => $this->attributes['name'],
        	'alternative_name' => $this->names->lists('name'),
        	'related_name' => $this->relationships->lists('name'),
			'biography' => (!empty($this->attributes['biography'])) ? strip_tags($this->attributes['biography']) : '',	        	
			'nationality' => $this->nationalities->lists('code'),
        	'place' => $this->places,
        	'role' => $this->roles->lists('role'),
        	'birth_year' => $this->birth_year,
        	'death_year' => $this->death_year,
        	'birth_place' => $this->birth_place,
        	'death_place' => $this->death_place,
        	'sex' => $this->sex,
        	'has_image' => $this->has_image,
        	'created_at' => $this->attributes['created_at'],
        	'items_count' => $this->items->count(),
        ];
        return Elastic::index([
        	'index' => Config::get('app.elasticsearch.index'),
        	'type' =>  self::ES_TYPE,
        	'id' => $this->attributes['id'],
        	'body' =>$data,
    	]);     	
	}

	public static function listValues($attribute, $search_params)
	{
		//najskor over, ci $attribute je zo zoznamu povolenych 
		if (!in_array($attribute, self::$filterable)) return false;
		$json_params = '
		{
		 "aggs" : { 
		    "'.$attribute.'" : {
		        "terms" : {
		          "field" : "'.$attribute.'",
		          "size": 1000
		        }
		    }
		}
		}
		';
		$params = array_merge(json_decode($json_params, true), $search_params);
		$result = Elastic::search([
	        	'search_type' => 'count',
	        	'type' => self::ES_TYPE,
	        	'body'  => $params       	
	      	]);
		$buckets = $result['aggregations'][$attribute]['buckets'];

		$return_list = array();
		foreach ($buckets as $bucket) {
			// dd($bucket);
			$single_value = $bucket['key'];
			// if ($attribute=='author') $single_value = preg_replace('/^([^,]*),\s*(.*)$/', '$2 $1', $single_value);
			$return_list[$bucket['key']] = "$single_value ({$bucket['doc_count']})";
		}
		return $return_list;

	}

}