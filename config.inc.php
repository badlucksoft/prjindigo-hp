<?php
define('USE_SSL_TLS',false);
define('BASE_DOMAIN_NAME','example.com');
define('BASE_URL','http' . (USE_SSL_TLS ? 's':'') . BASE_DOMAIN_NAME);
define('LOGIN_URI', '/Sojourn/Exodus'); // Strongly reommended that you change this.
define('LOGIN_PERIOD',120);
define('LOGIN_COOKIE_NAME',hash('sha256','XcRbzsGjBVMBvTroRUyC5MDx8PZDBCEE')); // Strongly recommended that you change this.
define('ADMIN_USER_NAME','user'); // Change this immediately!!
define('ADMIN_PASSWORD',password_hash('password',PASSWORD_ARGON2I,array('cost' => PASSWORD_HASH_COST,'memory_cost' => 128000, 'time_cost' => 30, 'threads' => 4))); // Change this immediately!!
if( password_verify('password',ADMIN_PASSWORD)) die('CHANGE THE PASSWORD IMMEDIATELY.');