<?php
require_once("init.php");

\AppRoot::debug("finished init");

/* CREST callback */
if (\Tools::GET("state") && \Tools::GET("code")) {
    \AppRoot::doCliOutput("CREST Callback");
    \AppRoot::redirect("crest/login/login/".\Tools::GET("state")."/".\Tools::GET("code"));
}
\AppRoot::title(\Config::getCONFIG()->get("system_title"));


// Koningsdag
if (date("Y-m-d") == date("Y")."-04-27")
    \SmartyTools::setTemplate("kingsday");
// Sinterklaas
if (date("Y-m-d") == date("Y")."-12-05" || date("Y-m-d") == date("Y")."-12-06")
	\SmartyTools::setTemplate("sinterklaas");
// Kerst
if (date("Y-m-d") == date("Y")."-12-24" || date("Y-m-d") == date("Y")."-12-25" || date("Y-m-d") == date("Y")."-12-26")
	\SmartyTools::setTemplate("kerst");
// Nieuw-jaar
if (date("Y-m-d") == date("Y")."-01-01")
    \SmartyTools::setTemplate("newyear");
// Anniversary
if (date("Y-m") == "2017-05")
    \SmartyTools::setTemplate("anniversary");

// Logout
if (\Tools::GET("action") == "logout") {
	if (\User::getUSER())
		\User::getUSER()->logout();
	\AppRoot::redirect("/");
}

// Een ajax verzoek hoeft geen javascript en css in te laden.
if (!\Tools::REQUEST("ajax") || (\Tools::REQUEST("ajax") && \Tools::REQUEST("debug"))) {
    // Load Javascripts
    \AppRoot::addJavascriptDirectory("javascript/common");
    \AppRoot::addJavascriptDirectory("javascript", false);
    \AppRoot::addJavascriptDirectory("javascript/" . \SmartyTools::getTemplate());
    \AppRoot::debug("Javascripts loaded");
}
if (!\Tools::REQUEST("ajax")) {
    // Load Stylesheets
    \AppRoot::addStylesheetDirectory("css/common");
    \AppRoot::addStylesheetDirectory("css", false);
    \AppRoot::addStylesheetDirectory("css/".\SmartyTools::getTemplate());
    \AppRoot::debug("Stylesheets loaded");
}

$mainContent = null;

// Niet ingelogd terwijl we dat wel moeten zijn
if (!\User::getUSER() && \AppRoot::loginRequired()) {
	if (\Tools::REQUEST("register")) {
		$content = \SmartyTools::getSmarty();
		if (isset($registerDifferentPasswords))
			$content->assign("regmsg", $registerDifferentPasswords);
		if (isset($registerIncorrectCaptcha))
			$content->assign("regmsg", $registerIncorrectCaptcha);
		$mainContent = $content->fetch("register");
	} else {
        $view = new \users\view\Login();
        $mainContent = $view->getLogin();
	}
} else {
    // We zijn ingelogd en mogen door.
	if (!\Tools::REQUEST("ajax")) {
		if (\User::getUSER()) {
			\User::getUSER()->addLog("login");
			\Modules::getModules();
		}
	}
	if (\Tools::GET("ajax") && \Tools::GET("autocomplete")) {
        // Auto Complete ajax verzoek
		$mainContent = \AutoCompleteElement\Element::getValues();
	} else if (\Tools::GET("module")) {
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
$mainTPL->assign("javascript", \AppRoot::$javascripts);
$mainTPL->assign("stylesheet", \AppRoot::$stylesheets);
if (!\Tools::REQUEST("ajax")) {
	$mainTPL->assign("pageTitle", \AppRoot::getTitle());
	$mainTPL->assign("mainheader", \Modules::getHeader());
}
\AppRoot::debug("Finishing");
$mainTPL->assign("maincontent", $mainContent);
$mainTPL->assign("debug", \AppRoot::printDebug());
$mainTPL->display((Tools::REQUEST("ajax"))?"ajax":"index");