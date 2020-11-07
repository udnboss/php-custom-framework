<?php

class Lang
{
    public $lang;
    public $currency;
    public $country_code;
    public $country;
    public $area;
    public $mobile;

    public $db;
    public $strings;
    public $currencies;
    public $countries;

    public function __construct($lang = "en", $currency = "usd", $country_code = "US", $area = "", $mobile = false)
    {
        $this->lang = $lang;
        $this->currency = $currency;
        $this->country_code = $country_code;
        $this->area = $area;
        $this->mobile = $mobile;
        $this->db = DB::getLangDB();
        $this->init();
        $this->country = $this->countries[$lang];
    }

    public function init()
    {
        $this->strings = [];
        $db = DB::getLangDB(); //$this->db;
        $lang = $this->lang;

        $get_strings = function () use ($db, $lang) {
            $strings = [];
            $stmt = $db->prepare("select id, lang, text from lang where lang = 'en' or lang = :lang");
            $stmt->bindValue(":lang", $lang, SQLITE3_TEXT);
            $results = $stmt->execute();
            while ($i = $results->fetchArray(1)) {            
                $strings[$i["id"]][$i["lang"]] = $i["text"]; 
            }  

            return $strings;
        };

        
        $result = MemoryCache::getCache("lang_".$lang."_strings", $get_strings);
        $this->strings = $result["data"];

        $this->currencies = [];
        $currency =  $this->currency;

        $get_currencies = function () use($db, $currency) {
            $currencies = [];
            $stmt = $db->prepare("select id, rate, symbol, country from currency --where id = 'usd' or id = :currency");
            $stmt->bindValue(":currency", $currency, SQLITE3_TEXT);
            $results = $stmt->execute();
            while ($i = $results->fetchArray(1)) {            
                $currencies[$i["id"]] = ["rate" => $i["rate"], "symbol" => $i["symbol"], "country" => $i["country"]]; 
            }
            return $currencies;
        };

        $this->currencies = (MemoryCache::getCache("lang_currencies", $get_currencies))["data"];

        $this->countries = [];
        $get_countries = function() use($db) {
            $countries = [];
            $stmt = $db->prepare("select lang, name, native_name from country order by lang");
            $results = $stmt->execute();
            while ($i = $results->fetchArray(1)) {            
                $countries[$i["lang"]] = ["name" => $i["name"], "native_name" => $i["native_name"]]; 
            }
            return $countries;
        };

        $this->countries = (MemoryCache::getCache("lang_countries", $get_countries))["data"];


    }

    public function getValue($v)
    {
        return $this->getCurrency($v)["value"];
    }

    public function getCurrency($v)
    {
        $result = $this->currencies[$this->currency];
        $result["value"] = round($v * $result["rate"], 1);
        $result["full_value"] = $result["symbol"] . " " . $result["value"]; 
        return $result;

        // $db = $this->db;
        
        // $stmt = $db->prepare('
        //     select rate, symbol from currency where id = :currency
        // ');

        // $stmt->bindValue(':currency', $this->currency, SQLITE3_TEXT);

        // $results = $stmt->execute();

        // $result = "";              

        // while ($t = $results->fetchArray(1)) {            
        //     $result = $t;    
        //     $result["value"] = round($v * $t["rate"], 1);
        // }       

        // return $result;
    }

    public function get($id)
    {
        if(!isset($this->strings[$id])) return ""; //todo set blank
        $result = isset($this->strings[$id][$this->lang]) ? $this->strings[$id][$this->lang] : $this->strings[$id]["en"];

        $currency_symbol = $this->currencies[$this->currency]["symbol"];
        $result = preg_replace("/\{currency\}/", $currency_symbol, $result);     
        return $result;



        // $db = $this->db;
        
        // $stmt = $db->prepare('
        //     select 
        //         text
        //         , (select symbol from currency where id = :currency) as symbol
        //     from 
        //         lang
        //     where 
        //         id = :id and lang = :lang
        //     limit 1
        // ');

        // $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        // $stmt->bindValue(':lang', $this->lang, SQLITE3_TEXT);
        // $stmt->bindValue(':currency', $this->currency, SQLITE3_TEXT);

        // $results = $stmt->execute();

        // $text = "";              

        // while ($t = $results->fetchArray(1)) {            
        //     $text = preg_replace("/\{currency\}/", $t["symbol"], $t["text"]);                
        // }       

        //infinite loop err
        // if($text == "") //default tp english of not found
        // {
        //     $lc = new Lang();
        //     $text = $lc->get($id);
        // }

        //return $text;
    }
}

?>