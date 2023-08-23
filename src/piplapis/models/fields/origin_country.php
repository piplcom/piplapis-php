<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';

class PiplApi_OriginCountry extends PiplApi_Field
{
//  An origin country of the person.
    protected $children = array('country');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `country` is a two letter country code.

        if (!empty($country))
        {
            $this->country = $country;
        }
    }

    public function __toString(){
        if (!empty($this->country))
        {
            $uppedcoutnry = strtoupper($this->country);
            return array_key_exists($uppedcoutnry, PiplApi_Utils::$piplapi_countries) ?
                PiplApi_Utils::$piplapi_countries[$uppedcoutnry] : NULL;
        }
        return "";
    }
}

