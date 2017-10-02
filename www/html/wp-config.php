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
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wordpress_demo');

/** MySQL database username */
define('DB_USER', 'wordpress_demo');

/** MySQL database password */
define('DB_PASSWORD', 'J4f%=-EwYw26#&<t');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '@nIC-}{9@ezHw4[ ,WeqaePr6$2FzoIMf$1BySw=(O&8s1yCa;aXBg61(.Tt<AAp');
define('SECURE_AUTH_KEY',  '>DX-dLvv(x*WgJV-_+tTWd|A;{PJ3~2m3w2dU`vVCA~rD%2R&`sI,n_z7CA})sYK');
define('LOGGED_IN_KEY',    'e},,Ua{qo|;6~H7BZ0c`[~?-o<@9J7deWq,xS87D+^!3 Vf(3)jKmX)?qW*R9^1a');
define('NONCE_KEY',        'M KAazzH5)f!3-?K%2v:7%<K+HyCi5ofX[wUtk:3>ye#hz=mK5S}tWTD+hVIcM;v');
define('AUTH_SALT',        'O?uju <l1JAowuy2(Bo&UC1QLw}JM6J[f@_7*>U(omP;i%^/.8*pyUR33ERO.|vT');
define('SECURE_AUTH_SALT', '=J<>.WWl8%d`i8J)s$)Lhk=CuXxj:5e{>hxut-(X]Jv}dR7Qp)J_!.iIjseJ<P98');
define('LOGGED_IN_SALT',   'JD2W?!{&p(32FC^^#:e`B*%u$|B(bwlw:K*R6@$X/KE<K5-p$px*=;:[Lt,Vi~aB');
define('NONCE_SALT',       'HiQ}]&.=wL5U0%sRsFuL8#3]6tTp02gG5)b?hDSQ{bJ?anJ4P6-Yiyn.1U1%HE#l');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
