<?php 
require_once './src/piplapis/search.php';


function send_direct_request($email){
    $url = 'https://' . getenv("PIPL_API_URL") . 'v5/';
    
    $data = array(
        'email' => $email,
        'key' => getenv("PIPL_API_KEY"),
    );

    $data = http_build_query($data, '', '&');
    $url = $url . '?' . $data;

    $curl_handle=curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Your application name');
    $query = curl_exec($curl_handle);

    curl_close($curl_handle);

    return $query;
}



function search_request($file_name, $email){
    try{
        PiplApi_SearchAPIRequest::get_default_configuration();

        $search = new PiplApi_SearchAPIRequest(array("email" => $email));

        echo "Sending request...\n";

        $response = $search->send();
        $json_response = json_encode($response);
        echo "sending direct request...\n";
        
        $direct_response = send_direct_request($email);

        if (file_put_contents($file_name, $json_response))
            echo "JSON file created successfully...\n";
        else 
            echo "Oops! Error creating json file...\n";

        if (file_put_contents("direct" . $file_name, $direct_response))
            echo "direct JSON file created successfully...\n";
        else 
            echo "Oops! Error creating direct json file...\n";
    } catch (PiplApi_SearchAPIError $e) {
        print $e->getMessage();
    }
   
}

    search_request("data1.json", "garth.moulton@pipl.com");
    search_request("data2.json", "vrajajee@yahoo.com");