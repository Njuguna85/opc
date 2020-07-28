<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'dennis' );

/** MySQL database password */
define( 'DB_PASSWORD', '1234567890' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ')D3(c|q2+OUuk9$W@<.5PdA#bzc}ZTe_YC[DG,KiO$)vGS,%gd!pT[mn!E7`;//F' );
define( 'SECURE_AUTH_KEY',  'dJQ*TN+-%h:26o<AFy^$.w,*vlrS wvk*ZJcYKV#Q7-#B~ Eh)_Q9Dr#d%j,ldAs' );
define( 'LOGGED_IN_KEY',    '9t=:6<6CygVd`QK[k 0K1>mwj%jsM}h]dV>^PQ;;wG/9Jr`vlN}:cUz4Txy% uyo' );
define( 'NONCE_KEY',        'rj6$4`*Z[OzgmXtA%F;XZ^e|XSyD?Pz.tnSX pU<sbVr%NzKU{;a)E]i #A+My`2' );
define( 'AUTH_SALT',        'h+qXm`JuXi!QR!ZDLP(]0Y},%a.<A}~_|3k_sTyT|{kAUG%{_o+JL+{C,bvR)}G`' );
define( 'SECURE_AUTH_SALT', 'f0c;m)=IOYv!PbU+>YyxyLX7,n uTA60T=9/dT;7&K6O,<HIYb#0X86XaOnM6SqP' );
define( 'LOGGED_IN_SALT',   '6-13l;yi3 Y%RVM.,BQ2:1wdp`+MwV| @XnIyV!efccU4N+s3gq`N+,)XsXThP&B' );
define( 'NONCE_SALT',       '2;$^=96N ^7LC)j#<57Ni65[awghM!r0rBun33?i+5R|cQ+d)3e>mI7[CE9h974`' );

/**#@-*/

/**
 * WordPress Database Table prefix.
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
