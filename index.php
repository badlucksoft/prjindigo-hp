<?php
ob_start();
require_once 'config.inc.php';
define('THE_URI', parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
define('THE_QUERY', parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY));
if(strcmp(THE_URI,LOGIN_URI) == 0) {
	setcookie(LOGIN_COOKIE_NAME,'1',strtotime(LOGIN_PERIOD . ' minutes'),'/',BASE_DOMAIN_NAME,USE_SSL_TLS,true);
	header('Location: ' . BASE_URL . '/login',true,302);
} elseif(strcmp(THE_URI,'/login') == 0) {
	if( isset($_COOKIE[LOGIN_COOKIE_NAME])) define('REAL_LOGIN',true);
	else define('REAL_LOGIN',false);
	
} else {
}