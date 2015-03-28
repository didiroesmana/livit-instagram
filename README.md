# livit-instagram-test
wordpress plugin to show instagram post

Use this shortcode to display image: 
[instalivit user="user1,user2" hashtag="hashtag1,hashtag2"]

======================
Installation
======================
1. Unzip and Upload all files to a sub directory in "/wp-content/plugins/".
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Enter client id and client secret in Livit Instagram Settings, follow the instructions for creating your own client for Instagram.
4. Add [instalivit user="user1,user2" hashtag="hashtag1,hashtag2"] shortcode in post/page

=======================
FEATURE
=======================
1. Fetch Multiple User with hashtag
2. If there's no matching media with hashtag , it will show 20 current post from user
3. Rating System 
4. Comment System 

=======================
Plugins used
=======================
1. Masonry https://github.com/desandro/masonry
2. JQuery WebRating https://github.com/PiyushRamuka/jquery.WebRating
3. Instagram PHP API V2 https://github.com/cosenary/Instagram-PHP-API

=======================
TODO
=======================
1. Refactoring
2. Add Multisite support
3. Add fecth media with hashtag
4. Add fetch all media from user (pagination)