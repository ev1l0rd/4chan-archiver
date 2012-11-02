4chan-archiver
==============

Note:
-----
This archiver is outdated and won't be maintained anymore, I've made a new version called "chan archiver" and uploaded it to http://github.com/emoose/chan-archiver/, I'd suggest you either use that or one of the forks available.

GNU public license 3 blah blah blah, can't be bothered to add the text and stuff in here. If you use/modify this just give credit to the github.

These small scripts let you create your own little 4chan archive, without needing to use crappy advert ridden websites! (or overly worked on perl scripts, this is 4 hours work)

Features:
---------

* Fully parse and download any thread
* Very small overhead
* Just over 300 lines of code!
* Simple login system (see config.php)

Requires:
---------

* PHP 4+
* MySQL
* Server that supports cronjobs (or some other kind of scheduling device)

Installation:
-------------

1. Import chanarchive.sql into some database
2. Setup config.php with your paths and mysql info
3. Add a cronjob to /usr/bin/php -f /path/to/cron.php (might not be /usr/bin/php, check with your server admin)

Have fun! and if you are updating MAKE SURE YOU DELETE VERSION.TXT!

Any bugs? Post on the github!

https://github.com/emoose/4chan-archiver
