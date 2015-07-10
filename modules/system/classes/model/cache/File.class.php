<?php
namespace system\model\cache
{
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
            fwrite($handle, $val);
            fclose($handle);
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
}