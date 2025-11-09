# WP AI Guardian v1.0

**AI-powered troubleshooter: Detects conflicts, optimizes speed/SEO, auto-fixes in premium. Lightweight & secure.**

## Features

- 🛡️ **Lightweight & Secure** - Only 18.67 KB total plugin size
- 🔍 **AI-Powered Detection** - Integrates with Hugging Face API
- 📊 **Activity Logging** - Custom database table for tracking events
- ⚡ **React Dashboard** - Modern admin interface built with React 18
- 🔐 **Security First** - All forms protected with WordPress nonces
- 🎨 **Beautiful UI** - Responsive design with gradient cards and modern styling

## Requirements

- WordPress 5.0 or higher
- PHP 8.2 or higher
- MySQL 5.6 or higher

## Installation

1. Upload the `wp-ai-guardian` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'WP AI Guardian' in the admin menu
4. Configure your Hugging Face API key in Settings

## Plugin Structure

```
wp-ai-guardian/
├── wp-ai-guardian.php          # Main plugin file (962 bytes)
├── includes/
│   └── class-wpaig-core.php    # Core functionality (10.8 KB)
├── assets/
│   ├── js/
│   │   └── dashboard.js        # React dashboard (4.6 KB)
│   └── css/
│       └── admin.css           # Admin styles (2.3 KB)
└── README.md
```

## Database Schema

**Table:** `wp_ai_guardian_logs`

| Column    | Type         | Description                    |
|-----------|--------------|--------------------------------|
| id        | INT          | Auto-increment primary key     |
| type      | VARCHAR(50)  | Log type (system/settings)     |
| message   | TEXT         | Log message                    |
| timestamp | DATETIME     | Entry timestamp                |

## Plugin Options

- `wpaig_hf_api_key` - Hugging Face API key for AI features
- `wpaig_is_premium` - Premium features toggle (default: false)

## Activation

On activation, the plugin:
1. Creates custom `wp_ai_guardian_logs` table
2. Initializes plugin options
3. Logs activation event

## Deactivation

On deactivation, the plugin:
1. Logs deactivation event
2. Drops custom table
3. Clears all plugin options

## Security Features

- ✅ WordPress nonce verification on all forms
- ✅ AJAX request validation
- ✅ User capability checks (`manage_options`)
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (proper escaping)
- ✅ Direct file access prevention

## Admin Menu

The plugin adds a top-level menu item "WP AI Guardian" with:
- Dashboard overview
- Settings configuration
- Activity logs display
- Premium status indicator

## React Integration

The plugin uses React 18 loaded via CDN:
- `react@18` - Core React library
- `react-dom@18` - React DOM renderer
- Custom dashboard component with hooks

## Usage

1. **Configure Settings:**
   - Enter your Hugging Face API key
   - Enable premium features if available

2. **View Dashboard:**
   - Monitor activity logs
   - Check statistics
   - Review system events

3. **Track Activity:**
   - All plugin actions are logged
   - View recent activity in the dashboard
   - Filter by log type

## Development

### PHP 8.2+ Features Used
- Typed properties (`:void`, `:string`)
- Arrow functions
- Null coalescing operators

### WordPress Best Practices
- Proper hook usage
- Internationalization ready (`__()`, `esc_html_e()`)
- Sanitization and validation
- Enqueue scripts properly

## License

GPL v2 - This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation.

## Author

Your Name

## Changelog

### Version 1.0 (Initial Release)
- Core plugin functionality
- Custom database table
- React-based admin dashboard
- Settings management
- Activity logging
- Security implementations

## Support

For support, please visit the plugin support forum or contact the author.

---

**Total Plugin Size:** 18.67 KB (4 files)  
**WordPress Compatibility:** 5.0+  
**PHP Version:** 8.2+  
**License:** GPL v2
