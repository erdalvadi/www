<?php
define( 'WP_CACHE', true ); // Added by WP Rocket



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
define( 'DB_NAME', 'kalyansa1_ybhza' );

/** Database username */
define( 'DB_USER', 'kalyansa1_ybhza' );

/** Database password */
define( 'DB_PASSWORD', 'Vs2@4RnP1%amPwrY' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

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
define( 'AUTH_KEY',          '&V+<xx:NzzaCQah4nm[]S#h,pZemx6#`?Q%Z]J LW8{cr/;~Dg34XAcMn9}-![D+' );
define( 'SECURE_AUTH_KEY',   'r[ozgY@&h &aVM#lLLECnY*jacv:2heI-^Ed3jB-m+[&^(snaF`@q<SVm bzg7cs' );
define( 'LOGGED_IN_KEY',     'khLG~[`xWoEBKAq<p,p5# Ir6g+BLV0J?~ytzOqE(y:D%@pD`}*WB5DRT?0dcM-6' );
define( 'NONCE_KEY',         'qh!j]SHd(JzYszx*;lY[bNTu6_:ydybW7G#Sld,&7}ztfkuh3eKC3g#$cY g`CpI' );
define( 'AUTH_SALT',         'dVj@AL>r&dpV*HTQpoW,@+!}uh[C~7(EJh8 qQ_^7|WB~`Z3SrDS0j+zXe~_&6tr' );
define( 'SECURE_AUTH_SALT',  ' 0kuR|BW>.#R0VS/lE{#%,QdlPH&w*`}u0/.0xOrp)R(|7tz*Oa,mK+~,>xx1ydb' );
define( 'LOGGED_IN_SALT',    'lnL*GLmPA;$I:}Pi~cPSR?D1mbQPEee%;;DA4]8Oc9rC0Zl8xn/4%U@ex_SZ#pe}' );
define( 'NONCE_SALT',        'jc5e&Q8Fpi]M(EH-([k|buvWW,jvRl9W(V8C$W[o]Vy}q=b~`/C6Ciwc(y-~<n`^' );
define( 'WP_CACHE_KEY_SALT', ';bKQ8GQGRTB3~S]UWf#P(/Z:2/*}1e@|$daMC3wA=JM#L8;hB_kv>L>:,fP{5/>N' );


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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', 'f889558cb6550bbf1bebfc44f70f3b02' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
