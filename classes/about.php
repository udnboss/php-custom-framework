<?php

class About extends Controller
{    
    public function getData()
    {
        $lc= $this->lc;

        $about = DataObject::create("about.html", ["lang"=> $lc->lang, "currency"=>$lc->currency, "about"=> $lc->get("about")  . " SITENAME.com", "webmaster" => $lc->get("webmaster"), "about-text" => $lc->get('about-text')]);
       
        return $about;
    }

    public function render()
    { 
        $q = $this->quality;
        $lc = $this->lc;

        $about = $this->getData();

        $page = $this->layout->get_page(
            [
                "page-title" => $lc->get("about"),
                "title" => $lc->get("about") . " SITENAME.com",
                "content" => $about,
                "meta-description" => substr($lc->get("about"), 0, 250),
                "meta-keywords" => $lc->get("meta-keywords")
            ]);

        return Renderer::render($page);
    }
}

?>