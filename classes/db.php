<?php 
class DB
{
    public static $data_db = null;   
    public static $lang_db = null;

    public static function getDataDB()
    {
        if(self::$data_db == null)
        {
            self::$data_db = new SQLite3('../db/chef_en.sqlite');
        }
        return self::$data_db;
    }    
    
    public static function getLangDB()
    {
        if(self::$lang_db == null)
        {
            self::$lang_db = new SQLite3('../db/lang.sqlite');
        }
        return self::$lang_db;
    }
}
?>