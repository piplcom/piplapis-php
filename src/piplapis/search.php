<?php
// PHP wrapper for easily making calls to Pipl's Search API.
//
// Pipl's Search API allows you to query with the information you have about
// a person (his name, address, email, phone, username and more) and in response
// get all the data available on him on the web.
//
// The classes contained in this module are:
// - PiplApi_SearchAPIRequest -- Build your request and send it.
// - PiplApi_SearchAPIResponse -- Holds the response from the API in case it contains data.
// - PiplApi_SearchAPIError -- An exception raised when the API response is an error.
//
// The classes are based on the person data-model that's implemented here in containers.php

require_once dirname(__FILE__) . '/models/search_api_error.php';
require_once dirname(__FILE__) . '/models/request_configuration.php';
require_once dirname(__FILE__) . '/models/request.php';
require_once dirname(__FILE__) . '/models/response.php';



