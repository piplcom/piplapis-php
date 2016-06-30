<?php

require_once dirname(__FILE__) . '/data/utils.php';

class PiplApi_APIError extends Exception
{
    // An exception raised when the response from the API contains an error.
    private $error;
    private $warnings;
    private $http_status_code;

    public function __construct($error, $warnings, $http_status_code, $qps_allotted = NULL, $qps_current = NULL,
                                $quota_allotted = NULL, $quota_current = NULL, $quota_reset = NULL)
    {
        // Extend Exception::__construct and set two extra attributes - 
        // error (string) and http_status_code (int).
        parent::__construct($error);
        $this->error = $error;
        $this->warnings = $warnings;
        $this->http_status_code = $http_status_code;

        // Header Parsed Parameters http://pipl.com/dev/reference/#errors
        // qps_allotted- int | The number of queries you are allowed to do per second.
        // qps_current- int | The number of queries you have run this second.
        // quota_allotted- int | Your quota limit.
        // quota_current- int | Your used quota.
        // quota_reset- DateTime Object | The time (in UTC) that your quota will be reset.
        $this->qps_allotted = $qps_allotted;
        $this->qps_current = $qps_current;
        $this->quota_allotted = $quota_allotted;
        $this->quota_current = $quota_current;
        $this->quota_reset = $quota_reset;
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

    public static function from_array($d, $headers=array())
    {
        // Transform the dict to a error object and return the error.

        $qps_allotted = !empty($headers['x-apikey-qps-allotted']) ? intval($headers['x-apikey-qps-allotted']) : null;
        $qps_current = !empty($headers['x-apikey-qps-current']) ? intval($headers['x-apikey-qps-current']) : null;
        $quota_allotted = !empty($headers['x-apikey-quota-allotted']) ? intval($headers['x-apikey-quota-allotted']) : null;
        $quota_current = !empty($headers['x-apikey-quota-current']) ? intval($headers['x-apikey-quota-current']) : null;
        $quota_reset = !empty($headers['x-quota-reset']) ?
            DateTime::createFromFormat(PiplApi_Utils::PIPLAPI_DATE_QUOTA_RESET, $headers['x-quota-reset']) : null;

        $error = !empty($d['error']) ? $d['error'] : "";
        $warnings = !empty($d['warnings']) ? $d['warnings'] : "";
        $http_status_code = !empty($d['@http_status_code']) ? $d['@http_status_code'] : 0;

        return new self($error, $warnings, $http_status_code, $qps_allotted, $qps_current,
                        $quota_allotted, $quota_current, $quota_reset);
    }

    public function to_array()
    {
        // Return a dict representation of the error.
        return array('error' => $this->error,
            '@http_status_code' => $this->http_status_code, 'warnings' => $this->warnings);
    }
}
