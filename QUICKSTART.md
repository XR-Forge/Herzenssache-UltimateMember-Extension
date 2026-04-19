# Quick Start Guide

## Installation

1. **Download the plugin:**
   ```bash
   cd wp-content/plugins
   git clone https://github.com/XR-Forge/Herzenssache-UltimateMember-Extension.git
   cd Herzenssache-UltimateMember-Extension
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Activate the plugin:**
   - Go to WordPress Admin → Plugins
   - Find "Herzenssache UltimateMember REST API Extension"
   - Click "Activate"

## Verification

### Test the API is Working

```bash
# Test public endpoint (no auth required)
curl https://yoursite.com/wp-json/um/v1/forms

# You should get a JSON response with forms list
```

### Access the Admin Dashboard

- WordPress Admin → UM REST API
- View statistics, recent users, submissions, and roles

## Enable JWT Authentication (Optional)

Add to `wp-config.php`:
```php
define( 'HZS_UM_JWT_SECRET', 'generate-a-strong-random-secret' );
```

## Next Steps

1. **Read the Full Documentation**: See [README.md](README.md)
2. **Review API Examples**: See [EXAMPLES.md](EXAMPLES.md)
3. **Test Endpoints**: Use cURL or Postman to test the API
4. **Configure Permissions**: Ensure WordPress roles have appropriate capabilities
5. **Enable Logging**: Add `define( 'WP_DEBUG_LOG', true );` to wp-config.php for debugging

## Common Issues

**Plugin not appearing after activation?**
- Check that Ultimate Member is installed and activated
- Verify WordPress version is 5.6+
- Check PHP version is 7.4+

**Can't access API endpoints?**
- Verify plugin is activated
- Try flushing permalinks: Settings → Permalinks → Save
- Check with `curl https://yoursite.com/wp-json/um/v1/forms`

**Nonce validation fails?**
- Get a fresh nonce from `/wp-json/um/v1/nonce`
- Ensure user is logged in when requesting nonce

## Support

For issues or questions:
- Check [README.md](README.md) troubleshooting section
- Review [EXAMPLES.md](EXAMPLES.md) for usage patterns
- Visit: https://github.com/XR-Forge/Herzenssache-UltimateMember-Extension
