<?php
define( 'WP_CACHE', false ); // By Speed Optimizer by SiteGround

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
define( 'DB_NAME', 'dbywuoszkaxwjt' );

/** Database username */
define( 'DB_USER', 'urhgsgyruysgz' );

/** Database password */
define( 'DB_PASSWORD', 'pcyjeilfextq' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'E?ON#HekHx_-jL!PuJ`rp4%2b%_i2|BdU-qfCr0j~v9G?C[O.>2)@4H(T(Dx(f8T' );
define( 'SECURE_AUTH_KEY',   'MfrqbsSbIHyr|qasFXa84}%tn*>s^XT%?&/v2rQ%s8(hEypY+N&~gbg1}X?!<>q[' );
define( 'LOGGED_IN_KEY',     '*5R7V6*|i9Lc,`$qSJ?3wq8*,)$B_Z>q^J3IN=#)1 T:Hbv,NvvE9jsn +I,Y-K@' );
define( 'NONCE_KEY',         '=+~|s^v~%(ia)3f63]j=x,~oG?r)u(FYd=(9xt}!aaL;(qOQlYXPa[0c>%^~[aqJ' );
define( 'AUTH_SALT',         'V>PPeF[g<E0.|:=*WBwCZtL=Hgvsh/qFg!B`1Q}ngqBQ9>x@$bCXO9TeN:k1Ozmn' );
define( 'SECURE_AUTH_SALT',  ';&ZWP<)$vQ%3h;HEsqMK)f`,aD:6>_`!EUFU=7A9/<_ahO}iF?Xp2sk&*AsW&&mB' );
define( 'LOGGED_IN_SALT',    'Rg<rIu(qvOZ``-h%=7Ii%NE=wa&mc>HkP!{YqHf?xY*pnW%M|=j69ZU.z>ql^Cbu' );
define( 'NONCE_SALT',        'KHMccgi_@WgerS24yRXhC9ULY|&EqQnn`id/!u}Y8w7[yN/u+?>w YW;W=?cK_?V' );
define( 'WP_CACHE_KEY_SALT', 'nEQVpDXF(b.wXM(L_;P.8&N5~rR&;3{H}^8+EXya5^N<JCo-/E 8;=?0%3|r n,C' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'rzg_';


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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
@include_once('/var/lib/sec/wp-settings-pre.php'); // Added by SiteGround WordPress management system
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
