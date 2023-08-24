<?php

require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';

class PiplApi_Phone extends PiplApi_Field
{
    // A phone number of a person.

    protected $attributes = array('type', 'do_not_call', 'voip');
    protected $children = array('country_code', 'number', 'extension', 'raw', 'display', 'display_international');
    protected $types_set = array('mobile', 'home_phone', 'home_fax', 'work_phone', 'work_fax', 'pager', 'voip');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `country_code`, `number` and `extension` should all be int/long.
        // `type` is one of PiplApi_Phone::$types_set.

        if (!empty($country_code))
        {
            $this->country_code = $country_code;
        }
        
        if (!empty($number))
        {
            $this->number = $number;
        }
        
        if (!empty($extension))
        {
            $this->extension = $extension;
        }
        
        if (!empty($raw))
        {
            $this->raw = $raw;
        }

        if (!empty($type))
        {
            $this->type = $type;
        }

        if (!empty($display))
        {
            $this->display = $display;
        }

        if (!empty($display_international))
        {
            $this->display_international = $display_international;
        }

        if (!empty($do_not_call))
        {
            $this->do_not_call = $do_not_call;
        }

        if (!empty($voip))
        {
            $this->voip = $voip;
        }
    }

    public function is_searchable()
    {
        // A bool value that indicates whether the phone is a valid phone
        // to search by.
        return (!empty($this->raw) || (!empty($this->number) && (empty($this->country_code) || $this->country_code == 1)));
    }

    public static function from_text($text)
    {
        return new PiplApi_Phone(array('raw' => $text));
    }

}

