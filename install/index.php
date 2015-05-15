<?php
ini_set("display_errors", 0);
error_reporting(E_ALL);
/*
 * Vippy uses namespaces which were introduced with php 5.3.0. Make sure we are running > 5.3.0
 * otherwise nothing will show. (blanc page, http code 500: internal server error.)
 */
 if (defined('PHP_MAJOR_VERSION') && (PHP_MAJOR_VERSION < 5)) {
    // if php 4 is still used.
   echo "Vippy requires php 5.3.0 or above. ";
   exit();
 } else if (defined('PHP_MINOR_VERSION') && (PHP_MINOR_VERSION < 3)) {
   echo "Vippy requires php 5.3.0 or above. ";
   exit();
}

require_once("../classes/AppRoot.class.php");
require_once("../classes/Tools.class.php");
require_once("../classes/MySQL.class.php");
require_once("../classes/User.class.php");
require_once("../classes/UserGroup.class.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title>VIPPY - INSTALL</title>
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
		<?php
		$installed = false;
		$errors = array();
		$db = null;
		if (\Tools::POST("install"))
		{
			// Test DB connection
			$db = new MySQL(array(	"host"	=> \Tools::POST("dbhost"),
									"user"	=> \Tools::POST("dbusername"),
									"pass"	=> \Tools::POST("dbpassword"),
									"dtbs"	=> \Tools::POST("dbschema")));
			if ($db->connected())
			{
				$_SESSION["mysql"] = $db;

				if (!\Tools::POST("username"))
					$errors[] = "No username given";
				if (!\Tools::POST("password1"))
					$errors[] = "No user-password given";
				if (\Tools::POST("password1") != \Tools::POST("password2"))
					$errors[] = "Paswords do not match";

				if (count($errors) == 0)
				{
					$dbFile = fopen("../config/db.php","w");
					fwrite($dbFile,'<?php'."\n");
					fwrite($dbFile,'// DATABASE CONFIGURATION'."\n");
					fwrite($dbFile,'define("MYSQL_HOST", "'.\Tools::POST("dbhost").'");'."\n");
					fwrite($dbFile,'define("MYSQL_USER", "'.\Tools::POST("dbusername").'");'."\n");
					fwrite($dbFile,'define("MYSQL_PASS", "'.\Tools::POST("dbpassword").'");'."\n");
					fwrite($dbFile,'define("MYSQL_DTBS", "'.\Tools::POST("dbschema").'");'."\n");
					fwrite($dbFile,'?>'."\n");
					fclose($dbFile);

					$group = new UserGroup();
					$group->name = "Admin";
					$group->addRight("admin","sysadmin");
					$group->store();

					$user = new User();
					$user->id = 999999;
					$user->username = "381rkCZtr73L8j";
					$user->displayname = "Vippy";
					$user->password = User::generatePassword("CuRA1KLz57A9zdc");
					$user->email = "";
					$user->addUserGroup($group->id);
					$user->store();

					$user = new User();
					$user->username = Tools::POST("username");
					$user->displayname = Tools::POST("username");
					$user->password = User::generatePassword(Tools::POST("password1"));
					$user->email = Tools::POST("email");
					$user->store();

					$installed = true;
					?>
					<div class="message">
						<div><b>Installation succesfull</b></div>
						<div>
							<p>If this is a live enviroment, it is recommended that you delete the install directory from vippy.</p>
							<p>Don't forget to add a chain in the admin section after login.</p>
						</div>
					</div>
					<?php
				}
			}
			else
				$errors[] = "Database connection failed";
		}

		if (!$installed)
		{
			?>
			<form method="post" action="">
			<input type="hidden" name="install" value="1" />
			<div>
				<h1>VIPPY INSTALLATION</h1>
			</div>
			<?php
			if (count($errors) > 0)
			{
				?>
				<div class="error">
					<?php
					foreach ($errors as $error)
					{
						?>
						<div><?php echo $error; ?></div>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>
			<br />
			<div>
				<h2>Step 1: Database</h2>
				<div>
					Import the dump.sql file to your database.
				</div>
				<div>
					Fill in database details to create VIPPY's configuration file.
				</div>
				<div class="form">
					<label class="field">Database host:</label>
					<input type="text" name="dbhost" value="<?php echo \Tools::POST("dbhost"); ?>" />
				</div>
				<div class="form">
					<label class="field">Username:</label>
					<input type="text" name="dbusername" value="<?php echo \Tools::POST("dbusername"); ?>" />
				</div>
				<div class="form">
					<label class="field">Password:</label>
					<input type="password" name="dbpassword" value="<?php echo \Tools::POST("dbpassword"); ?>" />
				</div>
				<div class="form">
					<label class="field">Schema name:</label>
					<input type="text" name="dbschema" value="<?php echo \Tools::POST("dbschema"); ?>" />
				</div>
				<div style="clear:both;"></div>
			</div>
			<br />
			<div>
				<h2>Step 2: Configure Apache 2.4+</h2>
				<p>
					If you are installing VIPPY on an Apache 2.4 or newer host, please read the <a href="notes.php">notes</a>.<br />
				</p>
			</div>
			<br />
			<div>
				<h2>Step 3: Configure directory permissions</h2>
				<p>
					VIPPY needs the following directories to be writable:<br />
					<ul>
						<li>apilog/</li>
						<li>classes/smarty/compiledTPL/</li>
						<li>documents/</li>
						<li>logs/</li>
						<li>update/sql/</li>
					</ul>
				</p>
			</div>
			<br />
			<div>
				<h2>Step 4: PHP Modules</h2>
				<br />
				<div>
					VIPPY needs certain php modules enabled. These modules come with any php installation, but may be disabled by default.
					<ul>
						<li>CURL for communication with the eve API server. <a href="http://php.net/curl" target="_blank">http://php.net/curl</a></li>
						<li>GD to create wormhole activity graphs. <a href="http://php.net/manual/en/book.image.php" target="_blank">http://php.net/manual/en/book.image.php</a></li>
					</ul>
				</div>
			</div>
			<br />
			<div>
				<h2>Step 5: Create your admin user account</h2>
				<br />
				<div class="form">
					<label class="field">Username:</label>
					<input type="text" name="username" value="<?php echo \Tools::POST("username"); ?>" />
				</div>
				<div class="form">
					<label class="field">E-mail:</label>
					<input type="text" name="email" value="<?php echo \Tools::POST("email"); ?>" />
				</div>
				<i>Note: Email is used for password retrieval, but will only work if your server is capable of sending emails.</i>
				<div style="clear:both;"></div>
				<br />
				<div class="form">
					<label class="field">Password:</label>
					<input type="password" name="password1"/>
				</div>
				<div class="form">
					<label class="field">Password (confirm):</label>
					<input type="password" name="password2"/>
				</div>
				<div style="clear:both;"></div>
			</div>
			<div>
				<button type="submit">INSTALL</button>
			</div>
			</form>
			<?php
		}
		?>
	</div>
</div>

</body>
</html>
