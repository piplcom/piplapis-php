<?php


class PiplApi_SearchRequestConfiguration
{

    public $api_key = NULL;
    public $minimum_probability = NULL;
    public $minimum_match = NULL;
    public $show_sources = NULL;
    public $live_feeds = NULL;
    public $use_https = true;
    public $hide_sponsored = NULL;
    public $match_requirements = NULL;
    public $source_category_requirements = NULL;
    public $infer_persons = NULL;
    public $top_match = NULL;

    private function get_effective_api_key($api_key){
        if ($api_key){
            return $api_key;
        }

        return getenv("PIPL_API_KEY");
        
    }

    function __construct($api_key = NULL, $minimum_probability = NULL, $minimum_match = NULL, $show_sources = NULL,
                         $live_feeds = NULL, $hide_sponsored = NULL, $use_https = true, $match_requirements = NULL,
                         $source_category_requirements = NULL, $infer_persons = NULL, $top_match = NULL)
    {
        $this->api_key = $this->get_effective_api_key($api_key);
        $this->minimum_probability = $minimum_probability;
        $this->minimum_match = $minimum_match;
        $this->show_sources = $show_sources;
        $this->live_feeds = $live_feeds;
        $this->hide_sponsored = $hide_sponsored;
        # We are using https only.
        $this->use_https = true;
        $this->match_requirements = $match_requirements;
        $this->source_category_requirements = $source_category_requirements;
        $this->infer_persons = $infer_persons;
        $this->top_match = $top_match;
    }

}


