<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';

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