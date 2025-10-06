# Collection Bulk Edit Support

A WordPress plugin that enables bulk editing functionality for collections in the WordPress admin panel.

## Features
- Bulk edit collection items
- Quick edit multiple items simultaneously  
- Sort and filter collection entries
- Batch update capabilities
- Compatible with standard WordPress collections

## Requirements
- WordPress 5.0+
- PHP 7.4+

## Installation

1. Upload the plugin files to `/wp-content/plugins/collection-bulk-edit-support`
2. Activate through WordPress admin panel
3. Navigate to Collections to access bulk edit features

## Usage

### Bulk Edit Mode
1. Go to Collections list view
2. Select multiple items using checkboxes
3. Choose "Bulk Edit" from bulk actions dropdown
4. Make changes and apply to all selected items

### Quick Edit Features
- Edit multiple fields at once
- Update taxonomies in bulk
- Change status for multiple items
- Set featured images for multiple entries

## Actions & Filters

```php
// Hook into bulk edit process
add_action('collection_bulk_edit_save', 'your_function');

// Filter bulk editable fields
add_filter('collection_bulk_edit_fields', 'your_function');
```

## Support
For bugs and feature requests, please contact plugin developer.

## License
GPL v2 or later

---
*Note: This plugin is meant to enhance WordPress collection management capabilities and streamline bulk content updates.*