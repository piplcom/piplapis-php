<?php

require_once dirname(__FILE__) . '/request_configuration.php';
require_once dirname(__FILE__) . '/utils.php';
require_once dirname(__FILE__) . '/containers/person.php';
require_once dirname(__FILE__) . '/fields/email.php';
require_once dirname(__FILE__) . '/fields/name.php';
require_once dirname(__FILE__) . '/fields/address.php';
require_once dirname(__FILE__) . '/fields/phone.php';
require_once dirname(__FILE__) . '/fields/date_range.php';
require_once dirname(__FILE__) . '/fields/job.php';
require_once dirname(__FILE__) . '/fields/url.php';
require_once dirname(__FILE__) . '/fields/username.php';
require_once dirname(__FILE__) . '/fields/user_id.php';
require_once dirname(__FILE__) . '/fields/tag.php';
require_once dirname(__FILE__) . '/fields/date_of_birth.php';


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
        
        if ($strict && isset($this->get_effective_configuration()->top_match) &&
            (!(is_bool($this->get_effective_configuration()->top_match) ||
                is_null($this->get_effective_configuration()->top_match)))
        ) {
            throw new InvalidArgumentException('top_match must be true, false or null');
        }

        if ($strict && $unsearchable = $this->person->unsearchable_fields()) {
            $display_strings = array_map(function($field) {
                return $field->get_representation();
            }, $unsearchable);
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
        // $request = new PiplApi_SearchAPIRequest(array('api_key' => 'YOUR_KEY',
        //                                                                 'email' => 'clark.kent@example.com'));
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
        if ($this->get_effective_configuration()->top_match) {
            $query['top_match'] = $this->get_effective_configuration()->top_match;
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
