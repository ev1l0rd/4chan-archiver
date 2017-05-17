<?php
/* Written by ev1l0rd
     chan-archivist - Archives threads from 4chan.org
    Copyright (C) 2012-2017 ev1l0rd

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

    Usage: https://github.com/ev1l0rd/chan-archivist/wiki/cron_monitor.php
*/
error_reporting(E_ALL);
include "chan_archiver.php";
include "config.php";

class threadMonitor extends chan_archiver{
	public $threadno;
	public $subject;
	function monitorCatalog($boardwatch, $filter, $basedescription) {
		$json=json_decode( file_get_contents('http://a.4cdn.org/'.$boardwatch.'/catalog.json'),true);
		$monitordescription=$basedescription.date(DATE_RFC850);
		/* Credits to http://stackoverflow.com/a/44009171/4666756 */
		foreach( $json as $obj ){
			$data=(object)$obj;
			foreach ( $data->threads as $thread ){
				$threadobj=(object)$thread;
					$threadno=$threadobj->no .PHP_EOL;
				if ( isset($threadobj->sub) ) {
					$subject=$threadobj->sub . PHP_EOL;
					if (stripos($subject, $filter) !== false){
						$this->addThread($threadno, $boardwatch, $monitordescription);
					}
				}
			}
		}
	}
}

$t=new threadMonitor();

/* IMPORTANT: Add arguments like this:
  board filter basedescription
  Example crontab command (that last space is needed, the command automatically adds the added date and time to the thread):
	php /path/to/cron_monitor.php vg "/dfg/" "Dwarf Fortress General - "

	Recommend to run every 5 minutes for high-speed boards, every 30 minutes for low-speed boards.
*/

$t->monitorCatalog($argv[1],$argv[2],$argv[3]);

/* Kinda ugly calling for now, needs wget to be ran (or you could visit it in the browser I guess. */
/* No longer needed, but you can uncomment it anyway. No support given. */

/* if (isset($_REQUEST['board']) && isset($_REQUEST['filter']) && isset($_REQUEST['desc'])) {
	$return .= $t->monitorCatalog($_REQUEST['board'],$_REQUEST['filter'],$_REQUEST['desc']);
} */
?>
