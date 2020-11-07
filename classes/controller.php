<?php

class Controller
{
    public $quality;
    public $lc;
    public $layout;
    public $data;
    public $ads;
    public $is_mobile;

    public function __construct($lc)
    {
       
        $this->lc = $lc;
        $this->layout = new Layout($lc);
        //$this->ads = new Ads($lc->country_code, $lc->mobile, $lc->area);
        
        //$detect = new Mobile_Detect;
        $this->is_mobile = $lc->mobile; //$detect->isMobile();
    }

    public function getitemSeries($title)
    {
        //nvidia geforce gtx 1080 ti

        //series = gtx 1080 ti
        //brand = geforce
        //maker = nvidia

        //amd radeon vii

        //series = radeon vii
        //brand = radeon
        //maker = amd

        $parts = explode(" ", $title);

        if(count($parts) < 4)
        {
            return substr($title, stripos($title, $parts[1]));
        }
        else
        {
            return substr($title, stripos($title, $parts[2]));
        }
    }

    public function getCpuBrand($title)
    {
        //amd phenom II x6
        //intel core i7
        $result = substr($title, stripos($title, " ") + 1);
        $result = str_ireplace("core i", "i", $result);

        $atPos = stripos($result, "@");
        if($atPos > 0)
        {
            $result = substr($result, 0, $atPos);
        }
        return trim($result);
    }

    function page_not_found()
    {
        $lc = $this->lc;
        http_response_code(404);
        $l = new Layout($lc);
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
}
?>