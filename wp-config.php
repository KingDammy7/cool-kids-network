<?php

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpresstest' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

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
define( 'AUTH_KEY',         'LT|n f!q=[%#/Tpf:kFubBqmvvPv8PP(xUb{:/{,Y$g0&51AyXLcw}8BzDr8dX2c' );
define( 'SECURE_AUTH_KEY',  'rQ5Gi8_<r{`zd()pq+7C=5txVxQl]J3nHyPwoKEC?B^}`px+~f*%d&lVYyS2^soa' );
define( 'LOGGED_IN_KEY',    '%Lea4|9dLIBce#yZ2cO/GN1@:5m`FrIUH2MCZN&c7qF:fZAW*c DNa&}la0Us|It' );
define( 'NONCE_KEY',        '9&;:T5Scwi~@kfqiw]v[kLhg`P,|kGachZ360TSfw@50zp&k7Z8,7krBS&`X.Uo)' );
define( 'AUTH_SALT',        'A;gYcgw:sMF)]mv;zOlY>T2%t&s1W$LblrjX0ExQ_bzSpT{bO:o5uHirvc7iv*fh' );
define( 'SECURE_AUTH_SALT', 'nPfCs<ZRVdAYS^[quFE[RB@Qz*g$mI!{7QB?A=Z{[lE0QdJ`t=`bm@x_0NJnEH^K' );
define( 'LOGGED_IN_SALT',   '{{^vhci5za@}zKCbQD57I[)A]+xeMA^g_lY;E#_19z<95jY1~l+`Ie(8L~p_p@+?' );
define( 'NONCE_SALT',       'MuJ!i/I9(#!{qM%M?^2euHM{6A9ks{L]:JN=[<L0Pd{h4@wap.=ar)LC1lDyrrha' );

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
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
