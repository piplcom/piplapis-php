<?php

require_once dirname(__FILE__) . '/fields.php';
require_once dirname(__FILE__) . '/utils.php';


class PiplApi_FieldsContainer implements JsonSerializable
{
    // The base class of Record and Person, made only for inheritance.

    public $names = array();
    public $addresses = array();
    public $phones = array();
    public $emails = array();
    public $jobs = array();
    public $ethnicities = array();
    public $origin_countries = array();
    public $languages = array();
    public $educations = array();
    public $images = array();
    public $usernames = array();
    public $user_ids = array();
    public $urls = array();
    public $dob;
    public $gender;

    protected $CLASS_CONTAINER = array(
        'PiplApi_Name' => 'names',
        'PiplApi_Address' => 'addresses',
        'PiplApi_Phone' => 'phones',
        'PiplApi_Email' => 'emails',
        'PiplApi_Job' => 'jobs',
        'PiplApi_Ethnicity' => 'ethnicities',
        'PiplApi_OriginCountry' => 'origin_countries',
        'PiplApi_Language' => 'languages',
        'PiplApi_Education' => 'educations',
        'PiplApi_Image' => 'images',
        'PiplApi_Username' => 'usernames',
        'PiplApi_UserID' => 'user_ids',
        'PiplApi_URL' => 'urls'
    );

    protected $singular_fields = array(
        'PiplApi_DOB' => 'dob',
        'PiplApi_Gender' => 'gender',
    );

    function __construct($fields=array())
    {
        // `fields` is an array of field objects from
        // fields.php.
        $this->add_fields($fields);
    }

    public function add_fields($fields)
    {
        // Add the fields to their corresponding container.
        // `fields` is an array of field objects from fields.php
        if (empty($fields))
        {
            return;
        }

        foreach ($fields as $field)
        {
            $cls = is_object($field) ? get_class($field) : NULL;

            if (array_key_exists($cls, $this->CLASS_CONTAINER))
            {
                $container = $this->CLASS_CONTAINER[$cls];
                $this->{$container}[] = $field;
            } elseif(array_key_exists($cls, $this->singular_fields)) {
                $this->{$this->singular_fields[$cls]} = $field;
            } else {
                $type = empty($cls) ? gettype($field) : $cls;
                throw new InvalidArgumentException('Object of type ' . $type . ' is an invalid field');
            }
        }
    }

    public function all_fields()
    {
        // An array with all the fields contained in this object.
        $allfields = array();
        foreach (array_values($this->CLASS_CONTAINER) as $val){
            $allfields = array_merge($allfields, $this->{$val});
        }
        foreach (array_values($this->singular_fields) as $val){
            if($this->{$val}) {
                $allfields[] = $this->{$val};
            }
        }

        return $allfields;
    }

    public function fields_from_array($d)
    {
        // Load the fields from the dict, return an array with all the fields.

        $fields = array();

        foreach (array_keys($this->CLASS_CONTAINER) as $field_cls){
            $container = $this->CLASS_CONTAINER[$field_cls];
            if (array_key_exists($container, $d)) {
                $field_array = $d[$container];
                foreach ($field_array as $x) {
                    $from_array_func = method_exists($field_cls, 'from_array') ? array($field_cls, 'from_array') : array('PiplApi_Field', 'from_array');
                    $fields[] = call_user_func($from_array_func, $field_cls, $x);
                }
            }
        }
        foreach (array_keys($this->singular_fields) as $field_cls){
            $container = $this->singular_fields[$field_cls];
            if (array_key_exists($container, $d)) {
                $field_array = $d[$container];
                $from_array_func = method_exists($field_cls, 'from_array') ? array($field_cls, 'from_array') : array('PiplApi_Field', 'from_array');
                $fields[] = call_user_func($from_array_func, $field_cls, $field_array);
            }
        }
        return $fields;
    }

    public function fields_to_array(){
        // Transform the object to an array and return it.
        $d = array();

        foreach (array_values($this->CLASS_CONTAINER) as $container){
            $fields = $this->{$container};
            if (!empty($fields)){
                $all_fields = array();
                foreach($fields as $field) {
                    $all_fields[] = $field->to_array();
                }
                if (count($all_fields) > 0){
                    $d[$container] = $all_fields;
                }
            }
        }
        foreach (array_values($this->singular_fields) as $container){
            $field = $this->{$container};
            if (!empty($field)){
                $d[$container] =  $field->to_array();
            }
        }
        return $d;
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

class PiplApi_Source extends PiplApi_FieldsContainer
{
    // A source is a single source of data.
    //
    // Every source object is based on the URL of the
    // page where the data is available, and the data itself that comes as field
    // objects (Name, Address, Email etc. see fields.php).
    //
    // Each type of field has its own container (note that Source is a subclass
    // of FieldsContainer).
    //
    // Sources come as results for a query and therefore they have attributes that
    // indicate if and how much they match the query. They also have a validity
    // timestamp available as an attribute.

    private $extended_containers = array(
        'PiplApi_Relationship' => 'relationships',
        'PiplApi_Tag' => 'tags'
    );
    public $name;
    public $category;
    public $origin_url;
    public $sponsored;
    public $domain;
    public $person_id;
    public $id;
    public $premium;
    public $match;
    public $valid_since;
    public $relationships = array();
    public $tags = array();

    function __construct($fields = array(), $match = NULL, $name = NULL, $category = NULL, $origin_url = NULL,
                         $sponsored = NULL, $domain = NULL, $person_id = NULL, $id = NULL,
                         $premium = NULL, $valid_since = NULL){
        // Extend FieldsContainer::__construct and set the record's source
        // and attributes.
        //
        // Args:
        //  $fields -- an array of fields
        //  $match -- A float between 0.0 and 1.0 that indicates how likely it is that this source holds data about
        //      the person from the query. Higher value means higher likelihood, value of 1.0
        //      means "this is definitely him". This value is based on Pipl's statistical algorithm that takes
        //      into account many parameters like the popularity of the name/address
        //      (if there was a name/address in the query) etc.
        //  $name -- A string, the source name
        //  $category -- A string, the source category
        //  $origin_url -- A string, the URL where Pipl's crawler found this data
        //  $sponsored -- A boolean, whether the source is a sponsored result or not
        //  $domain -- A string, the domain of this source
        //  $person_id -- A string, the person's unique ID
        //  $source_id -- A string, the source ID
        //  $premium -- A boolean, whether this is a premium source
        //  $valid_since -- A DateTime object, this is the first time Pipl's crawlers saw this source.
        $this->CLASS_CONTAINER = array_merge($this->CLASS_CONTAINER, $this->extended_containers);
        parent::__construct($fields);
        $this->name = $name;
        $this->category = $category;
        $this->origin_url = $origin_url;
        $this->sponsored = $sponsored;
        $this->domain = $domain;
        $this->person_id = $person_id;
        $this->id = $id;
        $this->premium = $premium;
        $this->match = $match;
        $this->valid_since = $valid_since;
    }

    public static function from_array($params)
    {
        // Transform the dict to a record object and return the record.
        $name = !empty($params['@name']) ? $params['@name'] : NULL;
        $match = !empty($params['@match']) ? $params['@match'] : NULL;
        $category = !empty($params['@category']) ? $params['@category'] : NULL;
        $origin_url = !empty($params['@origin_url']) ? $params['@origin_url'] : NULL;
        $sponsored = !empty($params['@sponsored']) ? $params['@sponsored'] : NULL;
        $domain = !empty($params['@domain']) ? $params['@domain'] : NULL;
        $person_id = !empty($params['@person_id']) ? $params['@person_id'] : NULL;
        $source_id = !empty($params['@id']) ? $params['@id'] : NULL;
        $premium = !empty($params['@premium']) ? $params['@premium'] : NULL;
        $valid_since = !empty($params['@valid_since']) ? $params['@valid_since'] : NULL;
        if (!empty($valid_since)){ $valid_since = PiplApi_Utils::piplapi_str_to_datetime($valid_since); }

        $instance = new self(array(), $match, $name, $category, $origin_url, $sponsored, $domain, $person_id,
            $source_id, $premium, $valid_since);
        $instance->add_fields($instance->fields_from_array($params));
        return $instance;
    }

    public function to_array()
    {
        // Return an array representation of the record.
        $d = array();
        if (!empty($this->valid_since)){ $d['@valid_since'] = PiplApi_Utils::piplapi_datetime_to_str($this->valid_since); }
        if (!empty($this->match)){ $d['@match'] = $this->match; }
        if (!empty($this->category)){ $d['@category'] = $this->category; }
        if (!empty($this->origin_url)){ $d['@origin_url'] = $this->origin_url; }
        if (!empty($this->sponsored)){ $d['@sponsored'] = $this->sponsored; }
        if (!empty($this->domain)){ $d['@domain'] = $this->domain; }
        if (!empty($this->person_id)){ $d['@person_id'] = $this->person_id; }
        if (!empty($this->id)){ $d['@source_id'] = $this->id; }
        if (!empty($this->premium)){ $d['@premium'] = $this->premium; }

        return array_merge($d, $this->fields_to_array());
    }
}


class PiplApi_Person extends PiplApi_FieldsContainer
{
    // A Person object is all the data available on an individual.
    //
    // The Person object is essentially very similar in its structure to the
    // Source object, the main difference is that data about an individual can
    // come from multiple sources.
    //
    // The person's data comes as field objects (Name, Address, Email etc. see fields.php).
    // Each type of field has its on container (note that Person is a subclass of FieldsContainer).
    //
    // For example:
    //
    // require_once dirname(__FILE__) . '/data/containers.php';
    // $fields = array(new PiplApi_Email(array('address' => 'clark.kent@example.com')), new PiplApi_Phone(array('number' => 9785550145)));
    // $person = new PiplApi_Person(array('fields' => $fields));
    // print implode(', ', $person->emails); // Outputs "clark.kent@example.com"
    // print implode(', ', $person->phones); // Outputs "+1-9785550145"
    //
    // Note that a person object is used in the Search API in two ways:
    // - It might come back as a result for a query (see PiplApi_SearchAPIResponse).
    // - It's possible to build a person object with all the information you
    //   already have about the person you're looking for and send this object as
    //   the query (see PiplApi_SearchAPIRequest).

    private $extended_containers = array(
        'PiplApi_Relationship' => 'relationships'
    );
    public $id;
    public $search_pointer;
    public $match;
    public $inferred;
    public $relationships = array();

    function __construct($fields = array(), $id = NULL, $search_pointer = NULL, $match = NULL, $inferred = false)
    {
        // Extend FieldsContainer.initialize and set the record's sources
        // and query_params_match attribute.
        //
        // Args:
        //
        // $fields -- An array of fields (fields.php).
        // $match -- A float value, the person's match score.
        // $id -- GUID. The person's ID.
        // $search_pointer -- string. Can be used for drill down searches.
        $this->CLASS_CONTAINER = array_merge($this->CLASS_CONTAINER, $this->extended_containers);
        parent::__construct($fields);
        $this->search_pointer = $search_pointer;
        $this->match = $match;
        $this->id = $id;
        $this->inferred = $inferred;
    }

    public function is_searchable()
    {
        // A bool value that indicates whether the person has enough data and
        // can be sent as a query to the API.
        $all = array_merge($this->names, $this->emails, $this->phones, $this->usernames, $this->user_ids, $this->urls);
        $searchable = array_filter($all, create_function('$field', 'return $field->is_searchable();'));
        $searchable_address = array_filter($this->addresses,
            create_function('$field', 'return $field->is_sole_searchable();'));
        return $searchable_address or $this->search_pointer or count($searchable) > 0;
    }

    public function unsearchable_fields()
    {
        // An array of all the fields that are invalid and won't be used in the search.

        // For example: names/usernames that are too short, emails that are
        // invalid etc.
        $all = array_merge($this->names, $this->emails, $this->phones, $this->usernames, $this->addresses,
            $this->user_ids, $this->urls, array($this->dob));
        $unsearchable = array_filter($all, create_function('$field', 'return $field && !$field->is_searchable();'));
        return $unsearchable;
    }

    public static function from_array($params)
    {
        // Transform the array to a person object and return it.
        $id = !empty($params['@id']) ? $params['@id'] : NULL;
        $search_pointer = !empty($params['@search_pointer']) ? $params['@search_pointer'] : NULL;
        $match = !empty($params['@match']) ? $params['@match'] : NULL;
        $inferred = !empty($params['@inferred']) ? $params['@inferred'] : false;

        $instance = new self(array(), $id, $search_pointer, $match, $inferred);
        $instance->add_fields($instance->fields_from_array($params));
        return $instance;
    }

    public function to_array()
    {
        // Return an array representation of the person.
        $d = array();

        if (!empty($this->id)){ $d['@id'] = $this->id; }
        if (!is_null($this->match)){ $d['@match'] = $this->match; }
        if (!empty($this->search_pointer)){ $d['@search_pointer'] = $this->search_pointer; }
        if ($this->inferred){ $d['@inferred'] = $this->inferred; }

        return array_merge($d, $this->fields_to_array());
    }

}

class PiplApi_Relationship extends PiplApi_FieldsContainer
{
    // Name of another person related to this person.

    protected $types_set = array('friend', 'family', 'work', 'other');

    public $type;
    public $subtype;
    public $valid_since;
    public $inferred;

    function __construct($fields = array(), $type = NULL, $subtype = NULL, $valid_since = NULL, $inferred = NULL)
    {
        parent::__construct($fields);

        // `fields` is an array of data fields (see fields.php)
        //
        // `type` and `subtype` should both be strings.
        // `type` is one of PiplApi_Relationship::$types_set.
        //
        // `subtype` is not restricted to a specific list of possible values (for
        // example, if type is "family" then subtype can be "Father", "Mother",
        // "Son" and many other things).
        //
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.
        // `inferred` is a boolean, indicating whether this field includes inferred data.
        $this->type = $type;
        $this->subtype = $subtype;
        $this->valid_since = $valid_since;
        $this->inferred = $inferred;
    }

    public static function from_array($class_name, $params)
    {
        // Transform the array to a person object and return it.
        $type = !empty($params['@type']) ? $params['@type'] : NULL;
        $subtype = !empty($params['@subtype']) ? $params['@subtype'] : NULL;
        $valid_since = !empty($params['@valid_since']) ? $params['@valid_since'] : NULL;
        $inferred = !empty($params['@inferred']) ? $params['@inferred'] : NULL;

        $instance = new self(array(), $type, $subtype, $valid_since, $inferred);
        $instance->add_fields($instance->fields_from_array($params));
        return $instance;
    }
    public function __toString(){
        return count($this->names) > 0 && $this->names[0]->first ? $this->names[0]->first : "";
    }
    public function to_array()
    {
        // Return an array representation of the person.
        $d = array();

        if (!empty($this->valid_since)){ $d['@valid_since'] = $this->valid_since; }
        if (!empty($this->inferred)){ $d['@inferred'] = $this->inferred; }
        if (!empty($this->type)){ $d['@type'] = $this->type; }
        if (!empty($this->subtype)){ $d['@subtype'] = $this->subtype; }

        return array_merge($d, $this->fields_to_array());
    }


}

class PiplApi_AvailableData
{
    function __construct($basic = NULL, $premium = NULL)
    {
        $this->basic = $basic ? PiplApi_FieldCount::from_array($basic) : NULL;
        $this->premium = $premium ? PiplApi_FieldCount::from_array($premium) : NULL;

    }
    public static function from_array($params) {
        $basic = !empty($params['basic']) ? $params['basic'] : NULL;
        $premium = !empty($params['premium']) ? $params['premium'] : NULL;
        $instance = new self($basic, $premium);
        return $instance;
    }
    public function to_array() {
        $res = array();
        if ($this->basic != NULL)
            $res['basic'] = $this->basic->to_array();
        if ($this->premium != NULL)
            $res['premium'] = $this->premium->to_array();
        return $res;
    }
}

class PiplApi_FieldCount
{
    protected $attributes = array(
        'addresses', 'ethnicities', 'emails', 'dobs', 'genders', 'user_ids', 'social_profiles',
        'educations', 'jobs', 'images', 'languages', 'origin_countries', 'names', 'phones',
        'relationships', 'usernames'
    );
    function __construct($dobs = NULL, $images = NULL, $educations = NULL, $addresses = NULL, $jobs = NULL,
                         $genders = NULL, $ethnicities = NULL, $phones = NULL, $origin_countries = NULL,
                         $usernames = NULL, $languages = NULL, $emails = NULL, $user_ids = NULL, $relationships = NULL,
                         $names = NULL, $social_profiles = NULL)
    {
        $this->dobs = $dobs;
        $this->images = $images;
        $this->educations = $educations;
        $this->addresses = $addresses;
        $this->jobs = $jobs;
        $this->genders = $genders;
        $this->ethnicities = $ethnicities;
        $this->phones = $phones;
        $this->origin_countries = $origin_countries;
        $this->usernames = $usernames;
        $this->languages = $languages;
        $this->emails = $emails;
        $this->user_ids = $user_ids;
        $this->relationships = $relationships;
        $this->names = $names;
        $this->social_profiles = $social_profiles;
    }
    public static function from_array($params) {
        $dobs = !empty($params['dobs']) ? $params['dobs'] : NULL;
        $images =!empty($params['images']) ? $params['images'] : NULL;
        $educations =!empty($params['educations']) ? $params['educations'] : NULL;
        $addresses =!empty($params['addresses']) ? $params['addresses'] : NULL;
        $jobs =!empty($params['jobs']) ? $params['jobs'] : NULL;
        $genders =!empty($params['genders']) ? $params['genders'] : NULL;
        $ethnicities =!empty($params['ethnicities']) ? $params['ethnicities'] : NULL;
        $phones =!empty($params['phones']) ? $params['phones'] : NULL;
        $origin_countries =!empty($params['origin_countries']) ? $params['origin_countries'] : NULL;
        $usernames =!empty($params['usernames']) ? $params['usernames'] : NULL;
        $languages =!empty($params['languages']) ? $params['languages'] : NULL;
        $emails =!empty($params['emails']) ? $params['emails'] : NULL;
        $user_ids =!empty($params['user_ids']) ? $params['user_ids'] : NULL;
        $relationships =!empty($params['relationships']) ? $params['relationships'] : NULL;
        $names =!empty($params['names']) ? $params['names'] : NULL;
        $social_profiles =!empty($params['social_profiles']) ? $params['social_profiles'] : NULL;

        $instance = new self($dobs, $images, $educations, $addresses, $jobs,
            $genders, $ethnicities, $phones, $origin_countries,
            $usernames, $languages, $emails, $user_ids, $relationships,
            $names, $social_profiles);
        return $instance;
    }
    public function to_array() {
        $res = array();
        foreach($this->attributes as $attr) {
            if ($this->$attr > 0)
                $res[$attr] = $this->$attr;
        }
        return $res;
    }
}