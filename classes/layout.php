<?php

class Layout
{
    public $lc;
    public $valid;

    public function __construct($lc, $valid = true)
    {
        $this->lc = $lc;
        $this->valid = $valid;
    }

    public function isMobileDev(){
        if(isset($_SERVER['HTTP_USER_AGENT']) and !empty($_SERVER['HTTP_USER_AGENT'])){
           $user_ag = $_SERVER['HTTP_USER_AGENT'];
           if(preg_match('/(Mobile|Android|Tablet|GoBrowser|[0-9]x[0-9]*|uZardWeb\/|Mini|Doris\/|Skyfire\/|iPhone|Fennec\/|Maemo|Iris\/|CLDC\-|Mobi\/)/uis',$user_ag)){
              return "true";
           }else{
              return "false";
           };
        }else{
           return "false";    
        };
    }

    public function getContactUsLink()
    {
        $text = $this->lc->get('contact-us');
        return DataObject::create("<a href=\"/contact-us/\" class=\"btn btn-warning\">{text}</a>", ["text" => $text]);
    }

    public function getData()
    {
        $lc= $this->lc;

        $db = DB::getDataDB();
        
        $stmt = $db->prepare("
            select 
                ap.uid || '/' || ap.id as id, ap.title as title
            from 
                articles ap         
            order by
                ap.title
        ");

        $results = $stmt->execute();
        
        $articles = array();

        while ($g = $results->fetchArray(1)) {

            $item = ["id" => $g["id"], "name" => $g["title"]];

            $articles[] = $item;
        }

        
        return json_encode($articles);
    }
    public function get_page($data):DataObject
    {
        $lc = $this->lc;
        
        $url = explode("?", $_GET["url"])[0];

        $url = strip_tags($url);

        if(!$this->valid)
        {
            //pre
            $url = "";
        }

        $prefix = 'https://'.$_SERVER['HTTP_HOST'];
        //hreflangs
        $hreflang_en = "<link rel=\"alternate\" hreflang=\"x-default\" href=`$prefix/$url\" />";
        $hreflang_template = "<link rel=\"alternate\" hreflang=\"{lang}\" href=`$prefix/{lang}-{currency}/$url\" />";

        $hreflangs = $hreflang_en;
        
        //langs and currencies
        $langs = explode(" ", "ar-sar fr-eur de-eur it-eur es-eur pl-pln ru-rub id-idr hi-inr ko-krw ja-jpy tr-try cs-czk da-dkk el-eur fi-eur he-ils hu-huf nb-nok no-nok nl-eur pt-eur ro-ron sv-eur th-thb uk-uah vi-vnd ur-pkr zh-Hans-cny ms-myr fa-irr tl-php bn-bdt az-azn");

        foreach($langs as $lnc)
        {
            

            $l = explode("-", $lnc)[0];
            $c = explode("-", $lnc)[1];
            
            // if($this->lc->lang == $l)
            // {
            //     continue; //skip current language
            // }

			if($l == 'zh') //chinese
			{
				$l = 'zh-Hans';
                $c = 'cny';
                
                if($this->lc->lang == $l)
                {
                    continue; //skip chinese language
                }

                $hreflang = "<link rel=\"alternate\" hreflang=\"zh\" href=`$prefix/zh-Hans-cny/$url\" />";
			}
            else
            {
                $hreflang = preg_replace("/\{lang\}/", $l, $hreflang_template) ;
                $hreflang = preg_replace("/\{currency\}/", $c, $hreflang) ;
            }
            

            $hreflangs .= "\n\t" . $hreflang;
        }

        $lang = $lc->lang;
        $currency = $lc->currency;
        $canonical = "<link rel=\"canonical\" href=\"$prefix/$lang-$currency/$url\" />";

        if($lang == "en" && $currency == "usd")
        {
            $canonical = "<link rel=\"canonical\" href=\"$prefix/$url\" />";
        }



        $meta_en = "<meta http-equiv=\"content-language\" content=\"en-us\">";
        $meta_template = "<meta http-equiv=\"content-language\" content=\"{lang}-{country}\">";
        
        //langs and countries
		$countries = explode(" ", "en-us ar-sa fr-fr de-de it-it es-es pl-pl ru-ru id-id hi-in ko-kr ja-jp tr-tr cs-cz da-dk el-gr fi-fi he-il hu-hu nb-no no-no nl-nl pt-pt ro-ro sv-se th-th uk-ua vi-vn ur-pk zh-Hans-cn ms-my fa-ir tl-ph bn-bd az-az");
        $dict_countries = [];
		
		foreach($countries as $lnc)
        {
			$l = explode("-", $lnc)[0];
            $c = explode("-", $lnc)[1];
			
			if($l == 'zh') //chinese
			{
				$l = 'zh-Hans';
				$c = 'cn';
			}
			
			$dict_countries[$l] = $c;
		}
		
		$country = $dict_countries[$lang];
		
		$meta_lang = preg_replace("/\{lang\}/", $lang, $meta_template);
		$meta_lang = preg_replace("/\{country\}/", $country, $meta_lang);    
        
        
        
        //show login/logout
        $logged_in = isset($_SESSION["email"]);
        $user = $logged_in ? $_SESSION["name"] : "Guest";

        $dir = $lc->lang == "ar" || $lc->lang == "fa" || $lc->lang == "he" || $lc->lang == "ur" ? "rtl" : "ltr";

        //$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
        //$browsing = stripos($referrer, "https://" . $_SERVER['HTTP_HOST']) === 0;
        $organic_ads = "";
        //$subscribe = DataObject::create("subscribe.html", []);
        
        //$poll = DataObject::create("poll.html", []);
        //$total_browse = $_SESSION["browsing"];

        //require_once('mobile_detect.php');
        //$detect = new Mobile_Detect;
        $mobile = $lc->mobile; //$detect->isMobile(); // $this->isMobileDev();
        $ads = new Ads($lc->country_code, $lc->mobile, $lc->area);

        //$no_interstitial_countries = explode(",","US,UK,FR,DE,AT,AU,CA,ES,BE,PL,CZ,IT,HU,CH,SE,DK,NO,NL,FI,PT"); //US, UK, EUROPE
        //$show_mobile_interstitial = $mobile == true && !in_array($lc->country_code, $no_interstitial_countries);

        $final_data = [
            "is-mobile" => $mobile,
            "dir" => $dir,
            "rtl-bootstrap" => $dir == "rtl" ? "<link rel=\"stylesheet\" href=\"/static/bootstrap_rtl.min.css\">" : "",
            "lang" => $lc->lang,
            "currency" => $lc->currency,
            "country" => $lc->country_code,
            "organic-ads" => $organic_ads, 
            "search" => $lc->get("search"),
            "copyright" => $lc->get("copyright"),
            "hreflang" => $hreflangs,
            "canonical" => $canonical,
            "language" => $lc->get("language"),
            "home" => $lc->get("home"),
            "cards" => $lc->get("cards"),
            
            "site-name" => $lc->get("site-name"),
            "page-title" => $lc->get("page-title"),
            "title" => $lc->get("title"),
            "meta-description" => $lc->get("meta-description"),
            "meta-keywords" => $lc->get("meta-keywords"),
            "about" => $lc->get("about"),
            "contact-us" => $lc->get("contact-us"),
            "items" => $this->getData(),

            "user" => $user,
            "main-col-size" => "8",
            "side-col-size" => "4",
            "ad-body-first" => $ads->getAd("ad-body-first"),
            "side-vertical-ad" => $ads->getAd("side_vertical"),
            "side-vertical-ad1" => $ads->getAd("side_vertical_left"),
            "side-vertical-ad2" => $ads->getAd("side_vertical_right"),
            "side-vertical-ad3" => $ads->getAd("side_vertical_left_2"),
            "side-vertical-ad4" => $ads->getAd("side_vertical_right_2"),
            "ad-sticky-bottom" => $ads->getAd("bottom_center_floating"),
            "ad-top-middle" => $ads->getAd("top_middle"), //1200x300 top middle
            "ad-top-left" =>  $ads->getAd("top_left"), //top left
            "ad-top-right" => $ads->getAd("top_right"), //top right
            "ad-push-notification" => $ads->getAd("push_notification"),
            "ad-interstitial" => $ads->getAd("interstitial"), 
            "ad-exit" => $ads->getAd("exit"), //
            "ad-late-slider" => $ads->getAd("late_slider"), //

            "ad-bottom-right" => $ads->getAd("bottom_right_floating"), 
            "ad-bottom-left" => $ads->getAd("bottom_left_floating") , 
            "desktop-side-ad" => "SIDE AD HERE!" //this is rendered later in index.php
        ];

        foreach($data as $k => $v)
        {
            $final_data[$k] = $v;
        }

        $page = DataObject::create("_layout.html", $final_data);

        return $page;
    }

}
?>