<?php
require_once("init.php");


/* SSO callback */
if (\Tools::GET("state") && \Tools::GET("code")) {
    \AppRoot::doCliOutput("CREST Callback");
    $view = new \sso\view\Login();
    $view->getLogin();
}
\AppRoot::title(\Config::getCONFIG()->get("system_title"));


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
if (!\Tools::REQUEST("ajax")) {
    $mainTPL->assign("javascript", \AppRoot::$javascripts);
    $mainTPL->assign("stylesheet", \AppRoot::$stylesheets);
	$mainTPL->assign("pageTitle", \AppRoot::getTitle());
	$mainTPL->assign("mainheader", \Modules::getHeader());
}
\AppRoot::debug("Printing main-content");
$mainTPL->assign("maincontent", $mainContent);
\AppRoot::debug("Finishing");
$mainTPL->assign("debug", \AppRoot::printDebug());
$mainTPL->display((\Tools::REQUEST("ajax"))?"ajax":"index");

if (\AppRoot::doDebug()) {
    \AppRoot::printDebug();
}