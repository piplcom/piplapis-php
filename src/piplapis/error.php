<?php

require_once dirname(__FILE__) . '/data/utils.php';

class PiplApi_APIError extends Exception
{
    // An exception raised when the response from the API contains an error.
    private $error;
    private $warnings;
    private $http_status_code;
    
    public function __construct($error, $warnings, $http_status_code)
    {
        // Extend Exception::__construct and set two extra attributes - 
        // error (string) and http_status_code (int).
        parent::__construct($error);
        $this->error = $error;
        $this->warnings = $warnings;
        $this->http_status_code = $http_status_code;
    }
    
    public function is_user_error()
    {
        // A bool that indicates whether the error is on the user's side.
        return in_array($this->http_status_code, range(400, 499));
    }
    
    public function is_pipl_error()
    {
        // A bool that indicates whether the error is on Pipl's side.
        return !$this->is_user_error();
    }
    
    public static function from_array($d)
    {
        // Transform the dict to a error object and return the error.
        return new self($d['error'], $d['warnings'], $d['@http_status_code']);
    }

    public function to_array()
    {
        // Return a dict representation of the error.
        return array('error' => $this->error,
            '@http_status_code' => $this->http_status_code, 'warnings' => $this->warnings);
    }
}
