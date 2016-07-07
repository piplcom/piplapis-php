<?php
require_once dirname(__FILE__) . '/utils.php';


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

    public function __set($name, $val)
    {
        if (in_array($name, $this->attributes) ||
            in_array($name, $this->children) ||
            ($name == 'valid_since') ||
            ($name == 'last_seen') ||
            ($name == 'current') ||
            ($name == 'inferred'))
            {
                if ($name == 'type')
                {
                    $this->validate_type($val);
                }
                $this->internal_params[$name] = $val;
            }
    }

    public function __get($name)
    {
        if (in_array($name, $this->attributes) ||
            in_array($name, $this->children) ||
            ($name == 'valid_since') ||
            ($name == 'inferred') ||
            ($name == 'current') ||
            ($name == 'last_seen'))
            {
                if (array_key_exists($name, $this->internal_params))
                {
                    return $this->internal_params[$name];
                }
            }
        return NULL;
    }

    public function __isset($name)
    {
        return ((in_array($name, $this->attributes) ||
            in_array($name, $this->children) ||
            ($name == 'valid_since') || ($name == "inferred") || ($name == 'current') || ($name == "last_seen")) &&
            array_key_exists($name, $this->internal_params));
    }

    public function __unset($name)
    {
        if (in_array($name, $this->attributes) ||
            in_array($name, $this->children) ||
            ($name == 'valid_since') || ($name == "inferred") || ($name == 'current') || ($name == "last_seen")
        )
        {
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

class PiplApi_Name extends PiplApi_Field
{
    // A name of a person.

    protected $attributes = array('type');
    protected $children = array('first', 'middle', 'last', 'prefix', 'suffix', 'raw', 'display');
    protected $types_set = array('present', 'maiden', 'former', 'alias', 'alternative', 'autogenerated');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);
        // `prefix`, `first`, `middle`, `last`, `suffix`, `raw`, `type`,
        // should all be strings.
        //
        // `raw` is an unparsed name like "Clark Joseph Kent", usefull when you
        // want to search by name and don't want to work hard to parse it.
        // Note that in response data there's never name.raw, the names in
        // the response are always parsed, this is only for querying with
        // an unparsed name.
        //
        // `type` is one of PiplApi_Name::$types_set.

        if (!empty($prefix))
        {
            $this->prefix = $prefix;
        }
        if (!empty($first))
        {
            $this->first = $first;
        }
        if (!empty($middle))
        {
            $this->middle = $middle;
        }
        if (!empty($last))
        {
            $this->last = $last;
        }
        if (!empty($suffix))
        {
            $this->suffix = $suffix;
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

    public function is_searchable()
    {
        // A bool value that indicates whether the name is a valid name to
        // search by.
        $first = PiplApi_Utils::piplapi_alpha_chars(!empty($this->first) ? $this->first : '');
        $last = PiplApi_Utils::piplapi_alpha_chars(!empty($this->last) ? $this->last : '');
        $raw = PiplApi_Utils::piplapi_alpha_chars(!empty($this->raw) ? $this->raw : '');

        $func = function_exists("mb_strlen") ? "mb_strlen" : "strlen";
        return ($func($first) >= 2 && $func($last) >= 2) || $func($raw) >= 4;
    }
}

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

class PiplApi_Phone extends PiplApi_Field
{
    // A phone number of a person.

    protected $attributes = array('type');
    protected $children = array('country_code', 'number', 'extension', 'raw', 'display', 'display_international');
    protected $types_set = array('mobile', 'home_phone', 'home_fax', 'work_phone', 'work_fax', 'pager');

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

class PiplApi_Email extends PiplApi_Field
{
    // An email address of a person with the md5 of the address, might come
    // in some cases without the address itself and just the md5 (for privacy
    // reasons).

    protected $attributes = array('type', "disposable", "email_provider");
    protected $children = array('address', 'address_md5');
    protected $types_set = array('personal', 'work');
    private $re_email = '/^[a-zA-Z0-9\'._%\-+]+@[a-zA-Z0-9._%\-]+\.[a-zA-Z]{2,24}$/';

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `address`, `address_md5`, `type` should be strings.
        // `type` is one of PiplApl_Email::$types_set.

        if (!empty($address))
        {
            $this->address = $address;
        }
        if (!empty($address_md5))
        {
            $this->address_md5 = $address_md5;
        }
        if (!empty($type))
        {
            $this->type = $type;
        }
        if (!empty($disposable)) {
            $this->disposable = $disposable;
        }
        if (!empty($email_provider))
        {
            $this->email_provider = $email_provider;
        }
    }

    public function is_valid_email()
    {
        // A bool value that indicates whether the address is a valid
        // email address.

        return (!empty($this->address) && preg_match($this->re_email, $this->address));
    }

    public function is_searchable()
    {
        // A bool value that indicates whether the email is a valid email
        // to search by.
        return !empty($this->address_md5) || $this->is_valid_email();
    }

    // Needed to catch username and domain
    public function __get($name)
    {
        if (0 == strcasecmp($name, 'username'))
        {
            // string, the username part of the email or None if the email is
            // invalid.

            // $email = new PiplApi_Email(array('address' => 'eric@cartman.com'));
            // print $email->username; // Outputs "eric"

            if ($this->is_valid_email())
            {
                $all = explode('@', $this->address);
                return $all[0];
            }
        }
        else if (0 == strcasecmp($name, 'domain'))
        {
            // string, the domain part of the email or None if the email is
            // invalid.

            // $email = new PiplApi_Email(array('address' => 'eric@cartman.com'));
            // print $email->domain; // Outputs "cartman.com"

            if ($this->is_valid_email())
            {
                $all = explode('@', $this->address);
                return $all[1];
            }
        }
        return parent::__get($name);
    }

    public function __toString(){
        return $this->address ? $this->address : "";
    }
}

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
        return ($func($clean) >= 4);
    }

    public function __toString(){
        return $this->content;
    }
}

class PiplApi_UserID extends PiplApi_Field
{
    // An ID associated with a person.

    // The ID is a string that's used by the site to uniquely identify a person,
    // it's guaranteed that in the site this string identifies exactly one person.

    protected $children = array('content');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `content` is the ID itself, it should be a string.


        if (!empty($content))
        {
            $this->content = $content;
        }
    }

    public function is_searchable()
    {
        return (!empty($this->content)) && preg_match('/(.)@(.)/', $this->content);
    }

    public function __toString(){
        return $this->content;
    }
}

class PiplApi_DOB extends PiplApi_Field
{
    // Date-of-birth of A person.
    // Comes as a PiplApi_DateRange (the exact date is within the range, if the exact
    // date is known the range will simply be with start=end).

    protected $children = array('date_range', 'display');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `date_range` is A DateRange object (PiplApi_DateRange),
        // the date-of-birth is within this range.


        if (!empty($date_range))
        {
            $this->date_range = $date_range;
        }
        if (!empty($display))
        {
            $this->display = $display;
        }
    }

    public function is_searchable()
    {
        return (!empty($this->date_range));
    }

    public function age()
    {
        // int, the estimated age of the person.

        // Note that A DOB object is based on a date-range and the exact date is
        // usually unknown so for age calculation the the middle of the range is
        // assumed to be the real date-of-birth.

        if (!empty($this->date_range))
        {
            $dob = $this->date_range->middle();
            $today = new DateTime('now', new DateTimeZone('GMT'));

            $diff = $today->format('Y') - $dob->format('Y');

            if ($dob->format('z') > $today->format('z'))
            {
                $diff -= 1;
            }

            return $diff;
        }
        return;
    }

    public function age_range()
    {
        // An array of two ints - the minimum and maximum age of the person.
        if (empty($this->date_range)){
            return array(NULL, NULL);
        }
        if(empty($this->date_range->start) || empty($this->date_range->end)){
            return array($this->age(), $this->age());
        }

        $start_date = new PiplApi_DateRange($this->date_range->start, $this->date_range->start);
        $end_date = new PiplApi_DateRange($this->date_range->end, $this->date_range->end);
        $start_age = new PiplApi_DOB(array('date_range' => $start_date));
        $start_age = $start_age->age();
        $end_age = new PiplApi_DOB(array('date_range' => $end_date));
        $end_age = $end_age->age();

        return (array($end_age, $start_age));
    }

    public static function from_birth_year($birth_year)
    {
        // Take a person's birth year (int) and return a new DOB object
        // suitable for him.
        if (!($birth_year > 0))
        {
            throw new InvalidArgumentException('birth_year must be positive');
        }

        $date_range = PiplApi_DateRange::from_years_range($birth_year, $birth_year);
        return (new PiplApi_DOB(array('date_range' => $date_range)));
    }

    public static function from_birth_date($birth_date)
    {
        // Take a person's birth date (Date) and return a new DOB
        // object suitable for him.
        if (!($birth_date <= new DateTime('now', new DateTimeZone('GMT'))))
        {
            throw new InvalidArgumentException('birth_date can\'t be in the future');
        }

        $date_range = new PiplApi_DateRange($birth_date, $birth_date);
        return (new PiplApi_DOB(array('date_range' => $date_range)));
    }

    public static function from_age($age)
    {
        # Take a person's age (int) and return a new DOB object
        # suitable for him.
        return (PiplApi_DOB::from_age_range($age, $age));
    }

    public static function from_age_range($start_age, $end_age)
    {
        // Take a person's minimal and maximal age and return a new DOB object
        // suitable for him.
        if (!($start_age >= 0 && $end_age >= 0))
        {
            throw new InvalidArgumentException('start_age and end_age can\'t be negative');
        }

        if ($start_age > $end_age)
        {
            $t = $end_age;
            $end_age = $start_age;
            $start_age = $t;
        }

        $start_date = new DateTime('now', new DateTimeZone('GMT'));
        $end_date = new DateTime('now', new DateTimeZone('GMT'));

        $start_date->modify('-' . $end_age . ' year');
        $start_date->modify('-1 year');
        $start_date->modify('+1 day');
        $end_date->modify('-' . $start_age . ' year');

        $date_range = new PiplApi_DateRange($start_date, $end_date);
        return (new PiplApi_DOB(array('date_range' => $date_range)));
    }
}

class PiplApi_Image extends PiplApi_Field
{
    // A URL of an image of a person.

    protected $children = array('url', 'thumbnail_token');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `url` should be a string.
        // `thumbnail_token` is a string used to create the URL for Pipl's thumbnail service.


        if (!empty($url))
        {
            $this->url = $url;
        }
        if (!empty($thumbnail_token))
        {
            $this->thumbnail_token = $thumbnail_token;
        }
    }

    public function is_valid_url()
    {
        // A bool value that indicates whether the image URL is a valid URL.
        return (!empty($this->url) && PiplApi_Utils::piplapi_is_valid_url($this->url));
    }
    public function get_thumbnail_url($width=100, $height=100, $zoom_face=true, $favicon=true, $use_https=false){
        if(!empty($this->thumbnail_token)){
            return self::generate_redundant_thumbnail_url($this);
        }
        return NULL;
    }
    public static function generate_redundant_thumbnail_url($first_image, $second_image=NULL, $width=100, $height=100,
                                                            $zoom_face=true, $favicon=true, $use_https=false){
        if (empty($first_image) && empty($second_image))
            throw new InvalidArgumentException('Please provide at least one image');


        if ((!empty($first_image) && !($first_image instanceof PiplApi_Image)) ||
            (!empty($second_image) && !($second_image instanceof PiplApi_Image)))
        {
            throw new InvalidArgumentException('Please provide PiplApi_Image Object');
        }

        $images = array();

        if (!empty($first_image->thumbnail_token))
            $images[] = $first_image->thumbnail_token;

        if (!empty($second_image->thumbnail_token))
            $images[] = $second_image->thumbnail_token;

        if (empty($images))
            throw new InvalidArgumentException("You can only generate thumbnail URLs for image objects with a thumbnail token.");

        if (sizeof($images) == 1)
            $tokens = $images[0];
        else {
            foreach ($images as $key=>$token) {
                $images[$key] = preg_replace("/&dsid=\d+/i","", $token);
            }
            $tokens = join(",", array_values($images));
        }

        $prefix = $use_https ? "https" : "http";
        $params = array("width" => $width, "height" => $height, "zoom_face" => $zoom_face, "favicon" => $favicon);
        $url = $prefix . "://thumb.pipl.com/image?tokens=" . $tokens . "&" . http_build_query($params);
        return $url;
    }
    public function __toString(){
        return $this->url;
    }
}

class PiplApi_Job extends PiplApi_Field
{
    // Job information of a person.

    protected $children = array('title', 'organization', 'industry', 'date_range', 'display');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `title`, `organization`, `industry`, should all be strings.
        // `date_range` is A DateRange object (PiplApi_DateRange),
        // that's the time the person held this job.

        if (!empty($title))
        {
            $this->title = $title;
        }
        if (!empty($organization))
        {
            $this->organization = $organization;
        }
        if (!empty($industry))
        {
            $this->industry = $industry;
        }
        if (!empty($display))
        {
            $this->display = $display;
        }
        if (!empty($date_range))
        {
            $this->date_range = $date_range;
        }
    }

}

class PiplApi_Education extends PiplApi_Field
{
    // Education information of a person.

    protected $children = array('degree', 'school', 'date_range', 'display');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `degree` and `school` should both be strings.
        // `date_range` is A DateRange object (PiplApi_DateRange),
        // that's the time the person was studying.

        if (!empty($degree))
        {
            $this->degree = $degree;
        }
        if (!empty($school))
        {
            $this->school = $school;
        }
        if (!empty($date_range))
        {
            $this->date_range = $date_range;
        }
        if (!empty($display))
        {
            $this->display = $display;
        }
    }
}

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

class PiplApi_Language extends PiplApi_Field
{
//  A language the person is familiar with.
    protected $children = array('language', "region", "display");

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `language` is the language code itself. For example "en"
        // `region` is the language region. For example "US"
        // `display` is a display value. For example "en_US"


        if (!empty($language))
        {
            $this->language = $language;
        }
        if (!empty($display))
        {
            $this->display = $display;
        }
        if (!empty($region))
        {
            $this->region = $region;
        }
    }
}

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

class PiplApi_URL extends PiplApi_Field
{
    //  A URL that's related to a person. Can either be a source of data
    //  about the person, or a URL otherwise related to the person.

    protected $attributes = array('category', 'sponsored', 'source_id', 'name', 'domain');
    protected $children = array('url');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        //    `url` is the URL address itself
        //    `domain` is the URL's domain
        //    `name` is the website name
        //    `category` is the URL's category.
        //
        //    `url`, `category`, `domain` and `name` should all be strings.
        //
        //    `sponsored` is a boolean - whether the URL is sponsored or not

        if (!empty($url))
        {
            $this->url = $url;
        }
        if (!empty($category))
        {
            $this->category = $category;
        }
        if (!empty($source_id))
        {
            $this->source_id = $source_id;
        }
        if (!empty($name))
        {
            $this->name = $name;
        }
        if (!empty($domain))
        {
            $this->domain = $domain;
        }
        if (!empty($sponsored))
        {
            $this->sponsored = $sponsored;
        }
    }

    public function is_valid_url()
    {
        // A bool value that indicates whether the URL is a valid URL.
        return (!empty($this->url) && PiplApi_Utils::piplapi_is_valid_url($this->url));
    }

    public function is_searchable()
    {
        return (!empty($this->url));
    }

    public function __toString(){
        return $this->url ? $this->url : $this->name;
    }
}

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

class PiplApi_DateRange
{
    // A time intervel represented as a range of two dates.
    // DateRange objects are used inside DOB, Job and Education objects.

    public $start;
    public $end;

    function __construct($start, $end)
    {
        // `start` and `end` are DateTime objects, at least one is required.

        // For creating a DateRange object for an exact date (like if exact
        // date-of-birth is known) just pass the same value for `start` and `end`.

        if (!empty($start))
        {
            $this->start = $start;
        }
        if (!empty($end))
        {
            $this->end = $end;
        }

        if (empty($this->start) && empty($this->end))
        {
            throw new InvalidArgumentException('Start/End parameters missing');
        }

        if (($this->start && $this->end) && ($this->start > $this->end))
        {
            $t = $this->end;
            $this->end = $this->start;
            $this->start = $t;
        }
    }

    public function __toString()
    {
        // Return a representation of the object.
        if($this->start && $this->end) {
            return sprintf('%s - %s', PiplApi_Utils::piplapi_date_to_str($this->start),
                PiplApi_Utils::piplapi_date_to_str($this->end));
        } elseif($this->start) {
            return PiplApi_Utils::piplapi_date_to_str($this->start);
        }
        return PiplApi_Utils::piplapi_date_to_str($this->end);
    }

    public function is_exact()
    {
        // True if the object holds an exact date (start=end),
        // False otherwise.
        return ($this->start == $this->end);
    }

    public function middle()
    {
        // The middle of the date range (a DateTime object).
        if($this->start && $this->end) {
            $diff = ($this->end->format('U') - $this->start->format('U')) / 2;
            $newts = $this->start->format('U') + $diff;
            $newdate = new DateTime('@' . $newts, new DateTimeZone('GMT'));
            return $newdate;
        }
        return $this->start ? $this->start : $this->end;
    }

    public function years_range()
    {
        // A tuple of two ints - the year of the start date and the year of the
        // end date.
        if(!($this->start && $this->end)){
            return NULL;
        }
        return array($this->start->format('Y'), $this->end->format('Y'));
    }

    public static function from_years_range($start_year, $end_year)
    {
        // Transform a range of years (two ints) to a DateRange object.
        $newstart = new DateTime($start_year . '-01-01', new DateTimeZone('GMT'));
        $newend = new DateTime($end_year . '-12-31', new DateTimeZone('GMT'));
        return new PiplApi_DateRange($newstart, $newend);
    }

    public static function from_array($d)
    {
        // Transform the dict to a DateRange object.
        $newstart = !empty($d['start']) ? $d['start'] : NULL;
        $newend = !empty($d['end']) ? $d['end'] : NULL;
        if($newstart) {
            $newstart = PiplApi_Utils::piplapi_str_to_date($newstart);
        }
        if($newend){
            $newend = PiplApi_Utils::piplapi_str_to_date($newend);
        }
        return new PiplApi_DateRange($newstart, $newend);
    }

    public function to_array()
    {
        // Transform the date-range to a dict.
        $d = array();
        if($this->start) {
            $d['start'] = PiplApi_Utils::piplapi_date_to_str($this->start);
        }
        if($this->end){
            $d['end'] = PiplApi_Utils::piplapi_date_to_str($this->end);
        }
        return $d;
    }
}


