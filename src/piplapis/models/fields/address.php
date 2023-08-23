<?php

require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';
class PiplApi_Address extends PiplApi_Field
{
    // An address of a person.

    protected $attributes = array('type');
    protected $children = array('country', 'state', 'city', 'po_box', 'zip_code', 'street', 'house', 'apartment', 'raw', 'display');
    protected $types_set = array('home', 'work', 'old');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);
        // `country`, `state`, `city`, `po_box`, `zip_code`, `street`, `house`, `apartment`,
        // `raw`, `type`, should all be strings.
        //
        // `country` and `state` are country code (like "US") and state code
        // (like "NY"), note that the full value is available as
        // address.country_full and address.state_full.
        //
        // `raw` is an unparsed address like "123 Marina Blvd, San Francisco,
        // California, US", usefull when you want to search by address and don't
        // want to work hard to parse it.
        // Note that in response data there's never address.raw, the addresses in
        // the response are always parsed, this is only for querying with
        // an unparsed address.
        //
        // `type` is one of PiplApi_Address::$types_set.
        //

        if (!empty($country))
        {
            $this->country = $country;
        }
        if (!empty($state))
        {
            $this->state = $state;
        }
        if (!empty($city))
        {
            $this->city = $city;
        }
        if (!empty($po_box))
        {
            $this->po_box = $po_box;
        }
        if (!empty($zip_code))
        {
            $this->zip_code = $zip_code;
        }
        if (!empty($street))
        {
            $this->street = $street;
        }
        if (!empty($house))
        {
            $this->house = $house;
        }
        if (!empty($apartment))
        {
            $this->apartment = $apartment;
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
    }
    public function is_sole_searchable() {
        return (!empty($this->raw) or (!empty($this->city) and !empty($this->street) and !empty($this->house)));
    }
    public function is_searchable()
    {
        // A bool value that indicates whether the address is a valid address
        // to search by.
        return (!empty($this->raw) || !empty($this->city) || !empty($this->state) || !empty($this->country));
    }

    public function is_valid_country()
    {
        // A bool value that indicates whether the object's country is a valid
        // country code.
        return (!empty($this->country) &&
            array_key_exists(strtoupper($this->country), PiplApi_Utils::$piplapi_countries));
    }

    public function is_valid_state()
    {
        // A bool value that indicates whether the object's state is a valid
        // state code.
        return ($this->is_valid_country() &&
            array_key_exists(strtoupper($this->country), PiplApi_Utils::$piplapi_states) &&
            !empty($this->state) &&
            array_key_exists(strtoupper($this->state), PiplApi_Utils::$piplapi_states[strtoupper($this->country)]));

    }

    public function country_full()
    {
        // the full name of the object's country.

        // $address = new PiplApi_Address(array('country' => 'FR'));
        // print $address->country; // Outputs "FR"
        // print $address->country_full(); // Outputs "France"
        if (!empty($this->country))
        {
            $uppedcoutnry = strtoupper($this->country);

            return array_key_exists($uppedcoutnry, PiplApi_Utils::$piplapi_countries) ?
                PiplApi_Utils::$piplapi_countries[$uppedcoutnry] :
                NULL;
        }
        return;
    }

    public function state_full()
    {
        // The full name of the object's state.

        // $address = new PiplApi_Address(array('country' => 'US', 'state' => 'CO'));
        // print $address->state; // Outputs "CO"
        // print $address->state_full(); // Outputs "Colorado"

        if ($this->is_valid_state())
        {
            return PiplApi_Utils::$piplapi_states[strtoupper($this->country)][strtoupper($this->state)];
        }
        return;
    }
}

