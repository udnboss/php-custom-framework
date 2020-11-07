<?php 
 
    //error_reporting(E_ERROR); //log only errors
    // error_reporting( E_ALL );
    // ini_set('display_errors', 1);

    // echo "site under maintenance and will be back in 10 minute";
    // die();

    //ini_set('zlib.output_compression', 1);
    //ob_start("ob_gzhandler"); //compress start
    require_once("data.php");
    require_once("memorycache.php");
    require_once("filecache.php");
    require_once('mobile_detect.php');
    require_once("renderer.php");    
    require_once("db.php");    
    require_once("lang.php");
    require_once("ads.php");
    require_once("controller.php");
    require_once("layout.php");





    //session_start();
   

    $url = $_GET["url"];
    $cleanurl = preg_replace("/['\"]+/","",$url);
    $cleanurl = strip_tags($cleanurl); //xss noobs

    $params = explode("/", $url);    
    $area = $params[0];
    
    if($url != $cleanurl)
    {
        $area = "404";
        
    }

    $lang = isset($_GET["lang"]) ? $_GET["lang"] : "en";
    $currency = isset($_GET["currency"]) ? $_GET["currency"] : "usd";
    $country_code = isset($_SERVER["HTTP_CF_IPCOUNTRY"]) ? $_SERVER["HTTP_CF_IPCOUNTRY"] : "SA";

    $detect = new Mobile_Detect;
    $mobile = $detect->isMobile();
    $device = $mobile ? "mobile" : "desktop";

    $lc = new Lang($lang, $currency, $country_code, $area, $mobile);
    $cc = $lc->countries[$lang];
    $country = $cc["name"] . " / " . $cc["native_name"];

    

if($_POST)
{
    
}
else //GET REQUEST
{
    //phpinfo();
    //libxml_use_internal_errors(true);
    header_remove("Cache-Control");
    header_remove('Pragma');
    header_remove('Cookie');

    //cache check
    $use_cache = false;
    $renderUnchached = true;

    if($use_cache)
    {
        $key = $device.$url.$lang.$currency;//.$country_code
        $cache = MemoryCache::getCache($key);

        if($cache != null)
        {
            echo $cache["data"];
            $renderUnchached = false;
        }
        else
        {
            $renderUnchached = true;
        }
    }
    
    if($renderUnchached)
    {
        $output = "";
        //echo $area;
    
        if($area == "" || $area == "articles" || !isset($url)) //home
        {
            $id = $params[1];
            require_once("articles.php");
            $c = new Articles($lc, $id);            
            $p = $c->render();
            $output = Renderer::render($p);
            
        }
        else if($area == "contact-us") 
        {
            $layout= new Layout($lc);
            $c = DataObject::create("contactus.html", []);
            $p = $layout->get_page([
                "page-title" => $lc->get("contact-us"),
                "title" => $lc->get("contact-us"),
                "content" => $c,
                "meta-description" => ""
            ]);
            $output = Renderer::render($p);
        }
        else if($area == "article")
        {
            $id = $params[1];
            require_once("article.php");
            $c = new Article( $lc, $id);
            $p = $c->render();
            $output = Renderer::render($p);
        }
        else if($area == "privacy-policy") //
        {
            $layout= new Layout($lc);
            $c = DataObject::create("_layout_privacy.html", []);
            $p = $layout->get_page([
                "page-title" => "Privacy Policy",
                "title" => "Privacy Policy",
                "content" => $c
            ]);
            $output = Renderer::render($p);
        }
        else if(stripos($area, "sitemap_") === 0) //sitemaps
        {
            header("Content-type: text/xml");
            header("Content-Disposition: attachment; filename=$area"); 
               
            require_once("sitemap.php");
            $lang = substr($area, stripos($area, ".") - 2, 2);
            if($lang == "zh")
            {
                $lang = "zh-Hans";
            }
    
            $lc = new Lang($lang, "usd", "US");
            $sc = new Sitemap($lc);        
    
            $sitemap = substr($area, 0, stripos($area, ".") - 3);
            switch($sitemap)
            {
                case "sitemap_article": $output = $sc->getSitemapArticle(); break;
                default: $output = "sitemap not found"; break;
            }
    
            //DBCache::setCache($key, $output);
        }
        else if ($area == "sitemap.xml")
        {
            header("Content-type: text/xml");
            header("Content-Disposition: attachment; filename=$area");
            require_once("sitemap.php");
            $sc = new Sitemap($lc);  
            $output = $sc->getSitemapIndex();
        }
        else //unknown area
        {
            $output = page_not_found($lc);
        }
    
        
            
    
        //global replacement for side ads
        $ads = new Ads($country_code, $mobile, $area);
        $obj = DataObject::create($output, 
            [
                "desktop-side-ad" => $ads->getAd("desktop_side"), //DataObject::create( $mobile == true ? "viralize-banner.html" : "selectmedia-desktop.html", [ "sub-ad" => DataObject::create("setupad-300x600.html", []) ]),
                "above-recommendations-ad" => $ads->getAd("recommendations_above_article"), // DataObject::create("taboola-side.html", []),
                "side-recommendations-ad" => $ads->getAd("recommendations_side"), // DataObject::create("taboola-side.html", []),
                "mid-recommendations-ad" => $ads->getAd("recommendations_mid_article"), //DataObject::create("taboola-mid-article.html", []),
                "bottom-recommendations-ad" => $ads->getAd("recommendations_below_article"), //DataObject::create("taboola-below-article.html", [])
            ]
        );
    
        $output = Renderer::render($obj, true);
    
        //all graphs offloaded to other site for performance
        //$output = str_replace("/graph/", "https://SITENAME.a2hosted.com/graph/", $output);
    
        //apply language and currency on all links (not containing a dot - to exclude files)
        if($lang != "en" && $currency != "usd")
        $output = preg_replace("/href\=(\"|\')(\/(.(?!\.))*)(\"|\')/", "href=$1/$lang-$currency$2$4", $output);
    
        //links for changing language in current page
        $output = preg_replace("/href=\`([^#]*)(#current_page#)/", "href=\"" . "$1" . explode("?", $url)[0] , $output);
    
        //special replacement for hreflangs
        $output = preg_replace("/`/", "\"", $output);
    
        $output = str_replace("</title>", " - SITENAME $country</title>", $output);
    
    
        //DBCache::setCache($key, $output);
        //set cache   
        if($use_cache) {
            //FileCache::set_object_in_cache($key, $output, $device);
            MemoryCache::setCache($key, $output);
        }
      
        echo $output;
    
    
        //ob_end_flush(); //finish
    }

    
}

    function page_not_found($lc)
    {
        http_response_code(404);
        $l = new Layout($lc,  false);
        $page = $l->get_page([                
            //"section-title" => "Game Performance (FPS) at High Quality Settings",
            "page-title" =>  "404: Page Not Found",
            "content" => DataObject::create("404.html", []),
            //DataObject::create("image_chart.html", ["image" => $item1["id"] . "-vs-". $item2["id"] ."/" . $cpu1 ."/". $cpu2 ."/" . "chart.png"])

            "meta-description" => "Page Not Found.",
            "meta-keywords" =>  ""
        ]);

        $output = Renderer::render($page);
        
        return $output;
    }

    /********************************************************** ROUTER END */

?>

