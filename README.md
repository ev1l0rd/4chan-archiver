Note: This is my personal, mostly rewritten and thus incompatible fork of emoose's 4chan-archiver. I've stopped using and maintaining it [in mid 2014](https://www.youtube.com/watch?v=Hhx6IfKrvEQ), but it's still working mostly fine as of ~~2014-12-27~~ 2016-06-13.

![Example](https://raw.githubusercontent.com/ev1l0rd/4chan-archiver/master/screenshot.jpg)

Features I've removed for the sake of simplicity:
* Username/password authentication. I recommend running 4chan-archiver in a directory that's password-protected using `.htaccess` or similar (which is more secure anyway).
* Automatic updates.

Some of the features doersino added:
* Different Design: not sure if it's a huge improvement over emoose's, but it's a bit more colorful.
* ZIP compression: you can create a ZIP archive of a thread at any time, it will be automatically updated once the thread 404's (or created if none exists, which you can deactivate in `config.php`).
* Marked Threads: will show up at the top of the thread list
* Added Column: see when each thread was added.
* Posts Column: for each thread, the number of posts and images will be displayed.
* Folder Size Stats.
* More granular control over how often different kinds of threads are checked. There's an example crontab below.

The features I (ev1l0rd) added:
* New: [cron_monitor.php](https://github.com/ev1l0rd/4chan-archiver/wiki/cron_monitor.php) - Monitors the board catalog for a specific string in a thread subject. Useful for tracking generals. Note that this feature uses the 4cdn API
* Modified: CSS buttons and description boxes have their color set to black to ensure readability.
* New: [Safe mode](https://github.com/ev1l0rd/4chan-archiver/wiki/safe-mode.php) - Disables all methods of input in index.php (includes undocumented parameters such as add). Basically blocks all additions or modifications to a thread, except through PHPmyAdmin or threads added through cron_monitor.php . Pages will continue to be updated if you have the cronjob set up.
* New: Confirm dialog before removing threads.
To-Do:
* Modify cron_monitor.php to also check for thread body. - Subject is not always set.
* Add table prefixes. - The program only uses two databases at the moment. It is a waste (and annoying for shared hosting which limits the amount of mySQL DBs) to force the archiver to one DB per instance.
* Add install.php script to automate config.php generation for first time usage.
* Fix SQL leaks (lol) -very much in the longterm-
* Add some static pages to detail how to use.
* Add theming support.
* Probably some other stuff as well. We'll see.

```
48 */4 * * *	php -f /path/to/cron.php
*/8 * * * *	php -f /path/to/cron_fast.php
*/32 * * * *	php -f /path/to/cron_marked.php
*/16 * * * *	php -f /path/to/cron_size.php
```

Known bugs:
* SQL injection and related exploits are very possible. I recommend running 4chan-archiver in a directory that's password-protected using `.htaccess` or similar.
* If your browser window is narrower than 1440px, things might look out of place. Try decreasing the width of `table.threads input.desc` in `style.css`.
* If adding a thread fails, make sure to remove the [appended subject/comment snippets](http://blog.4chan.org/post/82477681005/upcoming-namespace-changes).
* Entries of the `Posts` table aren't always properly deleted.

Below is the original `README.md`:

***

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
