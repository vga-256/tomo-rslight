Rocksolid Light (rslight) - a web based Usenet news client

Visit https://www.novabbs.com to try Rocksolid Light

![ScreenShot](https://www.novabbs.com/images/rslight-480.png)

Rocksolid Light is based on NewsPortal, which discontinued development in 2008, and was 
developed by Florian Amrhein https://florian-amrhein.de/newsportal/ 

rslight contains some major code and feature changes, but would not exist 
without NewsPortal as a basis for development.

Rocksolid Light is a php web forum interface that basically uses nntp as a backend. 
Forums can be Usenet newsgroups, or any groups you wish to create. Forums can be 
synchronized with other rslight installs, or other nntp servers.

* Does not require Javascript
* Built in nntp server
  * Synchronize with inn or another rslight site
  * Read and post using a news client
  * Tested with Claws Mail, Thunderbird, Knews, tin and some others

* Interface works reasonably well on small devices
* Colors in CSS are in a separate file for easy testing and modification
* Groups can be renamed for cleaner display
* Configuration options may be set for each individual 'section'

See INSTALL.md for installation instructions.

If you have trouble, post to rocksolid.nodes.help (www.novabbs.com) and we'll try to help.

Features added in 0.6.5

* NoCeM support
* Spamassassin support
* Message expiration
* Ability to ban incoming messages by user
* Display first image attachment inline

Features added in 0.6.6

* SSL encryption as a client and as a server
* New account created may be authenticated by email if configured to do so
* Display last poster per thread
* Link to display full headers of any message
* More commands supported in nntp server
* Config option to enable/disable displaying only partial poster email address
* Log rotation
* Main config file can now be modified using a browser
* Header links configured through a config file, not hard coded
* Added motd (message of the day) feature that can also display Unix fortunes
* Config option to limit a user to 'X' posts/hour. May also be different for individual users

Features added in 0.6.7

* Handle main article overview in sqlite db for easier management and speed
* Allow configuration of expire per group
* Search now searches across all sections
* Moving a group from one section to another doesn't break overview 

Retro Guy retroguy@novabbs.com
