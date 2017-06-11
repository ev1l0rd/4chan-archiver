<?php
class installer {
	private $mysql;
	private $sqlcommands;
	private $configfile;
	private $configsettings;
	private $safemode;
	private $zipthreads;
	function dbconnect(){
        	if (!$this->mysql) {
        	    $this->mysql = mysql_connect($_POST['hostname'], $_POST['username'], $_POST['password']);
        	    if (!$this->mysql)
        	        die('Could not connect: ' . mysql_error());
        	    mysql_select_db($_POST['database'], $this->mysql);
		}
	}
	function dbsetup(){
		$this->dbconnect();
		$sqlcommands = "CREATE TABLE IF NOT EXISTS `".$_POST["prefix"]."Posts` (
  `ID` int(15) NOT NULL,
  `ThreadID` int(15) NOT NULL,
  `Board` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `PostTime` int(15) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE IF NOT EXISTS `".$_POST["prefix"]."Threads` (
  `ID` int(15) NOT NULL,
  `Board` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Status` tinyint(1) NOT NULL,
  `LastChecked` int(15) NOT NULL,
  `TimeAdded` int(15) NOT NULL,
  `Marked` tinyint(1) NOT NULL,
  `PostCount` int(15) NOT NULL,
  `FileCount` int(15) NOT NULL,
  `NewestPostTime` int(15) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$configfile = fopen("config.php", "a");
		$configsettings = '$archiver_config["mysql_host"]		= "'.$_POST['hostname'].';' . PHP_EOL . '
$archiver_config["mysql_user"]		= "'.$_POST['username'].'";' . PHP_EOL . '
$archiver_config["mysql_pass"]		= "'.$_POST['password'].'";' . PHP_EOL . '
$archiver_config["mysql_db"]		= "'.$_POST['database'].'";' . PHP_EOL . '
$archiver_config["mysql_prefix"]	= "'.$_POST['prefix'].'";' . PHP_EOL;
		fwrite($configfile,$configsettings);
		fclose($configfile);
		touch(".setup");
	}
	function createconfig(){
		$configfile = fopen("config.php", "w");
		$configsettings = '<?php'.PHP_EOL;
		fwrite($configfile,$configsettings);
		fclose($configfile);
	}
	function configsetup(){
		if (isset($_POST["safemode"])){
	$safemode='$archiver_config["safe_mode"] = "true";';
} else { 
	$safemode='$archiver_config["safe_mode"] = "false";'; 
};
		if (isset($_POST["zipthreads"])){
	$zipthreads='$archiver_config["zip_threads"] = true;';
} else { 
	$zipthreads='$archiver_config["zip_threads"] = false;';
};
		$configfile = fopen("config.php", "a");
		$configsettings = '$archiver_config["title"] = "'.$_POST['archivetitle'].'";'. PHP_EOL . '
$archiver_config["storage"] = '.$_POST['serverpath'].'";'. PHP_EOL . '
$archiver_config["storage"]'.$_POST['publicpath'].'";'. PHP_EOL .
"$safemode" . PHP_EOL .
"$zipthreads" . PHP_EOL;
		fwrite($configfile,$configsettings);
		fclose($configfile);
	}
	function finalize(){
		fopen(".setup","w");
	}
}

if (isset($_POST["confirm"]) && isset($_POST["publicpath"])) {
	$t = new installer();
	$t->createconfig();
	$t->dbsetup();
	$t->configsetup();
	$t->finalize();
}
if (!file_exists(".setup")) {
echo <<<ENDHTML
<html>
<body>

<h2>chan-archivist install script</h2>
<p>All these options can later be reconfigured by editing config.php .</p>
<h3>Database Setup</h3>
<form action="install.php" method="post">
mySQL host:<br>
<input type="text" name="hostname" value="localhost"/><br>
mySQL username:<br>
<input type="text" name="username"/><br>
mySQL password:<br>
<input type="password" name="password"/><br>
mySQL database:<br>
<input type="text" name="database"/><br>
mySQL table prefix (optional):<br>
<input type="text" name="prefix"/><br>
Archive title:<br>
<input type="text" name="archivetitle" value="chan-archivist"/><br>
Storage path on server (end with a slash!):<br>
<input type="text" name="serverpath"/><br>
URL to storage path:<br>
<input type="text" name="publicpath"/><br>
Start in <a href="https://github.com/ev1l0rd/chan-archivist/wiki/safe-mode.php">Safe mode</a>?<br>
<input type="checkbox" name="safemode" value="yes"/>I want to enable safe mode.<br>
Zip threads when they 404?<br>
<input type="checkbox" name="zipthreads" value="yes"/>Zip 'em up!<br>
<input type="checkbox" name="confirm" value="yes"/>Tick this once you have set all the options<br>
<input type="submit" value="Submit"/>
</form>
ENDHTML;


} else {
echo <<<ENDHTML
<html>
<head></head>
<body>
<h2>Nothing left to set up!<h2>
<p>If you need to change any settings, edit config.php manually. Use config.php.example as a reference. If in the future you need to migrate the db, check the release notes.</p>
</body>
</html>
ENDHTML;
}
