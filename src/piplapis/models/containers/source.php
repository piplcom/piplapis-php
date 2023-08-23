<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/fields_container.php';
require_once dirname(__FILE__) . '/relationship.php';

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