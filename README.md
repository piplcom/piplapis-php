piplapis PHP Library
===========================

This is a PHP client library for easily integrating Pipl's APIs into your application.

* Full details about Pipl's APIs - [https://pipl.com/api](https://pipl.com/api)  
* This library is available in other languages - [https://docs.pipl.com/docs/code-libraries](https://docs.pipl.com/docs/code-libraries)

Library Requirements
--------------------

* PHP 5.2.17 and above.
* Make sure php-curl is enabled (Windows: in the php.ini file uncomment `;extension=php_curl.dll`, Linux (Debian derivatives): `apt-get install php5-curl`).
* Not required, but for input validation of unicode strings mb_string is recommended.

Installation
------------

    pip install piplapis-python

Hello World
------------
```
    <?php

    require_once './piplapis/search.php';

    $configuration = new PiplApi_SearchRequestConfiguration();
    $configuration->use_https = true;
    $configuration->api_key = 'YOURKEY';
    
    $request = new PiplApi_SearchAPIRequest(array('email' => 'clark.kent@example.com',
    'first_name' => 'Clark',
    'last_name' => 'Kent'), $configuration);

    ?>
```

Getting Started & Code Snippets
-------------------------------

**Pipl's Search API**
* API Portal - [https://pipl.com/api/](https://pipl.com/api/)
* Code snippets - [https://docs.pipl.com/docs/code-snippets](https://docs.pipl.com/docs/code-snippets)  
* Full reference - [https://docs.pipl.com/reference/](https://docs.pipl.com/reference/)
