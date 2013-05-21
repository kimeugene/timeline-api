<?php
/**
 * Foursquare API proxy class.
 * 
 * @todo Need to add logging as well as checking for errorType=deprecated.
 */

class FoursquareApiProxy {
    
    // Not really needed, but...
    const USER_AGENT = 'PHP';
    
    // Base urls for API access
    const URL_API = 'https://api.foursquare.com/v2';
    const VENUES_SEARCH_PATH = '/venues/search';
    const QUERY_VALUES = '?client_id=%s&client_secret=%s&intent=browse&v=%d&ll=%f,%f&radius=%d&limit=%d';
    
     // Configuration constants
    const CLIENT_ID = 'XII5B03ZVIOE4EQL3I5WXIWVGLXOM0QT3TMJT2FEYSAP1QZV';
    const CLIENT_SECRET = '3MYYCH0GVWWIXZG1LSQ1QGKKOITCSE13HKH4KKZPPOSRIJ3Y';
    
    /**
     * Latitude
     * @var string
     */
    private $latitude;
    
    /**
     * Longitude
     * @var string
     */
    private $longitude;
    
    /**
     * Limit results to venues within this many meters of the specified location. 
     * @var int
     */
    private $radius;
    
    /**
     * See: http://bit.ly/vywCav
     * @var int
     */
    private $api_version;

    /**
     * Amount of venues returned by the Foursquare API
     * @var int
     */
    private $results = 50;
    
    /**
     * cURL Resource Identifier
     * @var Object
     */
    private $curl;
    
    
    public function __construct ($latitude, $longitude, $radius=800)
    {
        $this->setLatitude($latitude);
        $this->setLongitude($longitude);
        $this->setRadius($radius);
        $this->setVersion( // Kind of a hack, admittedly.
            date('Y', filemtime(__FILE__)), 
            date('m', filemtime(__FILE__)), 
            date('d', filemtime(__FILE__))
        );
    }
    
    public function getVenueList($decode=true) {
        $result = $this->getUrlContents($this->buildUrl());
        return ($decode !== true) ? $result : json_decode($result);
    }
    
    protected function getUrlContents($url, $data=array())
    {
        if (empty($this->curl)) {
            $this->curl = curl_init();
        }
        
        $this->setCurlOptions($url, $data);
        return curl_exec($this->curl);
    }
    
    private function setCurlOptions($url) {
        curl_setopt_array($this->curl, array(
            CURLOPT_URL               => $url,
            CURLOPT_USERAGENT         => self::USER_AGENT,
            CURLOPT_SSL_VERIFYPEER    => false,
            CURLOPT_FOLLOWLOCATION    => true,
            CURLOPT_RETURNTRANSFER    => true
        ));
    }
    
    public function buildUrl()
    {
        return sprintf(self::URL_API . self::VENUES_SEARCH_PATH . self::QUERY_VALUES, 
            self::CLIENT_ID,
            self::CLIENT_SECRET,
            $this->api_version,
            $this->latitude, 
            $this->longitude,
            $this->radius,
            $this->results
        );
    }
    
    public function setLatitude($latitude) {
        if (is_float($latitude)) {
            $this->latitude = $latitude;
            return true;
        }
        return false;
    }
    
    public function setLongitude($longitude) {
        if (is_float($longitude)) {
            $this->longitude = $longitude;
            return true;
        }
        return false;
    }
    
    public function setRadius($radius) {
        if (is_int($radius)) {
            $this->radius = $radius;
            return true;
        }
        return false;
    }
    
    public function setVersion($y, $m, $d) {
        if (checkdate($m, $d, $y) !== false) {
            $this->api_version = $y . $m . $d;
        }
    }

    public function setResults($amount)
    {
        if (is_int($amount)) {
            $this->results = $amount;
            return true;
        }
        return false;
    }
    
    public function getVersion() {
        return $this->api_version;
    }
    
    public function getLatitude()
    {
        return $this->latitude;
    }
    
    public function getLongitude()
    {
        return $this->longitude;
    }
    
    public function getRadius()
    {
        return $this->radius;
    }

    public function getResults()
    {
        return $this->results;
    }
    
    public function __destruct()
    {
        if ($this->curl) {
            curl_close($this->curl);
        }
    }
    
}  

