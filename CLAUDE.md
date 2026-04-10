# Video Teaser Plugin - Development Guide

This document provides essential context for AI-assisted development and documents the release process.

## Project Overview

**Video Teaser** is a WordPress plugin that creates video teasers with autoplay loop and click-to-play functionality. It supports YouTube, Vimeo, Media Library, and external MP4 sources, powered by the Plyr player.

- **GitHub Repository**: https://github.com/breonwilliams/wp-video-teaser
- **Plugin Slug**: `video_teaser` (directory name, uses underscore)
- **Current Version**: 1.0.6

## Architecture

```
video_teaser/
├── video-teaser.php        # Main plugin file (entry point)
├── includes/
│   ├── class-video-teaser.php    # Core plugin class
│   └── class-updater.php         # GitHub update checker
├── src/
│   ├── ts/                 # TypeScript source
│   │   ├── frontend.ts
│   │   └── admin.ts
│   └── css/                # CSS source
│       ├── frontend.css
│       └── admin.css
├── assets/
│   ├── js/                 # Compiled JS (minified)
│   └── css/                # Compiled CSS (minified)
├── package.json
└── readme.txt              # WordPress readme
```

## Build System

The plugin uses esbuild for bundling TypeScript and CSS.

```bash
# Install dependencies
npm install

# Build all assets (JS + CSS)
npm run build

# Watch mode for development
npm run watch

# TypeScript type checking
npm run typecheck
```

**Build outputs:**
- `src/ts/frontend.ts` → `assets/js/frontend.min.js`
- `src/ts/admin.ts` → `assets/js/admin.min.js`
- `src/css/frontend.css` → `assets/css/frontend.min.css`
- `src/css/admin.css` → `assets/css/admin.min.css`

## GitHub Update System

The plugin updates via GitHub releases, not WordPress.org. Key implementation details:

- **Update checker**: `includes/class-updater.php`
- **Cache transient**: `video_teaser_github_release` (12-hour TTL)
- **API endpoint**: `https://api.github.com/repos/breonwilliams/wp-video-teaser/releases/latest`
- **Tag format**: Must start with `v` (e.g., `v1.0.5`) - the `v` is stripped for version comparison

The updater prefers attached ZIP assets over GitHub's auto-generated zipball to ensure the correct directory name.

---

## Release Process

### Version Locations

Version must be updated in **4 locations** before release:

| File | Location | Format |
|------|----------|--------|
| `video-teaser.php` | Line 6, header comment | `Version: X.Y.Z` |
| `video-teaser.php` | Line 23, constant | `define( 'VIDEO_TEASER_VERSION', 'X.Y.Z' );` |
| `package.json` | `version` field | `"version": "X.Y.Z"` |
| `readme.txt` | Line 6, Stable tag | `Stable tag: X.Y.Z` |

### Release Checklist

#### 1. Pre-Release Verification

```bash
# Run type checking
npm run typecheck

# Build assets
npm run build

# Test the plugin locally
# - Create/edit video teasers
# - Test all video sources
# - Verify frontend display
```

#### 2. Update Version Numbers

Update version in all 4 locations listed above. Use search/replace for consistency.

#### 3. Update Changelog

Add release notes to `readme.txt` under the `== Changelog ==` section:

```
= X.Y.Z =
* Fix: Description of fix
* Added: Description of new feature
* Improved: Description of improvement
```

#### 4. Build and Commit

```bash
# Build production assets
npm run build

# Stage all changes
git add -A

# Commit with release message
git commit -m "Release vX.Y.Z"
```

#### 5. Create Git Tag

```bash
# Create annotated tag (must start with 'v')
git tag -a vX.Y.Z -m "Version X.Y.Z"

# Push commits and tags
git push origin main
git push origin vX.Y.Z
```

#### 6. Create GitHub Release

1. Go to https://github.com/breonwilliams/wp-video-teaser/releases/new
2. Select the tag you just pushed
3. Set release title: `vX.Y.Z`
4. Add release notes (copy from changelog)
5. **Attach ZIP asset** (recommended):
   - Create ZIP of the plugin directory named `video_teaser.zip`
   - Ensure the ZIP extracts to a folder named `video_teaser`
   - Drag and drop to attach
6. Click "Publish release"

### Post-Release Verification

1. **Verify release is live**: Visit https://github.com/breonwilliams/wp-video-teaser/releases/latest

2. **Clear update cache on test site**:
   ```
   /wp-admin/?clear_video_teaser_cache=1
   ```

3. **Test update detection**: Go to Plugins page, verify update notice appears (or doesn't if already current)

4. **Test update installation**: If testing on a site with older version, run the update

5. **Verify no phantom notifications**: After update completes, refresh Plugins page - no update should be shown

---

## Troubleshooting

### Update Not Appearing

The plugin caches GitHub API responses for 12 hours. To force a fresh check:

1. Visit `/wp-admin/?clear_video_teaser_cache=1` (must be logged in as admin)
2. Or wait for cache to expire
3. Or delete transient `video_teaser_github_release` from database

### Phantom Update Notification

If update notification persists after upgrading to latest version:

1. **Verify version match**: Check all 4 version locations match the GitHub release tag
2. **Clear caches**:
   ```
   /wp-admin/?clear_video_teaser_cache=1
   ```
3. **Check transients**: The `post_install` hook should clear caches automatically, but may need manual clearing

This was fixed in v1.0.4 - the updater now clears both `video_teaser_github_release` and `update_plugins` transients after installation.

### Version Mismatch Errors

If versions are inconsistent:
1. Check all 4 version locations
2. Ensure Git tag matches version numbers (e.g., `v1.0.5` for version `1.0.5`)
3. Rebuild assets: `npm run build`

### Wrong Directory Name After Update

If plugin installs to wrong directory (e.g., `wp-video-teaser-main` instead of `video_teaser`):

1. **Use attached ZIP**: Create and attach a properly-named ZIP to the GitHub release
2. The `post_install` hook attempts to fix this, but attached ZIPs are more reliable
3. Manual fix: Rename directory to `video_teaser` and reactivate

### GitHub API Rate Limiting

Unauthenticated requests are limited to 60/hour. If updates stop working:
- Wait for rate limit to reset
- Consider adding authentication token (not currently implemented)

---

## Common Development Tasks

### Adding a New Video Source

1. Update `includes/class-video-teaser.php` - add source handling
2. Update `src/ts/frontend.ts` - add player initialization
3. Update `src/ts/admin.ts` - add admin preview support
4. Run `npm run build`

### Modifying Styles

1. Edit files in `src/css/`
2. Run `npm run build` or use `npm run watch` during development
3. Test both frontend and admin interfaces

### Testing Update System

1. Set a lower version temporarily in all 4 locations
2. Clear cache: `/wp-admin/?clear_video_teaser_cache=1`
3. Check Plugins page for update notification
4. Test the update flow
5. Restore correct version numbers

---

## Quick Reference

```bash
# Development
npm install          # Install dependencies
npm run watch        # Watch mode
npm run build        # Production build
npm run typecheck    # Check TypeScript

# Release
git tag -a vX.Y.Z -m "Version X.Y.Z"
git push origin main && git push origin vX.Y.Z

# Debug
/wp-admin/?clear_video_teaser_cache=1   # Clear update cache
```
