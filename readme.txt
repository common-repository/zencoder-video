=== Zencoder Video ===
Contributors: normanyung
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KKQKE5SX8M3VC
Tags: videos, zencoder, transcoder, transcoding, html5 video, self-hosted video
Requires at least: 3.0.1
Tested up to: 3.8.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow easy integration with Zencoder transcoding service to make HTML5 compatible video files.

== Description ==

Allow easy integration with Zencoder transcoding service to make HTML5 compatible 
video files. At the moment it generates up to 4 different formats:

* mp4 (h.264)
* ogg
* webm
* low-resolution (640x480) mp4. 

These formats are based on Zencoder's HTML5 video job template. If there's 
enough request for additional formats, they could be added in later versions. 
Just didn't want to overcomplicate this initial release.

This plugin strictly sends jobs and receives notifications from Zencoder. It 
does not try to do anything else but it should intergrate nicely with other
plugins such as [Video.js - HTML5 Video Player for WordPress](http://wordpress.org/plugins/videojs-html5-video-player-for-wordpress/).

== Installation ==
1. Upload the `zencodervideo` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin in **Settings > Zencoder Video Options**
4. Create new Zencoder jobs in the new Zencoder Video section.

== Frequently Asked Questions ==

= Do I need a Zencoder account to use this plugin? =

Yes. You will need a "Full Access API Key" from Zencoder to use their service.

= What output destinations do you support? =

The plugin can use anything listed in [Zencoder's Output Documentation](https://app.zencoder.com/docs/api/encoding/general-output-settings/base-url) in conjuction with Zencoder's [saved credentials](https://app.zencoder.com/docs/api/encoding/general-output-settings/credentials).

= How can I resend a job request to Zencoder without re-uploading? =

If you have already uploaded your file to your site and it's still on the server, just use the full URL to the file as the Video Source instead of uploading the file.

== Screenshots ==

1. Plugin configuration page.
2. Video listing page.
3. Individual video edit.

== Upgrade Notice ==

= 0.1.2 =
Allows use of WP_UPLOAD_DIR and WP_UPLOAD_URL tokens in base_url and public url.

= 0.1.1 =
Fixes bug where all notifications were sent to zencoder fetcher instead of proper endpoint.

= 0.1 =
This is the initial release.

== Changelog ==

= 0.1.2 =
Allows use of WP_UPLOAD_DIR and WP_UPLOAD_URL tokens in base_url and public url.

= 0.1.1 =
* accidentally left a debug flag on in zencodervideo.php which sent all notifications to zencoderfetcher.

= 0.1 =
* initial release.
