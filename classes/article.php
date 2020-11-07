<?php

class Article extends Controller
{
    public $id;
    public $uid;

    public function __construct($lc, $uid)
    {
        parent::__construct($lc);
        $this->uid = $uid;
    }

    public function getData()
    {
        $db = DB::getDataDB();
        $stmt = $db->prepare("
            select 
                a.uid, a.id, a.title, a.date, a.time, a.image, a.rating, a.kcal, a.category_uid, c.name as category, d.name as difficulty
            from articles a
                join categories c on a.category_uid = c.uid
                join difficulties d on a.difficulty_uid = d.uid
            where a.uid = :uid
            ");
        $stmt->bindValue(':uid', $this->uid, SQLITE3_TEXT);
        $results = $stmt->execute();
        $article = null;
        while($a = $results->fetchArray(1))
        {
            $date = new DateTime($a["date"]);
            $a["formatted_date"] = $date->format('F d, Y');
            $article = $a;
            $this->uid = $a["uid"];
            $this->id = $a["id"];
        }
        
        return $article;
    }

    public function getSimilar()
    {
        $db = DB::getDataDB();
        $stmt = $db->prepare("
            select 
                a.uid, a.id, a.title, a.date, a.time, a.image, a.rating, a.kcal, a.category_uid, c.name as category, d.name as difficulty
            from articles a
                join categories c on a.category_uid = c.uid
                join difficulties d on a.difficulty_uid = d.uid
            where a.uid <> :uid and a.category_uid = (select category_uid from articles where uid = :uid limit 1)
            limit 13
            ");
        $stmt->bindValue(':uid', $this->uid, SQLITE3_TEXT);
        $results = $stmt->execute();
        $articles = [];
        while($a = $results->fetchArray(1))
        {
            $date = new DateTime($a["date"]);
            $a["formatted_date"] = $date->format('F d, Y');
            $articles[] = DataObject::create("article-sm.html", $a);
        }
        
        return $articles;
    }

    public function getImages()
    {
        $db = DB::getDataDB();
        $stmt = $db->prepare("select srcset from images where parent_id = :uid");
        $stmt->bindValue(':uid', $this->uid, SQLITE3_INTEGER);

        $results = $stmt->execute();
        $images = [];
        while($a = $results->fetchArray(1))
        {
            $images[] = DataObject::create("image.html", $a);
        }
        
        return $images;
    }

    public function getIngredients()
    {
        $db = DB::getDataDB();
        $stmt = $db->prepare("
        select k.name as ingredient, i.quantity as quantity, u.name as unit
            from ingredients i 
                join ingredientkeys k on i.key_uid = k.uid 
                join units u on i.unit_uid = u.uid
            where i.parent_id = :uid");
        $stmt->bindValue(':uid', $this->uid, SQLITE3_INTEGER);

        $results = $stmt->execute();
        $ingredients = [];
        while($a = $results->fetchArray(1))
        {
            $ingredients[] = DataObject::create("ingredient.html", $a);
        }
        
        return $ingredients;
    }

    public function getNutritions()
    {
        $db = DB::getDataDB();
        $stmt = $db->prepare("
        select k.name as nutrition, i.amount as amount
            from nutritions i 
                join nutritionkeys k on i.key_uid = k.uid 
            where i.parent_id = :uid");
        $stmt->bindValue(':uid', $this->uid, SQLITE3_INTEGER);

        $results = $stmt->execute();
        $ingredients = [];
        while($a = $results->fetchArray(1))
        {
            $ingredients[] = DataObject::create("nutrition.html", $a);
        }
        
        return $ingredients;
    }

    public function getTags()
    {
        $db = DB::getDataDB();
        $stmt = $db->prepare("
        select k.name
            from tags i 
                join tagkeys k on i.key_uid = k.uid 
            where i.parent_id = :uid");
        $stmt->bindValue(':uid', $this->uid, SQLITE3_INTEGER);

        $results = $stmt->execute();
        $tags = [];
        while($a = $results->fetchArray(1))
        {
            $tags[] = DataObject::create("tag.html", $a);
        }
        
        return $tags;
    }

    public function getDirections()
    {
        $db = DB::getDataDB();
        $stmt = $db->prepare("select text from directions where parent_id = :uid");
        $stmt->bindValue(':uid', $this->uid, SQLITE3_INTEGER);

        $results = $stmt->execute();
        $directions = [];
        while($a = $results->fetchArray(1))
        {
            $directions[] = DataObject::create("paragraph.html", $a);
        }
        
        return $directions;
    }

    public function getComments()
    {
        $db = DB::getDataDB();
        $stmt = $db->prepare("select * from comments where parent_id = :uid");
        $stmt->bindValue(':uid', $this->uid, SQLITE3_INTEGER);

        $results = $stmt->execute();
        $comments = [];
        while($a = $results->fetchArray(1))
        {
            $a["first-letter"] = $a["user"][0];
            $date = new DateTime($a["date"]);
            $a["formatted_date"] = $date->format('F d, Y');
            $comments[] = DataObject::create("comment.html", $a);
        }
        
        return $comments;
    }

    public function render()
    {
        $lc = $this->lc;

        $article = $this->getData();

        if($article == null)
        {
            return $this->page_not_found();
        }

        $ingredients = $this->getIngredients();
        $nutritions = $this->getNutritions();
        $directions = $this->getDirections();
        $images = $this->getImages();
        $tags = $this->getTags();
        $similar = $this->getSimilar();
        
        $indicators = array_map(function($k, $v) {
            return DataObject::create("carousel_indicator.html", ["index" => $k]);
        }, array_keys($images), $images);

        $items = array_map(function($k, $v) use ($article) {
            return DataObject::create("carousel_item.html", ["srcset" => $v->data["srcset"], "active" => $k == 0 ? "active" : ""]);
        }, array_keys($images), $images);

        $carousel = DataObject::create("carousel.html", ["indicators" => $indicators, "items" => $items]);
        //$videos

        $comments = $this->getComments();
        
        $sub_page = DataObject::create("_layout_article.html", [
            "date" => $article["date"],
            "formatted_date" => $article["formatted_date"],
            "title" => $article["title"],
            "time" => $article["time"],
            "kcal" => $article["kcal"],
            "id" => $article["id"],
            "rating" => $article["rating"],
            "category" => $article["category"],
            "difficulty" => $article["difficulty"],
            "tags" => $tags,
            "image" => $article["image"],
            "images" => $images,     
            "ingredients" => $ingredients,    
            "nutritions" => $nutritions,
            "directions" => $directions,
            "similar" => $similar,
            "carousel" => $carousel,
            "comments" => $comments                     
        ]);

        $page = $this->layout->get_page(
            [               
                //"canonical" => "<link href=\"https://www.SITENAME.com/article/$id\" rel=\"canonical\" />",
                "page-title" => $article["title"],
                "content" => $sub_page,
                "meta-description" =>  $article["title"]
                //"meta-keywords" => DataObject::create($lc->get("meta-keywords-item"), ["item"=> $series, "cpu"=> $cpu_brand, "quality" => $quality]),
            ]);

        return $page;
    }
}

?>