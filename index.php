<?php
require_once("init.php");
\AppRoot::debug("finished init");

$mainContent = null;
$mainMenu = null;

\AppRoot::title(APP_TITLE);


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
	\AppRoot::redirect("index.php");
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

// Register
if (Tools::POST("doRegister") == "true")
{
	$check = true;

	// Check if passwords match
	if (!Tools::POST("password1") || (Tools::POST("password1") != Tools::POST("password2")))
	{
		$check = false;
		$registerDifferentPasswords = "Passwords do not match.";
	}
	// Anti spam check
	if (\Tools::POST("street")) {
		$check = false;
		$registerIncorrectCaptcha = "Suspected bot. Registration failed.";
	}

	if ($check)
	{
		$user = new \users\model\User();
		$user->username = Tools::POST("username");
		$user->displayname = Tools::POST("username");
		$user->password = \User::generatePassword(Tools::POST("password1"));
		$user->email = Tools::POST("email");
		$user->store();
		$user->setLoginStatus();
		AppRoot::redirect("index.php");
	}
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
	\AppRoot::addJavascriptFile("javascript/", "jquery.js");
	\AppRoot::addJavascriptFile("javascript/", "jquery.ui..custom.min.js");

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
// We zijn ingelogd en mogen door.
else
{
	if (!\Tools::REQUEST("ajax"))
	{
		if (\User::getUSER()->loggedIn())
		{
			\User::getUSER()->addLog("login");
			\Modules::getModules();
		}
	}

	// Auto Complete ajax verzoek
	if (\Tools::GET("ajax") && \Tools::GET("autocomplete"))
	{
		$mainContent = \AutoCompleteElement\Element::getValues();
	}
	// Load selected module
	else if (\Tools::GET("module"))
	{
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
