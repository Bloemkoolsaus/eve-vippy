
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title>VIPPY - INSTALL Notes</title>
	<link rel='shortcut icon' href='../images/fav.ico'>
	<link rel="shortcut icon" type="../image/x-icon" href="images/fav.ico">
	<script type="text/javascript" language="javascript" src="../javascript/default/jquery.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/jquery.ui..custom.min.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/jquery.dorpmenu.1.1.4.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/form.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/jquery.ui.autocompleteExt.accentFolding.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/jquery.tristatecheckbox.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/jquery.ui.autocompleteExt.html.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/doc.ready.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/ajax.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/util.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/jquery.ui.autocompleteExt.selectFirst.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/jquery.ui.autocompleteExt.autoSelect.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/kineticjs.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/jquery.ui.autocomplete.js?1348497579"></script>
	<script type="text/javascript" language="javascript" src="../javascript/default/popup.js?1348497579"></script>
	<link rel="stylesheet" type="text/css" href="../css/default/modulemenu.css?1348497579" />
	<link rel="stylesheet" type="text/css" href="../css/default/jquery.ui.css?1348497579" />
	<link rel="stylesheet" type="text/css" href="../css/default/popup.css?1348497579" />
	<link rel="stylesheet" type="text/css" href="../css/default/debug.css?1348497579" />
	<link rel="stylesheet" type="text/css" href="../css/default/form.css?1348497579" />
	<link rel="stylesheet" type="text/css" href="../css/default/menu.submenu.css?1348497579" />
	<link rel="stylesheet" type="text/css" href="../css/default/main.css?1348497579" />
	<link rel="stylesheet" type="text/css" href="../css/default/buttons.css?1348497579" />
	<link rel="stylesheet" type="text/css" href="../css/default/menu.mainmenu.css?1348497579" />
	<link rel="stylesheet" type="text/css" href="../css/default/jquery.tristatecheckbox.css?1348497579" />
</head>
<body>

<div id="maincontainer">
	<div id="maincontent" style="padding: 10px;">
	<h1>Apache 2.4+</h1>
	<p>Starting with Apache 2.4, Apache has become more strict about HTTP headers. Any headers with 
	illigal characters including underscores are dropped silently. As a result vippy will not be 
	able to collect the ingame information such as charactername or systemname. To fix this you can
	add the following lines to you apache configuration file:
	<pre style="background-color:#222;padding:5px 25px;">
SetEnvIfNoCase ^EVE_TRUSTED$ ^(.*)$ fix_eve_trusted=$1
RequestHeader set EVE-TRUSTED %{fix_eve_trusted}e env=fix_eve_trusted

SetEnvIfNoCase ^EVE_ALLIANCEID$ ^(.*)$ fix_eve_allianceid=$1
RequestHeader set EVE-ALLIANCEID %{fix_eve_allianceid}e env=fix_eve_allianceid

SetEnvIfNoCase ^EVE_ALLIANCENAME$ ^(.*)$ fix_eve_alliancename=$1
RequestHeader set EVE-ALLIANCENAME %{fix_eve_alliancename}e env=fix_eve_alliancename

SetEnvIfNoCase ^EVE_CHARID$ ^(.*)$ fix_eve_charid=$1
RequestHeader set EVE-CHARID %{fix_eve_charid}e env=fix_eve_charid

SetEnvIfNoCase ^EVE_CHARNAME$ ^(.*)$ fix_eve_charname=$1
RequestHeader set EVE-CHARNAME %{fix_eve_charname}e env=fix_eve_charname

SetEnvIfNoCase ^EVE_CONSETLLATIONID$ ^(.*)$ fix_eve_constellationid=$1
RequestHeader set EVE-CONSETLLATIONID %{fix_eve_constellationid}e env=fix_eve_constellationid

SetEnvIfNoCase ^EVE_CONSTELLATIONNAME$ ^(.*)$ fix_eve_constellationname=$1
RequestHeader set EVE-CONSTELLATIONNAME %{fix_eve_constellationname}e env=fix_eve_constellationname

SetEnvIfNoCase ^EVE_CORPID$ ^(.*)$ fix_eve_corpid=$1
RequestHeader set EVE-CORPID %{fix_eve_corpid}e env=fix_eve_corpid

SetEnvIfNoCase ^EVE_CORPNAME$ ^(.*)$ fix_eve_corpname=$1
RequestHeader set EVE-CORPNAME %{fix_eve_corpname}e env=fix_eve_corpname

SetEnvIfNoCase ^EVE_CORPROLE$ ^(.*)$ fix_eve_corprole=$1
RequestHeader set EVE-CORPROLE %{fix_eve_corprole}e env=fix_eve_corprole

SetEnvIfNoCase ^EVE_REGIONID$ ^(.*)$ fix_eve_regionid=$1
RequestHeader set EVE-REGIONID %{fix_eve_regionid}e env=fix_eve_regionid

SetEnvIfNoCase ^EVE_REGIONNAME$ ^(.*)$ fix_eve_regionname=$1
RequestHeader set EVE-REGIONNAME %{fix_eve_regionname}e env=fix_eve_regionname

SetEnvIfNoCase ^EVE_SERVERIP$ ^(.*)$ fix_eve_serverip=$1
RequestHeader set EVE-SERVERIP %{fix_eve_serverip}e env=fix_eve_serverip

SetEnvIfNoCase ^EVE_SHIPID$ ^(.*)$ fix_eve_shipid=$1
RequestHeader set EVE-SHIPID %{fix_eve_shipid}e env=fix_eve_shipid

SetEnvIfNoCase ^EVE_SHIPNAME$ ^(.*)$ fix_eve_shipname=$1
RequestHeader set EVE-SHIPNAME %{fix_eve_shipname}e env=fix_eve_shipname

SetEnvIfNoCase ^EVE_SHIPTYPEID$ ^(.*)$ fix_eve_shiptypeid=$1
RequestHeader set EVE-SHIPTYPEID %{fix_eve_shiptypeid}e env=fix_eve_shiptypeid

SetEnvIfNoCase ^EVE_SHIPTYPENAME$ ^(.*)$ fix_eve_shiptypename=$1
RequestHeader set EVE-SHIPTYPENAME %{fix_eve_shiptypename}e env=fix_eve_shiptypename

SetEnvIfNoCase ^EVE_SOLARSYSTEMID$ ^(.*)$ fix_eve_solarsystemid=$1
RequestHeader set EVE-SOLARSYSTEMID %{fix_eve_solarsystemid}e env=fix_eve_solarsystemid

SetEnvIfNoCase ^EVE_SOLARSYSTEMNAME$ ^(.*)$ fix_eve_solarsystemname=$1
RequestHeader set EVE-SOLARSYSTEMNAME %{fix_eve_solarsystemname}e env=fix_eve_solarsystemname</pre>

	</div>
</div>

</body>
</html>