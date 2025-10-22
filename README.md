# Video Teaser WordPress Plugin

Create engaging YouTube video teasers with autoplay loop and click-to-play functionality for WordPress.

## Overview

Video Teaser allows you to showcase YouTube videos with a clean, professional teaser that automatically loops a specific segment. When users click the play button, they're taken to the full video with controls. Perfect for landing pages, product demos, and marketing content.

## Features

- **🎬 YouTube Video Teasers** - Automatically loop specific video segments
- **🎨 Custom Styling** - Customizable play button and icon colors
- **📱 Responsive Design** - Works perfectly on all devices
- **♿ Accessible** - Full keyboard navigation and screen reader support
- **🚀 Clean & Minimal** - Flat design with no unnecessary visual clutter
- **⚡ Performance Optimized** - Lightweight with production-ready code
- **🌐 Translation Ready** - Full internationalization support

## Installation

1. **Download** the plugin files
2. **Upload** to `/wp-content/plugins/video-teaser/` directory
3. **Activate** the plugin through the 'Plugins' menu in WordPress
4. **Create** your first video teaser!

## Usage

### Creating a Video Teaser

1. Go to **Video Teasers** in your WordPress admin
2. Click **Add New**
3. Enter the **YouTube URL**
4. Set **Start Time** (in seconds) for the teaser segment
5. Set **End Time** (in seconds) for the teaser segment
6. Choose your **Play Button Color**
7. Choose your **Play Icon Color**
8. **Publish** the video teaser

### Displaying Video Teasers

Copy the shortcode from the Video Teaser edit screen and paste it anywhere:

```
[video_teaser id="123"]
```

### Customization Options

- **YouTube URL**: Full YouTube video URL
- **Start Time**: When the teaser segment begins (seconds)
- **End Time**: When the teaser segment ends (seconds)
- **Play Button Color**: Background color of the circular play button
- **Play Icon Color**: Color of the triangle play icon

## Examples

**Product Demo Teaser**
- Start: 30 seconds
- End: 45 seconds
- Creates a 15-second looping demo highlight

**Testimonial Teaser**
- Start: 0 seconds  
- End: 10 seconds
- Shows the first 10 seconds on repeat

## Technical Details

### Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Modern Browser**: For autoplay support

### Browser Compatibility

- ✅ Chrome 66+
- ✅ Firefox 69+
- ✅ Safari 11.1+
- ✅ Edge 79+

### Security Features

- Nonce verification for all form submissions
- Input sanitization and validation
- Capability checks for user permissions
- XSS protection with proper escaping

### Performance Features

- Conditional script loading
- Minimal CSS footprint (~2KB)
- Optimized JavaScript (~3KB)
- No external dependencies

## Development

### File Structure

```
video-teaser/
├── video-teaser.php    # Main plugin file
├── uninstall.php       # Cleanup on uninstall
├── README.md          # Documentation
└── languages/         # Translation files (future)
```

### Hooks & Filters

The plugin follows WordPress coding standards and provides clean, maintainable code.

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Troubleshooting

### Video Not Playing
- Ensure the YouTube URL is valid and public
- Check that autoplay is allowed in your browser
- Verify the video isn't age-restricted

### Teaser Not Looping
- Confirm start time is less than end time
- Check that both times are within the video duration
- Ensure the video allows embedding

### Play Button Not Visible
- Adjust the icon color for better contrast
- Check that both button and icon colors are set
- Verify the shortcode ID is correct

## FAQ

**Q: Can I use this with private YouTube videos?**
A: No, the video must be public and allow embedding.

**Q: Can I customize the button size?**
A: The button size is optimized for all devices (80px desktop, 64px mobile).

**Q: Does this work with YouTube Shorts?**
A: Yes, but the teaser timing may need adjustment.

**Q: Can I use multiple teasers on one page?**
A: Yes, you can use as many teasers as needed.

## Changelog

### 1.0.0
- Initial release
- YouTube video teaser functionality
- Custom color controls
- Responsive design
- Accessibility features
- Production-ready security

## Support

For support questions or feature requests, please visit:
- **GitHub**: [https://github.com/breonwilliams/video-teaser](https://github.com/breonwilliams/video-teaser)
- **Website**: [https://breonwilliams.com](https://breonwilliams.com)

## License

This plugin is licensed under the GPL v2 or later.

```
Video Teaser WordPress Plugin
Copyright (C) 2024 Breon Williams

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

---

Made with ❤️ for the WordPress community