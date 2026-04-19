# Herzenssache UltimateMember REST API - Example Client

This file demonstrates how to interact with the REST API using PHP.

## Example 1: Get a Nonce (For Logged-In Users)

```php
<?php

// Get nonce while logged in
$response = wp_remote_get(
    rest_url( 'um/v1/nonce' )
);

if ( is_wp_error( $response ) ) {
    echo 'Error: ' . $response->get_error_message();
    return;
}

$body = json_decode( wp_remote_retrieve_body( $response ), true );
$nonce = $body['nonce'];

echo 'Nonce: ' . $nonce;
?>
```

## Example 2: List Users with Nonce Authentication

```php
<?php

$nonce = 'your_nonce_here'; // Get from above

$response = wp_remote_get(
    rest_url( 'um/v1/users' ),
    array(
        'headers' => array(
            'X-WP-Nonce' => $nonce,
        ),
    )
);

$users = json_decode( wp_remote_retrieve_body( $response ), true );

foreach ( $users['users'] as $user ) {
    echo $user['username'] . ' (' . $user['email'] . ')' . PHP_EOL;
}
?>
```

## Example 3: Create a User

```php
<?php

$nonce = 'your_nonce_here';

$response = wp_remote_post(
    rest_url( 'um/v1/users' ),
    array(
        'headers' => array(
            'X-WP-Nonce' => $nonce,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode( array(
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'SecurePassword123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'roles' => array( 'subscriber' ),
        ) ),
    )
);

$user = json_decode( wp_remote_retrieve_body( $response ), true );

echo 'Created user: ' . $user['id'];
?>
```

## Example 4: Update a User Profile

```php
<?php

$nonce = 'your_nonce_here';
$user_id = 123;

$response = wp_remote_request(
    rest_url( 'um/v1/profiles/' . $user_id ),
    array(
        'method' => 'PATCH',
        'headers' => array(
            'X-WP-Nonce' => $nonce,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode( array(
            'display_name' => 'John Doe',
            'profile_fields' => array(
                'job_title' => 'Software Engineer',
                'location' => 'Berlin',
            ),
        ) ),
    )
);

$profile = json_decode( wp_remote_retrieve_body( $response ), true );

echo 'Profile updated for user: ' . $profile['username'];
?>
```

## Example 5: Submit a Form

```php
<?php

$nonce = 'your_nonce_here';
$form_id = 7;

$response = wp_remote_post(
    rest_url( 'um/v1/forms/' . $form_id . '/submissions' ),
    array(
        'headers' => array(
            'X-WP-Nonce' => $nonce,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode( array(
            'user_id' => 123,
            'data' => array(
                'field_name' => 'Some value',
                'another_field' => 'Another value',
            ),
        ) ),
    )
);

$submission = json_decode( wp_remote_retrieve_body( $response ), true );

echo 'Submission created: ' . $submission['id'];
?>
```

## Example 6: Using JWT Token Authentication

For external applications, first configure JWT in wp-config.php:

```php
define( 'HZS_UM_JWT_SECRET', 'your-secret-key' );
```

Then authenticate with JWT:

```php
<?php

// This example uses a JWT library. Install via Composer:
// composer require firebase/php-jwt

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Create a token (do this once, then store/cache it)
$secret = 'your-secret-key'; // Must match HZS_UM_JWT_SECRET
$payload = array(
    'iat' => time(),
    'exp' => time() + 604800, // 7 days
    'iss' => 'https://yoursite.com',
    'custom' => 'data',
);

$token = JWT::encode( $payload, $secret, 'HS256' );

// Use the token in requests
$response = wp_remote_get(
    rest_url( 'um/v1/users' ),
    array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
        ),
    )
);

$users = json_decode( wp_remote_retrieve_body( $response ), true );
?>
```

## Example 7: Get Form Submissions

```php
<?php

$nonce = 'your_nonce_here';
$form_id = 7;

$response = wp_remote_get(
    rest_url( 'um/v1/forms/' . $form_id . '/submissions' ) . '?page=1&per_page=20&status=pending',
    array(
        'headers' => array(
            'X-WP-Nonce' => $nonce,
        ),
    )
);

$result = json_decode( wp_remote_retrieve_body( $response ), true );

echo 'Total submissions: ' . $result['total'] . PHP_EOL;

foreach ( $result['submissions'] as $submission ) {
    echo 'Submission #' . $submission['id'] . ' - Status: ' . $submission['status'] . PHP_EOL;
}
?>
```

## Example 8: Error Handling

```php
<?php

$nonce = 'invalid_nonce';

$response = wp_remote_get(
    rest_url( 'um/v1/users' ),
    array(
        'headers' => array(
            'X-WP-Nonce' => $nonce,
        ),
    )
);

$status = wp_remote_retrieve_response_code( $response );
$body = json_decode( wp_remote_retrieve_body( $response ), true );

if ( 200 !== $status ) {
    echo 'Error: ' . $body['message'] . PHP_EOL;
    echo 'Code: ' . $body['code'] . PHP_EOL;
} else {
    // Process successful response
}
?>
```

## Notes

- All `Content-Type` should be `application/json` for POST/PATCH requests
- Nonces are valid for 24 hours by default
- JWT tokens are valid for 7 days
- Always validate and sanitize user input before sending to the API
- Use HTTPS in production to protect nonces and JWT tokens
- Cache nonces/tokens where appropriate to minimize API calls
