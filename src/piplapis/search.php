<?php
// PHP wrapper for easily making calls to Pipl's Search API.
// 
// Pipl's Search API allows you to query with the information you have about
// a person (his name, address, email, phone, username and more) and in response 
// get all the data available on him on the web.
// 
// The classes contained in this module are:
// - PiplApi_SearchAPIRequest -- Build your request and send it.
// - PiplApi_SearchAPIResponse -- Holds the response from the API in case it contains data.
// - PiplApi_SearchAPIError -- An exception raised when the API response is an error.
// 
// The classes are based on the person data-model that's implemented here in containers.php

require_once dirname(__FILE__) . '/error.php';
require_once dirname(__FILE__) . '/data/containers.php';

class PiplApi_SearchApi
{
    // Default API key value, you can set your key globally in this variable instead 
    // of passing it in each API call
    public static $default_api_key = NULL;
}

class PiplApi_SearchAPIRequest
{
    // A request to Pipl's Search API.
    // 
    // Building the request from the query parameters can be done in two ways:
    // 
    // Option 1 - directly and quickly (for simple requests with only few 
    //            parameters):
    //         
    // require_once dirname(__FILE__) . '/search.php';
    // $request = new PiplApi_SearchAPIRequest(array('api_key' => 'samplekey', 
    //                                                                  'email' => 'eric@cartman.com'));
    // $response = $request->send();
    // 
    // Option 2 - using the data-model (useful for more complex queries; for 
    //            example, when there are multiple parameters of the same type 
    //            such as few phones or a few addresses or when you'd like to use 
    //            information beyond the usual identifiers such as name or email, 
    //            information like education, job, relationships etc):
    //         
    // require_once dirname(__FILE__) . '/search.php';
    // require_once dirname(__FILE__) . '/data/fields.php';
    // $fields = array(new PiplApi_Name(array('first' => 'Eric', 'last' => 'Cartman')),
    //                     new PiplApi_Address(array('country' => 'US', 'state' => 'CO', 'city' => 'South Park')),
    //                     new PiplApi_Address(array('country' => 'US', 'state' => 'NY')),
    //                     new PiplApi_Job(array('title' => 'Actor')));
    // $request = new PiplApi_SearchAPIRequest(array('api_key' => 'samplekey',
    //                                                                  'person' => new PiplApi_Person(array('fields' => $fields))));
    // $response = $request->send();
    // 
    // The request also supports prioritizing/filtering the type of records you
    // prefer to get in the response (see the append_priority_rule and 
    // add_records_filter methods).
    // 
    // Sending the request and getting the response is very simple and can be done
    // by either making a blocking call to request.send().
    
    public $person;
    public $query_params_mode;
    public $exact_name;
    
    private $api_key;
    private $_filter_records_by;
    private $_prioritize_records_by;
    
    private static $BASE_URL = 'http://api.pipl.com/search/v3/json/?';
    // HTTPS is also supported:
    //private static $BASE_URL = 'https://api.pipl.com/search/v3/json/?';
    
    function __construct($params=array())
    {
        // Initiate a new request object with given query params.
        // 
        // Each request must have at least one searchable parameter, meaning 
        // a name (at least first and last name), email, phone or username. 
        // Multiple query params are possible (for example querying by both email 
        // and phone of the person).
        // 
        // Args:
        // 
        // api_key -- string, a valid API key (use "samplekey" for experimenting).
        //            Note that you can set a default API key 
        //            (PiplApi_SearchApi::$default_api_key = '<your_key>';) instead of 
        //            passing it to each request object. 
        // first_name -- string, minimum 2 chars.
        // middle_name -- string. 
        // last_name -- string, minimum 2 chars.
        // raw_name -- string, an unparsed name containing at least a first name 
        //             and a last name.
        // email -- string.
        // phone -- int/long. If a string is passed instead then it'll be 
        //          striped from all non-digit characters and converted to int.
        //          IMPORTANT: Currently only US/Canada phones can be searched by
        //          so country code is assumed to be 1, phones with different 
        //          country codes are considered invalid and will be ignored.
        // username -- string, minimum 4 chars.
        // country -- string, a 2 letter country code from:
        //            http://en.wikipedia.org/wiki/ISO_3166-2
        // state -- string, a state code from:
        //          http://en.wikipedia.org/wiki/ISO_3166-2%3AUS
        //          http://en.wikipedia.org/wiki/ISO_3166-2%3ACA
        // city -- string.
        // raw_address -- string, an unparsed address.
        // from_age -- int.
        // to_age -- int.
        // person -- A PiplApi::Person object (available at containers.php).
        //           The person can contain every field allowed by the data-model
        //           (fields.php) and can hold multiple fields of 
        //           the same type (for example: two emails, three addresses etc.)
        // query_params_mode -- string, one of "and"/"or" (default "and").
        //                      Advanced parameter, use only if you care about the 
        //                      value of record.query_params_match in the response 
        //                      records.
        //                      Each record in the response has an attribute 
        //                      "query_params_match" which indicates whether the 
        //                      record has the all fields from the query or not.
        //                      When set to "and" all query params are required in 
        //                      order to get query_params_match=True, when set to 
        //                      "or" it's enough that the record has at least one
        //                      of each field type (so if you search with a name 
        //                      and two addresses, a record with the name and one 
        //                      of the addresses will have query_params_match=true)
        // exact_name -- bool (default false).
        //               If set to True the names in the query will be matched 
        //               "as is" without compensating for nicknames or multiple
        //               family names. For example "Jane Brown-Smith" won't return 
        //               results for "Jane Brown" in the same way "Alexandra Pitt"
        //               won't return results for "Alex Pitt".
        // 
        // Each of the arguments that should have a string that accepts both 
        // strings.

        $fparams = $params;
        
        if (!array_key_exists('query_params_mode', $fparams))
        {
            $fparams['query_params_mode'] = 'and';
        }
        
        if (!array_key_exists('exact_name', $fparams))
        {
            $fparams['exact_name'] = false;
        }

        $person = !empty($fparams['person']) ? $fparams['person'] : new PiplApi_Person();

        if (!empty($fparams['first_name']) || !empty($fparams['middle_name']) || !empty($fparams['last_name']))
        {
            $name = new PiplApi_Name(array('first' => $fparams['first_name'],
                                                           'middle' => $fparams['middle_name'],
                                                           'last' => $fparams['last_name']));
            $person->add_fields(array($name));
        }

        if (!empty($fparams['raw_name']))
        {
            $person->add_fields(array(new PiplApi_Name(array('raw' => $fparams['raw_name']))));
        }

        if (!empty($fparams['email']))
        {
            $person->add_fields(array(new PiplApi_Email(array('address' => $fparams['email']))));
        }
        
        if (!empty($fparams['phone']))
        {
            if (is_string($fparams['phone']))
            {
                $person->add_fields(array(PiplApi_Phone::from_text($fparams['phone'])));
            }
            else
            {
                $person->add_fields(array(new PiplApi_Phone(array('number' => $fparams['phone']))));
            }
        }
        
        if (!empty($fparams['username']))
        {
            $person->add_fields(array(new PiplApi_Username(array('content' => $fparams['username']))));
        }

        if (!empty($fparams['country']) || !empty($fparams['state']) || !empty($fparams['city']))
        {
            $address = new PiplApi_Address(array('country' => $fparams['country'],
                                                                 'state' => $fparams['state'],
                                                                 'city' => $fparams['city']));
            $person->add_fields(array($address));
        }

        if (!empty($fparams['raw_address']))
        {
            $person->add_fields(array(new PiplApi_Address(array('raw' => $fparams['raw_address']))));
        }

        if (!empty($fparams['from_age']) || !empty($fparams['to_age']))
        {
            $dob = PiplApi_DOB::from_age_range(!empty($fparams['from_age']) ? $fparams['from_age'] : 0,
                                                              !empty($fparams['to_age']) ? $fparams['to_age'] : 1000);
            $person->add_fields(array($dob));
        }

        if (!empty($fparams['api_key']))
        {
            $this->api_key = $fparams['api_key'];
        }
        $this->person = $person;
        if (!empty($fparams['query_params_mode']))
        {
            $this->query_params_mode = $fparams['query_params_mode'];
        }
        $this->exact_name = !empty($fparams['exact_name']) && $fparams['exact_name'] ? 'true' : 'false';
        $this->_filter_records_by = array();
        $this->_prioritize_records_by = array();
    }
    
    public static function _prepare_filtering_params($params=array())
    {
        // Transform the params to the API format, return a list of params.
        if (isset($params['query_params_match']))
        {
            if ($params['query_params_match'] != true)
            {
                throw new InvalidArgumentException('query_params_match can only be `True`');
            }
        }
        
        if (isset($params['query_person_match']))
        {
            if ($params['query_person_match'] != true)
            {
                throw new InvalidArgumentException('query_person_match can only be `True`');
            }
        }
        
        $outparams = array();
        
        if (isset($params['domain']))
        {
            $outparams[] = sprintf('domain:%s', $params['domain']);
        }

        if (isset($params['category']))
        {
            PiplApi_Source::validate_categories(array($params['category']));
            $outparams[] = sprintf('category:%s', $params['category']);
        }
        
        if (isset($params['sponsored_source']))
        {
            $outparams[] = sprintf('sponsored_source:%s', $params['sponsored_source'] ? 'true' : 'false');
        }
        
        if (isset($params['query_params_match']))
        {
            $outparams[] = 'query_params_match';
        }
        
        if (isset($params['query_person_match']))
        {
            $outparams[] = 'query_person_match';
        }
        
        $params['has_fields'] = isset($params['has_fields']) ? $params['has_fields'] : array();

        if (isset($params['has_field']))
        {
            $params['has_fields'][] = $params['has_field'];
        }
        
        // Make sure we only take the class name for the string
        $params['has_fields'] = array_filter($params['has_fields'], create_function('$x', 'return strpos((string)$x, \'_\') !== false;'));
        
        foreach ($params['has_fields'] as $x)
        {
            $splitted = explode('_', (string)$x);
            $outparams[] = sprintf('has_field:%s', $splitted[1]);
        }
        return $outparams;
    }
        
    public function add_records_filter($params=array())
    {
        // Add a new "and" filter for the records returned in the response.
        // 
        // IMPORTANT: This method can be called multiple times per request for 
        // adding multiple "and" filters, each of these "and" filters is 
        // interpreted as "or" with the other filters.
        // For example:
        // 
        // require_once dirname(__FILE__) . '/search.php';
        // require_once dirname(__FILE__) . '/data/fields.php';
        // $request = new PiplApi_SearchAPIRequest(array('api_key' => 'samplekey',
        //                                                                  'username' => 'eric123'));
        // $request->add_records_filter(array('domain' => 'linkedin',
        //                                                 'has_fields' => array('PiplApi_Phone')));
        // $request->add_records_filter(array('has_fields' => array('PiplApi_Phone', 'PiplApi_Job')));
        // 
        // The above request is only for records that are:
        // (from LinkedIn AND has a phone) OR (has a phone AND has a job).
        // Records that don't match this rule will not come back in the response.
        // 
        // Please note that in case there are too many results for the query, 
        // adding filters to the request can significantly improve the number of
        // useful results; when you define which records interest you, you'll
        // get records that would have otherwise be cut-off by the limit on the
        // number of records per query.
        // 
        // Args:
        // 
        // domain --string, for example "linkedin.com", you may also use "linkedin"
        //           but note that it'll match "linkedin.*" and "*.linkedin.*" 
        //           (any sub-domain and any TLD).
        // category -- string, any one of the categories defined in
        //             PiplAPI_Source::$categories.
        // sponsored_source -- bool, true means you want just the records that 
        //                     come from a sponsored source and False means you 
        //                     don't want these records.
        // has_fields -- An array of fields classes from fields.php,
        //               records must have content in all these fields.
        //               For example: array('PiplApi_Name', 'PiplApi_Phone') means you only want records 
        //               that has at least one name and at least one phone.
        // query_params_match -- true is the only possible value and it means you 
        //                       want records that match all the params you passed 
        //                       in the query.
        // query_person_match -- true is the only possible value and it means you
        //                       want records that are the same person you 
        //                       queried by (only records with 
        //                       query_person_match == 1.0, see the documentation 
        //                       of record.query_person_match for more details).
        // 
        // InvalidArgumentException is raised in any case of an invalid parameter.

        $filtering_params = self::_prepare_filtering_params($params);
        if (!empty($filtering_params))
        {
            $this->_filter_records_by[] = implode(' AND ', $filtering_params);
        }
    }
    
    public function append_priority_rule($params=array())
    {
        // Append a new priority rule for the records returned in the response.
        // 
        // IMPORTANT: This method can be called multiple times per request for 
        // adding multiple priority rules, each call can be with only one argument
        // and the order of the calls matter (the first rule added is the highest 
        // priority, the second is second priority etc).
        // For example:
        // 
        // require_once dirname(__FILE__) . '/search.php';
        // require_once dirname(__FILE__) . '/data/fields.php';
        // $request = new PiplApi_SearchAPIRequest(array('api_key' => 'samplekey',
        //                                                                  'username' => 'eric123'));
        // $request->append_priority_rule(array('domain' => 'linkedin'));
        // $request->append_priority_rule(array('has_field' => 'PiplApi_Phone'));
        // 
        // In the response to the above request records from LinkedIn will be 
        // returned before records that aren't from LinkedIn and records with 
        // phone will be returned before records without phone. 
        // 
        // Please note that in case there are too many results for the query,
        // adding priority rules to the request does not only affect the order 
        // of the records but can significantly improve the number of useful 
        // results; when you define which records interest you, you'll get records
        // that would have otherwise be cut-off by the limit on the number
        // of records per query.  
        // 
        // Args:
        // 
        // domain -- string, for example "linkedin.com", "linkedin" is also possible 
        //           and it'll match "linkedin.*".
        // category -- string, any one of the categories defined in
        //             piplapis.data.source.Source.categories.
        // sponsored_source -- bool, True will bring the records that 
        //                     come from a sponsored source first and False 
        //                     will bring the non-sponsored records first.
        // has_fields -- A field class from fields.rb.
        //               For example: has_field=PiplApi::Phone means you want to give 
        //               a priority to records that has at least one phone.
        // query_params_match -- True is the only possible value and it means you 
        //                       want to give a priority to records that match all 
        //                       the params you passed in the query.
        // query_person_match -- True is the only possible value and it means you
        //                       want to give a priority to records with higher
        //                       query_person_match (see the documentation of 
        //                       record.query_person_match for more details).
        //              
        // InvalidArgumentException is raised in any case of an invalid parameter.

        $priority_params = self::_prepare_filtering_params($params);
        if (count($priority_params) > 1)
        {
            throw new InvalidArgumentException('The function should be called with one argument');
        }
        
        if (!empty($priority_params))
        {
            $this->_prioritize_records_by[] = $priority_params[0];
        }
    }

    public function validate_query_params($strict=true)
    {
        // Check if the request is valid and can be sent, raise InvalidArgumentException if 
        // not.
        // 
        // `strict` is a boolean argument that defaults to true which means an 
        // exception is raised on every invalid query parameter, if set to false
        // an exception is raised only when the search request cannot be performed
        // because required query params are missing.
        
        if ((empty($this->api_key) || strlen($this->api_key) == 0) && 
            (empty(PiplApi_SearchApi::$default_api_key)))
        {
            throw new InvalidArgumentException('API key is missing');
        }

        if (!$this->person->is_searchable())
        {
            throw new InvalidArgumentException('No valid name/username/phone/email in request');
        }
        
        if ($strict)
        {
            if (!empty($this->query_params_mode))
            {
                if (!in_array($this->query_params_mode, array('and', 'or')))
                {
                    throw new InvalidArgumentException('query_params_match should be one of "and"/"or"');
                }
            }
            
            $unsearchable = $this->person->unsearchable_fields();
            
            if (!empty($unsearchable) && count($unsearchable) > 0)
            {
                throw new InvalidArgumentException(sprintf('Some fields are unsearchable: %s', implode(', ', $unsearchable)));
            }
        }
    }
        
    public function url()
    {
        // The URL of the request (string).
        $query = array(
            'key' => !empty($this->api_key) ? $this->api_key : PiplApi_SearchApi::$default_api_key,
            'person' => PiplApi_Serializable::to_json($this->person),
            'query_params_mode' => $this->query_params_mode,
            'exact_name' => $this->exact_name,
            'prioritize_records_by' => implode(',', $this->_prioritize_records_by),
            'filter_records_by' => $this->_filter_records_by
        );
        
        return self::$BASE_URL . http_build_query($query);
    }

    public function send($strict_validation=true)
    {
        // Send the request and return the response or raise PiplApi_SearchAPIError.
        // 
        // Calling this method blocks the program until the response is returned,
        // 
        // The response is returned as a PiplApi_SearchAPIResponse object
        // Also raises an PiplApi_SearchAPIError object in case of an error
        // 
        // `strict_vailidation` is a bool argument that's passed to the 
        // validate_query_params method.
        // 
        // Example:
        // 
        // require_once dirname(__FILE__) . '/search.php';
        // $request = new PiplApi_SearchAPIRequest(array('api_key' => 'samplekey',
        //                                                                  'email' => 'eric@cartman.com'));
        // try {
        //      $response = $request->send();
        //      // All good!
        // } catch (PiplApi_SearchAPIError $e) {
        //      print $e->getMessage();
        // }

        $this->validate_query_params($strict_validation);
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array( CURLOPT_RETURNTRANSFER => 1,
                                                   CURLOPT_URL => $this->url(),
                                                   CURLOPT_USERAGENT => PIPLAPI_USERAGENT ));
        
        $resp = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (in_array($http_status, range(200, 299))) // success
        {
            return PiplApi_Serializable::from_json('PiplApi_SearchAPIResponse', $resp);
        }
        else
        {
            $err = PiplApi_Serializable::from_json('PiplApi_SearchAPIError', $resp);
            throw $err;
        }
    }
}


class PiplApi_SearchAPIResponse
{
    // A response from Pipl's Search API.
    // 
    // A response comprises the two things returned as a result to your query:
    // 
    // - A person (PiplApi_Person) that is the deta object 
    //   representing all the information available for the person you were 
    //   looking for.
    //   This object will only be returned when our identity-resolution engine is
    //   convinced that the information is of the person represented by your query.
    //   Obviously, if the query was for "John Smith" there's no way for our
    //   identity-resolution engine to know which of the hundreds of thousands of
    //   people named John Smith you were referring to, therefore you can expect
    //   that the response will not contain a person object.
    //   On the other hand, if you search by a unique identifier such as email or
    //   a combination of identifiers that only lead to one person, such as
    //   "Eric Cartman, Age 22, From South Park, CO, US", you can expect to get 
    //   a response containing a single person object.
    // 
    // - A list of records (PiplApi_Record) that fully/partially 
    //   match the person from your query, if the query was for "Eric Cartman from 
    //   Colorado US" the response might also contain records of "Eric Cartman 
    //   from US" (without Colorado), if you need to differentiate between records 
    //   with full match to the query and partial match or if you want to get a
    //   score on how likely is that record to be related to the person you are
    //   searching please refer to the record's attributes 
    //   record->query_params_match and record->query_person_match.
    // 
    // The response also contains the query as it was interpreted by Pipl. This 
    // part is useful for verification and debugging, if some query parameters 
    // were invalid you can see in response.query that they were ignored, you can 
    // also see how the name/address from your query were parsed in case you 
    // passed raw_name/raw_address in the query.
    // 
    // In some cases when the query isn't focused enough and can't be matched to 
    // a specific person, such as "John Smith from US", the response also contains 
    // a list of suggested searches. This is a list of Record objects, each of 
    // these is an expansion of the original query, giving additional query 
    // parameters so the you can zoom in on the right person.
    
    public $query;
    public $person;
    public $records;
    public $suggested_searches;
    public $warnings;
    
    function __construct($params=array())
    {
        // Args:
        // 
        // query -- A PiplApi_Person object with the query as interpreted by Pipl.
        // person -- A PiplApi_Person object with data about the person in the query.
        // records -- An array of PiplApi_Record objects with full/partial match to the 
        //            query.
        // suggested_searches -- An array of PiplApi_Record objects, each of these is an 
        //                       expansion of the original query, giving additional
        //                       query parameters to zoom in on the right person.
        // warnings_ -- A list of strings. A warning is returned when the query 
        //             contains a non-critical error and the search can still run.
        extract($params);

        $this->query = $query;
        $this->person = $person;
        $this->records = !empty($records) ? $records : array();
        $this->suggested_searches = !empty($suggested_searches) ? $suggested_searches : array();
        $this->warnings = !empty($warnings_) ? $warnings_ : array();
    }
        
    public function query_params_matched_records()
    {
        // Records that match all the params in the query.
        $this->records = array_filter($this->records, create_function('$rec', 'return $rec->query_params_match;'));
    }
    
    public function query_person_matched_records()
    {
        // Records that match the person from the query.
        // 
        // Note that the meaning of "match the person from the query" means "Pipl 
        // is convinced that these records hold data about the person you're 
        // looking for". 
        // Remember that when Pipl is convinced about which person you're looking 
        // for, the response also contains a Person object. This person is 
        // created by merging all the fields and sources of these records. 
        $this->records = array_filter($this->records, create_function('$rec', 'return $rec->query_params_match == 1.0;'));
    }
        
    public function group_records($key_function)
    {
        // Return an array with the records grouped by the key returned by 
        // `key_function`.
        // 
        // `key_function` takes a record and returns the value from the record to
        // group by (see examples in the group_records_by_* methods below).
        // 
        // The return value is an array, a key in this array is a key returned by
        // `key_function` and the value is a list of all the records with this key.
        $new_groups = array();
        foreach ($this->records as $rec)
        {
            $grp = $key_function($rec);
            $new_groups[$grp][] = $rec;
        }
        return $new_groups;
    }
    
    public function group_records_by_domain()
    {
        // Return the records grouped by the domain they came from.
        // 
        // The return value is an array, a key in this array is a domain
        // and the value is a list of all the records with this domain.

        $key_function = create_function('$x', 'return $x->source->domain');
        return $this->group_records($key_function);
    }
    
    public function group_records_by_category()
    {
        // Return the records grouped by the category of their source.
        // 
        // The return value is an array, a key in this array is a category
        // and the value is a list of all the records with this category.

        $key_function = create_function('$x', 'return $x->source->category');
        return $this->group_records($key_function);
    }
    
    public function group_records_by_query_params_match()
    {
        // Return the records grouped by their query_params_match attribute.
        // 
        // The return value is an array, a key in this array is a query_params_match
        // bool (so the keys can be just True or False) and the value is a list 
        // of all the records with this query_params_match value.

        $key_function = create_function('$x', 'return $x->query_params_match');
        return $this->group_records($key_function);
    }
    
    public function group_records_by_query_person_match()
    {
        // Return the records grouped by their query_person_match attribute.
        // 
        // The return value is an array, a key in this array is a query_person_match
        // float and the value is a list of all the records with this 
        // query_person_match value.

        $key_function = create_function('$x', 'return $x->query_person_match');
        return $this->group_records($key_function);
    }
    
    public static function from_dict($cls, $d)
    {
        // Transform the array to a response object and return the response.
        $warnings_ = !empty($d['warnings']) ? $d['warnings'] : array();
        $query = $d['query'];
        if (!empty($query))
        {
            $query = PiplApi_Person::from_dict($query);
        }
        $person = $d['person'];
        if (!empty($person))
        {
            $person = PiplApi_Person::from_dict($person);
        }
        
        $records = $d['records'];
        if (!empty($records))
        {
            $records = array_map(create_function('$rec', 'return PiplApi_Record::from_dict($rec);'), $records);
        }
        $suggested_searches = $d['suggested_searches'];
        if (!empty($suggested_searches))
        {
            $suggested_searches = array_map(create_function('$rec', 'return PiplApi_Record::from_dict($rec);'), $suggested_searches);
        }

        return new PiplApi_SearchAPIResponse(array( 'query' => $query,
                                                                        'person' => $person,
                                                                        'records' => $records,
                                                                        'suggested_searches' => $suggested_searches,
                                                                        'warnings_' => $warnings_));
    }
    
    public function to_dict()
    {
        // Return a dict representation of the response.
        $d = array();
        
        if (!empty($this->warnings))
        {
            $d['warnings'] = $this->warnings;
        }
        if (!empty($this->query))
        {
            $d['query'] = $this->query->to_dict();
        }
        if (!empty($this->person))
        {
            $d['person'] = $this->person->to_dict();
        }

        if (!empty($this->records))
        {
            $d['records'] = array_map(create_function('$x', 'return $x->to_dict();'), $this->records);
        }

        if (!empty($this->suggested_searches))
        {
            $d['suggested_searches'] = array_map(create_function('$x', 'return $x->to_dict();'), $this->suggested_searches);
        }
        return $d;
    }
}


class PiplApi_SearchAPIError extends PiplApi_APIError
{  
    // An exception raised when the response from the search API contains an 
    // error.  
}

?>