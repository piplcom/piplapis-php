<?php

require 'search.php';

/**
 * User: caligula
 * Date: 06/07/2015
 * Time: 14:17
 */

class APITester extends PHPUnit_Framework_TestCase {

    public function setUp(){
        PiplApi_SearchAPIRequest::get_default_configuration()->api_key = getenv("TESTING_KEY");
        PiplApi_SearchAPIRequest::$base_url = getenv("API_TESTS_BASE_URL") . "?developer_class=premium";
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
        foreach ($sources as $source){
            if($source->person_id != $response->person->id){
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
    public function test_make_sure_deserialization_works(){
        $request = new PiplApi_SearchAPIRequest(array("email" => "clark.kent@example.com"));
        $response = $request->send();
        $this->assertEquals($response->person->names[0]->display, "Clark Joseph Kent");
        $this->assertEquals($response->person->emails[1]->address_md5, "999e509752141a0ee42ff455529c10fc");
        $this->assertEquals($response->person->usernames[0]->content, "superman@facebook");
        $this->assertEquals($response->person->addresses[1]->display, "1000-355 Broadway, Metropolis, Kansas");
        $this->assertEquals($response->person->jobs[0]->display, "Field Reporter at The Daily Planet (2000-2012)");
        $this->assertEquals($response->person->educations[0]->degree, "B.Sc Advanced Science");
    }
    public function test_make_sure_md5_search_works(){
        $request = new PiplApi_SearchAPIRequest(array("person" => new PiplApi_Person(array(
            new PiplApi_Email(array("address_md5" => "e34996fda036d60aa2a595ca86ed8fef"))))));
        $response = $request->send();
        $this->assertTrue($response->person != null);
    }
    public function test_social_datatypes_are_as_expected(){
        PiplApi_SearchAPIRequest::$base_url = getenv("API_TESTS_BASE_URL") . "?developer_class=social";
        $res = $this->get_narrow_search()->send();
        $fields = $res->person->all_fields();
        $allowed_types = array('PiplApi_Email', 'PiplApi_URL', 'PiplApi_Username', 'PiplApi_UserID', 'PiplApi_Name', 'PiplApi_Image');
        foreach($fields as $field){
            $this->assertContains(get_class($field), $allowed_types);
        }
    }
    public function test_make_sure_insufficient_search_isnt_sent(){
        $request = new PiplApi_SearchAPIRequest(array("first_name" => "brian"));
        try {
            $response = $request->send();
            $failed = false;
        } catch (Exception $e){
            $failed = true;
        }
        $this->assertTrue($failed);
    }

}
