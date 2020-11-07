<?php

class Renderer
{
    public static $templates;
    public static function getTemplates()
    {
        if(!isset(self::$templates))
        {
            $key = "templates";
            $loadtemplates = function() {
                $files = array_slice(scandir("../templates"), 2);
                foreach($files as $file)
                {
                    $filename = $file;
                    $templates[$filename] = file_get_contents("../templates/$file");
                }
    
                return $templates;
            };
    
            self::$templates = MemoryCache::getCache($key, $loadtemplates); //on server
            
            
        }
        
        return self::$templates;
    }
    public static function render(DataObject $obj, $cleanup = false)
    {
        //$templates = (self::getTemplates())["data"];

        $template_file = $obj->template_file;

        $html = "";

        if($template_file != null)
        {

            ////******** */

            // if(isset($templates[$template_file]))
            // {
            //     $html = $templates[$template_file];
            // }
            // else
            // {
            //     $html = $template_file;
            // }

            ////******** */

            $file = "../templates/$template_file";
            if(file_exists ($file))
            {
                $html = file_get_contents("../templates/$template_file");
            }
            else
            {
                $html = $template_file;
            }
            
        }

        if(isset($obj->data) && (is_array($obj->data) || $obj->data instanceof \Traversable))
        {
            foreach($obj->data as $k => $v)
            {
                if($template_file == null && preg_match("/\{$k\}/", $html) == 0) //ensure a placeholder exists
                {
                    $html .= "{{$k}}";
                }

                if(is_a($v, "DataObject")) //DataObject
                {
                    $sub_html = Renderer::render($v);
                    
                    $html = preg_replace("/\{$k\}/", $sub_html, $html);

                }
                else if(is_array($v)) //array of DataObject
                {
                    $sub_html = "";

                    $last = count($v) - 1;

                    foreach($v as $i => $item)
                    {
                        if(is_a($item, "DataObject"))
                        {
                            $sub_html .= Renderer::render($item);
                        }
                        else 
                        {
                            $sub_html .= $item . $i < $last ? ", " : "";
                        }
                    }
        
                    $html = preg_replace("/\{$k\}/", $sub_html, $html);
                }
                else //primitive
                {
                    if(is_int($v))
                    {
                        //$v = number_format($v);
                    }
                    else if(is_float($v))
                    {
                        $v = number_format($v, 1);
                    }

                    $html = preg_replace("/\{$k\}/", $v, $html);
                }
                
            }
        }

        if($cleanup)
        {
            //hide un-replaced brackets
            //$html = preg_replace("/\{[^\}\"\,\[\]\s\;\\\]+\}/", "", $html);
            //$html = preg_replace("/\{[^\}\"\,\:\[\]\s\;\\\]+\}/", "", $html);
            $html = preg_replace("/\{[a-zA-Z0-9\-\_]+\}/", "", $html);
        }


        //hide commented html
        $html = preg_replace("/(?=<!--)([\s\S]*?-->)/", "", $html);

        return $html;
    }
}
?>