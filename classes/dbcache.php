<?php

class DBCache {

    public static function setCache($key, $object, $category = 'other', $serialize = true, $compress = true)
    {
        $contents = $object;
        if($serialize){
            $contents = serialize($contents);
        }
        if($compress){
            $contents = gzcompress($contents);
        }

        self::deleteCache($key);

        $db = DB::getCacheDB();
        $stmt = $db->prepare("insert into cache(key, value, category, date) values (:key, :value, :category, DateTime('now'))");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);      
        $stmt->bindValue(':value', $contents, SQLITE3_TEXT);      
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);      

        $results = $stmt->execute();
        $count = $db->changes();

        return $count > 0;
    }

    public static function deleteCache($key)
    {
        $db = DB::getCacheDB();
        $stmt = $db->prepare("delete from cache where key = :key");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);       

        $results = $stmt->execute();
        $count = $db->changes();

        return $count > 0;
    }

    public static function clearCache($category = "other")
    {
        $db = DB::getCacheDB();
        $stmt = $db->prepare("delete from cache where category = :category");
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);       

        $results = $stmt->execute();
        $count = $db->changes();

        return $count > 0;
    }

    public static function getCache($key, $days_old=5, $category = 'other', $serialize = true, $compress = true)
    {
        $db = DB::getCacheDB();
        $stmt = $db->prepare("select key, value, category, date from cache where key = :key and date(date) <= date('now','-5 day') limit 1");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);         

        $results = $stmt->execute();

        $contents = null;

        while($cache = $results->fetchArray(1))
        {
            $contents = $cache["value"];
        }

        if($contents != null)
        {
            if($compress)
            {
                $contents = gzuncompress($contents);
            }
            if($serialize)
            {
                $contents = unserialize($contents);
            }            
        }

        return $contents;
    }    
} 