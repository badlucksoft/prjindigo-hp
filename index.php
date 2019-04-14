<?php
ob_start();
require_once 'config.inc.php';
require_once 'functions.inc.php';
define('THE_URI', parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
define('THE_QUERY', parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY));
if(strcmp(THE_URI,LOGIN_URI) == 0) {
	setcookie(LOGIN_COOKIE_NAME,'1',strtotime(LOGIN_PERIOD . ' minutes'),'/',BASE_DOMAIN_NAME,USE_SSL_TLS,true);
	header('Location: ' . BASE_URL . '/login',true,302);
} elseif(strcmp(THE_URI,'/login') == 0) {
	session_start();
	if( isset($_COOKIE[LOGIN_COOKIE_NAME])) define('REAL_LOGIN',true);
	else define('REAL_LOGIN',false);
	if( ! empty($_POST) )
	{
		if( REAL_LOGIN && strcasecmp(ADMIN_USER_NAME,sanitizeString($_POST['un'])) == 0 && password_verify($_POST['pw'],ADMIN_PASSWORD) )
		{
			$_SESSION['logged_in'] = true;
			if( isset($_SESSION['login_failures']) && $_SESSION['login_failures'] > 0)
			{
				// clear out database record of failures now that admin has successfully logged in.
				if( isset($_SESSION['alogin_ids']) ) 
				{
					$GLOBALS['db']->beginTransaction();
					foreach($_SESSION['alogin_ids'] as $id )
					{
						$GLOBALS['stmts']['delete_alogin']->execute(array($id));
						$GLOBALS['stmts']['delete_alogin']->closeCursor();
					}
					$GLOBALS['db']->commit();
					unset($_SESSION['alogin_ids']);
					unset($_SESSION['login_failures']);
					define('FAILURES_CLEARED',true);
				}
			}
		}
		else {
			define('LOGIN_FAILURE',true);
			if( isset($_SESSION['login_failures']) ) $_SESSION['login_failures']++;
			else $_SESSION['login_failures'] = 1;
			if( isset($_SESSION['ips']) ) $_SESSION['ips'][] = requestorIP();
			else $_SESSION['ips'] = array(requestorIP());
			$data = array(
					':un' => $_POST['un'],
					':pw' => $_POST['pw'],
					':cookie_content' => (empty($_COOKIE) ? null:var_export($_COOKIE,true)),
					':get_content' => (empty($_GET) ? null:var_export($_GET,true)),
					':post_content' => (empty($_POST) ? null:var_export($_POST,true)),
					':useragent' => (isset($_SERVER['HTTP_USR_AGENT']) ? $_SERVER['HTTP_USER_AGENT']:null),
					':referrer' => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER']:null),
					':addr_id' => hasAddr($_SESSION['ips'][count($_SESSION['ips'])-1],true)
				);
			$GLOBALS['stmts']['insert_login_atk']->execute($data);
			$GLOBALS['stmts']['insert_login_atk']->closeCursor();
			if( isset($_SESSION['alogin_ids']) ) $_SESSION['alogin_ids'][] = $GLOBALS['db']->lastInsertId();
			else $_SESSION['alogin_ids'] = array($GLOBALS['db']->lastInsertId());
			$GLOBALS['stmts']['insert_login_atk']->closeCursor();
			unset($data);
		}
	}
	require_once 'header.inc.php';
	if( isset($_SESSION['logged_in']) && $_SESSION['logged_in'])
	{
		if( defined('FAILURES_CLEARED') && FAILURES_CLEARED) echo '<p>Your login failures have been cleared.</p>';
		echo '<p>Sorry, you\'re logged in correctly, but there\'s nothing to see right now.</p>';
	}
	else {
		if(defined('LOGIN_FAILURE') && LOGIN_FAILURE) echo '<p style="color: red; font-weignt: bold;">Incorrect username/password combination.</p>';
	?>
	<script>
	<!--
	function checkAndSubmit() {
		if( $('#un').val().length >= 4 && $('#pw').val().length >= 8) {
			$('#lf').submit();
		}
	}
	-->
	</script>
	<form action="<?php echo BASE_URL;?>/login" method="post" id="lf">
	<table>
	<tr><th>User Name:</th><td><input type="text" name="un" id="un" required="required"<?php if( isset($_POST['un']) && ! empty($_POST['un'])) echo ' value="' . htmlentities(sanitizeString($_POST['un']),ENT_QUOTES,'UTF-8') . '"';?>></td></tr>
	<tr><th>Password:</th><td><input type="password" name="pw" id="pw" required="required"></td></tr>
	<tr><td colspan="2" align="center"><input type="button" id="sbmt" onclick="checkAndSubmit()" value="Login"></td></tr>
	</table>
	</form>
	<?php
	}
	require_once 'footer.inc.php';
	
} else {
	if( strcmp(THE_URI, '/') != 0 && preg_match('#^\/(([a-zA-Z0-9-_\.]*(\.(jpg|gif|png|svg|htm|html|mp4|wav|mpg|mp3|css|js|txt|avi|mov|ico))?))$#i',THE_URI,$match) > 0 && strcmp(basename(THE_URI),basename(HOMEPAGE_CONTENT_FILENAME)) != 0 )
	{
		$file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . basename(sanitizeString($match[1]));
		if( file_exists($file) && is_readable($file) )
		{
			readfile($file);
			exit();
		} elseif( strcmp(THE_URI, '/favicon.ico') == 0 || strcmp(THE_URI,'/robots.txt') == 0 ) 
		{
			header('HTTP/1.0 404 Not Found',true,404);
		}
		else
		{
			// If the request isn't for one of the already specified URI's, it's at the very least a 404 attack as no other URLs exist on the site.
			$data = array(
				':requested_uri' => THE_URI,
				':cookie_content' =>(empty($_COOKIE) ? null:var_export($_COOKIE,true)),
				':get_content' => (empty($_GET) ? null:var_export($_GET,true)),
				':post_content' => (empty($_POST) ? null:var_export($_POST,true)) ,
				':useragent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT']:null,
				':referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER']:null,
				':addr_id' =>hasAddr(requestorIP(),true)
				);
			$GLOBALS['stmts']['insert_404_atk']->execute($data);
			$GLOBALS['stmts']['insert_404_atk']->closeCursor();
			header('HTTP/1.0 404 Not Found',true,404);
			require_once 'header.inc.php';
			echo '<p>Unfortunately, "' . THE_URI . '" could not be found.</p>';
			require_once 'footer.inc.php';
			
		}
	} elseif (preg_match('#^\/(default|index(\.(php|asp|jsp|htm|html|shtml|aspx|py|txt))?)?$#i',THE_URI) >0 ) {
		if( ! empty(THE_QUERY) || ! empty($_POST) || (! empty($_COOKIE) && (! isset($_COOKIE['LOGIN_COOKIE_NAME']) && ! isset($_COOKIE[session_name()]) )))
		{
			// Record general web attack, but show the homepage anyway.
		}
		require_once 'header.inc.php';
		if( file_exists(HOMEPAGE_CONTENT_FILENAME) && is_readable(HOMEPAGE_CONTENT_FILENAME) ) readfile(HOMEPAGE_CONTENT_FILENAME);
		else {
			echo '<article><header><h1>' . SITE_NAME . '</h1></header><p>Hello and welcome to my site! Unfortunately, I\'m still building things out at the moment, but I look forward to sharing it with you!</p></article>';
		}
		require_once 'footer.inc.php';
	} else {
		// If the request isn't for one of the already specified URI's, it's at the very least a 404 attack as no other URLs exist on the site.
		$data = array(
			':requested_uri' => THE_URI,
			':cookie_content' =>(empty($_COOKIE) ? null:var_export($_COOKIE,true)),
			':get_content' => (empty($_GET) ? null:var_export($_GET,true)),
			':post_content' => (empty($_POST)?null:var_export($_POST,true)) ,
			':useragent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT']:null,
			':referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER']:null,
			':addr_id' =>hasAddr(requestorIP(),true)
			);
		$GLOBALS['stmts']['insert_404_atk']->execute($data);
		$GLOBALS['stmts']['insert_404_atk']->closeCursor();
		header('HTTP/1.0 404 Not Found',true,404);
		require_once 'header.inc.php';
		echo '<p>Unfortunately, "' . THE_URI . '" could not be found.</p>';
		require_once 'footer.inc.php';
	}
}
