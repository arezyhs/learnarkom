<?php
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
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'learnarkom_db' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         'vP-NvO%&P-%#XI$Po8yJ-ciyh*B#].R~FFoZGW#J>:.Ai@Adwxt!PsuJ@<ywq]5I' );
define( 'SECURE_AUTH_KEY',  'sz]r{F=O`9stMOangaKMf}E|Bu*u8mK(FC|6Riq4V?`@Vv/qVv&Kox0c<|]HI#r ' );
define( 'LOGGED_IN_KEY',    'sZg+8DX8x@[.FA2X$.m+>4E8$}G(tdrnX+5nQ{gb<#$DkbxpNeyk+kZQxfHR!=rc' );
define( 'NONCE_KEY',        'oV5W1E{p&Bi-#!@Uvfl&Q-T/n=e-@A8VC)4!$;Tb;WX2<a8ckUGbTsrV[j.J(^$E' );
define( 'AUTH_SALT',        'bfJA% SXfslf#n~#2|BhH(*},B):uW[j<~Y7uu&#35S],dL>@>4/(>E,Ag.AY-<C' );
define( 'SECURE_AUTH_SALT', '5A[Q@44qhUcXhN+KVs5?Jt7 =+(aMyj$JR$$c2H9]`V4snir4Vj_y8Uq+@L&Kr?j' );
define( 'LOGGED_IN_SALT',   '}h;[c5qu3Dhz/!L~nUIE@4&L}j[qb}r8%,sM3^D^6*w@ro?~kWa&vl(97%hCTnI}' );
define( 'NONCE_SALT',       'd=B_iV+>AeCbT%Jj9u?%mFGJcN]`4sC!#oRZFzej/9P>-~F<DpKKxGG!kRe<C{c(' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
