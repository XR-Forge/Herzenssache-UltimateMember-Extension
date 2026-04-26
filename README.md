# Herzenssache UltimateMember REST API Extension

A comprehensive WordPress plugin that exposes [UltimateMember](https://www.ultimatemember.com/) membership data via a fully-featured REST API. Supports both WordPress nonce and JWT authentication for maximum flexibility.

## Development Notice

This project was developed with the assistance of AI tools. Design, implementation, and documentation were reviewed and refined as part of the development process.

## Features

- **Complete REST API**: All UltimateMember resources (Users, Profiles, Fields, Forms, Submissions, Roles)
- **Flexible Authentication**: WordPress session cookies (automatic), nonce headers, and JWT tokens
- **Admin Dashboard**: Read-only monitoring dashboard with statistics, recent submissions, user activity, and role overview
- **Full CRUD Operations**: Create, read, update, and delete users, manage form submissions, and more
- **Granular Permissions**: Fine-grained capability checks for all endpoints
- **Pagination & Filtering**: Built-in support for paginated results and filtering
- **Error Handling**: Standardized error responses matching the API specification
- **Session Recognition**: Automatically detects logged-in WordPress users via cookies

## Requirements

- **WordPress**: 5.6 or higher
- **PHP**: 7.4 or higher
- **Ultimate Member**: Plugin must be installed and activated

## Installation

1. Download or clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/XR-Forge/Herzenssache-UltimateMember-Extension.git
   ```

2. Navigate to the plugin directory:
   ```bash
   cd Herzenssache-UltimateMember-Extension
   ```

3. Install PHP dependencies (if using Composer):
   ```bash
   composer install
   ```

4. In WordPress admin, go to **Plugins** and activate "Herzenssache UltimateMember REST API Extension"

5. Verify the API is working by visiting:
   - `https://yoursite.com/wp-json/um/v1/` (API root)
   - `https://yoursite.com/wp-json/um/v1/forms` (list forms - public access)

## Configuration

### JWT Authentication (Optional)

To enable JWT token authentication for external applications, add this to your `wp-config.php`:

```php
define( 'HZS_UM_JWT_SECRET', 'your-super-secret-key-change-me' );
```

**Important**: Use a strong, random secret key. You can generate one at [randomkeygen.com](https://randomkeygen.com/).

### JWT Token Endpoints

Get a JWT token for authenticated requests:

```bash
# Get a nonce first (requires WordPress login)
curl -X GET https://yoursite.com/wp-json/um/v1/nonce \
  -H "X-WP-Nonce: YOUR_NONCE"
```

## API Endpoints

### Authentication

#### Get Nonce
```
GET /wp-json/um/v1/nonce
```
Returns a nonce for making authenticated requests.

### Users

#### List Users
```
GET /wp-json/um/v1/users?page=1&per_page=20&role=subscriber&search=john
```

#### Get User
```
GET /wp-json/um/v1/users/{user_id}
```

#### Create User
```
POST /wp-json/um/v1/users
Content-Type: application/json

{
  "username": "newuser",
  "email": "user@example.com",
  "password": "SecurePassword123!",
  "first_name": "John",
  "last_name": "Doe",
  "roles": ["subscriber"],
  "profile_fields": {
    "phone": "555-1234"
  }
}
```

#### Update User
```
PATCH /wp-json/um/v1/users/{user_id}
Content-Type: application/json

{
  "email": "newemail@example.com",
  "display_name": "John Doe Updated",
  "roles": ["subscriber", "um_member"]
}
```

#### Delete User
```
DELETE /wp-json/um/v1/users/{user_id}
```

### Profiles

#### Get Profile
```
GET /wp-json/um/v1/profiles/{user_id}
```
Returns complete profile with custom fields and metadata.

#### Update Profile
```
PATCH /wp-json/um/v1/profiles/{user_id}
Content-Type: application/json

{
  "display_name": "New Display Name",
  "profile_fields": {
    "job_title": "Software Engineer",
    "location": "Berlin"
  },
  "meta": {
    "custom_field": "value"
  }
}
```

### Fields

#### List All Fields
```
GET /wp-json/um/v1/fields
```

#### Get Field Definition
```
GET /wp-json/um/v1/fields/{field_key}
```

#### Update Field Definition
```
PATCH /wp-json/um/v1/fields/{field_key}
Content-Type: application/json

{
  "label": "New Label",
  "required": true,
  "order": 5,
  "config": {
    "placeholder": "Enter value"
  }
}
```

### Forms

#### List Forms
```
GET /wp-json/um/v1/forms
```

#### Get Form
```
GET /wp-json/um/v1/forms/{form_id}
```

#### List Form Submissions
```
GET /wp-json/um/v1/forms/{form_id}/submissions?page=1&per_page=20&status=pending
```

#### Submit Form
```
POST /wp-json/um/v1/forms/{form_id}/submissions
Content-Type: application/json

{
  "user_id": 123,
  "data": {
    "field_name": "field_value",
    "another_field": "another_value"
  }
}
```

#### Get Submission
```
GET /wp-json/um/v1/submissions/{submission_id}
```

#### Delete Submission
```
DELETE /wp-json/um/v1/submissions/{submission_id}
```

### Roles

#### List Roles
```
GET /wp-json/um/v1/roles
```

#### Get Role
```
GET /wp-json/um/v1/roles/{role_id}
```

## Authentication Methods

### WordPress Session Authentication (Recommended)

For logged-in WordPress users, the API automatically recognizes your session via cookies. Simply make requests while logged into WordPress - no additional headers required:

```bash
curl -X GET https://yoursite.com/wp-json/um/v1/users \
  --cookie "wordpress_logged_in=YOUR_SESSION_COOKIE"
```

### WordPress Nonce Authentication

Include the nonce in the `X-WP-Nonce` header for additional security:

```bash
curl -X GET https://yoursite.com/wp-json/um/v1/users \
  -H "X-WP-Nonce: YOUR_NONCE_VALUE"
```

### JWT Bearer Token Authentication (For External Apps)

1. First, enable JWT in wp-config.php (see Configuration section)
2. Include token in the `Authorization` header:

```bash
curl -X GET https://yoursite.com/wp-json/um/v1/users \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

JWT tokens are valid for 7 days from creation.

## Permissions & Capabilities

| Endpoint | Required Capability | Notes |
|----------|-------------------|-------|
| GET /users | `list_users` or `manage_options` | Admins can read all; others see own profile |
| POST /users | `manage_options` | Admins only |
| PATCH /users/{id} | `manage_options` | Admins only |
| DELETE /users/{id} | `manage_options` | Admins only (cannot self-delete) |
| GET /profiles/{id} | User's own profile or `list_users` | |
| PATCH /profiles/{id} | User's own profile or `manage_options` | |
| GET /forms | `list_users` | Logged-in users |
| GET /submissions | `manage_options` | Admins only |
| POST /forms/{id}/submissions | Any authenticated user | |
| DELETE /submissions/{id} | `manage_options` | Admins only |

## Admin Dashboard

Access the monitoring dashboard at:
- WordPress Admin → UM REST API

The dashboard shows:
- **Statistics**: Total users, forms, and roles count
- **Recent Users**: Last 5 registered users with status
- **Recent Submissions**: Latest form submissions across all forms
- **Available Roles**: System roles and their capabilities

## Error Responses

All errors follow this standardized format:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400
  }
}
```

### Common Error Codes

| Code | Status | Meaning |
|------|--------|---------|
| `missing_nonce` | 401 | No nonce provided in X-WP-Nonce header |
| `invalid_nonce` | 401 | Nonce validation failed |
| `invalid_token` | 401 | JWT token is invalid or expired |
| `insufficient_permissions` | 403 | User lacks required capabilities |
| `user_not_found` | 404 | Requested user doesn't exist |
| `form_not_found` | 404 | Requested form doesn't exist |
| `username_exists` | 400 | Username already taken |
| `email_exists` | 400 | Email already in use |
| `weak_password` | 400 | Password doesn't meet minimum requirements |

## Development

### Project Structure

```
src/
├── API/
│   ├── Router.php                    # Main route registration
│   └── Controllers/                  # Endpoint handlers
│       ├── UserController.php
│       ├── ProfileController.php
│       ├── FieldController.php
│       ├── FormController.php
│       ├── RoleController.php
│       └── NonceController.php
├── Auth/
│   ├── NonceValidator.php           # WordPress nonce validation
│   ├── JwtValidator.php             # JWT token validation
│   ├── CapabilityChecker.php        # Permission checks
│   └── RequestValidator.php         # Input validation/sanitization
├── Repository/
│   ├── UserRepository.php           # User CRUD operations
│   ├── ProfileRepository.php        # Profile management
│   ├── FieldRepository.php          # Field definitions
│   ├── FormRepository.php           # Forms and submissions
│   └── RoleRepository.php           # Role metadata
├── Admin/
│   └── Dashboard.php                # Admin dashboard UI
├── Utils/
│   └── ResponseFormatter.php        # API response formatting
├── Autoloader.php                   # PSR-4 autoloader
├── DependencyChecker.php            # Plugin dependency validation
└── Plugin.php                       # Main plugin class
```

### Running Tests

```bash
# Install dev dependencies
composer install

# Run tests
composer test

# Run linter
composer lint

# Fix code style issues
composer lint-fix
```

### Making Changes

1. Follow PSR-4 autoloading conventions
2. Use namespaces: `Herzenssache\UltimateMember\{Module}`
3. Add inline documentation and docblocks
4. Test all endpoints before committing

## Troubleshooting

### API endpoints not found (404)

- Verify the plugin is activated
- Check that WordPress REST API is enabled (default in WP 5.6+)
- Try flushing permalinks: Settings → Permalinks → Save Changes

### Authentication fails

- **For session auth**: Ensure you're logged into WordPress in the same browser/session
- **For nonce auth**: Get a fresh nonce from `/wp-json/um/v1/nonce` while logged in
- **For JWT auth**: Verify `HZS_UM_JWT_SECRET` is defined in wp-config.php
- **401 "unauthenticated" errors**: Try clearing browser cookies or re-logging in
- Check that tokens haven't expired (7-day lifetime for JWT)

### UltimateMember not detected

- Ensure Ultimate Member plugin is installed and activated
- Check plugin dependencies: Admin → Plugins → Look for "Herzenssache UltimateMember REST API Extension"

### Permission denied errors

- **403 "insufficient_permissions"**: Verify user has required capabilities in WordPress
- Admin users (`manage_options`) have full access
- Non-admin users need explicit capabilities assigned
- For `/users` endpoint: Logged-in users can access their own profile data

### Dashboard errors or crashes

- **Fatal errors in admin**: Ensure Ultimate Member plugin is up to date
- **Query errors**: Plugin automatically handles WordPress query compatibility
- **Permission issues**: Admin users should have access to UM REST API dashboard

## License

GPL v2 or later

## Support

For issues, questions, or contributions, please visit:
https://github.com/XR-Forge/Herzenssache-UltimateMember-Extension

## Changelog

### Version 1.0.5-alpha (Bug-Fix Release)

- Fixed authentication issues for REST API endpoints
- Improved session cookie recognition for logged-in users

### Version 1.0.4-alpha (Bug-Fix Release)

- Fixed retrieving user data via REST API
- Enhanced user data access permissions

### Version 1.0.3-alpha (Bug-Fix Release)

- Fixed session management for WordPress REST API
- Improved user data retrieval functionality

### Version 1.0.2-alpha (Bug-Fix Release)

- Fixed session management issues
- Enhanced authentication handling

### Version 1.0.1-alpha (Bug-Fix Release)

- Fixed admin section errors and crashes
- Improved login functionality
- Resolved dashboard compatibility issues

### Version 1.0.0-alpha (Initial Release)

- Initial alpha release with full API implementation
- WordPress nonce and JWT authentication
- Admin monitoring dashboard
- Complete user, profile, form, field, and role management
- Comprehensive error handling and validation
