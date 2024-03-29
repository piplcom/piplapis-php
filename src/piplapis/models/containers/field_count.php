<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/fields_container.php';

class PiplApi_FieldCount
{
    protected $dobs;
    protected $images;
    protected $educations;
    protected $addresses;
    protected $jobs;
    protected $genders;
    protected $ethnicities;
    protected $phones;
    protected $origin_countries;
    protected $usernames;
    protected $vehicles;
    protected $languages;
    protected $emails;
    protected $user_ids;
    protected $relationships;
    protected $names;
    protected $social_profiles;
    protected $mobile_phones;
    protected $landline_phones;
    protected $personal_emails;
    protected $work_emails;
    protected $voip_phones;

    protected $attributes = array(
        'addresses', 
        'ethnicities', 
        'emails', 
        'dobs', 
        'genders', 
        'user_ids', 
        'social_profiles',
        'educations', 
        'jobs', 
        'images', 
        'languages', 
        'origin_countries', 
        'names', 
        'phones',
        'relationships', 
        'usernames', 
        'mobile_phones', 
        'landline_phones', 
        'vehicles',
        'personal_emails',
        'work_emails',
        'voip_phones'
    );
    function __construct(
        $dobs = NULL,
        $images = NULL, 
        $educations = NULL, 
        $addresses = NULL, 
        $jobs = NULL,
        $genders = NULL, 
        $ethnicities = NULL, 
        $phones = NULL, 
        $origin_countries = NULL,
        $usernames = NULL, 
        $vehicles = NULL, 
        $languages = NULL, 
        $emails = NULL, 
        $user_ids = NULL, 
        $relationships = NULL,
        $names = NULL, 
        $social_profiles = NULL, 
        $mobile_phones = NULL, 
        $landline_phones = NULL,
        $personal_emails = NULL,
        $work_emails = NULL,
        $voip_phones = NULL
    ){
        $this->dobs = $dobs;
        $this->images = $images;
        $this->educations = $educations;
        $this->addresses = $addresses;
        $this->jobs = $jobs;
        $this->genders = $genders;
        $this->ethnicities = $ethnicities;
        $this->phones = $phones;
        $this->origin_countries = $origin_countries;
        $this->usernames = $usernames;
        $this->vehicles = $vehicles;
        $this->languages = $languages;
        $this->emails = $emails;
        $this->user_ids = $user_ids;
        $this->relationships = $relationships;
        $this->names = $names;
        $this->social_profiles = $social_profiles;
        $this->mobile_phones = $mobile_phones;
        $this->landline_phones = $landline_phones;
        $this->personal_emails = $personal_emails;
        $this->work_emails = $work_emails;
        $this->voip_phones = $voip_phones;
    }

    public static function from_array($params)
    {
        $dobs = !empty($params['dobs']) ? $params['dobs'] : NULL;
        $images = !empty($params['images']) ? $params['images'] : NULL;
        $educations = !empty($params['educations']) ? $params['educations'] : NULL;
        $addresses = !empty($params['addresses']) ? $params['addresses'] : NULL;
        $jobs = !empty($params['jobs']) ? $params['jobs'] : NULL;
        $genders = !empty($params['genders']) ? $params['genders'] : NULL;
        $ethnicities = !empty($params['ethnicities']) ? $params['ethnicities'] : NULL;
        $phones = !empty($params['phones']) ? $params['phones'] : NULL;
        $origin_countries = !empty($params['origin_countries']) ? $params['origin_countries'] : NULL;
        $usernames = !empty($params['usernames']) ? $params['usernames'] : NULL;
        $vehicles = !empty($params['vehicles']) ? $params['vehicles'] : NULL;
        $languages = !empty($params['languages']) ? $params['languages'] : NULL;
        $emails = !empty($params['emails']) ? $params['emails'] : NULL;
        $user_ids = !empty($params['user_ids']) ? $params['user_ids'] : NULL;
        $relationships = !empty($params['relationships']) ? $params['relationships'] : NULL;
        $names = !empty($params['names']) ? $params['names'] : NULL;
        $social_profiles = !empty($params['social_profiles']) ? $params['social_profiles'] : NULL;
        $landline_phones = !empty($params['landline_phones']) ? $params['landline_phones'] : NULL;
        $mobile_phones = !empty($params['mobile_phones']) ? $params['mobile_phones'] : NULL;
        $personal_emails = !empty($params['personal_emails']) ? $params['personal_emails'] : NULL;
        $work_emails = !empty($params['work_emails']) ? $params['work_emails'] : NULL;
        $voip_phones = !empty($params['voip_phones']) ? $params['voip_phones'] : NULL;

        $instance = new self($dobs, $images, $educations, $addresses, $jobs,
            $genders, $ethnicities, $phones, $origin_countries,
            $usernames, $vehicles, $languages, $emails, $user_ids, $relationships,
            $names, $social_profiles, $mobile_phones, $landline_phones,
            $personal_emails, $work_emails, $voip_phones
        );
        
        return $instance;
    }

    public function to_array()
    {
        $res = array();
        foreach ($this->attributes as $attr) {
            if ($this->$attr > 0)
                $res[$attr] = $this->$attr;
        }
        return $res;
    }

    public function get_usernames(){
        return $this->usernames;
    }

    public function get_emails(){
        return $this->emails;
    }

    public function get_phones(){
        return $this->phones;
    }   

    public function get_addresses(){
        return $this->addresses;
    }

    public function get_names(){
        return $this->names;
    }

    public function get_jobs(){
        return $this->jobs;
    }

    public function get_educations(){
        return $this->educations;
    }

    public function get_images(){
        return $this->images;
    }

    public function get_user_ids(){
        return $this->user_ids;
    }

    public function get_dobs(){
        return $this->dobs;
    }

    public function get_vehicles(){
        return $this->vehicles;
    }

    public function get_languages(){
        return $this->languages;
    }

    public function get_social_profiles(){
        return $this->social_profiles;
    }

    public function get_genders(){
        return $this->genders;
    }

    public function get_personal_emails(){
        return $this->personal_emails;
    }
    public function get_work_emails(){
        return $this->work_emails;
    }
    public function get_voip_phones(){
        return $this->voip_phones;
    }
}