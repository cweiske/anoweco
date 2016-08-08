********************************
anoweco - anonymous web comments
********************************
Anonymous web comments for the indieweb.

Provides everything needed to comment on sites that implement webmentions
and microformats as specified by https://indieweb.org/:

- IndieAuth endpoint that allows anyone to log in anonymously
- Micropub endpoint for storing comments and likes to other posts
- Each new comment/like is announced via linkback to the original URL
  (webmention or pingback)

Public instance available on https://commentpara.de/


============
Dependencies
============
* PHP 5.4+
* PEAR's Net_URL
* PEAR's Services_Libravatar
* PEAR2's Services_Linkback
* Twig


=============
About anoweco
=============

Source code
===========
anoweco's source code is available from http://git.cweiske.de/anoweco.git
or the `mirror on github`__.

__ https://github.com/cweiske/anoweco


License
=======
anoweco is licensed under the `AGPL v3 or later`__.

__ http://www.gnu.org/licenses/agpl.html


Author
======
anoweco was written by `Christian Weiske`__.

__ http://cweiske.de/
