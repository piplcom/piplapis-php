<?php

require_once dirname(__FILE__) . '/utils.php';
require_once dirname(__FILE__) . '/containers/person.php';
require_once dirname(__FILE__) . '/containers/available_data.php';

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
    public $qps_live_allotted;
    public $qps_live_current;
    public $qps_demo_allotted;
    public $qps_demo_current;
    public $demo_usage_allotted;
    public $demo_usage_current;
    public $demo_usage_expiry;

    public function __construct($http_status_code, $query, $visible_sources, $available_sources, $search_id, $warnings,
                                $person, $possible_persons, $sources, $available_data = NULL,
                                $match_requirements = NULL, $source_category_requirements = NULL, $persons_count = NULL,
                                $qps_allotted = NULL, $qps_current = NULL, $quota_allotted = NULL, $quota_current = NULL,
                                $quota_reset = NULL, $qps_live_allotted = NULL, $qps_live_current = NULL,
                                $qps_demo_allotted = NULL, $qps_demo_current = NULL, $demo_usage_allotted = NULL,
                                $demo_usage_current = NULL, $demo_usage_expiry = NULL)
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
        // qps_live_allotted - Your permitted queries per second
        // qps_live_current - The number of queries that you've run in the same second as this one.
        // qps_demo_allotted - Your permitted queries per second
        // qps_demo_current - The number of queries that you've run in the same second as this one.
        // demo_usage_allotted - Your permitted demo queries
        // demo_usage_current - The number of demo queries that you've already run
        // demo_usage_expiry -  The expiry time of your demo usage

        $this->qps_allotted = $qps_allotted;
        $this->qps_current = $qps_current;
        $this->qps_live_allotted = $qps_live_allotted;
        $this->qps_live_current = $qps_live_current;
        $this->qps_demo_allotted = $qps_demo_allotted;
        $this->qps_demo_current = $qps_demo_current;
        $this->quota_allotted = $quota_allotted;
        $this->quota_current = $quota_current;
        $this->quota_reset = $quota_reset;
        $this->demo_usage_allotted = $demo_usage_allotted;
        $this->demo_usage_current = $demo_usage_current;
        $this->demo_usage_expiry = $demo_usage_expiry;

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

        $key_function = function($x) {
            return $x->domain;
        };
        return $this->group_sources($key_function);
    }

    public function group_sources_by_category()
    {
        // Return the sources grouped by their category.
        //
        // The return value is an array, a key in this array is a category
        // and the value is a list of all the sources with this category.

        $key_function = function($x) {
            return $x->category;
        };
        return $this->group_sources($key_function);
    }

    public function group_sources_by_match()
    {
        // Return the sources grouped by their query_person_match attribute.
        //
        // The return value is an array, a key in this array is a query_person_match
        // float and the value is a list of all the sources with this
        // query_person_match value.

        $key_function = function($x) {
            return $x->match;
        };
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
            $query = PiplApi_Person::from_array($d['query'], true);
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

        $qps_allotted = !empty($headers['x-qps-allotted']) ? intval($headers['x-qps-allotted']) : null;
        $qps_current = !empty($headers['x-qps-current']) ? intval($headers['x-qps-current']) : null;
        $quota_allotted = !empty($headers['x-apikey-quota-allotted']) ? intval($headers['x-apikey-quota-allotted']) : null;
        $quota_current = !empty($headers['x-apikey-quota-current']) ? intval($headers['x-apikey-quota-current']) : null;
        $quota_reset = !empty($headers['x-quota-reset']) ?
            DateTime::createFromFormat(PiplApi_Utils::PIPLAPI_DATE_QUOTA_RESET, $headers['x-quota-reset']) : null;
        $qps_live_allotted = !empty($headers['x-qps-live-allotted']) ? intval($headers['x-qps-live-allotted']) : null;
        $qps_live_current = !empty($headers['x-qps-live-current']) ? intval($headers['x-qps-live-current']) : null;
        $qps_demo_allotted = !empty($headers['x-qps-demo-allotted']) ? intval($headers['x-qps-demo-allotted']) : null;
        $qps_demo_current = !empty($headers['x-qps-demo-current']) ? intval($headers['x-qps-demo-current']) : null;
        $demo_usage_allotted = !empty($headers['x-demo-usage-allotted']) ? intval($headers['x-demo-usage-allotted']) : null;
        $demo_usage_current = !empty($headers['x-demo-usage-current']) ? intval($headers['x-demo-usage-current']) : null;
        $demo_usage_expiry = !empty($headers['x-demo-usage-expiry']) ?
            DateTime::createFromFormat(PiplApi_Utils::PIPLAPI_DATE_QUOTA_RESET, $headers['x-demo-usage-expiry']) : null;


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
            $qps_allotted, $qps_current, $quota_allotted, $quota_current, $quota_reset, $qps_live_allotted,
            $qps_live_current, $qps_demo_allotted, $qps_demo_current, $demo_usage_allotted,
            $demo_usage_current, $demo_usage_expiry);
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

    public function vehicle()
    {
        return ($this->person && count($this->person->vehicles) > 0) ? $this->person->vehicles[0] : NULL;
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
