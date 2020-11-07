<?php

class FileCache {
    private static function get_dir_path($key, $category){
        $md5 = md5($key);
        $chars = str_split($md5);
        $dirs_path = "./static/cache/$category/".implode("/", $chars);
        return $dirs_path;
    }
    private static function get_path($key, $category){
        $filename = sha1($key);
        $file_path = self::get_dir_path($key,$category)."/".$filename;
        return $file_path;
    }

    private static function makedirs($key, $category, $mode=0777) {
        $dirpath = self::get_dir_path($key,$category);
        return is_dir($dirpath) || mkdir($dirpath, $mode, true);
    }
    public static function set_object_in_cache($key, $object, $category = 'other', $serialize = true, $compress = true){
        $file_path = self::get_path($key, $category);
        if(!file_exists($file_path)) {
            self::makedirs($key, $category);
        }

        $contents = $object;
        if($serialize){
            $contents = serialize($contents);
        }
        if($compress){
            $contents = gzcompress($contents);
        }

        file_put_contents($file_path, $contents);
    }

    public static function delete_object_from_cache($key, $category)
    {
        $file_path = self::get_path($key, $category);
        if(file_exists($file_path)) {
            unlink($file_path);
        }
    }

    public static function get_object_from_cache($key, $days_old=5, $category = 'other', $serialize = true, $compress = true){

        $file_path = self::get_path($key, $category);

        $expired = true;

        if(file_exists($file_path)) {
            $created = filectime($file_path);
            $today = time();
            $difference = $today - $created;
            $daysDifference = $difference/86400;
            if($daysDifference < $days_old){
                $expired = false;
            }
        }

        if(!$expired){
            $contents = file_get_contents($file_path);
            if($compress){
                $contents = gzuncompress($contents);
            }
            if($serialize){
                $contents = unserialize($contents);
            }
            return $contents;
        }

        return null;
    }    

    public static function clear_cache()
    {
        self::rmdir_recursive("./static/cache");
    }
    /* 
    * php delete function that deals with directories recursively
    */
    private static function rmdir_recursive($directory, $delete_parent = null)
    {
        $files = glob($directory . '/{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::rmdir_recursive($file, 1);
            } else {
                unlink($file);
            }
        }
        if ($delete_parent) {
            rmdir($directory);
        }
    }
} 