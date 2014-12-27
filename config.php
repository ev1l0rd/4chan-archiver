<?php
$archiver_config = array();
// -----------------------------------------------------------
// FOLDER CONFIG
// e.g. if your script is at /chandl/ these should be set to /chandl/arch/
// -----------------------------------------------------------

// where to store files, this folder should probably get made by you with 777 perms
$archiver_config["storage"] = "";

// the publicly accessible link to the file store
$archiver_config["pubstorage"] = "";

// -----------------------------------------------------------
// FUNCTIONS CONFIG
// some functionality may be disabled for faster performance
// -----------------------------------------------------------

// whether to zip threads once they 404; a ZIP button will be displayd to
// addenabled users no matter what - also, if a ZIP was generated via the ZIP
// button (or manually), the thread will be zipped again once it 404s
$archiver_config["zip_threads"] = false;

// -----------------------------------------------------------
// MYSQL CONFIG
// self explanatory
// -----------------------------------------------------------

$archiver_config["mysql_host"] = "localhost";
$archiver_config["mysql_user"] = "";
$archiver_config["mysql_pass"] = "";
$archiver_config["mysql_db"]   = "";
?>
