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
	}
	require_once 'header.inc.php';
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
	require_once 'footer.inc.php';
	
} else {
	if(preg_match('#^\/(.*(\.(jpg|gif|png|svg|htm|html|mp4|wav|mpg|mp3|css|js|txt|avi|mov|ico))?)$#i',THE_URI,$match) > 0 && strcmp(basename(THE_URI),basename(HOMEPAGE_CONTENT_FILENAME)) != 0 )
	{
		$file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . basename(sanitizeString($match[1]));
		if( file_exists($file) && is_readable($file) )
		{
			readfile($file);
			exit();
		}
	} elseif (preg_match('#^\/(default|index(\.(php|asp|jsp|htm|html|shtml|aspx|py|txt))?)?$#i',THE_URI) >0 ) {
		require_once 'header.inc.php';
		if( file_exists(HOMEPAGE_CONTENT_FILENAME) && is_readable(HOMEPAGE_CONTENT_FILENAME) ) readfile(HOMEPAGE_CONTENT_FILENAME);
		else {
			echo '<article><header><h1>' . SITE_NAME . '</h1></header><p>Hello and welcome to my site! Unfortunately, I\'m still building things out at the moment, but I look forward to sharing it with you!</p></article>';
		}
		require_once 'footer.inc.php';
	} else {
		// If the request isn't for one of the already specified URI's, it's at the very least a 404 attack as no other URLs exist on the site.
		
	}
}
