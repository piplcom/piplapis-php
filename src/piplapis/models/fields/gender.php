<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';

class PiplApi_Gender extends PiplApi_Field
{

//  An gender field.
    protected $children = array('content');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `content` is the gender value - "Male"/"Female"

        if (!empty($content))
        {
            $this->content = $content;
        }
    }

    public function __toString()
    {
        return $this->content ? ucwords($this->content) : "";
    }

}