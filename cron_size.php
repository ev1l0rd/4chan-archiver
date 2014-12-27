<?php
include "chan_archiver.php";
$t = new chan_archiver();
$size = $t->getDirSize($archiver_config["storage"]);
$file = fopen($archiver_config["storage"] . "/cron_size.txt", 'w') or die("Can't open cron_size.txt!");
fwrite($file, $size);
fclose($file);
?>
