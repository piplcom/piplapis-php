<?php

require_once dirname(__FILE__) . '/fields.php';

class PiplApi_Source extends PiplApi_Field
{
    public static $categories = array( 'background_reports', 'contact_details',
                                                    'email_address', 'media', 'personal_profiles',
                                                    'professional_and_business', 'public_records',
                                                    'publications', 'school_and_classmates', 'web_pages' );
                      
    protected $attributes = array( 'is_sponsored' );
    protected $children = array( 'name', 'category', 'url', 'domain' );

    function __construct($params=array())
    {
        // `is_sponsored` is a bool value that indicates whether the source is from 
        // one of Pipl's sponsored sources.
        // 
        // `category` is one of Source::$categories.
        parent::__construct();
        extract($params);
        
        if (isset($is_sponsored))
        {
            $this->is_sponsored = $is_sponsored;
        }
        if (!empty($name))
        {
            $this->name = $name;
        }
        if (!empty($category))
        {
            $this->category = $category;
        }
        if (!empty($url))
        {
            $this->url = $url;
        }
        if (!empty($domain))
        {
            $this->domain = $domain;
        }
    }
    
    public function is_valid_url()
    {
        // A bool that indicates whether the URL is valid.
        return (!empty($this->internal_params['url']) &&
                piplapi_is_valid_url($this->internal_params['url']));
    }
    
    public static function validate_categories($cats)
    {
        // Take an iterable of source categories and raise InvalidArgumentException if some 
        // of them are invalid.
        $invalid = array_diff($cats, self::$categories);
        
        if (count($invalid) > 0)
        {
            throw new InvalidArgumentException('Invalid categories!');
        }
    }
}

?>
