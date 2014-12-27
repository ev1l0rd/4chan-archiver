<?php
include "chan_archiver.php";
$t = new chan_archiver();
//echo $t->checkThreads(false, true, true, true); // fast and recent
$t->checkThreads(false, true, true, true); // fast and recent
?>
