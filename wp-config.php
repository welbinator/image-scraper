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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'wordpress' );

/** Database password */
define( 'DB_PASSWORD', 'wordpress' );

/** Database hostname */
define( 'DB_HOST', 'database' );

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
define( 'AUTH_KEY',          'Tn_CjDdLlRFAqGUQ6eS_]XCH3c6T>(T7ZlAV([Bv}E+)B!uFG;KX)j#iP:+pu^R)' );
define( 'SECURE_AUTH_KEY',   'o23YYx$jT|5pFOtCd|tt(AYT,2kMe*/[ ~%#Cf~.MDf,|adbAn6Y8Io~`1@lbapR' );
define( 'LOGGED_IN_KEY',     'u=PyjM_wk6n*lAf+w%dCD!1YQUZda*!&*),{+Q#~.`Z`SIw>|u#N1){6%r!1&_Wb' );
define( 'NONCE_KEY',         'Y6_/4L`?r59@Q$;nMQ*w++Xcx;&]T6d9:W.w,7XJuQSap^R&Sc>)>DA2&{/;.@dk' );
define( 'AUTH_SALT',         '*,(#w2tfbqZF#y&u<:iVYB-=.0/*^7>2p1FQe5)``1}O4FqD}Vmp+ESZ>g%! m40' );
define( 'SECURE_AUTH_SALT',  '}lOYvP>KS6#6;A2PLwm81A,a?(0Y^?tBQch+%IyC.o9D^Q=2/FhxkB3JB8Z,`0O>' );
define( 'LOGGED_IN_SALT',    '_sap_$evpsbYc2d7!lz{VtWSh0Fnk$&LW1)IG<njP8)mc9GV~)0D4(W@BG&=Qw=x' );
define( 'NONCE_SALT',        '.|xoB$_Q(QA60U~/!.2[x}G2f2H9~yY,Z+t/Dv:9Ed^e?mz%!AVI=usT7NJ.yse)' );
define( 'WP_CACHE_KEY_SALT', '6p0Z4OzDE0X./Rwyvt}shWio*#HpgF(KL</vUQW)n18O@^rmqX/HtI?]Z=q97YCS' );


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

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
