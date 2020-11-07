<?php

 class MemoryCache
 {
    public static $mc;
    public static $sequences = [];
    public static $caches = [];

    public static function getMemCached()
    {
        if(!isset(self::$mc))
        {
            if($_SERVER["HTTP_HOST"] == "localhost:4040" || $_SERVER["HTTP_HOST"] == "127.0.0.1:4040")
            {
                $mc = memcache_connect('localhost', 11211);
            }
            else
            {
                $mc = new Memcached(); //a2hosting
                // echo "inited";
                //$mc->addServer('/opt/memcached/run/SITENAME/memcached-1.sock', 0) or die ("Unable to connect"); //turbo hosting
                $mc->addServer('127.0.0.1', 0) or die ("Unable to connect"); //VPS
                // echo "added";
            }
            self::$mc = $mc;
            return $mc;
        }
        else
        {
            return self::$mc;
        }        
    }

    public static function getNextSequence($key, $array)
    {
        if(!isset(self::$sequences[$key])) 
        {
            $mc = self::getMemCached();
            $current = $mc->get($key);
            if($current === false || $current >= count($array)) { $current = 0; }
            $mc->set($key, $current + 1, 0); // or die ("Unable to save data in the cache");
            self::$sequences[$key] = $current;
        }

        return self::$sequences[$key];         
    }

    public static function getCache($key, $fn = null)
    {
        //$current = $fn(); 
        //return ["cached" => false, "data" => $current];

        if(!isset(self::$caches[$key])) //not within current request cache?
        {
            $fromcache = true;
            
            $mc = self::getMemCached();
            $current = $mc->get($key);
            if($current === false) //not found in memcache
            { 
                $fromcache = false;

                if($fn != null) //fn must be defined to use it and fill mem cache
                {
                    $current = $fn(); 
                    if($_SERVER["HTTP_HOST"] == "localhost:4040" || $_SERVER["HTTP_HOST"] == "127.0.0.1:4040") //memcache
                    {
                        $mc->set($key, $current, 0, 60); // or die ("Unable to save data in the cache"); cached for 5 mins
                    }
                    else //memcacheD
                    {
                        $mc->set($key, $current, 60); // or die ("Unable to save data in the cache"); cached for 5 mins
                    }
                    
                    self::$caches[$key] = $current;
                }
                else //no fn provided
                {
                    return null; //no request cache and no memcache and no fn to fill
                }
                                
            }
            else //found in memcache
            {
                self::$caches[$key] = $current;
            }
            
        }

        return ["cached" => $fromcache, "data" => self::$caches[$key]];         
    }

    public static function setCache($key, $current)
    {
        $mc = self::getMemCached();

        if($_SERVER["HTTP_HOST"] == "localhost:4040" || $_SERVER["HTTP_HOST"] == "127.0.0.1:4040") //memcache
        {
            $mc->set($key, $current, 0, 60); // or die ("Unable to save data in the cache"); cached for 1 min
        }
        else //memcacheD
        {
            $mc->set($key, $current, 60); // or die ("Unable to save data in the cache"); cached for 1 min
        }
        
        self::$caches[$key] = $current;
    }

    public static function clearCache($key)
    {
        $mc = self::getMemCached();
        $mc->delete($key);
        if(isset(self::$caches[$key])) 
        {
            unset(self::$caches[$key]);
        }
    }
 }
