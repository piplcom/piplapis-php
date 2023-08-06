<?php 
require_once './src/piplapis/search.php';



try {
    PiplApi_SearchAPIRequest::get_default_configuration()->api_key = getenv("API_KEY");
    PiplApi_SearchAPIRequest::$base_url = getenv("API_URL") . "?developer_class=business_premium";

    $search = new PiplApi_SearchAPIRequest(array("email" => "garth.moulton@pipl.com"));
    // $search = new PiplApi_SearchAPIRequest(array("email" => "brianperks@gmail.com"));
    $response = $search->send();


    if (file_put_contents("data1.json", json_encode($response)))
        echo "JSON1 file created successfully...\n";
    else 
        echo "Oops! Error creating json1 file...\n";

    $search2 = new PiplApi_SearchAPIRequest(array("email" => "vrajajee@yahoo.com"));
    $response2 = $search2->send();


    if (file_put_contents("data2.json", json_encode($response2)))
        echo "JSON2 file created successfully...\n";
    else 
        echo "Oops! Error creating json2 file...\n";
} catch (PiplApi_SearchAPIError $e) {
        print $e->getMessage();
}
?>