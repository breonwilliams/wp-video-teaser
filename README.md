# Video Teaser WordPress Plugin

Create engaging video teasers with autoplay loop and click-to-play functionality for WordPress. Supports YouTube, Vimeo, Media Library, and external MP4 sources. Powered by [Plyr](https://plyr.io/).

## Overview

Video Teaser lets you showcase videos from multiple sources with a clean, professional teaser that automatically loops a specific segment. When users click the play button, the full video plays with controls. Teaser mode is optional — you can also display a standard video player. When multiple video teasers are on a page, only one plays at a time.

## Features

- **Multi-Source Support** — YouTube, Vimeo, Media Library (uploaded), and external MP4 URLs
- **Plyr Player** — Consistent, accessible video player across all sources
- **Optional Teaser Mode** — Toggle teaser looping on or off per video
- **Admin Preview** — Live video preview in the editor while configuring
- **Single Active Video** — Only one video plays at a time when multiple teasers are on a page
- **Custom Play Button Color** — Color picker for the play button overlay
- **Responsive Design** — Works on all devices
- **Accessible** — Keyboard navigation and screen reader support
- **Translation Ready** — Full internationalization support

## Installation

1. **Download** the plugin files
2. **Upload** to `/wp-content/plugins/video-teaser/` directory
3. **Activate** the plugin through the 'Plugins' menu in WordPress
4. **Create** your first video teaser

## Usage

### Creating a Video Teaser

1. Go to **Video Teasers** in your WordPress admin
2. Click **Add New**
3. Select a **Video Source** tab: YouTube, Vimeo, Media Library, or External MP4
4. Enter the URL or select a media file
5. Optionally enable **Teaser Mode** and set start/end times for the looping segment
6. Choose a **Play Button Color** using the color picker
7. **Publish** the video teaser

### Displaying Video Teasers

Copy the shortcode from the Video Teaser edit screen and paste it anywhere:

```
[video_teaser id="123"]
```

### Video Source Options

- **YouTube** — Paste a YouTube video URL
- **Vimeo** — Paste a Vimeo video URL
- **Media Library** — Select a video from your WordPress media library
- **External MP4** — Paste a direct URL to an MP4 file

### Teaser Settings

- **Enable Teaser** — Toggle teaser looping on or off
- **Start Time** — When the teaser segment begins (seconds)
- **End Time** — When the teaser segment ends (seconds)

When teaser mode is off, the video displays as a standard Plyr player.

## Technical Details

### Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Modern Browser**: For autoplay support

### Browser Compatibility

- Chrome 66+
- Firefox 69+
- Safari 11.1+
- Edge 79+

### Security Features

- Nonce verification for all form submissions
- Input sanitization and validation
- Capability checks for user permissions
- XSS protection with proper escaping

### Performance

- Conditional script loading (assets only enqueued when shortcode is present)
- Plyr loaded from bundled vendor files (no external CDN calls)

### Dependencies

- [Plyr](https://plyr.io/) — bundled in `assets/vendor/`

## File Structure

```
video-teaser/
├── video-teaser.php          # Main plugin file
├── uninstall.php             # Cleanup on uninstall
├── README.md                 # Documentation
├── includes/
│   ├── class-video-teaser.php    # Core bootstrap class
│   ├── class-post-type.php       # Custom post type registration
│   ├── class-meta-boxes.php      # Admin meta boxes & preview
│   ├── class-shortcode.php       # [video_teaser] shortcode
│   ├── class-assets.php          # Script/style enqueuing
│   └── class-video-source.php    # Video source handling
└── assets/
    ├── css/
    │   ├── admin.css             # Admin editor styles
    │   └── frontend.css          # Frontend player styles
    ├── js/
    │   ├── admin.js              # Admin preview & meta box logic
    │   └── frontend.js           # Frontend player & teaser logic
    └── vendor/
        ├── plyr.min.js           # Plyr player library
        └── plyr.css              # Plyr player styles
```

## Troubleshooting

### Video Not Playing
- Ensure the video URL is valid and publicly accessible
- Check that autoplay is allowed in your browser
- For YouTube/Vimeo, verify the video allows embedding

### Teaser Not Looping
- Confirm teaser mode is enabled
- Confirm start time is less than end time
- Check that both times are within the video duration

### Media Library Video Not Loading
- Verify the uploaded file is a supported video format (MP4 recommended)
- Check that the file hasn't been deleted from the media library

### External MP4 Not Playing
- Verify the URL points directly to an MP4 file
- Check that the server hosting the file allows cross-origin requests (CORS)

### Play Button Not Visible
- Adjust the play button color for better contrast against your video
- Verify the shortcode ID is correct

## FAQ

**Q: What video sources are supported?**
A: YouTube, Vimeo, WordPress Media Library uploads, and direct external MP4 URLs.

**Q: Can I use this with private YouTube or Vimeo videos?**
A: No, the video must be public and allow embedding.

**Q: Is teaser mode required?**
A: No. Teaser mode is optional. With it disabled, the video displays as a standard player.

**Q: Can I use multiple teasers on one page?**
A: Yes. Only one video will play at a time — starting a new video pauses the others.

**Q: Does this work with YouTube Shorts?**
A: Yes, but the teaser timing may need adjustment.

**Q: Can I customize the button size?**
A: The button size is optimized for all devices (80px desktop, 64px mobile).

## Changelog

### 2.1.1
- Fixed admin preview Plyr button hover state to match frontend
- Changed video source options from radio buttons to dropdown for compactness

### 2.1.0
- Added configurable aspect ratio setting (16:9, 4:3, 21:9, Auto)
- Added GitHub-based auto-updater for seamless plugin updates

### 2.0.0
- Complete rewrite with modular class-based architecture
- Added Vimeo, Media Library, and external MP4 source support
- Integrated Plyr player for consistent playback across all sources
- Teaser mode is now optional (toggle per video)
- Added live admin preview
- Consolidated play button and icon color into a single button color picker
- Single active video enforcement when multiple teasers are on a page
- Moved assets into `assets/` directory with separate admin/frontend files
- Extracted logic into `includes/` classes

### 1.0.0
- Initial release
- YouTube video teaser functionality
- Custom color controls
- Responsive design
- Accessibility features

## Support

For support questions or feature requests, please visit:
- **GitHub**: [https://github.com/breonwilliams/video-teaser](https://github.com/breonwilliams/video-teaser)
- **Website**: [https://breonwilliams.com](https://breonwilliams.com)

## License

This plugin is licensed under the GPL v2 or later.

```
Video Teaser WordPress Plugin
Copyright (C) 2026 Breon Williams

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

Developed by **Breon Williams** - [breonwilliams.com](https://breonwilliams.com)
