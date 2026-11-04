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
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

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
define( 'AUTH_KEY',          'nHGF.cO13IffbjAnn/v#(OWo7POK{8t4 9Xx1c1^RhQtl(4<gqr{1~Kh>D${cn[6' );
define( 'SECURE_AUTH_KEY',   '}t)6nRNqmg{*fF^/dk&4viu|AY3d@7>@h66?!:M.iM=Cdp@H`SF;9dL8$@&T&udx' );
define( 'LOGGED_IN_KEY',     '=;*Ftl<x8%EHB-Z#kVKc[5hXaaXv,Z+p:8b!0=kK@Pu:)]|`41p}6)(rp.(J ._*' );
define( 'NONCE_KEY',         '$O%t|^KRtmCEG.qH/Tn11^#RX#_gy6{X}QLs#Qz-_r Y*T$VJYdUA]A29_quDYfv' );
define( 'AUTH_SALT',         '`dy5#-@0(m_q~MAYCkh!V*cQ6NFc&4]a]}EuCMrMF=C$#(ZjE0/7{Y8:@)@E}S)S' );
define( 'SECURE_AUTH_SALT',  '{uk!^IE~]|=X@j(p*hrKbqH~mPv`f|ug3&A5[^B*G-|G65Bxmu3>1WQ^pL7nk}&D' );
define( 'LOGGED_IN_SALT',    'JO.uSyx=AI,q`<>f_6F{s1Ody@(sBggUY?KC,y17=kXS<Z^;ObzNH2+{9r|Fy@Us' );
define( 'NONCE_SALT',        'k*2=NSl*;u$g*[*4JF%e=vW-G:?hJ58VF0{cQ639*U bgWBp#8Y1~J+O$d7GIZP1' );
define( 'WP_CACHE_KEY_SALT', 'T;r;q}Pq[~UqI:r&|nNH+7IOI05Tu_%h4cUWlf!A]UyM[&n>Ny8}qZ`b4|EC4)&*' );


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

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
