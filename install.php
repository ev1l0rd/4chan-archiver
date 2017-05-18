<?php
class installer {
	private $mysql
	function dbconnect(){
        	if (!$this->mysql) {
        	    $this->mysql = mysql_connect($_POST['hostname'], $archiver_config['username'], $archiver_config['password']);
        	    if (!$this->mysql)
        	        die('Could not connect: ' . mysql_error());
        	    mysql_select_db($archiver_config['database'], $this->mysql);
		}
	function dbsetup(){

	}
}

if (isset($_POST["hostname"]) && isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["database"]) && isset($_POST["prefix"])) {
	
}
if (!file_exists(".setup") {
echo <<<ENDHTML
<html>
<body>

<h2>chan-archivist install script</h2>
<p>All these options can later be reconfigured by editing config.php .</p>

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
Storage path on server (end with a slash!):<br>
<input type="text" name="serverpath"/>
URL to storage path:<br>
<input type="text" name="publicpath"/>
Start in <a href="https://github.com/ev1l0rd/chan-archivist/wiki/safe-mode.php">Safe mode</a>?
<input type="checkbox" value="safemode">I want to enable safe mode.
<input type="submit" value="Submit"/>
</form>
ENDHTML;
}
