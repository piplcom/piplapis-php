<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';


class PiplApi_Image extends PiplApi_Field
{
    // A URL of an image of a person.

    protected $children = array('url', 'thumbnail_token');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `url` should be a string.
        // `thumbnail_token` is a string used to create the URL for Pipl's thumbnail service.


        if (!empty($url))
        {
            $this->url = $url;
        }
        if (!empty($thumbnail_token))
        {
            $this->thumbnail_token = $thumbnail_token;
        }
    }

    public function is_valid_url()
    {
        // A bool value that indicates whether the image URL is a valid URL.
        return (!empty($this->url) && PiplApi_Utils::piplapi_is_valid_url($this->url));
    }
    public function get_thumbnail_url($width=100, $height=100, $zoom_face=true, $favicon=true, $use_https=false){
        if(!empty($this->thumbnail_token)){
            return self::generate_redundant_thumbnail_url($this);
        }
        return NULL;
    }
    public static function generate_redundant_thumbnail_url($first_image, $second_image=NULL, $width=100, $height=100,
                                                            $zoom_face=true, $favicon=true, $use_https=false){
        if (empty($first_image) && empty($second_image))
            throw new InvalidArgumentException('Please provide at least one image');


        if ((!empty($first_image) && !($first_image instanceof PiplApi_Image)) ||
            (!empty($second_image) && !($second_image instanceof PiplApi_Image)))
        {
            throw new InvalidArgumentException('Please provide PiplApi_Image Object');
        }

        $images = array();

        if (!empty($first_image->thumbnail_token))
            $images[] = $first_image->thumbnail_token;

        if (!empty($second_image->thumbnail_token))
            $images[] = $second_image->thumbnail_token;

        if (empty($images))
            throw new InvalidArgumentException("You can only generate thumbnail URLs for image objects with a thumbnail token.");

        if (sizeof($images) == 1)
            $tokens = $images[0];
        else {
            foreach ($images as $key=>$token) {
                $images[$key] = preg_replace("/&dsid=\d+/i","", $token);
            }
            $tokens = join(",", array_values($images));
        }

        $prefix = $use_https ? "https" : "http";
        $params = array("width" => $width, "height" => $height, "zoom_face" => $zoom_face, "favicon" => $favicon);
        $url = $prefix . "://thumb.pipl.com/image?tokens=" . $tokens . "&" . http_build_query($params);
        return $url;
    }
    public function __toString(){
        return $this->url;
    }
}