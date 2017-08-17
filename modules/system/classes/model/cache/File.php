<?php
namespace system\model\cache;

class File extends \Cache
{
    protected $type = "file";

    function get($var)
    {
        $file = $this->getDirectory().$var;
        \AppRoot::debug("getCache: ".$file);
        if (file_exists($file))
            return file_get_contents($file);

        \AppRoot::debug("Cache not found");
        return null;
    }

    function set($var, $val)
    {
        $file = $this->getDirectory().$var;
        \AppRoot::debug("setCache: ".$file);

        if (is_array($val) || is_object($val))
            $val = json_encode($val);

        $dirParts = explode("/", $file);
        $filename = array_pop($dirParts);
        $dirname = "";
        foreach ($dirParts as $part) {
            if (strlen(trim($part)) > 0) {
                $dirname .= $part."/";
                if (!file_exists($dirname))
                    mkdir($dirname,0777);
            }
        }

        $handle = fopen($dirname.$filename,"w");
        if ($handle) {
            fwrite($handle, $val);
            fclose($handle);
        } else {
            \AppRoot::error("Could not write cache file: ".$dirname.$filename);
        }
    }

    function remove($var)
    {
        $file = $this->getDirectory().$var;
        \AppRoot::debug("removeCache: ".$file);
        \Tools::deleteFile($file);
    }


    function getDirectory()
    {
        return "documents/cache/";
    }
}