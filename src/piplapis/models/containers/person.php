<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/fields_container.php';
require_once dirname(__FILE__) . '/relationship.php';

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
        $all = array_merge(
            $this->names,
            $this->emails, 
            $this->phones, 
            $this->usernames, 
            $this->vehicles, 
            $this->user_ids, 
            $this->urls
        );

        $searchable = array_filter($all, function($field) {
            return $field->is_searchable();
        });
        $searchable_address = array_filter($this->addresses, function($field) {
            return $field->is_sole_searchable();
        });
        return $searchable_address or $this->search_pointer or count($searchable) > 0;
    }

    public function unsearchable_fields()
    {
        // An array of all the fields that are invalid and won't be used in the search.

        // For example: names/usernames that are too short, emails that are
        // invalid etc.
        $all = array_merge(
            $this->names, 
            $this->emails, 
            $this->phones, 
            $this->usernames, 
            $this->vehicles, 
            $this->addresses,
            $this->user_ids, 
            $this->urls, 
            array($this->dob)
        );
        $unsearchable = array_filter($all, function($field) {
            return $field && !$field->is_searchable();
        });
        return $unsearchable;
    }

    public static function from_array($params, $is_query=false)
    {
        // Transform the array to a person object and return it.
        $id = !empty($params['@id']) ? $params['@id'] : NULL;
        $search_pointer = !empty($params['@search_pointer']) ? $params['@search_pointer'] : NULL;
        $match = !empty($params['@match']) ? $params['@match'] : NULL;
        $inferred = !empty($params['@inferred']) ? $params['@inferred'] : false;

        $instance = new self(array(), $id, $search_pointer, $match, $inferred);
        $instance->add_fields($instance->fields_from_array($params, $is_query));
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