<?php
/*
	Website basics
*/
define('USE_SSL_TLS',false);
define('BASE_DOMAIN_NAME','example.com');
define('BASE_URL','http' . (USE_SSL_TLS ? 's':'') . BASE_DOMAIN_NAME);

/*
	Site login settings
*/
define('LOGIN_URI', '/Sojourn/Exodus'); // Strongly reommended that you change this.
define('LOGIN_PERIOD',120);
define('LOGIN_COOKIE_NAME',hash('sha256','XcRbzsGjBVMBvTroRUyC5MDx8PZDBCEE')); // Strongly recommended that you change this.
define('ADMIN_USER_NAME','user'); // Change this immediately!!
define('ADMIN_PASSWORD',password_hash('password',PASSWORD_ARGON2I,array('cost' => PASSWORD_HASH_COST,'memory_cost' => 128000, 'time_cost' => 30, 'threads' => 4))); // Change this immediately!!
if( password_verify('password',ADMIN_PASSWORD)) die('CHANGE THE PASSWORD IMMEDIATELY.');

/*
	Project Indigo Settings. (More info coming soon.)
*/
define('PRJI_ACCOUNT_ID','');
define('PRJI_SECRET_HASH','');
define('PRJI_ENCRYPT_KEY','');
define('PRJI_SIGN_KEY','');

/*
	Session settings
*/
ini_set('date.timezone','America/Chicago');
ini_set('session.name','6Na9deZartDKwqmURc8LpqxMaV6U0A');
ini_set('session.cookie_lifetime',LOGIN_PERIOD * 60);
ini_set('session.cookie_domain',BASE_DOMAIN_NAME);
ini_set('session.cookie_samesite','strict');
ini_set('session.lazy_write',1);
ini_set('session.use_strict_mode',1);
ini_set('session.cookie_path','/');
ini_set('session.cookie_secure',USE_SSL_TLS);
ini_set('session.use_cookies',true);
ini_set('session.use_only_cookies',true);
