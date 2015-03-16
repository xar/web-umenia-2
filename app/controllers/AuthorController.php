<?php

class AuthorController extends \BaseController {

	public function index()
	{
		$search = Input::get('search', null);
		$input = Input::all();
		$params = array();
		$params["size"] = 100;
		$params["sort"][] = "_score";
		$params["sort"][] = ["created_at"=>["order"=>"desc"]];

		if (!empty($input)) {
			
			if (Input::has('search')) {
				$search = Input::get('search', '');
				$json_params = '
					{
					  "query": {
					  	"filtered" : {
					  	  "query": {
							  "bool": {
							    "should": [
							      { "match": {
							          "author.folded": {
							            "query": "'.$search.'",
							            "boost": 5
							          }
							        }
							      },

							      { "match": { "title":          "'.$search.'" }},
							      { "match": { "title.stemmed": "'.$search.'" }},
							      { "match": { 
							        "title.stemmed": { 
							          "query": "'.$search.'",  
							          "analyzer" : "slovencina_synonym" 
							        }
							      }
							      },

							      { "match": {
							          "subject.folded": {
							            "query": "'.$search.'",
							            "boost": 1
							          }
							        }
							      },

							      { "match": {
							          "description": {
							            "query": "'.$search.'",
							            "boost": 1
							          }
							        }
							      },
							      { "match": {
							          "description.stemmed": {
							            "query": "'.$search.'",
							            "boost": 0.9
							          }
							        }
							      },
							      { "match": {
							          "description.stemmed": {
							            "query": "'.$search.'",
							            "analyzer" : "slovencina_synonym",
							            "boost": 0.5
							          }
							        }
							      },

							      { "match": {
							          "place.folded": {
							            "query": "'.$search.'",
							            "boost": 1
							          }
							        }
							      }


							    ]
							  }
							}
						}
					  },
					  "size": 100
					}
				';
				$params = json_decode($json_params, true);

			}

			foreach ($input as $filter => $value) {
				if (in_array($filter, Item::$filterable) && !empty($value)) {
					$params["query"]["filtered"]["filter"]["and"][]["term"][$filter] = $value;
				}
			}
            if(!empty($input['year-range'])) {
            	$range = explode(',', $input['year-range']);
            	$params["query"]["filtered"]["filter"]["and"][]["range"]["date_earliest"]["gte"] = $range[0];
            	$params["query"]["filtered"]["filter"]["and"][]["range"]["date_latest"]["lte"] = $range[1];
            }
			
		} 

		$items = Item::search($params);

		$authors = Item::listValues('author', $params);
		$work_types = Item::listValues('work_type', $params);
		$tags = Item::listValues('subject', $params);
		$galleries = Item::listValues('gallery', $params);
		

		$queries = DB::getQueryLog();
		$last_query = end($queries);

		return View::make('katalog', array(
			'items'=>$items, 
			'authors'=>$authors, 
			'work_types'=>$work_types, 
			'tags'=>$tags, 
			'galleries'=>$galleries, 
			'search'=>$search, 
			'input'=>$input, 
			));
	}

	public function getSuggestions()
	{
	 	$q = (Input::has('search')) ? Input::get('search') : 'null';

		$result = Elastic::search([
	        	'type' => Authority::ES_TYPE,
	        	'body'  => array(
	                'query' => array(
	                    'multi_match' => array(
	                        'query'  	=> $q,
	                        'type' 		=> 'cross_fields',
							// 'fuzziness' =>  2,
							// 'slop'		=>  2,
        	                'fields' 	=> array("name.suggest", "alternative_name.suggest"),
	                        'operator' 	=> 'and'
	                    ),
	                ),
	                'size' => '10',
	                'sort' => [
	                	'items_count' => ['order' => 'desc'],
	                	'has_image' => ['order' => 'desc'],
	                ]
	            ),        	
	      	]);

		$data = array();
		$data['results'] = array();
		$data['count'] = 0;
		
		// $data['items'] = array();
		foreach ($result['hits']['hits'] as $key => $hit) {

			$name = preg_replace('/^([^,]*),\s*(.*)$/', '$2 $1', $hit['_source']['name']);

			$data['count']++;
			$params = array(
				'id' => $hit['_id'],
				'name' => $name,
				'birth_year' => $hit['_source']['birth_year'],
				'death_year' => $hit['_source']['death_year'],
				'image' => Authority::getImagePathForId($hit['_id'], $hit['_source']['has_image'], $hit['_source']['sex'],  false, 70)
			);
			$data['results'][] = array_merge($params) ;
		}

	    return Response::json($data);	
	}


}