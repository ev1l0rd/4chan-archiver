<?php
error_reporting(E_ALL);
ini_set("user_agent", "moot is love moot is life"); // won't work with a blank user_agent
include "config.php";

class chan_archiver {
    public $mysql;
    public $threadurl = "https://boards.4chan.org/%s/thread/%s"; // board, ID
    private $thumburl0 = "http://0.t.4cdn.org/%s/"; // board
    private $thumburl1 = "http://1.t.4cdn.org/%s/"; // board
    private $imgurl = "http://i.4cdn.org/%s/"; // board

    protected function connectDB() {
        global $archiver_config;
        if (!$this->mysql) {
            $this->mysql = mysql_connect($archiver_config['mysql_host'], $archiver_config['mysql_user'], $archiver_config['mysql_pass']);
            if (!$this->mysql)
                die('Could not connect: ' . mysql_error());
            mysql_select_db($archiver_config['mysql_db'], $this->mysql);
        }
    }

    protected function closeDB() {
        if ($this->mysql) {
            mysql_close($this->mysql);
            $this->mysql = null;
        }
    }

    protected function getSource($url) {
        if (($source = @file_get_contents($url)) == false)
            return false;
        return $source;
    }

    protected function downloadFile($url, $location) {
        $file = "";
        if ($handle = @fopen($url, "r")) {
            while ($line = fread($handle, 8192))
                $file .= $line;
            fclose($handle);
            $this->writeFile($file, $location);
        }
    }

    protected function writeFile($data, $location) {
        if ($handle = fopen($location, "w+")) {
            fwrite($handle, $data);
            fclose($handle);
            return true;
        }
        return false;
    }

    protected function rrmdir($dir) {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file))
                $this->rrmdir($file);
            else
                unlink($file);
        }
        rmdir($dir);
    }

    public function getDirSize($directory) {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if($file->getFileName() != "..")
                $size += $file->getSize();
        }
        return $size;
    }

    public function rmZip($threadid, $board) {
        global $archiver_config;
        $destination = $archiver_config['storage'] . $board . "/" . $board . "_" . $threadid . ".zip";
        if (file_exists($destination))
            unlink($destination);

        return sprintf("Removed ZIP for thread %s (/%s/)<br />\r\n", $threadid, $board);
    }

	public function zipThread($threadid, $board) {
		global $archiver_config;
		$source = $archiver_config['storage'] . $board . "/" . $threadid;
		$threadfile = $archiver_config['storage'] . $board . "/" . $threadid . ".html";
		$destination = $archiver_config['storage'] . $board . "/" . $board . "_" . $threadid . ".zip";

        if (file_exists($destination)) unlink($destination);
		if (!extension_loaded('zip') || !file_exists($source))
            return false;
		$zip = new ZipArchive();
		if (!$zip->open($destination, ZIPARCHIVE::CREATE))
            return false;

		if (is_dir($source) === true) {
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

			foreach ($files as $file) {
				$file = str_replace('\\', '/', realpath($file));
				if (strpos($file, $threadid) == false)
                    continue;
				if (is_dir($file) === true)
                    $zip->addEmptyDir(str_replace($source . '/', $threadid . '/', $file . '/'));
				else if (is_file($file) === true)
                    $zip->addFromString(str_replace($source . '/', $threadid . '/', $file), file_get_contents($file));
			}
		} else if (is_file($source) === true)
            $zip->addFromString($threadid . '/' . basename($source), file_get_contents($source));

		if (file_exists($threadfile))
            $zip->addFromString(basename($threadfile), file_get_contents($threadfile));
        $zip->close();
		return sprintf("Zipped thread %s (/%s/)<br />\r\n", $threadid, $board);
	}

    public function checkThreads($onlyMarkedThreads, $onlyFastThreads, $onlyRecentThreads, $verbose) {
        $this->connectDB();
        $query_text = "SELECT * FROM `Threads` WHERE `Status` = '1'";
        if ($onlyMarkedThreads || $onlyFastThreads || $onlyRecentThreads) $query_text .= " AND (";
        if ($onlyMarkedThreads) $query_text .= "`Marked` = '1'";
        if ($onlyMarkedThreads && ($onlyFastThreads || $onlyRecentThreads)) $query_text .= " OR ";
        if ($onlyFastThreads) $query_text .= "`Board` IN ('a','adv','b','co','fit','g','int','k','mlp','mu','sp','tg','tv','v')"; //really only b, v, a, fit, s, gif according to pmq #4 @ 12:30
        if ($onlyFastThreads && $onlyRecentThreads) $query_text .= " OR ";
        if ($onlyRecentThreads) $query_text .= sprintf("`TimeAdded` >= '%s'", time() - 1800); // threads newer that 30 minutes
        if ($onlyMarkedThreads || $onlyFastThreads || $onlyRecentThreads) $query_text .= ")";
        $query = mysql_query($query_text);

        if (!$query)
            die('Could not query database: ' . mysql_error());
        $num = mysql_num_rows($query);
        if ($num <= 0)
            return false;
        $return = "";
        while ($row = mysql_fetch_object($query))
            $return .= $this->updateThread($row->ID, $row->Board);
        $this->closeDB();

        if (!$verbose)
            $return = "";
        $return .= "Checked all" . ($onlyMarkedThreads ? " marked" : "") . (($onlyMarkedThreads && $onlyRecentThreads) ? " and" : "") . ($onlyRecentThreads ? " recent" : "") . " threads" . ($onlyFastThreads ? " on fast boards" : "") . " at " . time();
        return $return;
    }

    public function updateThread($threadid, $board) {
        global $archiver_config;
        $this->connectDB();
        $thrquery = mysql_query(sprintf("SELECT * FROM `Posts` WHERE `Board` = '%s' AND `ThreadID` = '%s'", $board, $threadid));
        $postarr  = array();
        while ($post = mysql_fetch_object($thrquery))
            array_push($postarr, $post->ID);

		$url = sprintf($this->threadurl, $board, $threadid);
        $data = $this->getSource($url);
        if (!$data) { // must have 404'd
			if ($archiver_config['zip_threads'] || file_exists($archiver_config['storage'] . $board . "/" . $board . "_" . $threadid . ".zip"))
                $this->zipThread($threadid, $board);
            mysql_query(sprintf("UPDATE `Threads` SET `Status` = '0' WHERE `Board` = '%s' AND `ID` = '%s'", $board, $threadid));
            //mysql_query( sprintf( "UPDATE `Threads` SET `LastChecked` = '%s' WHERE `Board` = '%s' AND `ID` = '%s'", time(), $board, $threadid ) );
            //mysql_query( sprintf( "DELETE FROM `Posts` WHERE `Board` = '%s' AND `ThreadID` = '%s'", $board, $threadid ) );
            return sprintf("Checked %s (/%s/) at %s<br />\r\n", $threadid, $board, time());
        }
        $fixeddata = str_replace("=\"//", "=\"http://", $data);
		$fixeddata = str_replace("s.4cdn.org/image/favicon.ico", "s.4cdn.org/image/favicon-nws-deadthread.ico", $fixeddata);
		$fixeddata = str_replace("s.4cdn.org/image/favicon-ws.ico", "s.4cdn.org/image/favicon-ws-deadthread.ico", $fixeddata);
        $fixeddata = str_replace("\"" . $threadid . "#", "\"" . $threadid . ".html#", $fixeddata);
        $fixeddata = str_replace("text/rocketscript", "text/javascript", $fixeddata);
        $fixeddata = str_replace("data-rocketsrc", "src", $fixeddata);
        if (is_dir($archiver_config['storage'] . $board . "/") === false)
            mkdir($archiver_config['storage'] . $board . "/");
        if (is_dir($archiver_config['storage'] . $board . "/" . $threadid . "/") === false)
            mkdir($archiver_config['storage'] . $board . "/" . $threadid . "/");
        if (is_dir($archiver_config['storage'] . $board . "/" . $threadid . "/thumbs/") === false)
            mkdir($archiver_config['storage'] . $board . "/" . $threadid . "/thumbs/");

        $files = 0;
        $newestPostTime = 0;
        $posts = explode("class=\"postContainer", $data);
        for ($i = 1; $i < count($posts); $i++) {
            $post = explode("</blockquote> </div>", $posts[$i]);
            $id   = explode("title=\"Reply to this post\">", $post[0]);
            $id   = explode("</a>", $id[1]);
            $id   = $id[0];

            $posttime = explode("data-utc=\"", $post[0]);
            $posttime = explode("\"", $posttime[1]);
            $posttime = $posttime[0];
            $newestPostTime = $posttime;

            $file = explode("\">File:", $post[0]);
            if (count($file) > 1) {
                $files++;
                if (in_array($id, $postarr))
                    continue;
                $file     = explode("</a></div>", $file[1]);
                $file     = $file[0];
                $fileorig = explode("<a class=\"fileThumb", $file);
                $fileorig = explode("\" href=\"", $fileorig[1]); // prev line and this account for spoiler images
                $fileorig = explode("\" target=\"_blank\"", $fileorig[1]);
                $fileorig = $fileorig[0];
                $fileurl  = "http:" . $fileorig;

                $filethum = explode("<img src=\"", $file);
                $filethum = explode("\" alt=", $filethum[1]);
                $filethum = $filethum[0];
                $thumurl  = "http:" . $filethum;

                $filestor    = $archiver_config['storage'] . $board . "/" . $threadid . "/" . $board . "_" . $threadid . "_" . basename($fileurl);
                $thumstor    = $archiver_config['storage'] . $board . "/" . $threadid . "/thumbs/" . $board . "_" . $threadid . "_" . basename($thumurl);
                $pubfilestor = $threadid . "/" . $board . "_" . $threadid . "_" . basename($fileurl);
                $pubthumstor = $threadid . "/thumbs/" . $board . "_" . $threadid . "_" . basename($thumurl);

                $this->downloadFile($fileurl, $filestor);
                $this->downloadFile($thumurl, $thumstor);
                $fixeddata = str_replace($fileorig, $pubfilestor, $fixeddata);
                $fixeddata = str_replace($filethum, $pubthumstor, $fixeddata);
            }
            mysql_query(sprintf("INSERT INTO `Posts` (`ID`, `ThreadID`, `Board`, `PostTime`) VALUES ('%s', '%s', '%s', '%s')", $id, $threadid, $board, $posttime));
        }
        // fix for posts we already have downloaded
        $fixeddata = str_replace(sprintf($this->thumburl0, $board), $threadid . "/thumbs/" . $board . "_" . $threadid . "_", $fixeddata);
        $fixeddata = str_replace(sprintf($this->thumburl1, $board), $threadid . "/thumbs/" . $board . "_" . $threadid . "_", $fixeddata);
        $fixeddata = str_replace(sprintf($this->imgurl, $board), $threadid . "/" . $board . "_" . $threadid . "_", $fixeddata);
        //$fixeddata = str_replace("http://1.t.4cdn.org/" . $board . "/thumb/", $threadid . "/thumbs/", $fixeddata);
        //$fixeddata = str_replace("http://0.t.4cdn.org/" . $board . "/thumb/", $threadid . "/thumbs/", $fixeddata);
        //$fixeddata = str_replace("http://i.4cdn.org/" . $board . "/src/", $threadid . "/", $fixeddata);
        // thread is done
        mysql_query(sprintf("UPDATE `Threads` SET `LastChecked` = '%s', `PostCount` = '%s', `FileCount` = '%s', `NewestPostTime` = '%s' WHERE `Board` = '%s' AND `ID` = '%s'", time(), count($posts) - 1, $files, $newestPostTime, $board, $threadid));
        $this->writeFile($fixeddata, $archiver_config['storage'] . $board . "/" . $threadid . ".html");
        $this->closeDB();
        return sprintf("Checked %s (/%s/) at %s<br />\r\n", $threadid, $board, time());
    }

    public function addThread($threadid, $board, $description) {
        $this->connectDB();
        // check if we already have it
        $query = mysql_query(sprintf("SELECT * FROM `Threads` WHERE `ID` = '%s' AND Board = '%s'", $threadid, $board));
        if (!$query)
            die('Could not query database: ' . mysql_error());
        if (mysql_num_rows($query) > 0)
            return false;
        // guess we don't, lets add it
        $description = str_replace("'", "", $description);
        $description = str_replace("\"", "", $description);
        $description = preg_replace("/[ ]{2,}/", " ", $description);
        $description = trim($description);
        $query = mysql_query(sprintf("INSERT INTO `Threads` (`ID`, `Board`, `Status`, `LastChecked`, `Description`, `TimeAdded`) VALUES ('%s', '%s', '1', '0', '%s', '%s')", $threadid, $board, $description, time()));
        if (!$query)
            die('Could not add thread: ' . mysql_error());
        $this->closeDB();
        return sprintf("Added thread %s (/%s/)<br />\r\n", $threadid, $board);
    }

    public function removeThread($threadid, $board, $deletefiles = 0) {
        global $archiver_config;
        $this->connectDB();
        // check if we already have it
        $query = mysql_query(sprintf("SELECT * FROM `Threads` WHERE `ID` = '%s' AND Board = '%s'", $threadid, $board));
        if (!$query)
            die('Could not query database: ' . mysql_error());
        if (mysql_num_rows($query) <= 0)
            return false;
        if ($deletefiles) {
            if (is_dir($archiver_config['storage'] . $board . "/" . $threadid . "/"))
                $this->rrmdir($archiver_config['storage'] . $board . "/" . $threadid . "/");
            if (file_exists($archiver_config['storage'] . $board . "/" . $threadid . ".html"))
                unlink($archiver_config['storage'] . $board . "/" . $threadid . ".html");
			if (file_exists($archiver_config['storage']. $board . "/" . $board . "_" . $threadid . ".zip"))
                $this->rmZip($threadid, $board);
        }
        if (count(scandir($archiver_config['storage'] . $board . "/")) == 2)
            rmdir($archiver_config['storage'] . $board . "/");
        mysql_query(sprintf("DELETE FROM `Posts` WHERE `ThreadID` = '%s' AND `Board` = '%s'", $threadid, $board));
        mysql_query(sprintf("DELETE FROM `Threads` WHERE `ID` = '%s' AND Board = '%s'", $threadid, $board));
        $this->closeDB();
        return sprintf("Removed thread %s (/%s/)<br />\r\n", $threadid, $board);
    }

    public function setThreadDescription($threadid, $board, $description) {
        $this->connectDB();
        // check if we already have it
        $query = mysql_query(sprintf("SELECT * FROM `Threads` WHERE `ID` = '%s' AND Board = '%s'", $threadid, $board));
        if (!$query)
            die('Could not query database: ' . mysql_error());
        if (mysql_num_rows($query) <= 0)
            return false;
        $description = str_replace("'", "", $description);
        $description = str_replace("\"", "", $description);
        $description = preg_replace("/[ ]{2,}/", " ", $description);
        $description = trim($description);
        mysql_query(sprintf("UPDATE `Threads` SET `Description` = '%s' WHERE `ID` = '%s' AND Board = '%s'", $description, $threadid, $board));
        $this->closeDB();
        return sprintf("Updated description of thread %s (/%s/)<br />\r\n", $threadid, $board);
    }

    public function toggleMarkedThread($threadid, $board) {
        $this->connectDB();
        // check if we already have it
        $query = mysql_query(sprintf("SELECT * FROM `Threads` WHERE `ID` = '%s' AND Board = '%s'", $threadid, $board));
        if (!$query)
            die('Could not query database: ' . mysql_error());
        if (mysql_num_rows($query) <= 0)
            return false;
        while ($thr = mysql_fetch_object($query)) {
            if ($thr->Marked == 1)
                $mark_new = 0;
            else $mark_new = 1;
            mysql_query(sprintf("UPDATE `Threads` SET `Marked` = '%s' WHERE `ID` = '%s' AND Board = '%s'", $mark_new, $threadid, $board));
        }
        $this->closeDB();
        return sprintf((($mark_new == 1) ? "Marked" : "Unmarked") . " thread %s (/%s/)<br />\r\n", $threadid, $board);
    }

    public function reactivateThread($threadid, $board) {
        $this->connectDB();
        $query = mysql_query(sprintf("SELECT * FROM `Threads` WHERE `ID` = '%s' AND Board = '%s'", $threadid, $board));
        if (!$query)
            die('Could not query database: ' . mysql_error());
        if (mysql_num_rows($query) <= 0)
            return false;
        mysql_query(sprintf("UPDATE `Threads` SET `Status` = '1' WHERE `ID` = '%s' AND Board = '%s'", $threadid, $board));
        $this->closeDB();
        return sprintf("Reactivated thread %s (/%s/)<br />\r\n", $threadid, $board);
    }

	public function getThreadCount($onlyOngoing = false) {
        $this->connectDB();
        if ($onlyOngoing)
            $query = mysql_query("SELECT COUNT(*) AS 'Count' FROM `Threads` WHERE `Status` = '1'");
        else
            $query = mysql_query("SELECT COUNT(*) AS 'Count' FROM `Threads`");
        if (!$query)
            die('Could not query database: ' . mysql_error());

        $count = mysql_fetch_object($query)->Count;
        $this->closeDB();
        return $count;
    }

    public function getThreads() {
        $this->connectDB();
        $query = mysql_query("SELECT * FROM `Threads` ORDER BY `Marked` DESC, `Board`, `TimeAdded`, `ID` ASC");
        if (!$query)
            die('Could not query database: ' . mysql_error());
        $thrarray = array();
        while ($thr = mysql_fetch_array($query, MYSQL_ASSOC))
            array_push($thrarray, $thr);
        $this->closeDB();
        return $thrarray;
    }
}
?>
