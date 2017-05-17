<?php
$start_time = microtime(true);
ini_set("max_execution_time", 600); // increase maximum execution time
session_start();
include "chan_archiver.php";
$t = new chan_archiver();

$return = "";
if ($archiver_config["safe_mode"] !== "true"){
if (isset($_REQUEST['del']) && isset($_REQUEST['id']) && isset($_REQUEST['brd']))
    $return .= $t->removeThread($_REQUEST['id'], $_REQUEST['brd'], $_REQUEST['files']);
if (isset($_REQUEST['chk']) && isset($_REQUEST['id']) && isset($_REQUEST['brd']))
    $return .= $t->updateThread($_REQUEST['id'], $_REQUEST['brd']);
if (isset($_REQUEST['mrk']) && isset($_REQUEST['id']) && isset($_REQUEST['brd']))
    $return .= $t->toggleMarkedThread($_REQUEST['id'], $_REQUEST['brd']);
if (isset($_REQUEST['zip']) && isset($_REQUEST['id']) && isset($_REQUEST['brd']))
    $return .= $t->zipThread($_REQUEST['id'], $_REQUEST['brd']);
if (isset($_REQUEST['rmzip']) && isset($_REQUEST['id']) && isset($_REQUEST['brd']))
    $return .= $t->rmZip($_REQUEST['id'], $_REQUEST['brd']);
if (isset($_REQUEST['reac']) && isset($_REQUEST['id']) && isset($_REQUEST['brd']))
    $return .= $t->reactivateThread($_REQUEST['id'], $_REQUEST['brd']);
if (isset($_REQUEST['upd']) && isset($_REQUEST['id']) && isset($_REQUEST['brd']))
    $return .= $t->setThreadDescription($_REQUEST['id'], $_REQUEST['brd'], $_REQUEST['desc']);
if (isset($_REQUEST['chkm']))
    $return .= $t->checkThreads(true, false, false, false);
if (isset($_REQUEST['chka']))
    $return .= $t->checkThreads(false, false, false, false);
if (isset($_REQUEST['add']) && isset($_REQUEST['url'])) {
	$_REQUEST['url'] = str_replace("https://", "http://", $_REQUEST['url']);
	if (substr($_REQUEST['url'], 0, 7) != "http://")
        $_REQUEST['url'] = "http://" . $_REQUEST['url'];
    if (!isset($_REQUEST['desc']))
        $_REQUEST['desc'] = "";
    if ($c = preg_match_all("/.*?(?:[a-z][a-z0-9_]*).*?(?:[a-z][a-z0-9_]*).*?(?:[a-z][a-z0-9_]*).*?(?:[a-z][a-z0-9_]*).*?((?:[a-z][a-z0-9_]*)).*?(\d+)/is", $_REQUEST['url'], $matches))
        $return .= $t->addThread($matches[2][0], $matches[1][0], $_REQUEST['desc']);
}
}
if ($return != "") {
    $_SESSION['returnvar'] = $return;
    header('Location: index.php');
    exit;
}

$threadCount = $t->getThreadCount();
$ongoingThreadCount = $t->getThreadCount(true);
$title = $archiver_config['title'];
echo <<<ENDHTML
<!DOCTYPE html>
<html>
<head>
    <title>$ongoingThreadCount/$threadCount - $title</title>
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="header">
<form action="?refresh" method="POST">
<table class="add">
ENDHTML;
if ($archiver_config["safe_mode"] !== "true"){
if (isset($_SESSION['returnvar']) && $_SESSION['returnvar'] != "" && $rtrn = $_SESSION['returnvar'])
{
    echo <<<ENDHTML
    <tr class="success">
        <td><b>Add Thread</b></td>
		<td colspan="2">$rtrn</td>
    </tr>
ENDHTML;
    $_SESSION['returnvar'] = "";
    unset($_SESSION['returnvar']);
} else {
	echo <<<ENDHTML
    <tr>
        <td><b>Add Thread</b></td>
		<td colspan="2"> </td>
    </tr>
ENDHTML;
}
	echo <<<ENDHTML
    <tr>
        <td>Thread URL:</td>
		<td><input type="text" class="url" name="url" size="60" /></td>
		<td> </td>
    </tr>
    <tr>
        <td>Thread Description:</td>
		<td><input type="text" class="desc" name="desc" size="60" /></td>
        <td><input type="submit" class="add" name="add" value="Add"/></td>
    </tr>
ENDHTML;
} else {
	echo <<<ENDHTML
    <tr>
        <td><b>SAFE MODE enabled. No threads can be added.</b></td>
		<td colspan="2"> </td>
    </tr>
    <tr>
        <td>-</td>
	<td></td>
    </tr>
    <tr>
        <td>-</td>
	<td></td>
	<td></td>
    </tr>
ENDHTML;
}

echo <<<ENDHTML
</table>
</form>
</div>
<table class="threads">
	<tr>
		<td>Thread ID</td>
		<td>Board</td>
		<td>Description</td>
        <td>Added</td>
		<td>Last Checked</td>
        <td>Posts</td>
		<td>Latest Post</td>
		<td>Actions</td>
	</tr>
ENDHTML;

function ago($timestamp, $unit, $digits = false) {
    $difference = time() - $timestamp;
    if ($unit == "d") {
        $ago = $difference / 86400;
        $unit = "day";
    } else if ($unit == "h") {
        $ago = $difference / 3600;
        $unit = "hour";
    } else if ($unit == "m") {
        $ago = $difference / 60;
        $unit = "minute";
    } else {
        $ago = $difference;
        $unit = "second";
    }
    if ($digits === false) return $ago; // no label wanted
    $ago = number_format($ago, $digits);
    if ($ago != 1 || $digits != 0) $unit .= "s"; // 1.0 does not refer to a single thing, it is a dimensional measure of size. It doesn't qualify as singular.
    return "$ago $unit ago";
}

$threads = $t->getThreads();

$i = 0;
$totalPosts = 0;
$totalImages = 0;
foreach ($threads as $thr)
{
    $i++;
    $totalPosts += $thr["PostCount"];
    $totalImages += $thr["FileCount"];

    $thrlink = sprintf($t->threadurl, $thr["Board"], $thr["ID"]);
    $added = "<abbr title=\"" . date("Y-m-d, H:i", $thr["TimeAdded"]) . "\">" . ago($thr["TimeAdded"], "d", 0) . "</abbr>";
    $lastchecked = "<abbr title=\"" . date("Y-m-d, H:i", $thr["LastChecked"]) . "\">" . ago($thr["LastChecked"], "m", 0) . "</abbr>";
    $status = ($thr["Status"] == 1) ? "ongoing" : "";
    $marked = ($thr["Marked"] == 1) ? "marked" : "";
    $local  = $archiver_config['pubstorage'] . $thr["Board"] . "/" . $thr["ID"] . ".html";
    $link   = "<a href=\"$thrlink\">{$thr["ID"]}</a> <a href=\"$local\">(local)</a>";
    $check  = "<input type=\"submit\" class=\"check\" name=\"chk\" value=\"Check\"/>";
    $desc   = "<input type=\"text\" class=\"desc\" name=\"desc\" value=\"{$thr["Description"]}\"/> <div class=\"right\"><input type=\"submit\" class=\"upd\" name=\"upd\" value=\"Update\"/> <input type=\"submit\" class=\"mark\" name=\"mrk\" value=\"" . ( $thr["Marked"] == 1 ? "Unmark" : "Mark" ) . "\"/></div>";
    if ($thr["Status"] == 0) {
        $lastchecked = "<abbr title=\"" . date("Y-m-d, H:i", $thr["LastChecked"]) . "\">" . ago($thr["LastChecked"], "d", 0) . "</abbr>";
		$link = "<a href=\"$local\">{$thr["ID"]}</a>";
        $check = "<input type=\"submit\" class=\"reactivate\" name=\"reac\" value=\"Check\"/>";
    }
    if ($thr["LastChecked"] == 0)
        $lastchecked = "never";
    if (file_exists($archiver_config['storage'] . $thr["Board"] . "/" . $thr["Board"] . "_" . $thr["ID"] . ".zip")) {
        $filesize = round(filesize($archiver_config['storage'] . $thr["Board"] . "/" . $thr["Board"] . "_" . $thr["ID"] . ".zip") / 1048576, 2);
        $link .= " <a href=\"" . $archiver_config['pubstorage'] . $thr["Board"] . "/" . $thr["Board"] . "_" . $thr["ID"] . ".zip\" title=\"" . $filesize ." MB\">(zip)</a>";
        $check .= " <input type=\"submit\" class=\"del\" name=\"rmzip\" value=\"ZIP\"/>";
    } else {
        $check .= " <input type=\"submit\" class=\"zip\" name=\"zip\" value=\"ZIP\"/>";
    }
    $check .= " <input type=\"submit\" class=\"del\" name=\"del\" onclick=\"document.getElementById('files{$i}').value='1';\" value=\"Remove\"/>";
    $postcount = ($thr["PostCount"] > 0) ? ( ($thr["PostCount"] == 765) ? "<em>"  . $thr["PostCount"] . "</em>" : $thr["PostCount"]) . " " . (($thr["FileCount"] == 151 || $thr["FileCount"] == 251) ? "<em>(" . $thr["FileCount"] . ")</em>" : "(" . $thr["FileCount"] . ")") : "";
    $lastpost = "<td class=\"" . ( (ago($thr["NewestPostTime"], "h") > 24) ? ( (ago($thr["NewestPostTime"], "h") > 72) ? "veryoldlastpost" : "oldlastpost" ) : "lastpost" ) . "\"><abbr title=\"" . date( "Y-m-d, H:i", $thr["NewestPostTime"] ) . "\">";
    if ($thr["Status"] == 0) $lastpost .= ago($thr["NewestPostTime"], "d", 0);
    else if (ago($thr["NewestPostTime"], "h") < 3) $lastpost .= ago($thr["NewestPostTime"], "h", 1);
    else $lastpost .= ago($thr["NewestPostTime"], "h", 0);
    $lastpost .= "</abbr></td>";
    if (empty($thr["NewestPostTime"]) || $thr["NewestPostTime"] <= 0)
        $lastpost = "<td></td>";
    echo <<<ENDHTML
    <form action="?refresh" method="POST">
    <input type="hidden" name="id" value="{$thr["ID"]}"/>
    <input type="hidden" name="brd" value="{$thr["Board"]}"/>
    <input type="hidden" name="files" id="files{$i}" value="0"/>
    	<tr class="threadrow $status $marked">
    		<td>$link</td>
    		<td><a href="https://boards.4chan.org/{$thr["Board"]}/catalog">/{$thr["Board"]}/</a></td>
    		<td style="width: 650px;">$desc</td>
            <td>$added</td>
    		<td>$lastchecked</td>
            <td>$postcount</td>
    		$lastpost
    		<td>$check</td>
    	</tr>
    </form>
ENDHTML;
}
$runtime = round((microtime(true) - $start_time) * 1000);
$directory_size = round(file_get_contents("cron_size.txt") / 1073741824, 2);
$time = date("H:i");
echo <<<ENDHTML
	<tr>
        <td colspan="2">
            ${runtime}ms, ${directory_size}GB, $time
        </td>
		<td colspan="6">
            <form action="?refresh" method="POST">
ENDHTML;
echo $ongoingThreadCount . " active threads, " . ($threadCount - $ongoingThreadCount) . " inactive threads, " . $threadCount . " total threads, " . $totalPosts . " posts, " . $totalImages . " images ";
if ($archiver_config["safe_mode"] !== "true"){
echo <<<ENDHTML
                <input type="submit" class="check" name="chkm" value="Recheck Marked"/>
                <input type="submit" class="check" name="chka" value="Recheck All"/>
ENDHTML;
}
echo <<<ENDHTML
            </form>
        </td>
    </tr>
</table>
ENDHTML;

$bookmarkleturl = "http://" . ($_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_SERVER["SERVER_NAME"]) . $_SERVER["SCRIPT_NAME"];
echo <<<ENDHTML
<!--<p>
    <a href="http://github.com/emoose/4chan-archiver/"><strong>4chan archiver - by anon e moose</strong> downloaded from github.com/emoose/4chan-archiver.</a><br />
    To add threads, you can use the <abbr title="use this when you're on the page you want to archive"><a href="javascript:open('$bookmarkleturl?add=Add&url=' + document.URL.replace('http://', ''));">bookmarklet</a></abbr>.<br />
    Runtime: $runtime<span>s</span>, folder size: $directory_size, time: $time
</p>-->
</body>
</html>
ENDHTML;
?>
