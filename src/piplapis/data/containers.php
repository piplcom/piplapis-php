<?php

require_once dirname(__FILE__) . '/fields.php';
require_once dirname(__FILE__) . '/source.php';
require_once dirname(__FILE__) . '/utils.php';


class PiplApi_FieldsContainer
{
    // The base class of Record and Person, made only for inheritance.
    
    public $names = array();
    public $addresses = array();
    public $phones = array();
    public $emails = array();
    public $jobs = array();
    public $educations = array();
    public $images = array();
    public $usernames = array();
    
    public $user_ids = array();
    public $dobs = array();
    public $related_urls = array();
    public $relationships = array();
    public $tags = array();
    
    public static $CLASS_CONTAINER = array(
        'PiplApi_Name' => 'names',
        'PiplApi_Address' => 'addresses', 
        'PiplApi_Phone' => 'phones', 
        'PiplApi_Email' => 'emails', 
        'PiplApi_Job' => 'jobs', 
        'PiplApi_Education' => 'educations', 
        'PiplApi_Image' => 'images', 
        'PiplApi_Username' => 'usernames', 
        'PiplApi_UserID' => 'user_ids', 
        'PiplApi_DOB' => 'dobs', 
        'PiplApi_RelatedURL' => 'related_urls', 
        'PiplApi_Relationship' => 'relationships', 
        'PiplApi_Tag' => 'tags'
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
            
            if (array_key_exists($cls, self::$CLASS_CONTAINER))
            {
                $container = self::$CLASS_CONTAINER[$cls];
                $this->{$container}[] = $field;
            }
            else
            {
                $type = empty($cls) ? gettype($field) : $cls;
                throw new InvalidArgumentException('Object of type ' . $type . ' is an invalid field');
            }
        }
    }
    
    public function all_fields()
    {
        // An array with all the fields contained in this object.
        $allfields = array();
        foreach (array_values(PiplApi_FieldsContainer::$CLASS_CONTAINER) as $val)
        {
            $allfields = array_merge($allfields, $this->{$val});
        }
        
        return $allfields;
    }

    public static function fields_from_dict($d)
    {
        // Load the fields from the dict, return an array with all the fields.
        
        $fields = array();
        
        foreach (array_keys(PiplApi_FieldsContainer::$CLASS_CONTAINER) as $field_cls)
        {
            $container = PiplApi_FieldsContainer::$CLASS_CONTAINER[$field_cls];
            if (array_key_exists($container, $d))
            {
                $field_dict = $d[$container];
                foreach ($field_dict as $x)
                {
                    $from_dict_func = method_exists($field_cls, 'from_dict') ? array($field_cls, 'from_dict') : array('PiplApi_Field', 'from_dict');
                    $fields[] = call_user_func($from_dict_func, $field_cls, $x);
                }
            }
        }
        return $fields;
    }

    public function fields_to_dict()
    {
        // Transform the object to a dict and return the dict.
        $d = array();
        
        foreach (array_values(PiplApi_FieldsContainer::$CLASS_CONTAINER) as $container)
        {
            $fields = $this->{$container};
            if (!empty($fields))
            {
                $allfields = array_map(create_function('$x', 'return $x->to_dict();'), $fields);
                
                if (count($allfields) > 0)
                {
                    $d[$container] = $allfields;
                }
            }
        }
        return $d;
    }
}

class PiplApi_Record extends PiplApi_FieldsContainer
{
    // A record is all the data available in a specific source. 
    // 
    // Every record object is based on a source which is basically the URL of the 
    // page where the data is available, and the data itself that comes as field
    // objects (Name, Address, Email etc. see fields.php).
    // 
    // Each type of field has its own container (note that Record is a subclass 
    // of FieldsContainer).
    // For example:
    // 
    // require_once dirname(__FILE__) . '/data/containers.php';
    // $fields = array(new PiplApi_Email(array('address' => 'eric@cartman.com')), new PiplApi_Phone(array('number' => 999888777)));
    // $record = new PiplApi_Record(array('fields' => $fields));
    // print implode(', ', $record->emails); // Outputs "PiplApi_Email(address=eric@cartman.com)"
    // print implode(', ', $record->phones); // Outputs "PiplApi_Phone(number=999888777, display=, display_international=)"
    // 
    // Records come as results for a query and therefore they have attributes that 
    // indicate if and how much they match the query. They also have a validity 
    // timestamp available as an attribute.
    
    public $source;
    public $query_params_match;
    public $query_person_match;
    public $valid_since;
    
    function __construct($params=array())
    {
        // Extend FieldsContainer::__construct and set the record's source
        // and attributes.
        // 
        // Args:
        // 
        // fields -- An array of fields (from fields.php).
        // source -- A Source object (PiplApi_Source).
        // query_params_match -- A bool value that indicates whether the record 
        //                       contains all the params from the query or not.
        // query_person_match -- A float between 0.0 and 1.0 that indicates how 
        //                       likely it is that this record holds data about 
        //                       the person from the query.
        //                       Higher value means higher likelihood, value 
        //                       of 1.0 means "this is definitely him".
        //                       This value is based on Pipl's statistical 
        //                       algorithm that takes into account many parameters
        //                       like the popularity of the name/address (if there 
        //                       was a name/address in the query) etc.
        // valid_since -- A DateTime object, this is the first time 
        //                Pipl's crawlers saw this record.
        parent::__construct(!empty($params['fields']) ? $params['fields'] : array());
        
        $this->source = !empty($params['source']) ? $params['source'] : new PiplApi_Source();
        
        if (isset($params['query_params_match']))
        {
            $this->query_params_match = $params['query_params_match'];
        }
        
        if (!empty($params['query_person_match']))
        {
            $this->query_person_match = $params['query_person_match'];
        }
        
        if (!empty($params['valid_since']))
        {
            $this->valid_since = $params['valid_since'];
        }
    }
    
    public static function from_dict($d)
    {
        // Transform the dict to a record object and return the record.
        $query_params_match = isset($d['@query_params_match']) ? $d['@query_params_match'] : NULL;
        $query_person_match = !empty($d['@query_person_match']) ? $d['@query_person_match'] : NULL;
        $valid_since = !empty($d['@valid_since']) ? $d['@valid_since'] : NULL;
        
        if (!empty($valid_since))
        {
            $valid_since = piplapi_str_to_datetime($valid_since);
        }
        
        $sourcedict = !empty($d['source']) ? $d['source'] : array();
        $source = PiplApi_Field::from_dict('PiplApi_Source', $sourcedict);
        
        $fields = PiplApi_Record::fields_from_dict($d);

        return new PiplApi_Record(array('source' => $source,
                                                      'fields' => $fields,
                                                      'query_params_match' => $query_params_match,
                                                      'query_person_match' => $query_person_match,
                                                      'valid_since' => $valid_since));
    }

    public function to_dict()
    {
        // Return a dict representation of the record.
        $d = array();
        
        if (isset($this->query_params_match))
        {
            $d['@query_params_match'] = $this->query_params_match;
        }
        
        if (!empty($this->query_person_match))
        {
            $d['@query_person_match'] = $this->query_person_match;
        }
        
        if (!empty($this->valid_since))
        {
            $d['@valid_since'] = piplapi_datetime_to_str($this->valid_since);
        }
        
        if (!empty($this->source))
        {
            $d['source'] = $this->source->to_dict();
        }
        
        return array_merge($d, $this->fields_to_dict());
    }
}


class PiplApi_Person extends PiplApi_FieldsContainer
{
    // A Person object is all the data available on an individual.
    // 
    // The Person object is essentially very similar in its structure to the 
    // Record object, the main difference is that data about an individual can 
    // come from multiple sources while a record is data from one source.
    // 
    // The person's data comes as field objects (Name, Address, Email etc. see 
    // fields.php).
    // Each type of field has its on container (note that Person is a subclass 
    // of FieldsContainer).
    // For example:
    // 
    // require_once dirname(__FILE__) . '/data/containers.php';
    // $fields = array(new PiplApi_Email(array('address' => 'eric@cartman.com')), new PiplApi_Phone(array('number' => 999888777)));
    // $person = new PiplApi_Person(array('fields' => $fields));
    // print implode(', ', $person->emails); // Outputs "PiplApi_Email(address=eric@cartman.com)"
    // print implode(', ', $person->phones); // Outputs "PiplApi_Phone(number=999888777, display=, display_international=)"
    // 
    // Note that a person object is used in the Search API in two ways:
    // - It might come back as a result for a query (see PiplApi_SearchAPIResponse).
    // - It's possible to build a person object with all the information you 
    //   already have about the person you're looking for and send this object as 
    //   the query (see PiplApi_SearchAPIRequest).
    
    public $sources;
    public $query_params_match;
    
    function __construct($params=array())
    {
        // Extend FieldsContainer.initialize and set the record's sources
        // and query_params_match attribute.
        // 
        // Args:
        // 
        // fields -- An array of fields (fields.php).
        // sources -- An array of Source objects (source.php).
        // query_params_match -- A bool value that indicates whether the record 
        //                       contains all the params from the query or not.
        parent::__construct(!empty($params['fields']) ? $params['fields'] : array());
        $this->sources = !empty($params['sources']) ? $params['sources'] : array();
        
        if (!empty($params['query_params_match']))
        {
            $this->query_params_match = $params['query_params_match'];
        }
    }
    
    public function is_searchable()
    {
        // A bool value that indicates whether the person has enough data and
        // can be sent as a query to the API.
        $all = array_merge( $this->names,
                                   $this->emails,
                                   $this->phones,
                                   $this->usernames);
        $searchable = array_filter($all, create_function('$field', 'return $field->is_searchable();'));
        return count($searchable) > 0;
    }
    
    public function unsearchable_fields()
    {
        // An array of all the fields that can't be searched by.
        
        // For example: names/usernames that are too short, emails that are 
        // invalid etc.
        $all = array_merge( $this->names,
                                   $this->emails,
                                   $this->phones,
                                   $this->usernames,
                                   $this->addresses,
                                   $this->dobs);
        $unsearchable = array_filter($all, create_function('$field', 'return !$field->is_searchable();'));
        return $unsearchable;
    }

    public static function from_dict($d)
    {
        // Transform the dict to a person object and return the person.
        $query_params_match = !empty($d['@query_params_match']) ? $d['@query_params_match'] : NULL;
        $all_sources = !empty($d['sources']) ? $d['sources'] : array();
        $sources = array_map(create_function('$src', 'return PiplApi_Field::from_dict(\'PiplApi_Source\', $src);'), $all_sources);
        $fields = PiplApi_Person::fields_from_dict($d);
        
        return new PiplApi_Person(array(    'fields' => $fields,
                                                          'sources' => $sources,
                                                          'query_params_match' => $query_params_match));
    }

    public function to_dict()
    {
        // Return a dict representation of the person.
        $d = array();
        
        if (isset($this->query_params_match))
        {
            $d['@query_params_match'] = $this->query_params_match;
        }
        
        if (!empty($this->sources))
        {
            $d['sources'] = array_map(create_function('$x', 'return $x->to_dict();'), $this->sources);
        }
        
        return array_merge($d, $this->fields_to_dict());
    }
}
?>