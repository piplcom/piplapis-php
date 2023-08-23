<?php

require_once dirname(__FILE__) . '/utils.php';

class PiplApi_APIError extends Exception
{
    // An exception raised when the response from the API contains an error.
    private $error;
    private $warnings;
    private $http_status_code;
    private $qps_allotted;
    private $qps_current;
    private $qps_live_allotted;
    private $qps_live_current;
    private $qps_demo_allotted;
    private $qps_demo_current;
    private $quota_allotted;
    private $quota_current;
    private $quota_reset;
    private $demo_usage_allotted;
    private $demo_usage_current;
    private $demo_usage_expiry;

    public function __construct($error, $warnings, $http_status_code, $qps_allotted = NULL, $qps_current = NULL,
                                $quota_allotted = NULL, $quota_current = NULL, $quota_reset = NULL, $qps_live_allotted = NULL,
                                $qps_live_current = NULL, $qps_demo_allotted = NULL, $qps_demo_current = NULL,
                                $demo_usage_allotted = NULL, $demo_usage_current = NULL, $demo_usage_expiry = NULL)
    {
        // Extend Exception::__construct and set two extra attributes - 
        // error (string) and http_status_code (int).
        parent::__construct($error);
        $this->error = $error;
        $this->warnings = $warnings;
        $this->http_status_code = $http_status_code;

        // qps_allotted- int | The number of queries you are allowed to do per second.
        // qps_current- int | The number of queries you have run this second.
        // quota_allotted- int | Your quota limit.
        // quota_current- int | Your used quota.
        // quota_reset- DateTime Object | The time (in UTC) that your quota will be reset.
        // qps_live_allotted - Your permitted queries per second
        // qps_live_current - The number of queries that you've run in the same second as this one.
        // qps_demo_allotted - Your permitted queries per second
        // qps_demo_current - The number of queries that you've run in the same second as this one.
        // demo_usage_allotted - Your permitted demo queries
        // demo_usage_current - The number of demo queries that you've already run
        // demo_usage_expiry -  The expiry time of your demo usage


        $this->qps_allotted = $qps_allotted;
        $this->qps_current = $qps_current;
        $this->qps_live_allotted = $qps_live_allotted;
        $this->qps_live_current = $qps_live_current;
        $this->qps_demo_allotted = $qps_demo_allotted;
        $this->qps_demo_current = $qps_demo_current;
        $this->quota_allotted = $quota_allotted;
        $this->quota_current = $quota_current;
        $this->quota_reset = $quota_reset;
        $this->demo_usage_allotted = $demo_usage_allotted;
        $this->demo_usage_current = $demo_usage_current;
        $this->demo_usage_expiry = $demo_usage_expiry;
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

        $qps_allotted = !empty($headers['x-qps-allotted']) ? intval($headers['x-qps-allotted']) : null;
        $qps_current = !empty($headers['x-qps-current']) ? intval($headers['x-qps-current']) : null;
        $quota_allotted = !empty($headers['x-apikey-quota-allotted']) ? intval($headers['x-apikey-quota-allotted']) : null;
        $quota_current = !empty($headers['x-apikey-quota-current']) ? intval($headers['x-apikey-quota-current']) : null;
        $quota_reset = !empty($headers['x-quota-reset']) ?
            DateTime::createFromFormat(PiplApi_Utils::PIPLAPI_DATE_QUOTA_RESET, $headers['x-quota-reset']) : null;
        $qps_live_allotted = !empty($headers['x-qps-live-allotted']) ? intval($headers['x-qps-live-allotted']) : null;
        $qps_live_current = !empty($headers['x-qps-live-current']) ? intval($headers['x-qps-live-current']) : null;
        $qps_demo_allotted = !empty($headers['x-qps-demo-allotted']) ? intval($headers['x-qps-demo-allotted']) : null;
        $qps_demo_current = !empty($headers['x-qps-demo-current']) ? intval($headers['x-qps-demo-current']) : null;
        $demo_usage_allotted = !empty($headers['x-demo-usage-allotted']) ? intval($headers['x-demo-usage-allotted']) : null;
        $demo_usage_current = !empty($headers['x-demo-usage-current']) ? intval($headers['x-demo-usage-current']) : null;
        $demo_usage_expiry = !empty($headers['x-demo-usage-expiry']) ?
            DateTime::createFromFormat(PiplApi_Utils::PIPLAPI_DATE_QUOTA_RESET, $headers['x-demo-usage-expiry']) : null;

        $error = !empty($d['error']) ? $d['error'] : "";
        $warnings = !empty($d['warnings']) ? $d['warnings'] : "";
        $http_status_code = !empty($d['@http_status_code']) ? $d['@http_status_code'] : 0;

        return new self($error, $warnings, $http_status_code, $qps_allotted, $qps_current,
                        $quota_allotted, $quota_current, $quota_reset, $qps_live_allotted, $qps_live_current,
                        $qps_demo_allotted, $qps_demo_current, $demo_usage_allotted,  $demo_usage_current, $demo_usage_expiry);
    }

    public function to_array()
    {
        // Return a dict representation of the error.
        return array('error' => $this->error,
            '@http_status_code' => $this->http_status_code, 'warnings' => $this->warnings);
    }
}
