# Admin Custom Columns

A comprehensive WordPress plugin that enhances the admin interface with custom columns, taxonomy filtering, quick edit enhancements, and bulk editing capabilities.

## Description

Admin Custom Columns transforms your WordPress admin experience by adding powerful column management and editing features. The plugin consists of two main components that work together to provide extensive customization options for post, page, and custom post type list tables.

### Core Components

1. **Taxonomy Columns & Filtering** - Automatically adds taxonomy columns with dropdown filters
2. **Enhanced Columns Suite** - Adds author, thumbnail, date, menu order, and other specialized columns
3. **Quick Edit Enhancements** - Extends quick edit with excerpt and custom field support
4. **Bulk Edit for Media** - Advanced bulk editing capabilities for media library
5. **Meta Field Columns** - Display custom meta fields as sortable columns

## Features

### 🏷️ Taxonomy Management
- **Automatic Taxonomy Columns**: Displays all registered taxonomies as columns in admin list tables
- **Smart Filtering**: Hierarchical dropdown filters for taxonomy-based content filtering
- **Custom Post Type Support**: Works seamlessly with all post types and custom taxonomies
- **Admin Settings Page**: Granular control over which taxonomy columns to display per post type
- **Term Linking**: Clickable taxonomy terms that filter the current view

### 📊 Enhanced Admin Columns

#### Standard Columns
- **Author Column**: Shows post author with user display name
- **Thumbnail Column**: Displays featured images or post thumbnails in list view
- **Modified Date Column**: Shows last modified date and time
- **Menu Order Column**: Displays and allows sorting by menu order (for hierarchical post types)
- **Trashed Date Column**: Shows deletion date for items in trash

#### Specialized Columns
- **Hierarchical Parent Column**: Parent page/post selector for hierarchical content types
- **Date Filtering**: Enhanced date filtering with month/year dropdowns
- **Meta Field Columns**: Display custom post meta as sortable columns

### ⚡ Quick Edit & Bulk Operations

#### Quick Edit Enhancements
- **Excerpt Support**: Edit post excerpts directly from quick edit
- **Custom Field Editing**: Quick edit support for registered custom fields
- **Inline Editing**: Fast editing without leaving the list view

#### Media Library Enhancements
- **Bulk Taxonomy Editing**: Assign taxonomies to multiple media items at once
- **Inline Media Editing**: Quick editing of media metadata
- **Enhanced Media Columns**: Additional columns for media management

### 🎛️ Administration Features

#### Settings & Configuration
- **Per-Post-Type Control**: Enable/disable columns individually for each post type
- **Taxonomy Toggle**: Choose which taxonomies appear as columns
- **User-Friendly Interface**: Intuitive settings page with checkboxes
- **Screen Options Integration**: Hide/show columns via WordPress Screen Options

#### Performance & Compatibility
- **Optimized Queries**: Efficient database queries for better performance
- **WordPress Standards**: Fully compliant with WordPress coding standards
- **Theme Compatibility**: Works with all themes and page builders
- **Plugin Integration**: Compatible with popular plugins and custom fields

## Installation

1. Download the plugin files
2. Upload the `admin-custom-columns` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin dashboard
4. Configure column display via **Settings > Custom columns**
5. Individual column features activate automatically

## Configuration

### Taxonomy Columns Setup
1. Navigate to **Settings > Custom columns** in your WordPress admin
2. Select post types from the tabs
3. Check/uncheck taxonomies to show as columns
4. Save changes

### Column Management
- Use **Screen Options** to hide/show individual columns
- Drag column headers to reorder (where supported)
- Click column headers to sort (for sortable columns)

## Usage Guide

### Working with Taxonomy Columns

#### Viewing Taxonomy Data
- Taxonomy terms appear as comma-separated links in their respective columns
- Empty taxonomies show "—" (em dash)
- Multiple terms are displayed with proper linking

#### Filtering Content
- Use dropdown filters above the list table for hierarchical taxonomies
- Combine filters with search and other WordPress filters
- Filter links in columns provide quick navigation

### Enhanced Columns Features

#### Thumbnail Column
- Displays featured images at 60x60px
- Shows placeholder for posts without thumbnails
- Click thumbnails to open attachment in new tab

#### Date Columns
- **Published Date**: Standard WordPress publish date
- **Modified Date**: Last modification timestamp
- **Trashed Date**: Deletion timestamp (trash view only)

#### Menu Order Column
- Shows numerical order for hierarchical content
- Sortable for custom ordering
- Only appears for post types supporting page attributes

### Quick Edit Enhancements

#### Excerpt Editing
- Edit post excerpts without opening full editor
- Supports all post types with excerpt support
- Real-time preview in quick edit form

#### Custom Field Editing
- Quick edit support for registered meta fields
- Bulk editing capabilities for custom fields
- Integration with popular custom field plugins

### Media Library Features

#### Bulk Taxonomy Assignment
- Select multiple media items
- Assign taxonomies to all selected items
- Bulk remove taxonomy terms

#### Enhanced Media Columns
- Additional metadata columns
- Sortable columns for better organization
- Quick access to media information

## Screenshots

### Settings Page
![Settings Page](screenshots/settings-page.png)
*Configure which taxonomy columns appear for each post type*

### Taxonomy Columns in Action
![Taxonomy Columns](screenshots/taxonomy-columns.png)
*View taxonomy terms directly in post list tables*

### Enhanced Quick Edit
![Quick Edit](screenshots/quick-edit.png)
*Extended quick edit with excerpt and custom field support*

### Media Bulk Edit
![Media Bulk Edit](screenshots/media-bulk-edit.png)
*Advanced bulk editing for media library items*

## Frequently Asked Questions

### Does this plugin work with custom post types?
Yes! The plugin automatically detects and supports all registered post types and their associated taxonomies.

### Can I control which taxonomies appear as columns?
Absolutely. Use the settings page to enable/disable specific taxonomy columns for each post type individually.

### Does it affect website performance?
The plugin is optimized for performance with efficient database queries and proper caching mechanisms.

### What taxonomies are supported?
All public taxonomies with `show_ui` enabled are supported, including built-in taxonomies (categories, tags) and custom taxonomies.

### Can I sort by custom columns?
Yes, sortable columns include: date, modified date, menu order, trashed date, and custom meta fields (when configured).

### Does it work with custom fields plugins?
Yes, the plugin integrates with popular custom fields solutions and provides column display and editing capabilities.

### Can I hide specific columns?
Yes, use WordPress Screen Options to hide/show individual columns, or disable them entirely in the settings.

### Is it compatible with page builders?
Yes, the plugin works with all themes and page builders as it only affects the admin interface.

### Does it support multisite?
Yes, the plugin works in both single-site and multisite WordPress installations.

### Can I customize the column display?
The plugin provides extensive customization options through its settings page and follows WordPress standards for theming.

## Technical Details

### Requirements
- WordPress 4.0 or higher
- PHP 5.6 or higher
- MySQL 5.0 or higher

### Browser Support
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

### File Structure
```
admin-custom-columns/
├── admin-custom-columns.php      # Main taxonomy columns plugin
├── admin-columns.php             # Enhanced columns suite
├── admin-columns/
│   ├── author-column.php         # Author display column
│   ├── custom-columns.php        # Taxonomy column management
│   ├── date-column.php           # Date filtering enhancements
│   ├── excerpt-quickedit.php     # Excerpt in quick edit
│   ├── media_inline_bulk_edit.php # Media bulk editing
│   ├── menu-order-column.php     # Menu order column
│   ├── modified-column.php       # Last modified date column
│   ├── post-hierarchical-column.php # Parent selector column
│   ├── post_meta_columns.class.php # Custom meta columns
│   ├── thumbnail-column.php      # Thumbnail display column
│   └── trashed-column.php        # Trashed date column
├── LICENSE                       # GPL v2 license
└── README.md                     # This file
```

### Hooks & Filters

The plugin provides several hooks for developers:

#### Actions
- `admin_custom_columns_init` - Fires when taxonomy columns are initialized
- `admin_columns_init` - Fires when enhanced columns are initialized

#### Filters
- `admin_custom_columns_taxonomies` - Modify supported taxonomies
- `admin_columns_default` - Modify default column settings
- `admin_columns_sortable` - Control sortable columns

### Database Usage
- Stores settings in `wp_options` table under `custom_columns` key
- No additional database tables required
- Settings are automatically cleaned up on uninstall

## Changelog

### Version 1.5 (Latest)
- **Enhanced Columns Suite**: Complete rewrite with modern PHP standards
- **Media Bulk Edit**: Advanced bulk editing for media library
- **Quick Edit Improvements**: Extended excerpt and custom field support
- **Performance Optimizations**: Improved query efficiency and caching
- **WordPress Standards**: Full compliance with WP coding standards

### Version 1.1
- **Taxonomy Columns**: Core taxonomy column functionality
- **Admin Settings**: User-friendly configuration interface
- **Filtering System**: Dropdown filters for taxonomy terms
- **Custom Post Type Support**: Extended compatibility

### Version 1.0
- Initial release with basic taxonomy column support

## Contributing

We welcome contributions! Here's how you can help:

### Development Setup
1. Fork the repository
2. Clone your fork: `git clone https://github.com/yourusername/admin-custom-columns.git`
3. Install development dependencies
4. Create a feature branch: `git checkout -b feature/your-feature`

### Coding Standards
- Follow WordPress Coding Standards
- Use PHP 5.6+ compatible syntax
- Include PHPDoc comments for all functions
- Test with multiple WordPress versions

### Testing
- Test with different post types and taxonomies
- Verify compatibility with popular plugins
- Check performance impact on large datasets

### Pull Request Process
1. Update documentation for new features
2. Add tests for new functionality
3. Ensure backward compatibility
4. Submit PR with clear description

## Support

### Documentation
- [Installation Guide](docs/installation.md)
- [Configuration Guide](docs/configuration.md)
- [Developer API](docs/api.md)

### Community Support
- [WordPress.org Support Forum](https://wordpress.org/support/plugin/admin-custom-columns/)
- [GitHub Issues](https://github.com/lophas/admin-custom-columns/issues)

### Professional Support
For custom development or priority support, contact the author.

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024 Attila Seres

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
```

## Credits

- **Author**: Attila Seres
- **Author URI**: https://lophas.github.io
- **Plugin URI**: https://github.com/lophas/admin-custom-columns

### Contributors
- Community contributors and testers

### Acknowledgments
- WordPress Core Team for the excellent platform
- WordPress Community for inspiration and support

---

**Admin Custom Columns** is not affiliated with or endorsed by WordPress.org or Automattic.