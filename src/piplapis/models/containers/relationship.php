<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/fields_container.php';

class PiplApi_Relationship extends PiplApi_FieldsContainer
{
    // Name of another person related to this person.

    protected $types_set = array('friend', 'family', 'work', 'other');

    public $type;
    public $subtype;
    public $valid_since;
    public $inferred;
    public $last_seen;

    function __construct(
        $fields = array(),
        $type = NULL, 
        $subtype = NULL, 
        $valid_since = NULL, 
        $inferred = NULL, 
        $last_seen = NULL
    ){
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
        $this->last_seen = $last_seen;
    }

    public static function from_array($class_name, $params)
    {
        // Transform the array to a person object and return it.
        $type = !empty($params['@type']) ? $params['@type'] : NULL;
        $subtype = !empty($params['@subtype']) ? $params['@subtype'] : NULL;
        $valid_since = !empty($params['@valid_since']) ? $params['@valid_since'] : NULL;
        $inferred = !empty($params['@inferred']) ? $params['@inferred'] : NULL;
        $last_seen = !empty($params['@last_seen']) ? $params['@last_seen'] : NULL;

        $instance = new self(array(), $type, $subtype, $valid_since, $inferred, $last_seen);
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
        if (!empty($this->last_seen)){ $d['@last_seen'] = $this->last_seen; }

        return array_merge($d, $this->fields_to_array());
    }


}