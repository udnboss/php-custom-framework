<?php

class Articles extends Controller{

    public $category_uid;
    public function __construct($lc, $category_uid)
    {
        parent::__construct($lc);
        $this->category_uid = $category_uid;
    }

    public function getData($category_uid = null)
    {
        $db = DB::getDataDB();

        $stmt = $db->prepare("
        select 
                a.uid, a.id, a.title, a.date, a.time, a.image, a.rating, a.kcal, a.category_uid, c.name as category, d.name as difficulty
            from articles a
                join categories c on a.category_uid = c.uid
                join difficulties d on a.difficulty_uid = d.uid
            where :category_uid is null or category_uid = :category_uid
            order by date(date) desc
        limit 10");
        $stmt->bindValue(":category_uid", $category_uid, $category_uid == null ? SQLITE3_NULL : SQLITE3_INTEGER);
        $results = $stmt->execute();
        $articles = [];

        while ($a = $results->fetchArray(1))
        {
            $item = DataObject::create("article.html", $a);
            $articles[] = $item;
        }

        return $articles;
    }

    public function render()
    {
        $articles = $this->getData($this->category_uid);
        
        $sub_page = DataObject::create("_layout_articles.html", [
            "title" => "articles title..",
            "articles" => $articles                 
        ]);

        $page = $this->layout->get_page(
            [               
                //"canonical" => "<link href=\"https://www.SITENAME.com/article/$id\" rel=\"canonical\" />",
                "page-title" => "articles title..",
                "content" => $sub_page,
                "meta-description" => "articles title.."
                //"meta-keywords" => DataObject::create($lc->get("meta-keywords-item"), ["item"=> $series, "cpu"=> $cpu_brand, "quality" => $quality]),
            ]);

        return $page;
    }
}

?>