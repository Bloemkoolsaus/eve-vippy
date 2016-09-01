<?php
use users\model\Oauth;

require_once("init.php");
\AppRoot::debug("finished init");

$mainContent = null;
$mainMenu = null;

\AppRoot::title(\Config::getCONFIG()->get("system_title"));

// KONINGSDAG
if (date("Y-m-d") == date("Y")."-04-27") {
	\SmartyTools::setTemplate("kingsday");
}
if (date("Y-m-d") == date("Y")."-12-05") {
	\SmartyTools::setTemplate("sinterklaas");
}
if (date("Y-m-d") == date("Y")."-12-25" || date("Y-m-d") == date("Y")."-12-24" || date("Y-m-d") == date("Y")."-12-26") {
	\SmartyTools::setTemplate("kerst");
}

// Logout
if (Tools::GET("action") == "logout")
{
	if (\User::getUSER())
		\User::getUSER()->logout();
	\AppRoot::redirect("");
}

// Forgot password
if (Tools::POST("forgotPW") == "true")
{
	if ($resetpw = User::generateNewPassword(\Tools::POST("email")))
	{
		if ($resetpw < 0)
			$forgotPwError = "Could not send password.<br />Ask administrator or your CEO to reset your password for you.";
		else
			$forgotPwMsg = "Password sent to: ".Tools::POST("email");
	}
	else
		$forgotPwError = "Email not fount. Could not send password.";
}

// Login
if (Tools::POST("doLogin") == "true")
{
	if (Tools::POST("username") || Tools::POST("password"))
	{
		if (\User::getUSER()->login(Tools::POST("username"), Tools::POST("password"), false, \Tools::POST("remember")))
			\AppRoot::refresh();
		else
			$loginMsg = "Incorrect username or password";
	}
}

if (Tools::POST("sso") == "true") 
{
	Print " tring SSO login";	
	
	$oath = new users\model\Oauth();
	$oath->requestAuthorization();
	$oath->getCharacterID();
}

if (Tools::GET("state") && Tools::GET("code")) {
	$oath = new users\model\Oauth();
	$oath->getAccessToken(Tools::GET("state"), Tools::GET("code"));	

} 

if (!\User::getLoggedInUserId())
{
	// Login by cookie
	if (\Tools::COOKIE("vippy"))
	{
		if (\User::getUSER()->loginByKey(Tools::COOKIE("vippy")))
			\AppRoot::redirect("index.php");
	}

	// Login by ATLAS
	if (\Tools::REQUEST("loginbyatlas"))
	{
		$atlas = new \atlas\console\Atlas();
		$atlas->loginByAtlas();
	}
}


$javascripts = array();
$stylesheets = array();

// Een ajax verzoek hoeft geen javascript en css in te laden.
if (!\Tools::REQUEST("ajax"))
{
	\AppRoot::addJavascriptFile("javascript/", "jquery.v1.11.3.js");

	// Load Javascripts
	$directories = array("javascript/");
	if (strlen(trim(SmartyTools::getTemplate())) > 0)
		$directories[] = "javascript/".SmartyTools::getTemplate()."/";
	foreach ($directories as $directory) {
		if (file_exists($directory)) {
			if ($handle = @opendir($directory)) {
				while (false !== ($file = readdir($handle))) {
					\AppRoot::addJavascriptFile($directory, $file);
				}
				closedir($handle);
			}
		}
	}
	\AppRoot::debug("Javascript loaded");

	// Load Stylesheets
	$directories = array("css/");
	if (strlen(trim(SmartyTools::getTemplate())) > 0)
		$directories[] = "css/".SmartyTools::getTemplate()."/";
	foreach ($directories as $directory) {
		if (file_exists($directory)) {
			if ($handle = @opendir($directory)) {
				while (false !== ($file = readdir($handle))) {
					\AppRoot::addStylesheetFile($directory, $file);
				}
				closedir($handle);
			}
		}
	}
	\AppRoot::debug("Stylesheets loaded");
}

// Niet ingelogd terwijl we dat wel moeten zijn
if (\AppRoot::loginRequired() && !\User::getLoggedInUserId())
{
	if (\Tools::REQUEST("register"))
	{
		$content = \SmartyTools::getSmarty();
		if (isset($registerDifferentPasswords))
			$content->assign("regmsg", $registerDifferentPasswords);
		if (isset($registerIncorrectCaptcha))
			$content->assign("regmsg", $registerIncorrectCaptcha);
		$mainContent = $content->fetch("register");
	}
	else
	{
		$content = \SmartyTools::getSmarty();
		if (isset($loginMsg))
			$content->assign("loginmsg", $loginMsg);
		if (isset($forgotPwError))
			$content->assign("forgotpwerror", $forgotPwError);
		if (isset($forgotPwMsg))
			$content->assign("forgotpwmsg", $forgotPwMsg);
		$mainContent = $content->fetch("login");
	}
}
else
{
    // We zijn ingelogd en mogen door.
	if (!\Tools::REQUEST("ajax")) {
		if (\User::getUSER()->loggedIn()) {
			\User::getUSER()->addLog("login");
			\Modules::getModules();
		}
	}

	if (\Tools::GET("ajax") && \Tools::GET("autocomplete"))
	{
        // Auto Complete ajax verzoek
		$mainContent = \AutoCompleteElement\Element::getValues();
	}
	else if (\Tools::GET("module"))
	{
        // Load selected module
		$selectedModule = new Modules(Tools::GET("module"));
		$module = $selectedModule->loadModule();

		AppRoot::debug("=== " . $module->moduleName . ": Get Content");
		$mainContent = $module->getContent();
		AppRoot::debug("=== " . $module->moduleName . ": Has Content");
	}

	if ($mainContent == null)
		$mainContent = \Modules::getHomepage();
}

// Render templates
$mainTPL = \SmartyTools::getSmarty();

if (!\Tools::REQUEST("ajax"))
{
	$mainTPL->assign("javascript", \AppRoot::$javascripts);
	$mainTPL->assign("stylesheet", \AppRoot::$stylesheets);
	$mainTPL->assign("pageTitle", \AppRoot::getTitle());
	$mainTPL->assign("mainheader", \Modules::getHeader());
}
\AppRoot::debug("Finishing");

$mainTPL->assign("maincontent", $mainContent);
$mainTPL->assign("debug", \AppRoot::printDebug());
$mainTPL->display((Tools::REQUEST("ajax"))?"ajax":"index");
?>