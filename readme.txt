=== Video Teaser ===
Contributors: breonwilliams
Tags: video, teaser, youtube, vimeo, plyr
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create engaging video teasers with autoplay loop and click-to-play functionality.

== Description ==

Video Teaser lets you showcase videos from multiple sources with a clean, professional teaser that automatically loops a specific segment. When users click the play button, the full video plays with controls. Teaser mode is optional — you can also display a standard video player. When multiple video teasers are on a page, only one plays at a time.

= Features =

* **Multi-Source Support** — YouTube, Vimeo, Media Library (uploaded), and external MP4 URLs
* **Plyr Player** — Consistent, accessible video player across all sources
* **Optional Teaser Mode** — Toggle teaser looping on or off per video
* **Admin Preview** — Live video preview in the editor while configuring
* **Single Active Video** — Only one video plays at a time when multiple teasers are on a page
* **Custom Play Button Color** — Color picker for the play button overlay
* **Responsive Design** — Works on all devices
* **Accessible** — Keyboard navigation and screen reader support
* **Translation Ready** — Full internationalization support

= Video Source Options =

* **YouTube** — Paste a YouTube video URL
* **Vimeo** — Paste a Vimeo video URL
* **Media Library** — Select a video from your WordPress media library
* **External MP4** — Paste a direct URL to an MP4 file

== Installation ==

1. Download the plugin files
2. Upload to `/wp-content/plugins/video_teaser/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Create your first video teaser

== Usage ==

= Creating a Video Teaser =

1. Go to **Video Teasers** in your WordPress admin
2. Click **Add New**
3. Select a **Video Source** tab: YouTube, Vimeo, Media Library, or External MP4
4. Enter the URL or select a media file
5. Optionally enable **Teaser Mode** and set start/end times for the looping segment
6. Choose a **Play Button Color** using the color picker
7. **Publish** the video teaser

= Displaying Video Teasers =

Copy the shortcode from the Video Teaser edit screen and paste it anywhere:

`[video_teaser id="123"]`

== Frequently Asked Questions ==

= What video sources are supported? =

YouTube, Vimeo, WordPress Media Library uploads, and direct external MP4 URLs.

= Can I use this with private YouTube or Vimeo videos? =

No, the video must be public and allow embedding.

= Is teaser mode required? =

No. Teaser mode is optional. With it disabled, the video displays as a standard player.

= Can I use multiple teasers on one page? =

Yes. Only one video will play at a time — starting a new video pauses the others.

= Does this work with YouTube Shorts? =

Yes, but the teaser timing may need adjustment.

== Screenshots ==

1. Video Teaser admin editor with live preview
2. Frontend video teaser with play button overlay
3. Video source selection options

== Changelog ==

= 1.0.1 =
* Added: Loading spinner for videos without poster images
* Added: New aspect ratio options (1:1 Square, 9:16 Vertical)
* Security: Improved input sanitization with wp_unslash and sanitize_text_field
* Improved: Media attachment validation with graceful fallback
* Improved: Frontend and admin code refinements

= 1.0.0 =
* Initial release
* YouTube, Vimeo, Media Library, and external MP4 support
* Plyr player integration
* Optional teaser mode with customizable loop timing
* Live admin preview
* Custom play button color
* Responsive design
* Accessibility features

== Upgrade Notice ==

= 1.0.0 =
Initial release of Video Teaser plugin.
