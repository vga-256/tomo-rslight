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
* Synchronizes via nntp, but does not require Usenet specifically
  * rslight is a nntp server, and can connect to other rslight sites
* Built in nntp server compatible with some news clients
  * Read and Post using a news client
  * Tested with Claws Mail, Thunderbird, Knews, tin and some others
* Synchronizes and works well in slow networks (tested in tor and i2p)
* No database required

* Interface works reasonably well on small devices
* Colors in CSS are in a separate file for easy testing and modification
* Groups can be renamed for cleaner display
* Configuration and options can be different per 'section'

See INSTALL.md for installation instructions.

If you have trouble, post to rocksolid.nodes.help (www.novabbs.com) and we'll try to help.

Retro Guy retroguy@novabbs.com