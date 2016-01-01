<?php
Class Tools
{
	public static function GET($var, $raw=false)
	{
		if (!isset($_GET[$var]))
			return false;

		$value = $_GET[$var];

		if (!$raw)
			$value = self::Escape($value);

		return $value;
	}

	public static function POST($var, $raw=false)
	{
		if (!isset($_POST[$var]))
			return false;

		$value = $_POST[$var];

		if (!$raw && !is_array($value))
			$value = self::Escape($value);

        if (is_array($value) && count($value) == 0)
            return false;

		return $value;
	}

	public static function SERVER($var, $raw=false)
	{
		if (!isset($_SERVER[$var]))
			return false;

		$value = $_SERVER[$var];

		if (!$raw)
			$value = self::Escape($value);

		return $value;
	}

	public static function REQUEST($var, $postprio=true)
	{
		if ($postprio) {
			$post = self::POST($var);
			if ($post)
				return $post;
			$get = self::GET($var);
			if ($get)
				return $get;
		} else {
			$get = self::GET($var);
			if ($get)
				return $get;
			$post = self::POST($var);
			if ($post)
				return $post;
		}

		return false;
	}

	public static function COOKIE($var)
	{
		if (isset($_COOKIE[$var]))
			return $_COOKIE[$var];
		else
			return false;
	}

	public static function setCOOKIE($var,$val,$lifetime=null)
	{
		AppRoot::debug("set COOKIE(".$var.",".$val.")");
		if ($lifetime==null || !is_numeric($lifetime))
			$lifetime = time()+(60*60*24*60);
		setcookie($var,$val,$lifetime,"/");
	}

	public static function unsetCOOKIE($var)
	{
		AppRoot::debug("Unset COOKIE(".$var.")");
		setcookie($var,"",time()-3600,"/");
		unset($_COOKIE[$var]);
	}

	public static function Escape($value)
	{
		$value = trim($value);
		$value = htmlentities($value, ENT_COMPAT, "iso-8859-1");
		return $value;
	}

	public static function isEmpty($value) {

		if (strlen(trim($value)) > 0)
			return false;
		else
			return true;
	}

	public static function printArray($value) {

		if (count($value) > 0) {
			$debug = "PrintArray: ". count($value) . " values<br />";
			$debug .= Tools::showArray($value);
			AppRoot::debug($debug);
		}
		else
			AppRoot::debug("PrintArray: Empty");
	}

	private static function showArray($var, $step=1) {

		$ret = "";
		foreach ($var as $key => $val) {
			for ($i=0; $i<$step; $i++)
				$ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$ret .= "[" . $key . "] => " . $val . "<br />";
			if (is_array($val))
				$ret .= Tools::showArray($val, $step+1);
		}
		return $ret;
	}

	public static function getWrittenMonth($month=false)
	{
		if (!$month)
			$monty = date("m");

		switch ($month)
		{
			case 1:
				return "Januari";
				break;
			case 2:
				return "Februari";
				break;
			case 3:
				return "March";
				break;
			case 4:
				return "April";
				break;
			case 5:
				return "May";
				break;
			case 6:
				return "June";
				break;
			case 7:
				return "July";
				break;
			case 8:
				return "August";
				break;
			case 9:
				return "September";
				break;
			case 10:
				return "October";
				break;
			case 11:
				return "November";
				break;
			case 12:
				return "December";
				break;
			default:
				return "Unknown";
				break;
		}
	}

	public static function getAge($sdate=false, $edate=false, $allownegative=false, $short=false)
	{
		if (!$sdate)
			$sdate = date("Y-m-d H:i:s");
		if (!$edate)
			$edate = date("Y-m-d H:i:s");

		$age = strtotime($edate)-strtotime($sdate);

		if (!$allownegative && $age < 0)
			$age = 0;

		$nrDays = 0;
		$nrHours = 0;
		$nrMinutes = 0;
		$nrSeconds = 0;

		if ($age > 86400)
		{
			$nrDays = floor($age/86400);
			$age = $age-($nrDays*86400);
		}

		if ($age > 3600)
		{
			$nrHours = floor($age/3600);
			$age = $age-($nrHours*3600);
		}

		if ($age > 60)
			$nrMinutes = floor($age/60);
		else
			$nrSeconds = $age;

		$ageStr = "";
		if ($nrDays > 0)
		{
			$ageStr .= $nrDays." Day".(($nrDays>1)?"s":"");

			if ($nrHours > 0)
				$ageStr .= ((strlen(trim($ageStr))>0)?", ":"").$nrHours." Hour".(($nrHours>1)?"s":"");
		}
		else
		{
			if ($nrHours > 0)
			{
				if ($short)
					$ageStr .= ((strlen(trim($ageStr))>0)?", ":"").$nrHours." Hr".(($nrHours>1)?"s":"");
				else
					$ageStr .= ((strlen(trim($ageStr))>0)?", ":"").$nrHours." Hour".(($nrHours>1)?"s":"");
			}

			if ($nrMinutes > 0)
			{
				if ($short)
					$ageStr .= ((strlen(trim($ageStr))>0)?", ":"").$nrMinutes." Min";
				else
					$ageStr .= ((strlen(trim($ageStr))>0)?", ":"").$nrMinutes." Minute".(($nrMinutes>1)?"s":"");
			}

			if ($nrHours == 0 && $nrMinutes == 0)
			{
				if ($short)
					$ageStr .= "< 1 min";
				else
					$ageStr .= "Less then 1 minute";
			}
		}

		return trim($ageStr);
	}

	public static function getFullDate($date=false, $inclWeekDay=false, $fullWeekDay=false)
	{
		if (!$date)
			$date = strtotime("now");
		else
			$date = strtotime($date);

		$fullDate = "";

		if ($inclWeekDay)
				$fullDate .= self::getDayOfTheWeek(date("w",$date), !$fullWeekDay) . ", ";

		$fullDate .= self::getFullMonth(date("m",$date)) . " ";
		$fullDate .= date("d", $date) . ", ";
		$fullDate .= date("Y", $date) . " ";

		return trim($fullDate);
	}

	public static function showValuta($amount)
	{
		if (is_numeric($amount))
			return number_format($amount, 2, ',', '.');

		return false;
	}

	public static function varToDBValuta($amount)
	{
		return (int)(str_replace(",",".",$amount)*100);
	}


	public static function  toInt($str)
	{
		return (int)preg_replace("/\..+$/i", "", preg_replace("/[^0-9\.]/i", "", $str));
	}


	public static function leadingZeros($number, $nrDigits)
	{
		return sprintf("%0".$nrDigits."d",$number);
	}

	public static function getVariableContentString($var)
	{
		if (is_array($var) || is_object($var))
			return "<pre style='margin: 0px; padding: 0px;'>".print_r($var,true)."</pre>";

		$validReturnTypes = array("boolean","integer","double","string");
		$varType = gettype($var);
		if (in_array(strtolower($varType), $validReturnTypes))
			return $var;
		else
			return $varType;
	}

	public static function addMinutes($nrMinutes, $curDate=false)
	{
		if (!$curDate)
			strtotime("now");
		else
			strtotime($curDate);

		$hours = date("H", $curDate);
		$minutes = date("i", $curDate)+$nrMinutes;
		$seconds = date("s", $curDate);
		$days = date("d", $curDate);
		$month = date("m", $curDate);
		$year = date("Y", $curDate);

		return self::calcDate($seconds, $minutes, $hours, $days, $month, $year);
	}

	public static function addHours($nrHours, $curDate=false)
	{
		if (!$curDate)
			strtotime("now");
		else
			strtotime($curDate);

		$hours = date("H", $curDate)+$nrHours;
		$minutes = date("i", $curDate);
		$seconds = date("s", $curDate);
		$days = date("d", $curDate);
		$month = date("m", $curDate);
		$year = date("Y", $curDate);

		return self::calcDate($seconds, $minutes, $hours, $days, $month, $year);
	}

	public static function addDays($nrDays, $curDate=false)
	{
		if (!$curDate)
			$curDate = strtotime("now");
		else
			$curDate = strtotime($curDate);

		$hours = date("H", $curDate);
		$minutes = date("i", $curDate);
		$seconds = date("s", $curDate);
		$days = date("d", $curDate) + $nrDays;
		$month = date("m", $curDate);
		$year = date("Y", $curDate);

		return self::calcDate($seconds, $minutes, $hours, $days, $month, $year);
	}

	public static function addMonths($nrMonths, $curDate=false)
	{
		if (!$curDate)
			$curDate = strtotime("now");
		else
			$curDate = strtotime($curDate);

		$hours = date("H", $curDate);
		$minutes = date("i", $curDate);
		$seconds = date("s", $curDate);
		$days = date("d", $curDate);
		$month = date("m", $curDate)+$nrMonths;
		$year = date("Y", $curDate);

		return self::calcDate($seconds, $minutes, $hours, $days, $month, $year);
	}

	public static function addYears($nrYears, $curDate=false)
	{
		if (!$curDate)
			$curDate = strtotime("now");
		else
			$curDate = strtotime($curDate);

		$hours = date("H", $curDate);
		$minutes = date("i", $curDate);
		$seconds = date("s", $curDate);
		$days = date("d", $curDate);
		$month = date("m", $curDate);
		$year = date("Y", $curDate)+$nrYears;

		return self::calcDate($seconds, $minutes, $hours, $days, $month, $year);
	}

	private static function calcDate($seconds, $minutes, $hours, $days, $months, $years)
	{
		return mktime($hours,$minutes,$seconds,$months,$days,$years);
	}

	public static function getDayOfTheWeek($day=false, $short=false)
	{
		if (!$day)
			$day = date("w");

		switch ($day) {
			case 0:
				if ($short)
					return "Sun";
				else
					return "Sunday";
				break;
			case 1:
				if ($short)
					return "Mo";
				else
					return "Monday";
				break;
			case 2:
				if ($short)
					return "Tue";
				else
					return "Tuesday";
				break;
			case 3:
				if ($short)
					return "Wed";
				else
					return "Wednesday";
				break;
			case 4:
				if ($short)
					return "Thu";
				else
					return "Thursday";
				break;
			case 5:
				if ($short)
					return "Fri";
				else
					return "Friday";
				break;
			case 6:
				if ($short)
					return "Sat";
				else
					return "Saturday";
				break;
		}
	}

	public static function getFullMonth($month=false)
	{
		if (!$month)
			$month = date("m");

		switch ($month) {
			case 1:
				return "January";
				break;
			case 2:
				return "February";
				break;
			case 3:
				return "March";
				break;
			case 4:
				return "April";
				break;
			case 5:
				return "May";
				break;
			case 6:
				return "June";
				break;
			case 7:
				return "July";
				break;
			case 8:
				return "August";
				break;
			case 9:
				return "September";
				break;
			case 10:
				return "October";
				break;
			case 11:
				return "November";
				break;
			case 12:
				return "December";
				break;
			default:
				return "";
				break;
		}
	}

	public static function getCurrentURL($exceptParams=array())
	{
		$urlVars = explode("?", $_SERVER["REQUEST_URI"]);
		$url = $urlVars[0];

		if (isset($urlVars[1])) {
			$urlVars = explode("&", $urlVars[1]);
			foreach ($urlVars as $key => $var) {
				$param = explode("=", $var);
				if (!in_array($param[0], $exceptParams)) {
					$url .= ($key == 0) ? "?" : "&";
					$url .= $var;
				}
			}
		}
		return $url;
	}

	public static function noRightMessage()
	{
		$tpl = \SmartyTools::getSmarty();
		return $tpl->fetch("norights");
	}

	public static function IPToNumber($ip)
	{
		return sprintf("%u", ip2long($ip));
	}

	public static function NumberToIP($nr)
	{
		return long2ip($nr);
	}

	public static function deleteFile($file)
	{
		\AppRoot::debug("DELETING FILE: ".$file);
        shell_exec("rm -rf ".$file);
        if (file_exists($file))
            unlink($file);

        // Confirm file is deleted
        if (file_exists($file))
            \AppRoot::error("File ".$file." not deleted!!");
	}

	public static function deleteDir($dir)
	{
		\AppRoot::debug("DELETING DIR: ".$dir);
        \AppRoot::doCliCommand("rm -rf ".$dir);
		if (file_exists($dir))
		{
            \AppRoot::debug("<span style='color:red;'>".$dir." still exists</span>");
			self::emptyDir($dir);
			rmdir($dir);
		}

        // Confirm folder is deleted
        if (file_exists($dir))
            \AppRoot::error("Directory ".$dir." not deleted!!");
	}

	public static function emptyDir($dir)
	{
		\AppRoot::debug("EMPTY DIR: ".$dir);
		if ($handle = @opendir($dir))
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file == "." || $file == "..")
					continue;

				$filename = str_replace("//","/",$dir."/".$file);
				if (is_dir($filename))
					self::deleteDir($filename);
				else
					self::deleteFile($filename);
			}
			closedir($handle);
		}
	}

	public static function getBrowser()
	{
	    $u_agent = $_SERVER['HTTP_USER_AGENT'];
	    $bname = 'Unknown';
		$ub = "";
	    $platform = 'Unknown';
	    $version = "";

	    //First get the platform?
	    if (preg_match('/linux/i', $u_agent))
	        $platform = 'linux';
	    elseif (preg_match('/macintosh|mac os x/i', $u_agent))
	        $platform = 'mac';
	    elseif (preg_match('/windows|win32/i', $u_agent))
	        $platform = 'windows';

	    // Next get the name of the useragent yes seperately and for good reason
	    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
	    {
	        $bname = 'Internet Explorer';
	        $ub = "MSIE";
	    }
	    elseif(preg_match('/Firefox/i',$u_agent))
	    {
	        $bname = 'Mozilla Firefox';
	        $ub = "Firefox";
	    }
	    elseif(preg_match('/Chrome/i',$u_agent))
	    {
	        $bname = 'Google Chrome';
	        $ub = "Chrome";
	    }
	    elseif(preg_match('/Safari/i',$u_agent))
	    {
	        $bname = 'Apple Safari';
	        $ub = "Safari";
	    }
	    elseif(preg_match('/Opera/i',$u_agent))
	    {
	        $bname = 'Opera';
	        $ub = "Opera";
	    }
	    elseif(preg_match('/Netscape/i',$u_agent))
	    {
	        $bname = 'Netscape';
	        $ub = "Netscape";
	    }

	    // finally get the correct version number
	    $known = array('Version', $ub, 'other');
	    $pattern = '#(?<browser>' . join('|', $known) .
	    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
	    if (!preg_match_all($pattern, $u_agent, $matches)) {
	        // we have no matching number just continue
	    }

	    // see how many we have
	    $i = count($matches['browser']);
	    if ($i != 1) {
	        //we will have two since we are not using 'other' argument yet
	        //see if version is before or after the name
	        if (strripos($u_agent,"Version") < strripos($u_agent,$ub))
	            $version= $matches['version'][0];
	        else
	            $version= $matches['version'][1];
	    }
	    else
	        $version= $matches['version'][0];

	    // check if we have a number
	    if ($version==null || $version=="")
	    	$version="?";

	    return array(	'userAgent' => $u_agent,
        				'name'      => $bname,
        				'version'   => $version,
        				'platform'  => $platform,
        				'pattern'   => $pattern);
	}

	public static function isValidEmail($email)
	{
		$expression = "^[\w-]+(\.[\w-]+)*@([a-z0-9-]+(\.[a-z0-9-]+)*?\.[a-z]{2,6}|(\d{1,3}\.){3}\d{1,3})(:\d{4})?$";
		if (preg_match($expression, $email))
			return true;
		else
			return false;
	}

	public static function getCurrentSystem()
	{
		if (\eve\model\IGB::getIGB()->isIGB())
			return \eve\model\IGB::getIGB()->getSolarsystemName();
		else if (isset($_SESSION["currentsystem"]))
			return $_SESSION["currentsystem"];
		else
			return false;
	}

	public static function setCurrentSystem($system)
	{
		$_SESSION["currentsystem"] = $system;
	}

	public static function generateRandomString($length=10)
	{
		$string = "";
		$characters = "a2bc3de4fg5hi6jk7mn8pq9rs2tu3vw4xyz";
		while (strlen(trim($string)) < $length)
		{
			$char = substr($characters, rand(0, strlen($characters)-1), 1);
			if (rand(0,200)%2 == 0)
				$char = strtoupper($char);
			$string .= $char;
		}
		return $string;
	}


	private static $IPLocations = array();
	public static function getLocationByIP($ip)
	{
		if (!isset(self::$IPLocations[$ip]))
		{
			if ($result = \MySQL::getDB()->getRow("SELECT * FROM user_locations WHERE ipaddress = ?", array($ip)))
				$info = $result["info"];
			else
			{
				$info = file_get_contents("http://api.hostip.info/get_json.php?ip=".$ip);
				\MySQL::getDB()->insert("user_locations", array("ipaddress" => $ip, "info" => $info, "updatedate" => date("Y-m-d H:i:s")));
			}

			self::$IPLocations[$ip] = json_decode($info, true);
		}

		return self::$IPLocations[$ip];
	}

	public static function formatFilename($fileName,$isFile=true)
	{
		$directories = explode("/",$fileName);
		$fileName = array_pop($directories);

		if ($isFile)
		{
			// Format filename
			$nameParts = explode(".",$fileName);
			$extension = ($isFile) ? $nameParts[count($nameParts)-1] : "";
			$fileName = str_replace(".".$extension,"",$fileName);
			$fileName = preg_replace("/[^A-Za-z0-9]/i", "-", $fileName);
			$fileName = $fileName.".".strtolower($extension);
		}

		// Format directory names
		$dirName = "";
		foreach ($directories as $dir)
		{
			$dir = preg_replace("/[^A-Za-z0-9]/i", "-", $dir);
			if (strlen(trim($dir)) > 0) {
				$dirName .= $dir."/";
			}
		}

		return strtolower($dirName.$fileName);
	}

	public static function checkDirectory($directory)
	{
		$path = "";
		foreach (explode("/",$directory) as $dir)
		{
			$dir = self::formatFilename($dir,false);
			if (strlen(trim($dir)) > 0)
			{
				$path .= $dir."/";
				if (!file_exists($path))
					mkdir($path,0777);
			}
		}
		return $path;
	}


	/**
	 * Get shortest path using Dijkstra's algorithm
	 * @param string $start
	 * @param string $end
	 * @param array $routes			$_distArr[from][to] = length/weight;
									$_distArr[from][to] = length/weight;
	 * @return array
	 */
	public static function getDijkstraRoute($start, $end, $routes)
	{
        if (count($routes) == 0)
            return array();

		// The start and the end
		$a = $start;
		$b = $end;

		// Initialize the array for storing
		$S = array(); // The nearest path with its parent and weight
		$Q = array(); // The left nodes without the nearest path
		foreach(array_keys($routes) as $val) {
			$Q[$val] = 99999;
		}
		$Q[$a] = 0;

		// Start calculating
		while (!empty($Q))
		{
		    $min = array_search(min($Q), $Q); // The most min weight
		    if ($min == $b)
		    	break;

		    foreach($routes[$min] as $key=>$val)
		    {
		    	if(!empty($Q[$key]) && $Q[$min] + $val < $Q[$key])
		    	{
		        	$Q[$key] = $Q[$min] + $val;
		        	$S[$key] = array($min, $Q[$key]);
		    	}
		    }

		    unset($Q[$min]);
		}

		// List the path
		$path = array();
		$pos = $b;

        $i = 0;
		while ($pos != $a && $i < 500)
		{
            $i++;
            if (!isset($S[$pos][0]))
                continue;

		    $path[] = $pos;
		    $pos = $S[$pos][0];
		}
		$path[] = $a;

		return array_reverse($path);
	}
}
?>