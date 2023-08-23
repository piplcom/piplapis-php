<?php

class PiplApi_Utils {

    const PIPLAPI_TIMESTAMP_FORMAT = "Y-m-d";
    const PIPLAPI_DATE_FORMAT = "Y-m-d";
    const PIPLAPI_DATE_QUOTA_RESET = "l, F d, Y H:i:s A e";
    const PIPLAPI_VERSION = "5.0.0";
    const PIPLAPI_USERAGENT = "piplapis/php/5.0.0";

    public static $piplapi_states = array(
    'US'=> array('WA'=> 'Washington', 'VA'=> 'Virginia', 'DE'=> 'Delaware', 'DC'=> 'District Of Columbia', 'WI'=> 'Wisconsin', 'WV'=> 'West Virginia', 'HI'=> 'Hawaii', 'FL'=> 'Florida', 'YT'=> 'Yukon', 'WY'=> 'Wyoming', 'PR'=> 'Puerto Rico', 'NJ'=> 'New Jersey', 'NM'=> 'New Mexico', 'TX'=> 'Texas', 'LA'=> 'Louisiana', 'NC'=> 'North Carolina', 'ND'=> 'North Dakota', 'NE'=> 'Nebraska', 'FM'=> 'Federated States Of Micronesia', 'TN'=> 'Tennessee', 'NY'=> 'New York', 'PA'=> 'Pennsylvania', 'CT'=> 'Connecticut', 'RI'=> 'Rhode Island', 'NV'=> 'Nevada', 'NH'=> 'New Hampshire', 'GU'=> 'Guam', 'CO'=> 'Colorado', 'VI'=> 'Virgin Islands', 'AK'=> 'Alaska', 'AL'=> 'Alabama', 'AS'=> 'American Samoa', 'AR'=> 'Arkansas', 'VT'=> 'Vermont', 'IL'=> 'Illinois', 'GA'=> 'Georgia', 'IN'=> 'Indiana', 'IA'=> 'Iowa', 'MA'=> 'Massachusetts', 'AZ'=> 'Arizona', 'CA'=> 'California', 'ID'=> 'Idaho', 'PW'=> 'Pala', 'ME'=> 'Maine', 'MD'=> 'Maryland', 'OK'=> 'Oklahoma', 'OH'=> 'Ohio', 'UT'=> 'Utah', 'MO'=> 'Missouri', 'MN'=> 'Minnesota', 'MI'=> 'Michigan', 'MH'=> 'Marshall Islands', 'KS'=> 'Kansas', 'MT'=> 'Montana', 'MP'=> 'Northern Mariana Islands', 'MS'=> 'Mississippi', 'SC'=> 'South Carolina', 'KY'=> 'Kentucky', 'OR'=> 'Oregon', 'SD'=> 'South Dakota'),
    'CA'=> array('AB'=> 'Alberta', 'BC'=> 'British Columbia', 'MB'=> 'Manitoba', 'NB'=> 'New Brunswick', 'NT'=> 'Northwest Territories', 'NS'=> 'Nova Scotia', 'NU'=> 'Nunavut', 'ON'=> 'Ontario', 'PE'=> 'Prince Edward Island', 'QC'=> 'Quebec', 'SK'=> 'Saskatchewan', 'YU'=> 'Yukon', 'NL'=> 'Newfoundland and Labrador'),
    'AU'=> array('WA'=> 'State of Western Australia', 'SA'=> 'State of South Australia', 'NT'=> 'Northern Territory', 'VIC'=> 'State of Victoria', 'TAS'=> 'State of Tasmania', 'QLD'=> 'State of Queensland', 'NSW'=> 'State of New South Wales', 'ACT'=> 'Australian Capital Territory'),
    'GB'=> array('WLS'=> 'Wales', 'SCT'=> 'Scotland', 'NIR'=> 'Northern Ireland', 'ENG'=> 'England')
    );

    public static $piplapi_countries = array(
    'BL'=> 'Saint Barthélemy', 'BQ'=> 'Caribbean Netherlands', 'MF'=> 'Saint Martin','SS'=> 'South Sudan', 'SX'=> 'Sint Maarten', 'XK'=> "Kosovo", 'CW'=> 'Curaçao', 'RS' => 'Serbia', 'BD'=> 'Bangladesh', 'WF'=> 'Wallis And Futuna Islands', 'BF'=> 'Burkina Faso', 'PY'=> 'Paraguay', 'BA'=> 'Bosnia And Herzegovina', 'BB'=> 'Barbados', 'BE'=> 'Belgium', 'BM'=> 'Bermuda', 'BN'=> 'Brunei Darussalam', 'BO'=> 'Bolivia', 'BH'=> 'Bahrain', 'BI'=> 'Burundi', 'BJ'=> 'Benin', 'BT'=> 'Bhutan', 'JM'=> 'Jamaica', 'BV'=> 'Bouvet Island', 'BW'=> 'Botswana', 'WS'=> 'Samoa', 'BR'=> 'Brazil', 'BS'=> 'Bahamas', 'JE'=> 'Jersey', 'BY'=> 'Belarus', 'BZ'=> 'Belize', 'RU'=> 'Russian Federation', 'RW'=> 'Rwanda', 'LT'=> 'Lithuania', 'RE'=> 'Reunion', 'TM'=> 'Turkmenistan', 'TJ'=> 'Tajikistan', 'RO'=> 'Romania', 'LS'=> 'Lesotho', 'GW'=> 'Guinea-bissa', 'GU'=> 'Guam', 'GT'=> 'Guatemala', 'GS'=> 'South Georgia And South Sandwich Islands', 'GR'=> 'Greece', 'GQ'=> 'Equatorial Guinea', 'GP'=> 'Guadeloupe', 'JP'=> 'Japan', 'GY'=> 'Guyana', 'GG'=> 'Guernsey', 'GF'=> 'French Guiana', 'GE'=> 'Georgia', 'GD'=> 'Grenada', 'GB'=> 'Great Britain', 'GA'=> 'Gabon', 'GN'=> 'Guinea', 'GM'=> 'Gambia', 'GL'=> 'Greenland', 'GI'=> 'Gibraltar', 'GH'=> 'Ghana', 'OM'=> 'Oman', 'TN'=> 'Tunisia', 'JO'=> 'Jordan', 'HR'=> 'Croatia', 'HT'=> 'Haiti', 'SV'=> 'El Salvador', 'HK'=> 'Hong Kong', 'HN'=> 'Honduras', 'HM'=> 'Heard And Mcdonald Islands', 'AD'=> 'Andorra', 'PR'=> 'Puerto Rico', 'PS'=> 'Palestine', 'PW'=> 'Pala', 'PT'=> 'Portugal', 'SJ'=> 'Svalbard And Jan Mayen Islands', 'VG'=> 'Virgin Islands, British', 'AI'=> 'Anguilla', 'KP'=> 'North Korea', 'PF'=> 'French Polynesia', 'PG'=> 'Papua New Guinea', 'PE'=> 'Per', 'PK'=> 'Pakistan', 'PH'=> 'Philippines', 'PN'=> 'Pitcairn', 'PL'=> 'Poland', 'PM'=> 'Saint Pierre And Miquelon', 'ZM'=> 'Zambia', 'EH'=> 'Western Sahara', 'EE'=> 'Estonia', 'EG'=> 'Egypt', 'ZA'=> 'South Africa', 'EC'=> 'Ecuador', 'IT'=> 'Italy', 'AO'=> 'Angola', 'KZ'=> 'Kazakhstan', 'ET'=> 'Ethiopia', 'ZW'=> 'Zimbabwe', 'SA'=> 'Saudi Arabia', 'ES'=> 'Spain', 'ER'=> 'Eritrea', 'ME'=> 'Montenegro', 'MD'=> 'Moldova', 'MG'=> 'Madagascar', 'MA'=> 'Morocco', 'MC'=> 'Monaco', 'UZ'=> 'Uzbekistan', 'MM'=> 'Myanmar', 'ML'=> 'Mali', 'MO'=> 'Maca', 'MN'=> 'Mongolia', 'MH'=> 'Marshall Islands', 'US'=> 'United States', 'UM'=> 'United States Minor Outlying Islands', 'MT'=> 'Malta', 'MW'=> 'Malawi', 'MV'=> 'Maldives', 'MQ'=> 'Martinique', 'MP'=> 'Northern Mariana Islands', 'MS'=> 'Montserrat', 'NA'=> 'Namibia', 'IM'=> 'Isle Of Man', 'UG'=> 'Uganda', 'MY'=> 'Malaysia', 'MX'=> 'Mexico', 'IL'=> 'Israel', 'BG'=> 'Bulgaria', 'FR'=> 'France', 'AW'=> 'Aruba', 'AX'=> '\xc3\x85land', 'FI'=> 'Finland', 'FJ'=> 'Fiji', 'FK'=> 'Falkland Islands', 'FM'=> 'Micronesia', 'FO'=> 'Faroe Islands', 'NI'=> 'Nicaragua', 'NL'=> 'Netherlands', 'NO'=> 'Norway', 'SO'=> 'Somalia', 'NC'=> 'New Caledonia', 'NE'=> 'Niger', 'NF'=> 'Norfolk Island', 'NG'=> 'Nigeria', 'NZ'=> 'New Zealand', 'NP'=> 'Nepal', 'NR'=> 'Naur', 'NU'=> 'Niue', 'MR'=> 'Mauritania', 'CK'=> 'Cook Islands', 'CI'=> "C\xc3\xb4te D'ivoire", 'CH'=> 'Switzerland', 'CO'=> 'Colombia', 'CN'=> 'China', 'CM'=> 'Cameroon', 'CL'=> 'Chile', 'CC'=> 'Cocos (keeling) Islands', 'CA'=> 'Canada', 'CG'=> 'Congo (brazzaville)', 'CF'=> 'Central African Republic', 'CD'=> 'Congo (kinshasa)', 'CZ'=> 'Czech Republic', 'CY'=> 'Cyprus', 'CX'=> 'Christmas Island', 'CS'=> 'Serbia', 'CR'=> 'Costa Rica', 'HU'=> 'Hungary', 'CV'=> 'Cape Verde', 'CU'=> 'Cuba', 'SZ'=> 'Swaziland', 'SY'=> 'Syria', 'KG'=> 'Kyrgyzstan', 'KE'=> 'Kenya', 'SR'=> 'Suriname', 'KI'=> 'Kiribati', 'KH'=> 'Cambodia', 'KN'=> 'Saint Kitts And Nevis', 'KM'=> 'Comoros', 'ST'=> 'Sao Tome And Principe', 'SK'=> 'Slovakia', 'KR'=> 'South Korea', 'SI'=> 'Slovenia', 'SH'=> 'Saint Helena', 'KW'=> 'Kuwait', 'SN'=> 'Senegal', 'SM'=> 'San Marino', 'SL'=> 'Sierra Leone', 'SC'=> 'Seychelles', 'SB'=> 'Solomon Islands', 'KY'=> 'Cayman Islands', 'SG'=> 'Singapore', 'SE'=> 'Sweden', 'SD'=> 'Sudan', 'DO'=> 'Dominican Republic', 'DM'=> 'Dominica', 'DJ'=> 'Djibouti', 'DK'=> 'Denmark', 'DE'=> 'Germany', 'YE'=> 'Yemen', 'AT'=> 'Austria', 'DZ'=> 'Algeria', 'MK'=> 'Macedonia', 'UY'=> 'Uruguay', 'YT'=> 'Mayotte', 'MU'=> 'Mauritius', 'TZ'=> 'Tanzania', 'LC'=> 'Saint Lucia', 'LA'=> 'Laos', 'TV'=> 'Tuval', 'TW'=> 'Taiwan', 'TT'=> 'Trinidad And Tobago', 'TR'=> 'Turkey', 'LK'=> 'Sri Lanka', 'LI'=> 'Liechtenstein', 'LV'=> 'Latvia', 'TO'=> 'Tonga', 'TL'=> 'Timor-leste', 'LU'=> 'Luxembourg', 'LR'=> 'Liberia', 'TK'=> 'Tokela', 'TH'=> 'Thailand', 'TF'=> 'French Southern Lands', 'TG'=> 'Togo', 'TD'=> 'Chad', 'TC'=> 'Turks And Caicos Islands', 'LY'=> 'Libya', 'VA'=> 'Vatican City', 'AC'=> 'Ascension Island', 'VC'=> 'Saint Vincent And The Grenadines', 'AE'=> 'United Arab Emirates', 'VE'=> 'Venezuela', 'AG'=> 'Antigua And Barbuda', 'AF'=> 'Afghanistan', 'IQ'=> 'Iraq', 'VI'=> 'Virgin Islands, U.s.', 'IS'=> 'Iceland', 'IR'=> 'Iran', 'AM'=> 'Armenia', 'AL'=> 'Albania', 'VN'=> 'Vietnam', 'AN'=> 'Netherlands Antilles', 'AQ'=> 'Antarctica', 'AS'=> 'American Samoa', 'AR'=> 'Argentina', 'AU'=> 'Australia', 'VU'=> 'Vanuat', 'IO'=> 'British Indian Ocean Territory', 'IN'=> 'India', 'LB'=> 'Lebanon', 'AZ'=> 'Azerbaijan', 'IE'=> 'Ireland', 'ID'=> 'Indonesia', 'PA'=> 'Panama', 'UA'=> 'Ukraine', 'QA'=> 'Qatar', 'MZ'=> 'Mozambique'
    );

    public static function piplapi_date_parse_from_format($format, $date)
    {
        $returnArray = array('hour' => 0, 'minute' => 0, 'second' => 0,
            'month' => 0, 'day' => 0, 'year' => 0);

        $dateArray = array();

        // array of valid date codes with keys for the return array as the values
        $validDateTimeCode = array('Y' => 'year', 'y' => 'year',
            'm' => 'month', 'n' => 'month',
            'd' => 'day', 'j' => 'day',
            'H' => 'hour', 'G' => 'hour',
            'i' => 'minute', 's' => 'second');

        /* create an array of valid keys for the return array
         * in the order that they appear in $format
         */
        for ($i = 0 ; $i <= strlen($format) - 1 ; $i++) {
            $char = substr($format, $i, 1);

            if (array_key_exists($char, $validDateTimeCode)) {
                $dateArray[$validDateTimeCode[$char]] = '';
            }
        }

        // create array of reg ex things for each date part
        $regExArray = array('.' => '\.', // escape the period

            // parse d first so we dont mangle the reg ex
            // day
            'd' => '(\d{2})',

            // year
            'Y' => '(\d{4})',
            'y' => '(\d{2})',

            // month
            'm' => '(\d{2})',
            'n' => '(\d{1,2})',

            // day
            'j' => '(\d{1,2})',

            // hour
            'H' => '(\d{2})',
            'G' => '(\d{1,2})',

            // minutes
            'i' => '(\d{2})',

            // seconds
            's' => '(\d{2})');

        // create a full reg ex string to parse the date with
        $regEx = str_replace(array_keys($regExArray),
            array_values($regExArray),
            $format);

        // Parse the date
        preg_match("#$regEx#", $date, $matches);

        // some checks...
        if (!is_array($matches) ||
            (count($matches) > 0 && $matches[0] != $date) ||
            sizeof($dateArray) != (sizeof($matches) - 1)) {
            return $returnArray;
        }

        // an iterator for the $matches array
        $i = 1;

        foreach ($dateArray AS $key => $value) {
            $dateArray[$key] = $matches[$i++];

            if (array_key_exists($key, $returnArray)) {
                $returnArray[$key] = $dateArray[$key];
            }
        }

        return $returnArray;
    }

    public static function validate_datetime($parsed)
    {
        extract($parsed);
        if (!(checkdate($month, $day, $year)))
        {
            throw new InvalidArgumentException('Invalid date/time!');
        }
    }

    public static function piplapi_str_to_datetime($s)
    {
        // Transform a string to a DateTime object.
        $parsed = self::piplapi_date_parse_from_format(self::PIPLAPI_TIMESTAMP_FORMAT, $s);
        self::validate_datetime($parsed);
        return new DateTime(sprintf('%04d-%02d-%02d',
            $parsed['year'],
            $parsed['month'],
            $parsed['day']
        ), new DateTimeZone('GMT'));
    }

    public static function piplapi_datetime_to_str($dt)
    {
        // Transform a DateTime object to a string.
        return $dt->format(self::PIPLAPI_TIMESTAMP_FORMAT);
    }

    public static function piplapi_str_to_date($s)
    {
        // Transform an string to a DateTime object.
        $parsed = self::piplapi_date_parse_from_format(self::PIPLAPI_DATE_FORMAT, $s);
        self::validate_datetime($parsed);
        return new DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d',
            $parsed['year'],
            $parsed['month'],
            $parsed['day'],
            $parsed['hour'],
            $parsed['minute'],
            $parsed['second']), new DateTimeZone('GMT'));
    }

    public static function piplapi_date_to_str($d)
    {
        // Transform a date object to a string.
        return $d->format(self::PIPLAPI_DATE_FORMAT);
    }

    public static function piplapi_is_valid_url($url)
    {
        // Return true if given url is valid
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function piplapi_alpha_chars($s)
    {
        // Strip all non alphabetic characters from string
        return preg_replace('/\PL+/u', '', $s);
    }

    public static function piplapi_alnum_chars($s)
    {
        // Strip all non alphanumeric characters from string
        return preg_replace('/[^(\pL|\pN)]/u', '', $s);
    }

    public static function piplapi_string_startswith($str1, $str2)
    {
        // returns true if str1 begins with str2.
        return (0 == strncmp($str1, $str2, strlen($str2)));
    }

}

if (!interface_exists('JsonSerializable')) {
    interface JsonSerializable
    {
        /**
         * Returns data that can be serialized by json_encode
         *
         */
        public function jsonSerialize();
    }
}