<?php
class Ads
{
    public $country_code;
    public $mobile;
    public $browsing;
    public $locations;
    public $tags;
    public $area;

    public function __construct($country_code, $mobile = false, $area = "")
    {
        $this->mobile = $mobile;
        $this->area = $area;
        $this->country_code = strtolower($country_code);
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
        $this->browsing = stripos($referrer, "https://www.SITENAME.com") === 0;
        $this->locations = json_decode(file_get_contents("../ads.json"), true);
        //$this->tags = json_decode(file_get_contents("tags.json"), true);
    }

    public function getAd($location, $ad_title = "", $title = "Graphics Card")
    {
        if($_SERVER["HTTP_HOST"] == "localhost:4040" || $_SERVER["HTTP_HOST"] == "127.0.0.1:4040") 
        {
            return [];
        }    
        
        //$lastSequence = 0;
        
        
        $mobile = $this->mobile;
        //$mobile = false; //!quickfix forced
        $desktop = !$mobile;
        $dorm = ($desktop ? "desktop" : "mobile");
        

        $browsing = $this->browsing;
        $cc = $this->country_code;

        $high_cpm_countries = explode(",","us,uk,gb,fr,de,at,au,ca,es,be,pl,cz,it,hu,ch,se,dk,no,nl,fi,pt,jp"); //US, UK, EUROPE, JAPAN
        $ishighcpm = in_array($cc, $high_cpm_countries);

        $ad = "";
               
        if($location == "search_products_1") //big amazon listing
        {
            switch($this->country_code)
            {
                default:
                    $ad = DataObject::create("amazon_gaming.html", ["video-ad" => 
                        $this->getAd("middle_video_1")
                    ,"ad-title" => $ad_title, "title" => $title]);
                    break;
            }
        }
        else if($location == "list_products_1") //small amazon listing
        {
            switch($this->country_code)
            {
                default:
                    $ad = [DataObject::create("amazon_electronics.html", ["ad-title" => $ad_title, "title" => $title]),
                    $this->getAd("middle_video_1")
                ];
                    break;
            }
        }
        else
        {
            $adset = $this->locations[$location][$dorm];
            $ex_countries = $this->locations[$location]["exclude_country"];
            $adsetmode = $this->locations[$location][$dorm."_mode"]; //rotate or use all combined
            $adset_excludeareas = $this->locations[$location][$dorm."_exclude_area"]; //do not show ads on given areas

            $width = $this->locations[$location]["width"];
            $height = $this->locations[$location]["height"];
            $height = "auto";
            
            $ad = [];

            //ensure not excluded by area(controller) or country code
            if(!in_array($this->area, $adset_excludeareas) && !in_array($this->country_code, $ex_countries))
            {            
                if($adsetmode == "rotate")
                {
                    $ad_template = $adset != null ? $adset[MemoryCache::getNextSequence($dorm."_".$location, $adset)] : "";
                    //$ad_template = $this->getAdTemplate($ad_template);
                    if(count($adset) > 0)
                    {
                        $adunit = DataObject::create($ad_template, []);
                        $adblock = DataObject::create("_ad_container.html", ["name" => $location . " $dorm", "height" => $height, "width" => $width, "ad" => $adunit]);
                        //$ad[] = $adunit; 
                        $ad[] = $height === 0 ? $adunit : $adblock;
                    }
                    
                }
                else //all
                {
                    //if (!is_array($adset)) { echo $location; }
                    
                    foreach($adset as $ad_template)
                    {
                        //$ad_template = $this->getAdTemplate($ad_template);
                        $adunit = DataObject::create($ad_template, []);
                        $adblock = DataObject::create("_ad_container.html", ["name" => $location . " $dorm", "height" => $height + 2, "width" => $width, "ad" => $adunit]);
                        //$ad[] = $adunit; 
                        $ad[] = $height === 0 ? $adunit : $adblock;
                    }
                }    
            }  
            else
            {
               // echo "skipped $location";
            }      
        }

        return $ad;
    }

    public function getAdTemplate($ad_template)
    {
        try //see if this needs js management
        {
            $def = $this->tags[$ad_template];

            //script
            $script = "";
            if (isset($def["script"])) {
                $block = $def["script"];
                $script = "<script";
                foreach ($block as $k => $v) {
                    $script .= " $k=\"$v\"";
                }
                $script .= "></script>";
            }

            //loader
            $loader = "";
            if (isset($def["loader"])) {
                
                $fn = $def["loader"]["function"];
                if(isset($def["loader"]["params"]))
                    $params = substr(json_encode($def["loader"]["params"]),1, -1);
                else
                    $params = "";

                $tag = isset($def["div"]) ? "div" :  "ins";
                $block = $def[$tag];
                $divid = $block["id"];

                $loader = "<script>registerForLoad('$divid', function() { $fn($params); });</script>";
            }
            else 
            {
                if(isset($def["lazy"]) && $def["lazy"] == 0)
                {
                    //$loader = $script;
                }
                else
                {
                    $fn = "loadScript";
                    if (isset($def["script"]))
                        $params = json_encode($def["script"]);
                    else
                        $params = "";

                    $tag = isset($def["div"]) ? "div" :  "ins";
                    $block = $def[$tag];
                    $divid = $block["id"];

                    $loader = "<script>registerForLoad('$divid', function() { $fn('$divid', $params); });</script>";
                }
                
            }

            //div/ins
            $output = "";              
            $tag = isset($def["div"]) ? "div" :  "ins";
            $block = $def[$tag];
            $output .= "<$tag"; 
            foreach($block as $k => $v)
            {
                $output .= " $k=\"$v\"";
            }

            if (isset($def["lazy"]) && $def["lazy"] == 0) {
                $output .= ">$script</$tag>";  //script inside if no loader
            }
            else 
            {
                $output .= "></$tag>";   
            }
                         

            return "$output $loader";

        } catch (Exception $ex2) { //output the actual html template..
            return $ad_template;
        }
        
    }
}
