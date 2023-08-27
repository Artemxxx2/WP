<?php

define('AUTOSAVE_INTERVAL', 120);
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'freelancer');
/** Database username */
define('DB_USER', 'root');
/** Database password */
define('DB_PASSWORD', 'root');
/** Database hostname */
define('DB_HOST', 'localhost');
/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');
/** The database collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');
/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', '3#Ad|-gAiKv1U&Y)^;~e4H9x#c>0|Wfh&<><%1PwRca}]_jaX6O2l4V+#,`<OHxY');
define('SECURE_AUTH_KEY', 'q$+X+T$6vzPA^lXLF~U#98>C~Uen=n2]]KS:9vz3CMSiiyi{VqzxLCrPt{if)|V5');
define('LOGGED_IN_KEY', '1x8qdP8h*du=Ifq`jfJgC`<b|,TQD!:rr]/I{gSVU}$}0V}gdCL(OVsCDZu~F3Rx');
define('NONCE_KEY', '&|zw<O&g&B#;lr`bu`u8X1 o3^lIU)MNHx*psSd 2KxRtf+ziR^rjar6bMz[~J<f');
define('AUTH_SALT', 'm+)F+`cXCWL] &AE QL+c2CKY3nlrvNw,rRKiLW^_M.[{#|Hd7nIeIhS;X0U:R*p');
define('SECURE_AUTH_SALT', 'iTrfkp!X25+._]sK+o8^q<Qg.}3p!$3G^_VkE)K2 iun9;HJr}C;N|j)4LpRS3dy');
define('LOGGED_IN_SALT', ',] ^[uGqI|z_>VQjr}$&Th6 Ic? zSBCj5cQ6(Q6!+<m$0*rDj!)=myW*iZdH0F:');
define('NONCE_SALT', 'C2DP?uEftPXJCeQ0f/R:wJ`/YG%w5CIE#)|)2tv@KPzWN3{xgiw{Xr^[nz2m1=-S');
define('WP_CACHE_KEY_SALT', '}{chXDY~Cgs[^Am+A+hBs7d76U$@YBMnx98<(zCCQ]>_ -]3wG>AA0yk w+84v{?');
/**#@-*/
/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';
/* Add any custom values between this line and the "stop editing" line. */
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';