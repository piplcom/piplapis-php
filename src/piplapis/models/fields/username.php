<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';

class PiplApi_Username extends PiplApi_Field
{
    // A username/screen-name associated with the person.

    // Note that even though in many sites the username uniquely identifies one
    // person it's not guarenteed, some sites allow different people to use the
    // same username.

    protected $children = array('content');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `content` is the username itself, it should be a string.


        if (!empty($content))
        {
            $this->content = $content;
        }
    }

    public function is_searchable()
    {
        // A bool value that indicates whether the username is a valid username
        // to search by.
        $st = !empty($this->content) ? $this->content : '';
        $clean = PiplApi_Utils::piplapi_alnum_chars($st);
        $func = function_exists("mb_strlen") ? "mb_strlen" : "strlen";
        return ($func($clean) >= 3);
    }

    public function __toString(){
        return $this->content;
    }
}