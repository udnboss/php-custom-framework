<?php

class Sitemap extends Controller
{
    public function getSitemapIndex()
	{
        $prefix = 'https://'.$_SERVER['HTTP_HOST'];
        
        $sitemaps = explode(" ", "articles");
		$langs = explode(" ", "ar fr de it es pl ru id hi ko ja tr cs da el fi he hu nb nl pt ro sv th uk vi ur ms fa tl bn az zh");
        
        $urls = [];
        
		foreach($langs as $l)
		{
            foreach($sitemaps as $s)
            {
                $url = $prefix . "/sitemap_" . $s . "_" . $l . ".xml";
                $sitemap = DataObject::create("sitemap_index_entry.xml", ["url" => $url]);
                $urls[] = $sitemap;
            }            
		}
		
		$sitemap_index = DataObject::create("sitemap_index.xml", ["urls" => $urls]);

        return $sitemap_index;
    }
    
    public function getSitemapArticle()
    {
        $lang = $this->lc->lang;
        $db = DB::getDataDB();
        
        $stmt = $db->prepare("
            select 
                distinct uid || '/' || id as url
            from 
                articles 
            order by
                date(date) desc
            limit 50000
        ");

        $results = $stmt->execute();

        $urls = null;

        $c = Data::getCurrency($lang);
        

        while ($g = $results->fetchArray(1)) 
        {
            $g["url"] = "https://{$_SERVER['HTTP_HOST']}/$lang-$c/article/" . $g["url"];
            $item = DataObject::create("sitemap_entry.xml", $g);
            $urls[] = $item;
        }

        $sitemap = DataObject::create("sitemap.xml", ["urls" => $urls]);

        return $sitemap;
    }
}

?>