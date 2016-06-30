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

class PiplApi_SearchRequestConfiguration
{

    public $api_key = NULL;
    public $minimum_probability = NULL;
    public $minimum_match = NULL;
    public $show_sources = NULL;
    public $live_feeds = NULL;
    public $use_https = NULL;
    public $hide_sponsored = NULL;
    public $match_requirements = NULL;
    public $source_category_requirements = NULL;
    public $infer_persons = NULL;

    function __construct($api_key = "sample_key", $minimum_probability = NULL, $minimum_match = NULL, $show_sources = NULL,
                         $live_feeds = NULL, $hide_sponsored = NULL, $use_https = false, $match_requirements = NULL,
                         $source_category_requirements = NULL, $infer_persons = NULL)
    {
        $this->api_key = $api_key;
        $this->minimum_probability = $minimum_probability;
        $this->minimum_match = $minimum_match;
        $this->show_sources = $show_sources;
        $this->live_feeds = $live_feeds;
        $this->hide_sponsored = $hide_sponsored;
        $this->use_https = $use_https;
        $this->match_requirements = $match_requirements;
        $this->source_category_requirements = $source_category_requirements;
        $this->infer_persons = $infer_persons;
    }

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
    // $request = new PiplApi_SearchAPIRequest(array('email' => 'clark.kent@example.com'));
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
    // $fields = array(new PiplApi_Name(array('first' => 'Clark', 'last' => 'Kent')),
    //                     new PiplApi_Address(array('country' => 'US', 'state' => 'KS', 'city' => 'Metropolis')),
    //                     new PiplApi_Address(array('country' => 'US', 'state' => 'KS')),
    //                     new PiplApi_Job(array('title' => 'Field Reporter')));
    // $request = new PiplApi_SearchAPIRequest(array('person' => new PiplApi_Person(array('fields' => $fields))));
    // $response = $request->send();
    //
    // Sending the request and getting the response is very simple and can be done calling $request->send().

    public static $default_configuration;
    public $person;
    public $configuration;

    public static $base_url = 'api.pipl.com/search/?';

    static function set_default_configuration($configuration)
    {
        self::$default_configuration = $configuration;
    }

    static function get_default_configuration()
    {
        if (!isset(self::$default_configuration)) {
            self::$default_configuration = new PiplApi_SearchRequestConfiguration();
        }
        return self::$default_configuration;
    }

    public function __construct($search_params = array(), $configuration = NULL)
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
        // first_name -- string, minimum 2 chars.
        // middle_name -- string.
        // last_name -- string, minimum 2 chars.
        // raw_name -- string, an unparsed name containing at least a first name
        //             and a last name.
        // email -- string.
        // phone -- string. A raw phone number.
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
        // search_pointer -- a pointer from a possible person, received from an API response object.
        //
        // Each of the arguments that should have a string that accepts both
        // strings.

        # Set default configuration
        if (is_null(self::$default_configuration)) {
            self::$default_configuration = new PiplApi_SearchRequestConfiguration();
        }

        $person = !empty($search_params['person']) ? $search_params['person'] : new PiplApi_Person();

        if (!empty($search_params['first_name']) || !empty($search_params['middle_name']) || !empty($search_params['last_name'])) {
            $first = !empty($search_params['first_name']) ? $search_params['first_name'] : NULL;
            $last = !empty($search_params['last_name']) ? $search_params['last_name'] : NULL;
            $middle = !empty($search_params['middle_name']) ? $search_params['middle_name'] : NULL;
            $name = new PiplApi_Name(array('first' => $first, 'middle' => $middle, 'last' => $last));
            $person->add_fields(array($name));
        }

        if (!empty($search_params['raw_name'])) {
            $person->add_fields(array(new PiplApi_Name(array('raw' => $search_params['raw_name']))));
        }

        if (!empty($search_params['email'])) {
            $person->add_fields(array(new PiplApi_Email(array('address' => $search_params['email']))));
        }

        if (!empty($search_params['phone'])) {
            $person->add_fields(array(PiplApi_Phone::from_text($search_params['phone'])));
        }

        if (!empty($search_params['username'])) {
            $person->add_fields(array(new PiplApi_Username(array('content' => $search_params['username']))));
        }

        if (!empty($search_params['user_id'])) {
            $person->add_fields(array(new PiplApi_UserID(array('content' => $search_params['user_id']))));
        }

        if (!empty($search_params['url'])) {
            $person->add_fields(array(new PiplApi_URL(array('url' => $search_params['url']))));
        }

        if (!empty($search_params['country']) || !empty($search_params['state']) || !empty($search_params['city'])) {
            $country = !empty($search_params['country']) ? $search_params['country'] : NULL;
            $state = !empty($search_params['state']) ? $search_params['state'] : NULL;
            $city = !empty($search_params['city']) ? $search_params['city'] : NULL;
            $address = new PiplApi_Address(array('country' => $country, 'state' => $state, 'city' => $city));
            $person->add_fields(array($address));
        }

        if (!empty($search_params['raw_address'])) {
            $person->add_fields(array(new PiplApi_Address(array('raw' => $search_params['raw_address']))));
        }

        if (!empty($search_params['from_age']) || !empty($search_params['to_age'])) {
            $dob = PiplApi_DOB::from_age_range(!empty($search_params['from_age']) ? $search_params['from_age'] : 0,
                !empty($search_params['to_age']) ? $search_params['to_age'] : 1000);
            $person->add_fields(array($dob));
        }

        if (!empty($search_params['search_pointer'])) {
            $person->search_pointer = $search_params['search_pointer'];
        }

        $this->person = $person;
        $this->configuration = $configuration;
    }

    public function validate_query_params($strict = true)
    {
        // Check if the request is valid and can be sent, raise InvalidArgumentException if
        // not.
        //
        // `strict` is a boolean argument that defaults to true which means an
        // exception is raised on every invalid query parameter, if set to false
        // an exception is raised only when the search request cannot be performed
        // because required query params are missing.

        if (empty($this->get_effective_configuration()->api_key)) {
            throw new InvalidArgumentException('API key is missing');
        }

        if ($strict && (isset($this->get_effective_configuration()->show_sources) &&
                !in_array($this->get_effective_configuration()->show_sources, array("all", "matching", "true")))
        ) {
            throw new InvalidArgumentException('show_sources has a wrong value, should be "matching", "all" or "true"');
        }

        if ($strict && isset($this->get_effective_configuration()->minimum_probability) &&
            (!(is_float($this->get_effective_configuration()->minimum_probability) ||
                (0. < $this->get_effective_configuration()->minimum_probability ||
                    $this->get_effective_configuration()->minimum_probability > 1)))
        ) {
            throw new InvalidArgumentException('minimum_probability should be a float between 0 and 1');
        }

        if ($strict && isset($this->get_effective_configuration()->minimum_match) &&
            (!(is_float($this->get_effective_configuration()->minimum_match) ||
                (0. < $this->get_effective_configuration()->minimum_match ||
                    $this->get_effective_configuration()->minimum_match > 1)))
        ) {
            throw new InvalidArgumentException('minimum_match should be a float between 0 and 1');
        }

        if ($strict && isset($this->get_effective_configuration()->infer_persons) &&
            (!(is_bool($this->get_effective_configuration()->infer_persons) ||
                is_null($this->get_effective_configuration()->infer_persons)))
        ) {
            throw new InvalidArgumentException('infer_persons must be true, false or null');
        }

        if ($strict && $unsearchable = $this->person->unsearchable_fields()) {
            $display_strings = array_map(create_function('$field', 'return $field->get_representation();'), $unsearchable);
            throw new InvalidArgumentException(sprintf('Some fields are unsearchable: %s', implode(', ', $display_strings)));
        }

        if (!$this->person->is_searchable()) {
            throw new InvalidArgumentException('No valid name/username/phone/email/address/user_id/url in request');
        }
    }

    public function url()
    {
        // The URL of the request (string).
        return $this->get_base_url() . http_build_query($this->get_query_params());
    }

    public function send($strict_validation = true)
    {
        // Send the request and return the response or raise PiplApi_SearchAPIError.
        //
        // Calling this method blocks the program until the response is returned,
        //
        // The response is returned as a PiplApi_SearchAPIResponse object
        // Also raises an PiplApi_SearchAPIError object in case of an error
        //
        // `strict_validation` is a bool argument that's passed to the
        // validate_query_params method.
        //
        // Example:
        //
        // require_once dirname(__FILE__) . '/search.php';
        // $request = new PiplApi_SearchAPIRequest(array('api_key' => 'sample_key',
        //                                                                  'email' => 'clark.kent@example.com'));
        // try {
        //      $response = $request->send();
        //      // All good!
        // } catch (PiplApi_SearchAPIError $e) {
        //      print $e->getMessage();
        // }

        $this->validate_query_params($strict_validation);

        $curl = curl_init();
        $params = $this->get_query_params();
        $url = $this->get_base_url();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_VERBOSE => 0,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => PiplApi_Utils::PIPLAPI_USERAGENT,
            CURLOPT_POST => count($params),
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => array('Expect:')
        ));
        $resp = curl_exec($curl);

        #https://github.com/zendframework/zend-http/issues/24
        #https://github.com/kriswallsmith/Buzz/issues/181
        list($header_raw, $body) = explode("\r\n\r\n", $resp, 2);
        $headers = $this->extract_headers_from_curl($header_raw);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (in_array($http_status, range(200, 299))) {
            // Trying to parse header_raw from curl request
            $res = PiplApi_SearchAPIResponse::from_array(json_decode($body, true), $headers);
            // save the raw json to response object
            $res->raw_json = $body;
            return $res;
        } elseif ($resp) {
            $err = PiplApi_SearchAPIError::from_array(json_decode($body, true), $headers);
            throw $err;
        } else {
            $err = PiplApi_SearchAPIError::from_array(
                array("error" => curl_error($curl),
                    "warnings" => null,
                    "@http_status_code" => $http_status),
                $headers);
            throw $err;
        }
    }

    private function get_effective_configuration()
    {
        if (is_null($this->configuration)) {
            return self::get_default_configuration();
        }
        return $this->configuration;
    }

    private function get_query_params()
    {

        $query = array('key' => $this->get_effective_configuration()->api_key);
        if ($this->person->search_pointer) {
            $query['search_pointer'] = $this->person->search_pointer;
        } elseif ($this->person) {
            $query['person'] = json_encode($this->person->to_array());
        }
        if ($this->get_effective_configuration()->show_sources) {
            $query['show_sources'] = $this->get_effective_configuration()->show_sources;
        }
        if (isset($this->get_effective_configuration()->live_feeds)) {
            $query['live_feeds'] = $this->get_effective_configuration()->live_feeds;
        }
        if (isset($this->get_effective_configuration()->hide_sponsored)) {
            $query['hide_sponsored'] = $this->get_effective_configuration()->hide_sponsored;
        }
        if ($this->get_effective_configuration()->minimum_probability) {
            $query['minimum_probability'] = $this->get_effective_configuration()->minimum_probability;
        }
        if ($this->get_effective_configuration()->minimum_match) {
            $query['minimum_match'] = $this->get_effective_configuration()->minimum_match;
        }
        if ($this->get_effective_configuration()->match_requirements) {
            $query['match_requirements'] = $this->get_effective_configuration()->match_requirements;
        }
        if ($this->get_effective_configuration()->source_category_requirements) {
            $query['source_category_requirements'] = $this->get_effective_configuration()->source_category_requirements;
        }
        if ($this->get_effective_configuration()->infer_persons) {
            $query['infer_persons'] = $this->get_effective_configuration()->infer_persons;
        }

        return $query;
    }

    private function get_base_url()
    {
        $prefix = $this->get_effective_configuration()->use_https ? "https://" : "http://";
        return $prefix . self::$base_url;
    }

    private function extract_headers_from_curl($header_raw)
    {
        $headers = array();
        foreach (explode("\r\n", $header_raw) as $i => $line) {
            if ($i === 0)
                $headers['http_code'] = $line;
            else {
                list ($key, $value) = explode(': ', $line);
                $key = strtolower($key);
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}

class PiplApi_SearchAPIResponse implements JsonSerializable
{

    //    A response comprises the three things returned as a result to your query:
    //
    //    - a person (PiplApi_Person) that is the data object
    //    representing all the information available for the person you were
    //    looking for.
    //    this object will only be returned when our identity-resolution engine is
    //    convinced that the information is of the person represented by your query.
    //    obviously, if the query was for "John Smith" there's no way for our
    //    identity-resolution engine to know which of the hundreds of thousands of
    //    people named John Smith you were referring to, therefore you can expect
    //    that the response will not contain a person object.
    //    on the other hand, if you search by a unique identifier such as email or
    //    a combination of identifiers that only lead to one person, such as
    //    "Clark Kent from Smallville, KS, US", you can expect to get
    //    a response containing a single person object.
    //
    //    - a list of possible persons (PiplApi_Person). If our identity-resolution
    //    engine did not find a definite match, you can use this list to further
    //    drill down using the persons' search_pointer field.
    //
    //    - a list of sources (PiplApi_Source) that fully/partially
    //    match the person from your query, if the query was for "Clark Kent from
    //    Kansas US" the response might also contain sources of "Clark Kent
    //    from US" (without Kansas), if you need to differentiate between sources
    //    with full match to the query and partial match or if you want to get a
    //    score on how likely is that source to be related to the person you are
    //    searching please refer to the source's "match" field.
    //
    //    the response also contains the query as it was interpreted by Pipl. This
    //    part is useful for verification and debugging, if some query parameters
    //    were invalid you can see in response.query that they were ignored, you can
    //    also see how the name/address from your query were parsed in case you
    //    passed raw_name/raw_address in the query.

    public $query;
    public $person;
    public $sources;
    public $possible_persons;
    public $warnings;
    public $http_status_code;
    public $visible_sources;
    public $available_sources;
    public $available_data;
    public $search_id;
    public $match_requirements;
    public $source_category_requirements;
    public $persons_count;
    public $qps_allotted;
    public $qps_current;
    public $quota_allotted;
    public $quota_current;
    public $quota_reset;
    public $raw_json;

    public function __construct($http_status_code, $query, $visible_sources, $available_sources, $search_id, $warnings,
                                $person, $possible_persons, $sources, $available_data = NULL,
                                $match_requirements = NULL, $source_category_requirements = NULL, $persons_count = NULL,
                                $qps_allotted = NULL, $qps_current = NULL, $quota_allotted = NULL, $quota_current = NULL,
                                $quota_reset = NULL)
    {
        // Args:
        //  http_status_code -- The resposne code. 2xx if successful.
        //  query -- A PiplApi_Person object with the query as interpreted by Pipl.
        //  person -- A PiplApi_Person object with data about the person in the query.
        //  sources -- A list of PiplApi_Source objects with full/partial match to the query.
        //  possible_persons -- An array of PiplApi_Person objects, each of these is an
        //      expansion of the original query, giving additional
        //  query parameters to zoom in on the right person.
        //  warnings -- An array of strings. A warning is returned when the query
        //      contains a non-critical error and the search can still run.
        //  visible_sources -- the number of sources in response
        //  available_sources -- the total number of known sources for this search
        //  search_id -- a unique ID which identifies this search. Useful for debugging.
        //  available_data - showing the data available for your query.
        //  match_requirements: string. Shows how Pipl interpreted your match_requirements criteria.
        //  source_category_requirements: string. Shows how Pipl interpreted your source_category_requirements criteria.
        //  persons_count : int. The number of persons in this response.

        $this->http_status_code = $http_status_code;
        $this->visible_sources = $visible_sources;
        $this->available_sources = $available_sources;
        $this->search_id = $search_id;
        $this->query = $query;
        $this->person = $person;
        $this->match_requirements = $match_requirements;
        $this->source_category_requirements = $source_category_requirements;
        $this->possible_persons = !empty($possible_persons) ? $possible_persons : array();
        $this->sources = !empty($sources) ? $sources : array();
        $this->warnings = !empty($warnings) ? $warnings : array();
        $this->available_data = !empty($available_data) ? $available_data : array();
        $this->persons_count = !empty($persons_count) ? $persons_count : (!empty($person) ? 1 : count($this->possible_persons));

        // Header Parsed Parameters http://pipl.com/dev/reference/#errors
        // qps_allotted- int | The number of queries you are allowed to do per second.
        // qps_current- int | The number of queries you have run this second.
        // quota_allotted- int | Your quota limit.
        // quota_current- int | Your used quota.
        // quota_reset- DateTime Object | The time (in UTC) that your quota will be reset.

        $this->qps_allotted = $qps_allotted;
        $this->qps_current = $qps_current;
        $this->quota_allotted = $quota_allotted;
        $this->quota_current = $quota_current;
        $this->quota_reset = $quota_reset;

        // raw json
        $this->raw_json = NULL;
    }

    public function group_sources($key_function)
    {
        // Return an array with the sources grouped by the key returned by
        // `key_function`.
        //
        // `key_function` takes a source and returns the value from the source to
        // group by (see examples in the group_sources_by_* methods below).
        //
        // The return value is an array, a key in this array is a key returned by
        // `key_function` and the value is a list of all the sources with this key.
        $new_groups = array();
        foreach ($this->sources as $rec) {
            $grp = $key_function($rec);
            $new_groups[$grp][] = $rec;
        }
        return $new_groups;
    }

    public function group_sources_by_domain()
    {
        // Return the sources grouped by the domain they came from.
        //
        // The return value is an array, a key in this array is a domain
        // and the value is a list of all the sources with this domain.

        $key_function = create_function('$x', 'return $x->domain;');
        return $this->group_sources($key_function);
    }

    public function group_sources_by_category()
    {
        // Return the sources grouped by their category.
        //
        // The return value is an array, a key in this array is a category
        // and the value is a list of all the sources with this category.

        $key_function = create_function('$x', 'return $x->category;');
        return $this->group_sources($key_function);
    }

    public function group_sources_by_match()
    {
        // Return the sources grouped by their query_person_match attribute.
        //
        // The return value is an array, a key in this array is a query_person_match
        // float and the value is a list of all the sources with this
        // query_person_match value.

        $key_function = create_function('$x', 'return $x->match;');
        return $this->group_sources($key_function);
    }

    public function to_array()
    {
        // Return a dict representation of the response.
        $d = array();

        if (!empty($this->http_status_code)) {
            $d['@http_status_code'] = $this->http_status_code;
        }
        if (!empty($this->visible_sources)) {
            $d['@visible_sources'] = $this->visible_sources;
        }
        if (!empty($this->available_sources)) {
            $d['@available_sources'] = $this->available_sources;
        }
        if (!empty($this->search_id)) {
            $d['@search_id'] = $this->search_id;
        }
        if (!empty($this->persons_count)) {
            $d['@persons_count'] = $this->persons_count;
        }

        if (!empty($this->warnings)) {
            $d['warnings'] = $this->warnings;
        }
        if (!empty($this->query)) {
            $d['query'] = $this->query->to_array();
        }
        if (!empty($this->person)) {
            $d['person'] = $this->person->to_array();
        }
        if (!empty($this->possible_persons)) {
            $d['possible_persons'] = array();
            foreach ($this->possible_persons as $possible_person) {
                $d['possible_persons'][] = $possible_person->to_array();
            }
        }
        if (!empty($this->sources)) {
            $d['sources'] = array();
            foreach ($this->sources as $source) {
                $d['sources'][] = $source->to_array();
            }
        }

        if (!empty($this->available_data)) {
            $d['available_data'] = $this->available_data->to_array();
        }

        if (!empty($this->match_requirements)) {
            $d['match_requirements'] = $this->match_requirements;
        }

        return $d;
    }

    public static function from_array($d, $headers = array())
    {
        // Transform the array to a response object and return the response.
        $warnings = !empty($d['warnings']) ? $d['warnings'] : array();
        $query = NULL;
        if (!empty($d['query'])) {
            $query = PiplApi_Person::from_array($d['query']);
        }
        $person = NULL;
        if (!empty($d['person'])) {
            $person = PiplApi_Person::from_array($d['person']);
        }

        $sources = array();
        if (array_key_exists("sources", $d) && count($d['sources']) > 0) {
            foreach ($d["sources"] as $source) {
                $sources[] = PiplApi_Source::from_array($source);
            }
        }

        $possible_persons = array();
        if (array_key_exists("possible_persons", $d) && count($d['possible_persons']) > 0) {
            foreach ($d["possible_persons"] as $possible_person) {
                $possible_persons[] = PiplApi_Person::from_array($possible_person);
            }
        }

        // Handle headers

        $qps_allotted = !empty($headers['x-apikey-qps-allotted']) ? intval($headers['x-apikey-qps-allotted']) : null;
        $qps_current = !empty($headers['x-apikey-qps-current']) ? intval($headers['x-apikey-qps-current']) : null;
        $quota_allotted = !empty($headers['x-apikey-quota-allotted']) ? intval($headers['x-apikey-quota-allotted']) : null;
        $quota_current = !empty($headers['x-apikey-quota-current']) ? intval($headers['x-apikey-quota-current']) : null;
        $quota_reset = !empty($headers['x-quota-reset']) ?
            DateTime::createFromFormat(PiplApi_Utils::PIPLAPI_DATE_QUOTA_RESET, $headers['x-quota-reset']) : null;

        // API V5 - New attributes

        $available_data = NULL;
        if (!empty($d['available_data'])) {
            $available_data = PiplApi_AvailableData::from_array($d['available_data']);
        }

        $match_requirements = NULL;
        if (!empty($d['match_requirements'])) {
            $match_requirements = $d['match_requirements'];
        }

        $source_category_requirements = NULL;
        if (!empty($d['source_category_requirements'])) {
            $source_category_requirements = $d['source_category_requirements'];
        }

        $persons_count = NULL;
        if (!empty($d['@persons_count'])) {
            $persons_count = $d['@persons_count'];
        }

        $response = new PiplApi_SearchAPIResponse($d["@http_status_code"], $query, $d["@visible_sources"],
            $d["@available_sources"], $d["@search_id"], $warnings, $person, $possible_persons, $sources,
            $available_data, $match_requirements, $source_category_requirements, $persons_count,
            $qps_allotted, $qps_current, $quota_allotted, $quota_current, $quota_reset);
        return $response;

    }

    public function name()
    {
        return ($this->person && count($this->person->names) > 0) ? $this->person->names[0] : NULL;
    }

    public function address()
    {
        return ($this->person && count($this->person->addresses) > 0) ? $this->person->addresses[0] : NULL;
    }

    public function phone()
    {
        return ($this->person && count($this->person->phones) > 0) ? $this->person->phones[0] : NULL;
    }

    public function email()
    {
        return ($this->person && count($this->person->emails) > 0) ? $this->person->emails[0] : NULL;
    }

    public function username()
    {
        return ($this->person && count($this->person->usernames) > 0) ? $this->person->usernames[0] : NULL;
    }

    public function user_id()
    {
        return ($this->person && count($this->person->user_ids) > 0) ? $this->person->user_ids[0] : NULL;
    }

    public function dob()
    {
        return ($this->person && $this->person->dob) ? $this->person->dob : NULL;
    }

    public function image()
    {
        return ($this->person && count($this->person->images) > 0) ? $this->person->images[0] : NULL;
    }

    public function job()
    {
        return ($this->person && count($this->person->jobs) > 0) ? $this->person->jobs[0] : NULL;
    }

    public function education()
    {
        return ($this->person && count($this->person->educations) > 0) ? $this->person->educations[0] : NULL;
    }

    public function gender()
    {
        return ($this->person && $this->person->gender) ? $this->person->gender : NULL;
    }

    public function ethnicity()
    {
        return ($this->person && count($this->person->ethnicities) > 0) ? $this->person->ethnicities[0] : NULL;
    }

    public function language()
    {
        return ($this->person && count($this->person->languages) > 0) ? $this->person->languages[0] : NULL;
    }

    public function origin_country()
    {
        return ($this->person && count($this->person->origin_countries) > 0) ? $this->person->origin_countries[0] : NULL;
    }

    public function relationship()
    {
        return ($this->person && count($this->person->relationships) > 0) ? $this->person->relationships[0] : NULL;
    }

    public function url()
    {
        return ($this->person && count($this->person->urls) > 0) ? $this->person->urls[0] : NULL;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return $this->to_array();
    }
}

class PiplApi_SearchAPIError extends PiplApi_APIError
{
    // An exception raised when the response from the search API contains an
    // error.
}

