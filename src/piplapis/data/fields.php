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
        extract($params);
        if (!empty($valid_since))
        {
            $this->valid_since =  $valid_since;
        }
    }
    
    public function __set($name, $val)
    {
        if (in_array($name, $this->attributes) ||
            in_array($name, $this->children) ||
            ($name == 'valid_since'))
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
            ($name == 'valid_since'))
        {
            if (array_key_exists($name, $this->internal_params))
            {
                return $this->internal_params[$name];
            }
        }
    }
  
    public function __isset($name)
    {
        if (in_array($name, $this->attributes) ||
            in_array($name, $this->children) ||
            ($name == 'valid_since'))
        {
            return array_key_exists($name, $this->internal_params);
        }
    }
  
    public function __unset($name)
    {
        if (in_array($name, $this->attributes) ||
            in_array($name, $this->children) ||
            ($name == 'valid_since'))
        {
            unset($this->internal_params[$name]);
        }
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
                    $val = piplapi_datetime_to_str($val);
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
    
    public function __toString()
    {
        // Return a string representation of the object.
        $allattrs = array_merge($this->attributes, $this->children);
        array_push($allattrs, "valid_since");

        $allattrsvalues = array_map(array($this, 'internal_mapcb_buildattrslist'), $allattrs);
        
        // $allattrsvalues is now a multidimensional array
        $args = array_reduce($allattrsvalues, array($this, 'internal_reducecb_buildattrslist'));
        $args = substr_replace($args, "", -2);

        return get_class($this) . '(' . $args . ')';
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
    
    public static function from_dict($clsname, $d)
    {
        // Transform the dict to a field object and return the field.
        $newdict = array();
        
        foreach ($d as $key => $val)
        {
            if (piplapi_string_startswith($key, 'display'))
            {
                continue;
            }
            
            if (piplapi_string_startswith($key, '@'))
            {
                $key = substr($key, 1);
            }
            
            if ($key == 'valid_since')
            {
                $val = piplapi_str_to_datetime($val);
            }
            
            if ($key == 'date_range')
            {
                // PiplApi_DateRange has its own from_dict implementation
                $val = PiplApi_DateRange::from_dict($val);
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
    
    public function to_dict()
    {
        // Return a dict representation of the field.
        $d = array();
        if (!empty($this->valid_since))
        {
            $d['@valid_since'] = piplapi_datetime_to_str($this->valid_since);
            
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
                
                if (isset($value) && is_object($value) && method_exists($value, 'to_dict'))
                {
                    $value = $value->to_dict();
                }
                
                if (isset($value))
                {
                    $d[$prefix . $key] = $value;
                }
            }
        }

        if (method_exists($this, 'display'))
        {
            $d['display'] = $this->display();
        }
        
        return $d;
    }
}
    
class PiplApi_Name extends PiplApi_Field
{
    // A name of a person.
    
    protected $attributes = array('type');
    protected $children = array('first', 'middle', 'last', 'prefix', 'suffix', 'raw');
    protected $types_set = array('present', 'maiden', 'former', 'alias');

    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);
        // `prefix`, `first`, `middle`, `last`, `suffix`, `raw`, `type`, 
        // should all be strings.
        //
        // `raw` is an unparsed name like "Eric T Van Cartman", usefull when you 
        // want to search by name and don't want to work hard to parse it.
        // Note that in response data there's never name.raw, the names in 
        // the response are always parsed, this is only for querying with 
        // an unparsed name.
        // 
        // `type` is one of PiplApi_Name::$types_set.
        // 
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

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
   }
   
    public function display()
    {
        // A string with the object's data, to be used for displaying 
        // the object in your application.
        $vals = array($this->prefix,
                      $this->first,
                      $this->middle,
                      $this->last,
                      $this->suffix);

        $disp = implode(' ', array_filter($vals, create_function('$x', 'return !empty($x);')));

        if (strlen($disp) == 0)
        {
            $disp = $this->raw;
        }

        if ($disp == NULL)
        {
            $disp = '';
        }
        
        return $disp;
    }
    
    public function is_searchable()
    {
        // A bool value that indicates whether the name is a valid name to 
        // search by.
        $first = piplapi_alpha_chars(!empty($this->first) ? $this->first : '');
        $last = piplapi_alpha_chars(!empty($this->last) ? $this->last : '');
        $raw = piplapi_alpha_chars(!empty($this->raw) ? $this->raw : '');
        
        return (strlen($first) >= 2 && strlen($last) >= 2) || strlen($raw) >= 4;
    }
}



class PiplApi_Address extends PiplApi_Field
{
    // An address of a person.
    
    protected $attributes = array('type');
    protected $children = array('country', 'state', 'city', 'po_box', 'street', 'house', 'apartment', 'raw');
    protected $types_set = array('home', 'work', 'old');
    
    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);
        // `country`, `state`, `city`, `po_box`, `street`, `house`, `apartment`, 
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
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.     

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
    }
    
    public function display()
    {
        // A string with the object's data, to be used for displaying 
        // the object in your application.
        $country = !empty($this->state) ? $this->country : $this->country_full();
        $state = !empty($this->city) ? $this->state : $this->state_full();

        $vals = array($this->street,
                            $this->city,
                            $state,
                            $country);
                     
        $disp = implode(', ', array_filter($vals, create_function('$x', 'return !empty($x);')));
        
        if (!empty($this->street) &&
            ( !empty($this->house) ||
              !empty($this->apartment)))
        {
            $prefixarr = array( $this->house, $this->apartment );
                                      
            $prefix = implode('-', array_filter($prefixarr, create_function('$x', 'return !empty($x);')));
            
            $disp = $prefix . ' ' . $disp;
        }
        
         if (!empty($this->po_box) && empty($this->street))
         {
            $disp = 'P.O. Box ' . $this->po_box . ' ' . $disp;
         }
         return $disp;
    }
    
    public function is_searchable()
    {
        // A bool value that indicates whether the address is a valid address 
        // to search by.
        return (!empty($this->raw) ||
                   ($this->is_valid_country() && (empty($this->state) || $this->is_valid_state())));
    }
     
    public function is_valid_country()
    {
        // A bool value that indicates whether the object's country is a valid 
        // country code.
        return (!empty($this->country) &&
                   array_key_exists(strtoupper($this->country), $GLOBALS['piplapi_countries']));
    }
    
    public function is_valid_state()
    {
        // A bool value that indicates whether the object's state is a valid 
        // state code.
        return ($this->is_valid_country() &&
                   array_key_exists(strtoupper($this->country), $GLOBALS['piplapi_states']) &&
                   !empty($this->state) &&
                   array_key_exists(strtoupper($this->state), $GLOBALS['piplapi_states'][strtoupper($this->country)]));

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
            
            return array_key_exists($uppedcoutnry, $GLOBALS['piplapi_countries']) ?
                      $GLOBALS['piplapi_countries'][$uppedcoutnry] :
                      NULL;
        }
    }
    
    public function state_full()
    {
        // The full name of the object's state.
        
        // $address = new PiplApi_Address(array('country' => 'US', 'state' => 'CO'));
        // print $address->state; // Outputs "CO"
        // print $address->state_full(); // Outputs "Colorado"
    
        if ($this->is_valid_state())
        {
            return $GLOBALS['piplapi_states'][strtoupper($this->country)][strtoupper($this->state)];
        }
    }
}


class PiplApi_Phone extends PiplApi_Field
{
    // A phone number of a person.
    
    protected $attributes = array('type');
    protected $children = array('country_code', 'number', 'extension', 'display', 'display_international');
    protected $types_set = array('mobile', 'home_phone', 'home_fax', 'work_phone', 'work_fax', 'pager');
        
    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);

        // `country_code`, `number` and `extension` should all be int/long.
        // `type` is one of PiplApi_Phone::$types_set.
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

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
        if (!empty($type))
        {
            $this->type = $type;
        }
        // The two following display attributes are available when working with 
        // a response from the API, both hold strings that can be used to 
        // display the phone in your application.
        // Note that in other fields the display attribute is a property, this 
        // is not the case here since generating the display for a phone is 
        // country specific and requires a special library.
        $this->display = '';
        $this->display_international = '';
    }
    
    public function is_searchable()
    {
        // A bool value that indicates whether the phone is a valid phone 
        // to search by.
        return (!empty($this->number) &&
                   (empty($this->country_code) || $this->country_code == 1));
    
    }

    public static function from_text($text)
    {
        // Strip `text` (string) from all non-digit chars and return a new
        // Phone object with the number from text.
        
        // $phone = PiplApi_Phone::from_text('(888) 777-666');
        // print $phone->number // Outputs "888777666"

        $number = preg_replace("/[^0-9]/", '', $text);
        return new PiplApi_Phone(array('number' => $number));
    }
    
    public static function from_dict($clsname, $d)
    {
        // Extend PiplApi_Field::from_dict, set display/display_international 
        // attributes.
        $phone = PiplApi_Field::from_dict('PiplApi_Phone', $d);
        $phone->display = !empty($d['display']) ? $d['display'] : '';
        $phone->display_international = !empty($d['display_international']) ? $d['display_international'] : '';
        return $phone;
    }
    
    public function to_dict()
    {
        // Extend PiplApi_Field::from_dict, take the display_international attribute.
        $d = parent::to_dict();
        
        if (!empty($this->display_international))
        {
            $d['display_international'] = $this->display_international;
        }
        return $d;
    }
}


class PiplApi_Email extends PiplApi_Field
{
    // An email address of a person with the md5 of the address, might come
    // in some cases without the address itself and just the md5 (for privacy 
    // reasons).
    
    protected $attributes = array('type');
    protected $children = array('address', 'address_md5');
    protected $types_set = array('personal', 'work');
    private $re_email = '/^[\w.%\-+]+@[\w.%\-]+\.[a-zA-Z]{2,6}$/';
    
    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);

        // `address`, `address_md5`, `type` should be strings.
        // `type` is one of PiplApl_Email::$types_set.
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

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
        return $this->is_valid_email();
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
}


class PiplApi_Job extends PiplApi_Field
{
    // Job information of a person.
    
    protected $children = array('title', 'organization', 'industry', 'date_range');

    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);

        // `title`, `organization`, `industry`, should all be strings.
        // `date_range` is A DateRange object (PiplApi_DateRange), 
        // that's the time the person held this job.
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page. 

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
        
        if (!empty($date_range))
        {
            $this->date_range = $date_range;
        }
    }
    
    function display()
    {
        // A string with the object's data, to be used for displaying 
        // the object in your application.
        if (!empty($this->title) && !empty($this->organization))
        {
            $disp = $this->title . ' at ' . $this->organization;
        }
        else
        {
            $disp = !empty($this->title) ? $this->title : $this->organization;
            $disp = !empty($disp) ? $disp : NULL;
        }
        
        if (!empty($disp) && !empty($this->industry))
        {
            if (!empty($this->date_range))
            {
                $range = $this->date_range->years_range();
                $disp .= sprintf(' (%s, %d-%d)', $this->industry, $range[0], $range[1]);
            }
            else
            {
                $disp .= sprintf(' (%s)', $this->industry);
            }
        }
        else
        {
            $disp = trim((!empty($disp) ? $disp : '') . ' ' . (!empty($this->industry) ? $this->industry : ''));
            if (strlen($disp) > 0 && !empty($this->date_range))
            {
                $range = $this->date_range->years_range();
                $disp .= sprintf(' (%d-%d)', $range[0], $range[1]);
            }
        }
        return $disp;
    }
}


class PiplApi_Education extends PiplApi_Field
{
    // Education information of a person.
    
    protected $children = array('degree', 'school', 'date_range');

    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);

        // `degree` and `school` should both be strings.
        // `date_range` is A DateRange object (PiplApi_DateRange), 
        // that's the time the person was studying.
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

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
    }
    
    public function display()
    {
        // A string with the object's data, to be used for displaying 
        // the object in your application.
        if (!empty($this->degree) && !empty($this->school))
        {
            $disp = $this->degree . ' from ' . $this->school;
        }
        else
        {
            $disp = !empty($this->degree) ? $this->degree : $this->school;
            $disp = !empty($disp) ? $disp : NULL;
        }
        
        if (!empty($disp) && !empty($this->date_range))
        {
            $range = $this->date_range->years_range();
            $disp .= sprintf(' (%d-%d)', $range[0], $range[1]);
        }
        
        $disp = !empty($disp) ? $disp : '';
        return $disp;
    }
}


class PiplApi_Image extends PiplApi_Field
{
    // A URL of an image of a person.
    
    protected $children = array('url');
    
    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);

        // `url` should be a string.
        
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

        if (!empty($url))
        {
            $this->url = $url;
        }
    }
    
    public function is_valid_url()
    {
        // A bool value that indicates whether the image URL is a valid URL.
        return (!empty($this->url) && piplapi_is_valid_url($this->url));
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
        
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

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
        return (strlen(piplapi_alnum_chars($st)) >= 4);
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
        
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

        if (!empty($content))
        {
            $this->content = $content;
        }
    }
}


class PiplApi_DOB extends PiplApi_Field
{
    // Date-of-birth of A person.
    // Comes as a date-range (the exact date is within the range, if the exact 
    // date is known the range will simply be with start=end).
    
    protected $children = array('date_range');

    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);

        // `date_range` is A DateRange object (PiplApi_DateRange), 
        // the date-of-birth is within this range.
        
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

        if (!empty($date_range))
        {
            $this->date_range = $date_range;
        }
    }
    
    public function display()
    {
        // A string with the object's data, to be used for displaying 
        // the object in your application.
        
        // Note: in a DOB object the display is the estimated age.
        return (string)$this->age();
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
    }

    public function age_range()
    {
        // An array of two ints - the minimum and maximum age of the person.
        if (empty($this->date_range))
        {
            return array(NULL, NULL);
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
        $start_date->modify('-1 day');
        $end_date->modify('-' . $start_age . ' year');
      
        $date_range = new PiplApi_DateRange($start_date, $end_date);
        return (new PiplApi_DOB(array('date_range' => $date_range)));
    }
}


class PiplApi_RelatedURL extends PiplApi_Field
{
    // A URL that's related to a person (blog, personal page in the work 
    // website, profile in some other website).
    
    // IMPORTANT: This URL is NOT the origin of the data about the person, it's 
    // just an extra piece of information available on him.

    protected $attributes = array('type');
    protected $children = array('content');
    protected $types_set = array('personal', 'work', 'blog');
    
    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);

        // `content` is the URL address itself, both content and type should 
        // be a string.
        // `type` is one of PiplApi_RelatedURL::$types_set.
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

        if (!empty($content))
        {
            $this->content = $content;
        }
        
        if (!empty($type))
        {
            $this->type = $type;
        }
    }

    public function is_valid_url()
    {
        // A bool value that indicates whether the URL is a valid URL.
        return (!empty($this->content) && piplapi_is_valid_url($this->content));
    }
}
        

class PiplApi_Relationship extends PiplApi_Field
{
    // Name of another person related to this person.

    protected $attributes = array('type', 'subtype');
    protected $children = array('name');
    protected $types_set = array('friend', 'family', 'work', 'other');
    
    function __construct($params=array())
    {
        extract($params);    
        parent::__construct($params);

        // `name` is a Name object (PiplApi_Name).
        // 
        // `type` and `subtype` should both be strings.
        //
        // `type` is one of PiplApi_RelatedURL::$types_set.
        // 
        // `subtype` is not restricted to a specific list of possible values (for 
        // example, if type is "family" then subtype can be "Father", "Mother", 
        // "Son" and many other things).
        // 
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

        if (!empty($name))
        {
            $this->name = $name;
        }
        if (!empty($type))
        {
            $this->type = $type;
        }
        if (!empty($subtype))
        {
            $this->subtype = $subtype;
        }
    }

    public static function from_dict($clsname, $d)
    {
        // Extend Field.from_dict and also load the name from the dict.
        $relationship = PiplApi_Field::from_dict('PiplApi_Relationship', $d);
        if (!empty($relationship->name))
        {
            $relationship->name = PiplApi_Field::from_dict('PiplApi_Name', $relationship->name);
        }
        return $relationship;
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
        
        // `valid_since` is a DateTime object, it's the first time Pipl's
        // crawlers found this data on the page.

        if (!empty($content))
        {
            $this->content = $content;
        }
        if (!empty($classification))
        {
            $this->classification = $classification;
        }
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
        // `start` and `end` are datetime.date objects, both are required.
        
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
        
         if (empty($this->start) || empty($this->end))
        {
            throw new InvalidArgumentException('Start/End parameters missing');
        }

        if ($this->start > $this->end)
        {
            $t = $this->end;
            $this->end = $this->start;
            $this->start = $t;
        }
    }
    
    public function __toString()
    {
        // Return a representation of the object.
        return (sprintf('DateRange(%s, %s)', piplapi_date_to_str($this->start), piplapi_date_to_str($this->end)));
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
        $diff = ($this->end->format('U') - $this->start->format('U')) / 2;
        $newts = $this->start->format('U') + $diff;
        $newdate = new DateTime('@' . $newts, new DateTimeZone('GMT'));
        return $newdate;
    }

    public function years_range()
    {
        // A tuple of two ints - the year of the start date and the year of the 
        // end date.
        return array($this->start->format('Y'), $this->end->format('Y'));
    }
    
    public static function from_years_range($start_year, $end_year)
    {
        // Transform a range of years (two ints) to a DateRange object.
        $newstart = new DateTime($start_year . '-01-01', new DateTimeZone('GMT'));
        $newend = new DateTime($end_year . '-12-31', new DateTimeZone('GMT'));
        return new PiplApi_DateRange($newstart, $newend);
    }

    public static function from_dict($d)
    {
        // Transform the dict to a DateRange object.
        $newstart = $d['start'];
        $newend = $d['end'];
        
        if (empty($newstart) || empty($newend))
        {
            throw new InvalidArgumentException('DateRange must have both start and end');
        }
        
        $newstart = piplapi_str_to_date($newstart);
        $newend = piplapi_str_to_date($newend);
        return new PiplApi_DateRange($newstart, $newend);
    }

    public function to_dict()
    {
        // Transform the date-range to a dict.
        $d = array();
        $d['start'] = piplapi_date_to_str($this->start);
        $d['end'] = piplapi_date_to_str($this->end);
        return $d;
    }
}
?>
