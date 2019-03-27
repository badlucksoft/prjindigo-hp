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
	
	require_once 'header.inc.php';
	?>
	<script>
	<!--
	function checkAndSubmit() {
		if( $('#un').val().length >= 4 && $('#pw').val().length) {
			$('#lf').submit();
		}
	}
	-->
	</script>
	<form action="<?php echo BASE_URL;?>/login" method="post" id="lf">
	<table>
	<tr><th>User Name:</th><td><input type="text" name="un" id="un" required="required"<?php if( isset($_POST['un']) && ! empty($_POST['un'])) echoo ' value="' . htmlentities(sanitizeString($_POST['un']),ENT_QUOTES,'UTF-8') . '"';?>></td></tr>
	<tr><th>Password:</th><td><input type="password" name="pw" id="pw" required="required"></td></tr>
	<tr><td colspan="2" align="center"><button id="sbmt" onclick="checkAndSubmit()">Submit</button></td></tr>
	</table>
	</form>
	<?php
	require_once 'footer.inc.php';
	
} else {
	if(preg_match('#^\/(.*(\.(jpg|gif|png|svg|htm|html|mp4|wav|mpg|mp3|css|js|txt|avi|mov))?)$#i',THE_URI,$match) > 0 )
	{
		$file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . basename(sanitizeString($match[1]));
		if( file_exists($file) && is_readable($file) )
		{
			readfile($file);
			exit();
		}
	}
}
function sanitizeString($STRING)
{
	return trim(filter_var(stripslashes(strip_tags($STRING)),FILTER_SANITIZE_STRING,array('flags' => FILTER_FLAG_STRIP_LOW|FILTER_SANITIZE_ENCODED)));//FILTER_FLAG_STRIP_LOW|FILTER_FLAG_ENCODE_HIGH
}
