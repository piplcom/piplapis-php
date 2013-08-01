<?php
// PHP wrapper for Pipl's Thumbnail API.
// 
// Pipl's thumbnail API provides a thumbnailing service for presenting images in 
// your application. The images can be from the results you got from our Search
// API but it can also be any web URI of an image.
// 
// The thumbnails returned by the API are in the height/width defined in the 
// request. Additional features of the API are:
// - Detect and Zoom-in on human faces (in case there's a human face in the image).
// - Optionally adding to the thumbnail the favicon of the website where the image 
//   is from (for attribution, recommended for copyright reasons).
// 
// This file contains only one function - PiplApi_generate_thumbnail_url() that can be 
// used for transforming an image URL into a thumbnail API URL.

require_once dirname(__FILE__) . '/data/utils.php';
require_once dirname(__FILE__) . '/data/fields.php';

define('PIPLAPI_THUMB_BASE_URL', 'http://api.pipl.com/thumbnail/v2/?');
// HTTPS is also supported:
//define('PIPLAPI_THUMB_BASE_URL', 'https://api.pipl.com/thumbnail/v2/?');
define('PIPLAPI_THUMB_MAX_PIXELS', 500);

class PiplApi_ThumbnailApi
{
    // Default API key value, you can set your key globally in this variable instead 
    // of passing it in each API call
    public static $default_api_key = NULL;
}

function PiplApi_generate_thumbnail_url($params=array())
{
    // Take an image URL and generate a thumbnail URL for that image.
    // 
    // Args:
    // 
    // image_url -- string, URL of the image you want to 
    //              thumbnail.   
    // height -- int, requested thumbnail height in pixels, maximum 500.
    // width -- int, requested thumbnail width in pixels, maximum 500.
    // favicon_domain -- string, optional, the domain of 
    //                   the website where the image came from, the favicon will 
    //                   be added to the corner of the thumbnail, recommended for 
    //                   copyright reasones.
    //                   IMPORTANT: Don't assume that the domain of the website is
    //                   the domain from `image_url`, it's possible that 
    //                   domain1.com hosts its images on domain2.com.
    // zoom_face -- bool, indicates whether you want the thumbnail to zoom on the 
    //              face in the image (in case there is a face) or not.
    // api_key -- string, a valid API key (use "samplekey" for experimenting).
    // 
    // InvalidArgumentException is raised in case of illegal parameters.
    // 
    // Example (thumbnail URL from an image URL):
    // 
    // require_once dirname(__FILE__) . '/thumbnail.php';
    // $image_url = 'http://a7.twimg.com/a/ab76f.jpg';
    // print PiplApi_generate_thumbnail_url(array('image_url' => $image_url,
    //                                                         'height' => 100,
    //                                                         'width' => 100, 
    //                                                         'favicon_domain' => 'twitter.com',
    //                                                         'api_key' => 'samplekey'));
    // (Outputs: "http://apis.pipl.com/thumbnail/v2/?key=samplekey&image_url=http%3A%2F%2Fa7.t
    // wimg.com%2Fa%2Fab76f.jpg&height=100&width=100&favicon_domain=twitter.com&zoom_fa
    // ce=true")
    // 
    // Example (thumbnail URL from a record that came in the response of our 
    // Search API):
    // 
    // require_once dirname(__FILE__) . '/thumbnail.php';
    // PiplApi_generate_thumbnail_url(array('image_url' => $record->images[0]->url,
    //                                                   'height' => 100,
    //                                                   'width' => 100, 
    //                                                   'favicon_domain' => record.source.domain,
    //                                                   'api_key' => 'samplekey'));

    $fparams = $params;
    if (!array_key_exists('zoom_face', $fparams))
    {
        $fparams['zoom_face'] = true;
    }

    if (empty($fparams['image_url']) || empty($fparams['width']) || empty($fparams['height']))
    {
        throw new InvalidArgumentException('Some parameters are missing!');
    }
    
    $key = !empty($fparams['api_key']) ? $fparams['api_key'] : PiplApi_ThumbnailApi::$default_api_key;
    if (!empty($fparams['image_url']))
    {
        $image_url = $fparams['image_url'];
    }
    if (!empty($fparams['width']))
    {
        $width = $fparams['width'];
    }
    if (!empty($fparams['height']))
    {
        $height = $fparams['height'];
    }
    if (!empty($fparams['favicon_domain']))
    {
        $favicon_domain = $fparams['favicon_domain'];
    }
    if (!empty($fparams['zoom_face']))
    {
        $zoom_face = $fparams['zoom_face'];
    }

    if (empty($key))
    {
        throw new InvalidArgumentException('A valid API key is required');
    }
    
    $img = new PiplApi_Image(array('url'=>$image_url));
    if (!$img->is_valid_url())
    {
        throw new InvalidArgumentException('image_url is not a valid URL');
    }

    if (!in_array($height, range(1, PIPLAPI_THUMB_MAX_PIXELS)) ||
        !in_array($width, range(1, PIPLAPI_THUMB_MAX_PIXELS)))
    {
        throw new InvalidArgumentException('height/width must be between 1 and PIPLAPI_THUMB_MAX_PIXELS');
    }
    
    $query = array(
        'key' => $key,
        'image_url' => urldecode($image_url),
        'height' => $height,
        'width' => $width,
        'favicon_domain' => !empty($favicon_domain) ? $favicon_domain : '',
        'zoom_face' => isset($zoom_face) && $zoom_face ? 'true' : 'false'
    );

    return PIPLAPI_THUMB_BASE_URL . http_build_query($query);
}
?>