<?php

// require 'search.php';
// include dirname(__FILE__) . 'src/piplapis/search.php';
require(__DIR__.'/src/piplapis/search.php');
function sendRequest(){
    PiplApi_SearchAPIRequest::get_default_configuration()->api_key = "650og536yur0rq4zdxuwubfo";
    PiplApi_SearchAPIRequest::$base_url = "qa-api-gateway.pipl.pro/search/?";
    $search = new PiplApi_SearchAPIRequest(array("email" => "garth.moulton@pipl.com"));

    $response = $search->send();
    print_r($response);
    $json_response = json_encode($response);
    file_put_contents("vinAndEmail.json", $json_response);
    $search2 = new PiplApi_SearchAPIRequest(array("phone" => "+1 561-983-1106"));

    $response2 = $search2->send();
    print_r($response2);
    $json_response2 = json_encode($response2);
    file_put_contents("voip.json", $json_response2);
}
sendRequest();