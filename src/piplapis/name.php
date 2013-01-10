<?php
// PHP wrapper for easily making calls to Pipl's Name API.
// 
// Pipl's Name API provides useful utilities for applications that need to work
// with people names, the utilities include:
// - Parsing a raw name into prefix/first-name/middle-name/last-name/suffix. 
// - Getting the gender that's most common for people with the name.
// - Getting possible nicknames of the name.
// - Getting possible full-names of the name (in case the name is a nick).
// - Getting different spelling options of the name.
// - Translating the name to different languages.
// - Getting the list of most common locations for people with this name.
// - Getting the list of most common ages for people with this name.
// - Getting an estimated number of people in the world with this name.
// 
// The classes contained in this file are:
// - PiplApi_NameAPIRequest -- Build your request and send it.
// - PiplApi_NameAPIResponse -- Holds the response from the API in case it contains data.
// - PiplApi_NameAPIError -- An exception raised when the API response is an error.
// 
// - PiplApi_AltNames -- Used in PiplApi_NameAPIResponse for holding alternative names.
// - PiplApi_LocationStats -- Used in PiplApi_NameAPIResponse for holding location data.
// - PiplApi_AgeStats -- Used in PiplApi_NameAPIResponse for holding age data.

require_once dirname(__FILE__) . '/data/utils.php';
require_once dirname(__FILE__) . '/data/fields.php';
require_once dirname(__FILE__) . '/error.php';

class PiplApi_NameApi
{
    // Default API key value, you can set your key globally in this variable instead 
    // of passing it in each API call
    public static $default_api_key = NULL;
}

class PiplApi_NameAPIRequest
{
    // A request to Pipl's Name API.
    // 
    // A request is build with a name that can be provided parsed to 
    // first/middle/last (in case it's already available to you parsed) 
    // or unparsed (and then the API will parse it).
    // Note that the name in the request can also be just a first-name or just 
    // a last-name.
    
    private static $BASE_URL = 'http://api.pipl.com/name/v2/json/?';
    
    private $api_key;
    public $name;
    
    function __construct($params=array())
    {
        // `api_key` is a valid API key (string), use "samplekey" for 
        // experimenting, note that you can set a default API key
        // (PiplApi_NameApi::$default_api_key = '<your_key>';) instead of passing it 
        // to each request object.
        // 
        // `first_name`, `middle_name`, `last_name`, `raw_name` should all be 
        // strings.
        // 
        // InvalidArgumentException is thrown in case of illegal parameters.
        // 
        // Examples:
        // 
        // require_once dirname(__FILE__) . '/name.php';
        // $request1 = new PiplApi_NameAPIRequest(array('api_key' => 'samplekey',
        //                                                                 'first_name' => 'Eric',
        //                                                                 'last_name' => 'Cartman'));
        // $request2 = new PiplApi_NameAPIRequest(array('api_key' => 'samplekey',
        //                                                                  'last_name' => 'Cartman'));
        // $request3 = new PiplApi_NameAPIRequest(array('api_key' => 'samplekey',
        //                                                                  'raw_name' => 'Eric Cartman'));
        // $request4 = new PiplApi_NameAPIRequest(array('api_key' => 'samplekey',
        //                                                                  'raw_name' => 'Eric' ));

        if ((empty($params['api_key']) || strlen($params['api_key']) == 0) && empty(PiplApi_NameApi::$default_api_key))
        {
            throw new InvalidArgumentException('A valid API key is required');
        }

        $haveraw = !empty($params['raw_name']) && strlen($params['raw_name']) > 0;
        $haveparsed = (!empty($params['first_name']) && strlen($params['first_name']) > 0) ||
                             (!empty($params['middle_name']) && strlen($params['middle_name']) > 0) ||
                             (!empty($params['last_name']) && strlen($params['last_name']) > 0);
        
        if (!$haveraw && !$haveparsed)
        {
            throw new InvalidArgumentException('A name is missing');
        }
        
        if ($haveraw && $haveparsed)
        {
            throw new InvalidArgumentException('Name should be provided raw or parsed, not both');
        }
        
        if (!empty($params['api_key']))
        {
            $this->api_key = $params['api_key'];
        }
        $this->name = new PiplApi_Name(array( 'first' => !empty($params['first_name']) ? $params['first_name'] : NULL,
                                                                'middle' => !empty($params['middle_name']) ? $params['middle_name'] : NULL,
                                                                'last' => !empty($params['last_name']) ? $params['last_name'] : NULL, 
                                                                'raw' => !empty($params['raw_name']) ? $params['raw_name'] : NULL));
    }
    
    public function url()
    {
        // The URL of the request (string).
        $query = array(
            'key' => !empty($this->api_key) ? $this->api_key : PiplApi_NameApi::$default_api_key,
            'first_name' => !empty($this->name->first) ? $this->name->first : '',
            'middle_name' => !empty($this->name->middle) ? $this->name->middle : '',
            'last_name' => !empty($this->name->last) ? $this->name->last : '',
            'raw_name' => !empty($this->name->raw) ? $this->name->raw : ''
        );

        return self::$BASE_URL . http_build_query($query);
    }

    public function send()
    {
        // Send the request and return the response or raise PiplApi_NameAPIError.
        // 
        // The response is returned as a PiplApi_NameAPIResponse object.
        // 
        // Raises a PiplApi_NameAPIError object in case of error
        // 
        // Example:
        // 
        // require_once dirname(__FILE__) . '/name.php';
        // $request = new PiplApi_NameAPIRequest(array('api_key' => 'samplekey',
        //                                                                'raw_name' => 'Eric Cartman'));
        // try {
        //      $response = $request->send();
        //      // All good!
        // } catch (PiplApi_NameAPIError $e) {
        //      print $e->getMessage();
        // }
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array( CURLOPT_RETURNTRANSFER => 1,
                                                   CURLOPT_URL => $this->url(),
                                                   CURLOPT_USERAGENT => PIPLAPI_USERAGENT ));
        
        $resp = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (in_array($http_status, range(200, 299))) // success
        {
            return PiplApi_Serializable::from_json('PiplApi_NameAPIResponse', $resp);
        }
        else
        {
            $err = PiplApi_Serializable::from_json('PiplApi_NameAPIError', $resp);
            throw $err;
        }
    }
}
    
class PiplApi_NameAPIResponse
{
    // A response from Pipl's search API.
    
    // A response contains the name from the query (parsed), and when available
    // the gender, nicknames, full-names, spelling options, translations, common 
    // locations and common ages for the name. It also contains an estimated 
    // number of people in the world with this name.
    
    public $name;
    public $gender;
    public $gender_confidence;
    public $full_names;
    public $nicknames;
    public $spellings;
    public $translations;
    public $top_locations;
    public $top_ages;
    public $estimated_world_persons_count;
    public $warnings;
    
    
    function __construct($params=array())
    {
        // Args:
        // 
        // name -- A PiplApi_Name object - the name from the query.
        // gender -- string, "male" or "female".
        // gender_confidence -- float between 0.0 and 1.0, represents how 
        //                     confidence Pipl is that `gender` is the correct one.
        //                     (Unisex names will get low confidence score).
        // full_names -- An PiplApi_AltNames object.
        // nicknames -- An PiplApi_AltNames object.
        // spellings -- An PiplApi_AltNames object.
        // translations -- An array of language_code -> PiplApi_AltNames object for this 
        //                 language.
        // top_locations -- An array of PiplApi_LocationStats objects.
        // top_ages -- An array of PiplApi_AgeStats objects.
        // estimated_world_persons_count -- int, estimated number of people in the 
        //                                  world with the name from the query.
        // warnings_ -- A list of strings. A warning is returned when the query 
        //              contains a non-critical error.
        extract($params);
        
        $this->name = !empty($name) ? $name : new PiplApi_Name();
        if (isset($gender))
        {
            $this->gender = $gender;
        }
        if (isset($gender_confidence))
        {
            $this->gender_confidence = $gender_confidence;
        }
        $this->full_names = !empty($full_names) ? $full_names : new PiplApi_AltNames();
        $this->nicknames = !empty($nicknames) ? $nicknames : new PiplApi_AltNames();
        $this->spellings = !empty($spellings) ? $spellings : new PiplApi_AltNames();
        $this->translations = !empty($translations) ? $translations : array();
        $this->top_locations = !empty($top_locations) ? $top_locations : array();
        $this->top_ages = !empty($top_ages) ? $top_ages : array();
        if (isset($estimated_world_persons_count))
        {
            $this->estimated_world_persons_count = $estimated_world_persons_count;
        }
        $this->warnings = !empty($warnings_) ? $warnings_ : array();
    }
        
    public static function from_dict($cls, $d)
    {
        // Transform the dict to a response object and return the response.
        $name = PiplApi_Field::from_dict('PiplApi_Name', !empty($d['name']) ? $d['name'] : array());
        $gender = !empty($d['gender']) ? $d['gender'][0] : NULL;
        $gender_confidence = !empty($d['gender']) ? $d['gender'][1] : NULL;
        $full_names = PiplApi_Field::from_dict('PiplApi_AltNames', !empty($d['full_names']) ? $d['full_names'] : array());
        $nicknames = PiplApi_Field::from_dict('PiplApi_AltNames', !empty($d['nicknames']) ? $d['nicknames'] : array());
        $spellings = PiplApi_Field::from_dict('PiplApi_AltNames', !empty($d['spellings']) ? $d['spellings'] : array());
        
        $translations = array();
        
        if (!empty($d['translations']))
        {
            foreach ($d['translations'] as $k => $v)
            {
                $translations[$k] = PiplApi_Field::from_dict('PiplApi_AltNames', $v);
            }
        }

        $top_locations = array_map(create_function('$loc', 'return PiplApi_Field::from_dict(\'PiplApi_LocationStats\', $loc);'), !empty($d['top_locations']) ? $d['top_locations'] : array());
        $top_ages = array_map(create_function('$loc', 'return PiplApi_Field::from_dict(\'PiplApi_AgeStats\', $loc);'), !empty($d['top_ages']) ? $d['top_ages'] : array());

        $world_count = $d['estimated_world_persons_count'];
        $warnings_ = $d['warnings'];
        
        
        return new PiplApi_NameAPIResponse(array( 'name' => $name,
                                                                      'gender' => $gender, 
                                                                      'gender_confidence' => $gender_confidence,
                                                                      'full_names' => $full_names,
                                                                      'nicknames' => $nicknames, 
                                                                      'spellings' => $spellings,
                                                                      'translations' => $translations, 
                                                                      'top_locations' => $top_locations,
                                                                      'top_ages' => $top_ages, 
                                                                      'estimated_world_persons_count' => $world_count, 
                                                                      'warnings_' => $warnings_ ));
    }
    
    public function to_dict()
    {
        // Return a dict representation of the response.
        $t = array();
        foreach ($this->translations as $k => $v)
        {
            $t[$k] = $v->to_dict();
        }
        
        $top_locations = array_map(create_function('$loc', 'return PiplApi_Field::from_dict(\'PiplApi_LocationStats\', $loc);'), !empty($d['top_locations']) ? $d['top_locations'] : array());
        $top_ages = array_map(create_function('$loc', 'return PiplApi_Field::from_dict(\'PiplApi_AgeStats\', $loc);'), !empty($d['top_ages']) ? $d['top_ages'] : array());
        
        $d = array(
            'warnings' => $this->warnings,
            'name' => $this->name->to_dict(),
            'gender' => array($this->gender, $this->gender_confidence),
            'full_names' => $this->full_names->to_dict(),
            'nicknames' => $this->nicknames->to_dict(),
            'spellings' => $this->spellings->to_dict(),
            'translations' => $t,
            'top_locations' => array_map(create_function('$x', 'return $x->to_dict();'), $this->top_locations),
            'top_ages' => array_map(create_function('$x', 'return $x->to_dict();'), $this->top_ages),
            'estimated_world_persons_count' => $this->estimated_world_persons_count,
        );
        return $d;
    }
}

class PiplApi_AltNames extends PiplApi_Field
{
    // Helper class for PiplApi_NameAPIResponse, holds alternate 
    // first/middle/last names for a name.
    
    protected $children = array('first', 'middle', 'last');
    
    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);
    
        $this->first = !empty($first) ? $first : NULL;
        $this->middle = !empty($middle) ? $middle : NULL;
        $this->last = !empty($last) ? $last : NULL;
    }
}
        

class PiplApi_LocationStats extends PiplApi_Address
{
    // Helper class for PiplApi_NameAPIResponse, holds a location and the estimated 
    // percent of people with the name that lives in this location.
    
    // Note that this class inherits from Address and therefore has the 
    // methods location_stats.country_full(), location_stats.state_full() and
    // location_stats.display().
    
    protected $children = array('country', 'state', 'city', 'estimated_percent');
    
    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);
    
        $this->estimated_percent = $estimated_percent; // 0 <= int <= 100
    }
}
        

class PiplApi_AgeStats extends PiplApi_Field
{
    // Helper class for PiplApi_NameAPIResponse, holds an age range and the estimated 
    // percent of people with the name that their age is within the range.
    
    protected $children = array('from_age', 'to_age', 'estimated_percent');
    
    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);
    
        $this->from_age = $from_age;  // int
        $this->to_age = $to_age;  // int
        $this->estimated_percent = $estimated_percent;  // 0 <= int <= 100
    }
}

class PiplApi_NameAPIError extends PiplApi_APIError
{
    // An exception raised when the response from the name API contains an 
    // error.
}

?>