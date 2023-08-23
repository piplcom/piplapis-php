<?php

require_once dirname(__FILE__) . '/../utils.php';

abstract class PiplApi_Field
{
    // Base class of all data fields, made only for inheritance.

    protected $attributes = array();
    protected $children = array();
    protected $types_set = array();

    protected $internal_params = array();

    function __construct($params=array())
    {
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.
        // `inferred` is a boolean indicating whether this field includes inferred data.
        extract($params);
        if (!empty($valid_since))
        {
            $this->valid_since =  $valid_since;
        }
        if (!empty($inferred))
        {
            $this->inferred =  $inferred;
        }
        // API v5
        if (!empty($last_seen))
        {
            $this->last_seen =  $last_seen;
        }
        if (!empty($current))
        {
            $this->current =  $current;
        }

    }

    private function is_name_valid($name){
        $condition = (
            in_array($name, $this->attributes) ||
            in_array($name, $this->children) ||
            ($name == 'valid_since') ||
            ($name == 'last_seen') ||
            ($name == 'current') ||
            ($name == 'inferred')
        );

        return $condition;
    }

    public function __set($name, $val)
    {
        if (!$this->is_name_valid($name)){
            return;
        }
        
        if ($name == 'type'){
            $this->validate_type($val);
        }

        $this->internal_params[$name] = $val;
    }

    public function __get($name)
    {
        if (!$this->is_name_valid($name)){
            return NULL;
        }

        if (array_key_exists($name, $this->internal_params)){
            return $this->internal_params[$name];
        }

        return NULL;
    }

    public function __isset($name)
    {
        return (
            $this->is_name_valid($name) &&
            array_key_exists($name, $this->internal_params)
        );
    }

    public function __unset($name)
    {
        if ($this->is_name_valid($name)){
            unset($this->internal_params[$name]);
        }
    }

    public function __toString()
    {
        return isset($this->display) ? $this->display : "";
    }

    public function get_representation(){
        // Return a string representation of the object.
        $allattrs = array_merge($this->attributes, $this->children);
        array_push($allattrs, "valid_since");

        $allattrsvalues = array_map(array($this, 'internal_mapcb_buildattrslist'), $allattrs);

        // $allattrsvalues is now a multidimensional array
        $args = array_reduce($allattrsvalues, array($this, 'internal_reducecb_buildattrslist'));
        $args = substr_replace($args, "", -2);

        return get_class($this) . '(' . $args . ')';
    }

    private function internal_mapcb_buildattrslist($attr)
    {
        if (isset($this->internal_params[$attr]))
        {
            return array($attr => $this->internal_params[$attr]);
        }
        else
        {
            return NULL;
        }
    }

    private function internal_reducecb_buildattrslist($res, $x)
    {
        if (is_array($x) && count($x) > 0)
        {
            $keys = array_keys($x);
            if (isset($x[$keys[0]]))
            {
                $val = $x[$keys[0]];

                if ($val instanceof DateTime)
                {
                    $val = PiplApi_Utils::piplapi_datetime_to_str($val);
                }
                else if (is_array($val))
                {
                    $val = '[' . implode(', ', $val) . ']';
                }
                else
                {
                    $val = (string)$val;
                }

                $newval = $keys[0] . '=' . $val . ', ';
                // This is a bit messy, but gets around the weird fact that array_reduce
                // can only accept an initial integer.
                if (empty($res))
                {
                    $res = $newval;
                }
                else
                {
                    $res .= $newval;
                }
            }
        }
        return $res;
    }

    public function validate_type($type)
    {
        // Take an string `type` and raise an InvalidArgumentException if it's not
        // a valid type for the object.

        // A valid type for a field is a value from the types_set attribute of
        // that field's class.

        if (!empty($type) && !in_array($type, $this->types_set))
        {
            throw new InvalidArgumentException('Invalid type for ' . get_class($this) . ' ' . $type);
        }
    }

    public static function from_array($clsname, $d)
    {
        // Transform the dict to a field object and return the field.
        $newdict = array();

        foreach ($d as $key => $val)
        {
            if (PiplApi_Utils::piplapi_string_startswith($key, '@'))
            {
                $key = substr($key, 1);
            }

            if ($key == 'last_seen')
            {
                $val = PiplApi_Utils::piplapi_str_to_datetime($val);
            }

            if ($key == 'valid_since')
            {
                $val = PiplApi_Utils::piplapi_str_to_datetime($val);
            }

            if ($key == 'date_range')
            {
                // PiplApi_DateRange has its own from_array implementation
                $val = PiplApi_DateRange::from_array($val);
            }

            $newdict[$key] = $val;
        }

        return new $clsname($newdict);
    }

    private function internal_mapcb_attrsarr($attr)
    {
        return array($attr => '@');
    }

    private function internal_mapcb_childrenarr($attr)
    {
        return array($attr => '');
    }

    public function to_array()
    {
        // Return a dict representation of the field.
        $d = array();
        if (!empty($this->valid_since))
        {
            $d['@valid_since'] = PiplApi_Utils::piplapi_datetime_to_str($this->valid_since);
        }
        if (!empty($this->last_seen))
        {
            $d['@last_seen'] = PiplApi_Utils::piplapi_datetime_to_str($this->last_seen);
        }
        $newattr = array_map(array($this, "internal_mapcb_attrsarr"), $this->attributes);
        $newchild = array_map(array($this, "internal_mapcb_childrenarr"), $this->children);

        // $newattr and $newchild are multidimensionals- this is used to iterate over them
        // we first merge the two arrays and then create an iterator that flattens them
        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator(array_merge($newattr, $newchild)));

        foreach ($it as $key => $prefix)
        {
            if (array_key_exists($key, $this->internal_params))
            {
                $value = $this->internal_params[$key];

                if (isset($value) && is_object($value) && method_exists($value, 'to_array'))
                {
                    $value = $value->to_array();
                }

                if (isset($value))
                {
                    $d[$prefix . $key] = $value;
                }
            }
        }

        return $d;
    }

    public function is_searchable(){
        return true;
    }
}

