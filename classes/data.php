<?php

class Data
{

    public static function getCurrency($lang)
    {
        $c = "usd";

        switch($lang)
        {
            case "en": $c = "usd"; break;
            case "ar": $c = "sar"; break;
            case "fr": $c = "eur"; break;
            case "de": $c = "eur"; break;
            case "it": $c = "eur"; break;
            case "es": $c = "eur"; break;
            case "pl": $c = "pln"; break;
            case "ru": $c = "rub"; break;
            case "id": $c = "idr"; break;

            case "hi": $c = "inr"; break;
            case "ko": $c = "krw"; break;           
            case "ja": $c = "jpy"; break;
            case "zh-Hans": $c = "cny"; break;
            case "tr": $c = "try"; break;
            case "ur": $c = "pkr"; break;

            case "cs": $c = "czk"; break;
            case "da": $c = "dkk"; break;
            case "el": $c = "eur"; break;
            case "fi": $c = "eur"; break;
            case "he": $c = "ils"; break;
            case "hu": $c = "huf"; break;
            case "no": $c = "nok"; break;
            case "nl": $c = "eur"; break;
            case "pt": $c = "eur"; break;
            case "ro": $c = "ron"; break;
            case "sv": $c = "eur"; break;
            case "th": $c = "thb"; break;
            case "uk": $c = "uah"; break;
            case "vi": $c = "vnd"; break;

        }

        return $c;
    }
}

class DataObject
{
    public $template_file;
    public $data;

    public static function create(String $template_file = null, Array $data): DataObject
    {
        $instance = new self();
        $instance->template_file = $template_file;
        $instance->data = $data;
        return $instance;
    }
}
class GUID
{
    public static function getGUID(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }
        else {
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"

                $uuid = preg_replace("/\{/", "", $uuid);
                $uuid = preg_replace("/\}/", "", $uuid);
                $uuid = strtolower($uuid);

            return $uuid;
        }
    }
}

class Color {
    

    public static function randomColor() {
        $str = '#';
        for ($i = 0; $i < 6; $i++) {
            $randNum = rand(9, 14);
            switch ($randNum) {
                case 10: $randNum = 'A';
                    break;
                case 11: $randNum = 'B';
                    break;
                case 12: $randNum = 'C';
                    break;
                case 13: $randNum = 'D';
                    break;
                case 14: $randNum = 'E';
                    break;
                case 15: $randNum = 'F';
                    break;
            }
            $str .= $randNum;
        }
        return $str;}
    
    
}

class Time
{
    public static function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
    
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
    
        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
    
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

class Performance
{
    public static $prof_timing = [];
    public static $prof_names = [];

    // Call this at each point of interest, passing a descriptive string
    public static function prof_flag($str)
    {
        self::$prof_timing[] = microtime(true);
        self::$prof_names[] = $str;
    }

    // Call this when you're done and want to see the results
    public static function prof_print()
    {
        $prof_timing = self::$prof_timing;
        $prof_names = self::$prof_names;
        $output = [];
        $size = count($prof_timing);
        for($i=0;$i<$size - 1; $i++)
        {
            $output[$prof_names[$i]] = $prof_timing[$i+1]-$prof_timing[$i];
        }

        var_dump($output);
    }
}

class Strings
{
    public static function titleCase($title){
        $str = ucwords($title);     
        $exclude = 'a,an,the,for,and,nor,but,or,yet,so,such,as,at,around,by,after,along,for,from,of,on,to,with,without';        
        $excluded = explode(",",$exclude);
        foreach($excluded as $noCap){$str = str_replace(ucwords($noCap),strtolower($noCap),$str);}      
        return ucfirst($str);
    }

    public static function sentenceCase($str) {
        $cap = true;
        $ret='';
        for($x = 0; $x < strlen($str); $x++){
            $letter = substr($str, $x, 1);
            if($letter == "." || $letter == "!" || $letter == "?"){
                $cap = true;
            }elseif($letter != " " && $cap == true){
                $letter = strtoupper($letter);
                $cap = false;
            } 
            $ret .= $letter;
        }
        return $ret;
     }
}
?>
