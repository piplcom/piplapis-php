<?php

require 'search.php';

/**
 * User: caligula
 * Date: 06/07/2015
 * Time: 14:17
 */

class APITester extends PHPUnit_Framework_TestCase {

    public function setUp(){
        PiplApi_SearchAPIRequest::get_default_configuration()->api_key = getenv("KEY_PREMIUM_V4");
        PiplApi_SearchAPIRequest::$base_url = getenv("PIPL_API_ENDPOINT");
    }

    private function get_broad_search(){
        $search = new PiplApi_SearchAPIRequest(array("first_name" => "Brian", "last_name" => "Perks"));
        return $search;
    }
    private function get_narrow_search(){
        $search = new PiplApi_SearchAPIRequest(array("email" => "brianperks@gmail.com"));
        return $search;
    }
    public function test_basic_request(){
        $response = $this->get_broad_search()->send();
        $this->assertEquals(200, $response->http_status_code);
    }
    public function test_search_makes_a_match_request(){
        $response = $this->get_narrow_search()->send();
        $this->assertTrue($response->person != null);
    }
    public function test_recursive_request(){
        $response = $this->get_broad_search()->send();
        $this->assertGreaterThan(0, count($response->possible_persons));
        $s = new PiplApi_SearchAPIRequest(array("search_pointer" => $response->possible_persons[0]->search_pointer));
        $this->assertTrue($s->send()->person != null);
    }
    public function test_make_sure_hide_sponsored_works(){
        $request = $this->get_narrow_search();
        $request->configuration = new PiplApi_SearchRequestConfiguration(
            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
        $request->configuration->hide_sponsored = true;
        $response = $request->send();
        $urls = $response->person->urls;
        $bad_fields = array();
        foreach ($urls as $url){
            if($url->sponsored){
                $bad_fields[] = $url;
            }
        }
        $this->assertEquals(count($bad_fields), 0);
    }
    public function test_make_sure_we_can_hide_inferred(){
        $request = $this->get_narrow_search();
        $request->configuration = new PiplApi_SearchRequestConfiguration(
            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
        $request->configuration->minimum_probability = 1.0;
        $response = $request->send();
        $fields = $response->person->all_fields();
        $bad_fields = array();
        foreach ($fields as $field){
            if($field->inferred){
                $bad_fields[] = $field;
            }
        }
        $this->assertEquals(count($bad_fields), 0);
    }
    public function test_make_sure_we_get_inferred(){
        $request = $this->get_narrow_search();
        $request->configuration = new PiplApi_SearchRequestConfiguration(
            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
        $request->configuration->minimum_probability = 0.5;
        $response = $request->send();
        $fields = $response->person->all_fields();
        $good_fields = array();
        foreach ($fields as $field){
            if($field->inferred){
                $good_fields[] = $field;
            }
        }
        $this->assertGreaterThan(0, count($good_fields));
    }
    public function test_make_sure_show_sources_matching_works(){
        $request = $this->get_narrow_search();
        $request->configuration = new PiplApi_SearchRequestConfiguration(
            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
        $request->configuration->show_sources = "matching";
        $response = $request->send();
        $sources = $response->sources;
        $bad_sources = array();
        foreach ($sources as $source){
            if($source->person_id != $response->person->id){
                $bad_sources[] = $source;
            }
        }
        $this->assertEquals(0, count($bad_sources));
    }
    public function test_make_sure_show_sources_all_works(){
        $request = $this->get_narrow_search();
        $request->configuration = new PiplApi_SearchRequestConfiguration(
            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
        $request->configuration->show_sources = "all";
        $response = $request->send();
        $sources = $response->sources;
        $good_sources = array();
        foreach ($sources as $source) {
            if ($source->person_id != $response->person->id) {
                $good_sources[] = $source;
            }
        }
        $this->assertGreaterThan(0, count($good_sources));
    }
    public function test_make_sure_minimum_match_works(){
        $request = $this->get_broad_search();
        $request->configuration = new PiplApi_SearchRequestConfiguration(
            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
        $request->configuration->minimum_match = .7;
        $response = $request->send();
        $persons = $response->possible_persons;
        $bad_persons = array();
        foreach ($persons as $person){
            if($person->match < .7){
                $bad_persons[] = $persons;
            }
        }
        $this->assertEquals(count($bad_persons), 0);
    }

    public function test_make_sure_deserialization_works()
    {
        $request = new PiplApi_SearchAPIRequest(array("email" => "clark.kent@example.com"));
        $response = $request->send();
        $this->assertEquals($response->person->names[0]->display, "Clark Joseph Kent");
        $this->assertEquals($response->person->emails[1]->address_md5, "999e509752141a0ee42ff455529c10fc");
        $this->assertEquals($response->person->usernames[0]->content, "superman@facebook");
        $this->assertEquals($response->person->addresses[1]->display, "1000-355 Broadway, Metropolis, Kansas");
        $this->assertEquals($response->person->jobs[0]->display, "Field Reporter at The Daily Planet (2000-2012)");
        $this->assertEquals($response->person->educations[0]->degree, "B.Sc Advanced Science");
    }

    public function test_make_sure_md5_search_works()
    {
        $request = new PiplApi_SearchAPIRequest(array("person" => new PiplApi_Person(array(
            new PiplApi_Email(array("address_md5" => "e34996fda036d60aa2a595ca86ed8fef"))))));
        $response = $request->send();
        $this->assertTrue($response->person != null);
    }

    public function test_contact_datatypes_are_as_expected()
    {
        PiplApi_SearchAPIRequest::get_default_configuration()->api_key = getenv("KEY_CONTACT_V5");
        $res = $this->get_narrow_search()->send();
        $fields = $res->person->all_fields();
        $allowed_types = array('PiplApi_Name', 'PiplApi_Language', 'PiplApi_OriginCountry',
            'PiplApi_DOB', 'PiplApi_Gender', 'PiplApi_Address', 'PiplApi_Phone', 'PiplApi_URL'
        );
        foreach ($fields as $field) {
            if ($field instanceof PiplApi_Email) {
                $this->assertEquals('full.email.available@business.subscription', $field->address);
            } else {
                $this->assertContains(get_class($field), $allowed_types);
            }
        }
    }

    public function test_social_datatypes_are_as_expected()
    {
        PiplApi_SearchAPIRequest::get_default_configuration()->api_key = getenv("KEY_SOCIAL_V5");
        $res = $this->get_narrow_search()->send();
        $fields = $res->person->all_fields();
        $allowed_types = array(
            'PiplApi_Name', 'PiplApi_Language', 'PiplApi_OriginCountry',
            'PiplApi_DOB', 'PiplApi_Gender', 'PiplApi_Address', 'PiplApi_Phone', 'PiplApi_URL', 'PiplApi_UserID',
            'PiplApi_Relationship', 'PiplApi_Image', 'PiplApi_Username'
        );
        foreach ($fields as $field) {
            if ($field instanceof PiplApi_Email) {
                $this->assertEquals('full.email.available@business.subscription', $field->address);
            } else {
                $this->assertContains(get_class($field), $allowed_types);
            }
        }
    }

    public function test_make_sure_insufficient_search_isnt_sent()
    {
        $request = new PiplApi_SearchAPIRequest(array("first_name" => "brian"));
        try {
            $response = $request->send();
            $failed = false;
        } catch (Exception $e) {
            $failed = true;
        }
        $this->assertTrue($failed);
    }

    public function test_forward_compatibility()
    {
        PiplApi_SearchAPIRequest::$base_url .= "?show_unknown_fields=1";
        $request = new PiplApi_SearchAPIRequest(array("email" => "brianperks@gmail.com"));
        $response = $request->send();
        $this->assertNotEmpty($response->person);
    }

    public function test_thumbnail_example()
    {

        $request = $this->get_narrow_search();
        $response = $request->send();

        $image = $response->image();
        # Creating a thumbnail URL
        $thumbUrl = $image->get_thumbnail_url(200, 100, true, true);
        $this->assertEquals($thumbUrl,
            'http://thumb.pipl.com/image?tokens=AE2861B242686E6097CF0D9953368FA89391E569DE1FA5AA7AF0D1EB9D7254B9592B9A85A9E2EC2137FD9A220E50AF751B1870CBEB08AE16E7AE775535727714A69A996204BA35C90A43E1BDEAE9FDE790C19C3680F3588A2CF22FDBDF5147DD&dsid=56140&width=100&height=100&zoom_face=1&favicon=1');

        $first_image = $response->person->images[0];
        $second_image = $response->person->images[1];
        # Creating a redundant image URL
        $thumbUrl = PiplApi_Image::generate_redundant_thumbnail_url($first_image, $second_image, 100, 100);
        $this->assertEquals($thumbUrl,
            "http://thumb.pipl.com/image?tokens=AE2861B242686E6097CF0D9953368FA89391E569DE1FA5AA7AF0D1EB9D7254B9592B9A85A9E2EC2137FD9A220E50AF751B1870CBEB08AE16E7AE775535727714A69A996204BA35C90A43E1BDEAE9FDE790C19C3680F3588A2CF22FDBDF5147DD,AE2861B242686E6ACBCD539D133B8AE59A9AE962DB1FA5AA7AF08DAED86B0DFC1E608283D9CDB2322ADF85744B699B543C4E5FE3AC5A92&width=100&height=100&zoom_face=1&favicon=1");
    }

    public function test_json_encode() {
        $request = $this->get_narrow_search();
        $response = $request->send();
        $this->assertNotEmpty(json_encode($response));
        $this->assertNotEmpty(json_encode($response->person));
    }

    /*
     * API V5 Tests - unit tests
     */

    public function test_show_sources_can_be_boolean()
    {
        $request = $this->get_narrow_search();
        $request->configuration = new PiplApi_SearchRequestConfiguration(
            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
        $request->configuration->show_sources = "dfgdfg";

        try {
            $request->validate_query_params(true);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), 'show_sources has a wrong value, should be "matching", "all" or "true"');
        }

        $request->configuration->show_sources = "true";
        $request->validate_query_params(true);

        $request->configuration->show_sources = true;
        $request->validate_query_params(true);

        $request->configuration->show_sources = "matching";
        $request->validate_query_params(true);

        $request->configuration->show_sources = "all";
        $request->validate_query_params(true);
    }

    public function test_new_country_code()
    {
        $country = new PiplApi_Address(array("country" => "BL"));
        $this->assertTrue($country->is_valid_country());

        $country = new PiplApi_Address(array("country" => "BQ"));
        $this->assertTrue($country->is_valid_country());

        $country = new PiplApi_Address(array("country" => "MF"));
        $this->assertTrue($country->is_valid_country());

        $country = new PiplApi_Address(array("country" => "SS"));
        $this->assertTrue($country->is_valid_country());

        $country = new PiplApi_Address(array("country" => "SX"));
        $this->assertTrue($country->is_valid_country());

        $country = new PiplApi_Address(array("country" => "XK"));
        $this->assertTrue($country->is_valid_country());

        $country = new PiplApi_Address(array("country" => "CW"));
        $this->assertTrue($country->is_valid_country());

        $country = new PiplApi_Address(array("country" => "CS"));
        $this->assertTrue($country->is_valid_country());

        $country = new PiplApi_Address(array("country" => "RS"));
        $this->assertTrue($country->is_valid_country());
    }

    public function test_available_data_object() {
        $arr = array(
            "premium" => array(
                "usernames" => 1,
                "jobs" => 4,
                "addresses" => 1,
                "phones" => 1,
                "educations" => 1,
                "languages" => 1,
                "user_ids" => 4,
                "social_profiles" => 2,
                "names" => 1,
                "images" => 2,
                "genders" => 0,
                "emails" => 0
            )
        );
        $av_obj = PiplApi_AvailableData::from_array($arr);

        $this->assertEquals($av_obj->premium->usernames, 1);
        $this->assertEquals($av_obj->premium->jobs, 4);
        $this->assertEquals($av_obj->premium->addresses, 1);
        $this->assertEquals($av_obj->premium->phones, 1);
        $this->assertEquals($av_obj->premium->educations, 1);
        $this->assertEquals($av_obj->premium->languages, 1);
        $this->assertEquals($av_obj->premium->user_ids, 4);
        $this->assertEquals($av_obj->premium->social_profiles, 2);
        $this->assertEquals($av_obj->premium->names, 1);
        $this->assertEquals($av_obj->premium->images, 2);
        $this->assertEquals($av_obj->premium->genders, 0);
        $this->assertEquals($av_obj->premium->emails, 0);

        $this->assertEquals($av_obj->to_array(), array("premium" => array("usernames" => 1, "jobs" => 4, "addresses" => 1,
            "phones" => 1, "educations" => 1, "languages" => 1,
            "user_ids" => 4, "social_profiles" => 2, "images" => 2,
            "names" => 1)));
    }

    public function test_field_count_obj()
    {
        $arr = array(
            "usernames" => 1,
            "jobs" => 4,
            "addresses" => 1,
            "phones" => 1,
            "educations" => 1,
            "languages" => 1,
            "user_ids" => 4,
            "social_profiles" => 2,
            "names" => 0,
            "images" => 0,
            "genders" => 0,
            "emails" => 0
        );

        $field_count = PiplApi_FieldCount::from_array($arr);
        $this->assertEquals($field_count->usernames, 1);
        $this->assertEquals($field_count->jobs, 4);
        $this->assertEquals($field_count->addresses, 1);
        $this->assertEquals($field_count->phones, 1);
        $this->assertEquals($field_count->educations, 1);
        $this->assertEquals($field_count->languages, 1);
        $this->assertEquals($field_count->user_ids, 4);
        $this->assertEquals($field_count->social_profiles, 2);
        $this->assertEquals($field_count->names, 0);
        $this->assertEquals($field_count->images, 0);
        $this->assertEquals($field_count->genders, 0);
        $this->assertEquals($field_count->emails, 0);

        $this->assertEquals($field_count->to_array(), array("usernames" => 1, "jobs" => 4,
            "addresses" => 1, "phones" => 1, "educations" => 1,
            "languages" => 1, "user_ids" => 4, "social_profiles" => 2));

        $this->assertArrayNotHasKey("names", $field_count->to_array());
        $this->assertArrayNotHasKey("images", $field_count->to_array());
        $this->assertArrayNotHasKey("genders", $field_count->to_array());
        $this->assertArrayNotHasKey("emails", $field_count->to_array());
    }
    public function test_request_object_match_requirements() {
        /*
            Reflection of PiplApi_SearchAPIRequest::get_query_params
        */
        $reflector = new ReflectionClass('PiplApi_SearchAPIRequest');
        $method = $reflector->getMethod('get_query_params');
        $method->setAccessible(true);


        $request = $this->get_narrow_search();
        $request->configuration = new PiplApi_SearchRequestConfiguration(
            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
        $request->configuration->match_requirements = "test_value";

        $arr = $method->invoke($request);
        $this->assertArrayHasKey("match_requirements", $method->invoke($request));
        $this->assertEquals($arr['match_requirements'], "test_value");
    }

    public function test_response_match_requirements() {
        $arr = array(
            "match_requirements" => "test_word",
            "@http_status_code" => "test",
            "@visible_sources" => "test",
            "@available_sources" => "test",
            "@search_id" => "test",
        );
        $res = PiplApi_SearchAPIResponse::from_array($arr);
        $this->assertEquals($res->match_requirements, "test_word");
    }

    public function test_last_seen_and_current_attrs() {
        $arr = array(
            'first' => "tester",
            'last' => "tester",
            'current' => true,
            'last_seen' => "2015-04-11",
            'valid_since' => "2015-04-11",
        );
        $name = PiplApi_Name::from_array('PiplApi_Name', $arr);

        $this->assertEquals("tester", $name->first);
        $this->assertEquals("tester", $name->last);
        $this->assertEquals(true, $name->current);
        $this->assertEquals(new DateTime("2015-04-11",new DateTimeZone('GMT')), $name->last_seen);
    }

    public function test_person_inferred_field()
    {
        $res = array(
            "@inferred" => true
        );
        $person = PiplApi_Person::from_array($res);
        $this->assertTrue($person->inferred);
        $this->assertArrayHasKey('@inferred', $person->to_array());

        # if inderred is false should be omitted on to_array();
        $res = array(
            "@inferred" => false
        );
        $person = PiplApi_Person::from_array($res);
        $this->assertFalse($person->inferred);
        $this->assertArrayNotHasKey('@inferred', $person->to_array());
    }

    public function test_get_redundant_image_url()
    {
        $token_image1 = "token_image1";

        $throw = false;
        try {
            PiplApi_Image::generate_redundant_thumbnail_url(NULL, NULL);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), 'Please provide at least one image');
            $throw = true;
        }
        $this->assertTrue($throw);

        $throw = false;
        try {
            PiplApi_Image::generate_redundant_thumbnail_url($token_image1, NULL);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), 'Please provide PiplApi_Image Object');
            $throw = true;
        }
        $this->assertTrue($throw);


        $token_image1 = new PiplApi_Image();
        $throw = false;
        try {
            PiplApi_Image::generate_redundant_thumbnail_url($token_image1, "bla");
        } catch (InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), 'Please provide PiplApi_Image Object');
            $throw = true;
        }
        $this->assertTrue($throw);


        $throw = false;
        try {
            PiplApi_Image::generate_redundant_thumbnail_url($token_image1);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), "You can only generate thumbnail URLs for image objects with a thumbnail token.");
            $throw = true;
        }
        $this->assertTrue($throw);


        $token_image1 = new PiplApi_Image(array(
            "thumbnail_token" => "DE6473138E1&dsid=55",
        ));
        $path = PiplApi_Image::generate_redundant_thumbnail_url($token_image1);
        # if only one image was passed dsid will not be removed
        $this->assertEquals("http://thumb.pipl.com/image?tokens=DE6473138E1&dsid=55&width=100&height=100&zoom_face=1&favicon=1", $path);


        $token_image2 = new PiplApi_Image(array(
            "thumbnail_token" => "AAAAAAAA&dsid=55",
        ));
        $path = PiplApi_Image::generate_redundant_thumbnail_url($token_image1, $token_image2);
        $this->assertEquals("http://thumb.pipl.com/image?tokens=DE6473138E1,AAAAAAAA&width=100&height=100&zoom_face=1&favicon=1", $path);

    }

    public function test_request_extarct_user_id() {
        $request = new PiplApi_SearchAPIRequest(
            array("user_id" => "10019355@gravatar")
        );
        $this->assertEquals($request->person->user_ids[0], "10019355@gravatar");
    }

    public function test_request_extract_url() {
        $request = new PiplApi_SearchAPIRequest(
            array("url" => "http://facebook.com/asdasd/")
        );
        $this->assertEquals($request->person->urls[0], "http://facebook.com/asdasd/");
    }

    public function test_get_thumbnail_url()
    {
        $token_image2 = new PiplApi_Image(array(
            "thumbnail_token" => "AAAAAAAA&dsid=55",
        ));
        $path = $token_image2->get_thumbnail_url();
        $this->assertEquals("http://thumb.pipl.com/image?tokens=AAAAAAAA&dsid=55&width=100&height=100&zoom_face=1&favicon=1", $path);
    }

//    public function test_search_by_user_id() {
//        PiplApi_SearchAPIRequest::$base_url = getenv("API_TESTS_BASE_URL") . "5?developer_class=business_premium";
//        $request = new PiplApi_SearchAPIRequest(array("user_id" => "1077462361@facebook"));
//        $request->configuration = new PiplApi_SearchRequestConfiguration(
//            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
//        $response = $request->send();
//        $this->assertEquals($response->person->names[0]->display, "Mark Parris");
//    }
//
//    public function test_search_by_url() {
//        PiplApi_SearchAPIRequest::$base_url = getenv("API_TESTS_BASE_URL") . "5?developer_class=business_premium";
//        $request = new PiplApi_SearchAPIRequest(array("url" => "https://www.linkedin.com/pub/superman/20/7a/365"));
//        $request->configuration = new PiplApi_SearchRequestConfiguration(
//            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
//        $response = $request->send();
//        $this->assertEquals($response->person->jobs[0]->display, "Senior Superhero at Superheros Inc (since 1902)");
//        $this->assertEquals($response->person->jobs[1]->display,
//            "Kryptonian Physician Apprentice Program at Superheros Inc (1900-1902)");
//    }

    public function test_userid_is_searchable() {
        $user_id = new PiplApi_UserID();
        $this->assertFalse($user_id->is_searchable());

        $user_id = new PiplApi_UserID(array("content" => "blabla"));
        $this->assertFalse($user_id->is_searchable());

        $user_id = new PiplApi_UserID(array("content" => "blabla@"));
        $this->assertFalse($user_id->is_searchable());

        $user_id = new PiplApi_UserID(array("content" => "@asdas"));
        $this->assertFalse($user_id->is_searchable());

        $user_id = new PiplApi_UserID(array("content" => "asdsa@asdas"));
        $this->assertTrue($user_id->is_searchable());

        $user_id = new PiplApi_UserID(array("content" => "1@1"));
        $this->assertTrue($user_id->is_searchable());
    }

    public function test_url_is_searchable() {
        $user_id = new PiplApi_URL(array("url" => "blabla"));
        $this->assertTrue($user_id->is_searchable());

        $user_id = new PiplApi_URL();
        $this->assertFalse($user_id->is_searchable());
    }
    public function test_search_by_unknown_user_id()
    {
        $request = new PiplApi_SearchAPIRequest(array("user_id" => "10019355@blabla"));
        $request->configuration = new PiplApi_SearchRequestConfiguration(
            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);

        $throw = false;
        try {
            $response = $request->send();
        } catch (PiplApi_APIError $e) {
            $this->assertEquals($e->getMessage(),
                'The query does not contain any valid name/username/user_id/phone/email to search by');
            $throw = true;
        }
        $this->assertTrue($throw);
    }

    public function test_search_by_unknown_url()
    {
        $request = new PiplApi_SearchAPIRequest(array("url" => "http://asd.com"));
        $request->configuration = new PiplApi_SearchRequestConfiguration(
            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);

        $throw = false;
        try {
            $response = $request->send();
        } catch (PiplApi_APIError $e) {
            $this->assertEquals($e->getMessage(),
                'The query does not contain any valid name/username/user_id/phone/email to search by');
            $throw = true;
        }
        $this->assertTrue($throw);
    }

    public function test_sole_searchable_address() {
        $address = new PiplApi_Address(array("raw" => "blabla"));
        $this->assertTrue($address->is_sole_searchable());

        $address = new PiplApi_Address(array("city" => "blabla","street"=>"bla", "house"=>"12"));
        $this->assertTrue($address->is_sole_searchable());

        $address = new PiplApi_Address(array("city" => "blabla","street"=>"bla"));
        $this->assertFalse($address->is_sole_searchable());

        $address = new PiplApi_Address(array("city" => "blabla"));
        $this->assertFalse($address->is_sole_searchable());
    }

//    public function test_search_request_with_address_only()
//    {
//        PiplApi_SearchAPIRequest::get_default_configuration()->api_key = getenv("TESTING_KEY_API");
//        PiplApi_SearchAPIRequest::$base_url = getenv("API_TESTS_BASE_URL") . "5?developer_class=business_premium";
//        $request = new PiplApi_SearchAPIRequest(
//            array("person" => new PiplApi_Person(array(
//                new PiplApi_Address(array(
//                    "country" => "North Carolina",
//                    "city" => "Raleigh",
//                    "street" => "Harrington Grove Drive",
//                    "house" => "5213")))
//            )));
//        $request->configuration = new PiplApi_SearchRequestConfiguration(
//            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
//        $response = $request->send();
//    }
//
//    public function test_search_request_with_raw_address_only()
//    {
//
//        $request = new PiplApi_SearchAPIRequest(array("raw_address" => "5213 Harrington Grove Drive, Raleigh, North Carolina"));
//        $request->configuration = new PiplApi_SearchRequestConfiguration(
//            PiplApi_SearchAPIRequest::get_default_configuration()->api_key);
//        $response = $request->send();
//    }

}
