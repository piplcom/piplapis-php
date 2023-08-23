<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';

class PiplApi_Ethnicity extends PiplApi_Field
{

//  An ethnicity field.
//  The content will be a string with one of the following values (based on US census definitions)
//        'white', 'black', 'american_indian', 'alaska_native',
//        'chinese', 'filipino', 'other_asian', 'japanese',
//        'korean', 'viatnamese', 'native_hawaiian', 'guamanian',
//        'chamorro', 'samoan', 'other_pacific_islander', 'other'.
    protected $children = array('content');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `content` is the ethnicity value.

        if (!empty($content))
        {
            $this->content = $content;
        }
    }

    public function __toString()
    {
        return $this->content ? ucwords(str_replace("_", " ", $this->content)) : "";
    }
}