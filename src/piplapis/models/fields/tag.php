<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';

class PiplApi_Tag extends PiplApi_Field
{
    // A general purpose element that holds any meaningful string that's
    // related to the person.
    // Used for holding data about the person that either couldn't be clearly
    // classified or was classified as something different than the available
    // data fields.

    protected $attributes = array('classification');
    protected $children = array('content');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `content` is the tag itself, both `content` and `classification`
        // should be strings.


        if (!empty($content))
        {
            $this->content = $content;
        }
        if (!empty($classification))
        {
            $this->classification = $classification;
        }
    }

    public function __toString()
    {
        return $this->content;
    }
}