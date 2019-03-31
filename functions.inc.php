<?php
function hasAddr($ADDR,$ADD = true)
{
	$GLOBALS['stmts']['get_addr_id']->execute(array(trim(sanitizeString($ADDR))));
	$addr_id = $GLOBALS['stmts']['get_addr_id']->fetchColumn();
	$GLOBALS['stmts']['get_addr_id']->closeCursor();
	if( $ADD && (empty($addr_id) || $addr_id === false) )  {
		$GLOBALS['stmts']['insert_addr']->execute(array(trim(sanitizeString($ADDR))));
		$addr_id = $GLOBALS['db']->lastInsertId();
		$GLOBALS['stmts']['insert_addr']->closeCursor();
		if( $addr_id === false || empty($addr_id) ) $addr_id = hasAddr($ADDR);
	}
	return $addr_id;
}
function sanitizeString($STRING)
{
	return trim(filter_var(stripslashes(strip_tags($STRING)),FILTER_SANITIZE_STRING,array('flags' => FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH|FILTER_SANITIZE_ENCODED)));//FILTER_FLAG_STRIP_LOW|FILTER_FLAG_ENCODE_HIGH
}
function sanitizeEmail($EMAIL_ADDR)
{
	return filter_var(trim(stripslashes(strip_tags($EMAIL_ADDR))),FILTER_SANITIZE_EMAIL);
}
function isPrivateIP($ADDR)
{
	if(filter_var($ADDR,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)) return false;
	return true;
}
function hasDangerousChars($TEXT)
{
	if (strcmp($TEXT,filter_var($TEXT,FILTER_SANITIZE_STRING,array('flags' =>  FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH|FILTER_FLAG_STRIP_BACKTICK))) != 0) return true;
	return false;
}
function requestorIP()
{
	$ip = null;
	if( isPrivateIP($_SERVER['REMOTE_ADDR']) === false) $ip = $_SERVER['REMOTE_ADDR'];
	$skeys = array_keys($_SERVER);
	$cfip  = null;
	$proxyip = null;
	foreach($skeys as $key)
	{
		if( strcasecmp($key,'CF-Connecting-IP') == 0 ) {
			$cfip = $_SERVER[$key];
		} elseif( strcasecmp($key,'X-Forwarded-For') == 0) {
			preg_match('/^([\da-fA-F\:\.]*)\[?.*\]?$/',$_SERVER[$key],$ips);
			$proxyip = $ips[1];
		}
	}
	if( ! is_null($cfip) && is_null($proxyip) && isPrivateIP($cfip) === false ) $ip = $cfip;
	elseif( ! is_null($proxyip) && isPrivateIP($proxyip) === false  ) $ip = $proxyip;
	return $ip;
}
function PISynchronize()
{
	echo '<!-- PISynchronize -->';
}
function pkEncrypt($SENDER_PRIVATE_KEY,$RECEIVER_PUBLIC_KEY,$CONTENT,$NONCE = null)
{
	$kp = sodium_crypto_box_keypair_from_secretkey_and_publickey($SENDER_PRIVATE_KEY, $RECEIVER_PUBLIC_KEY);
	$nonce = $NONCE;
	if( is_null($NONCE) ) $nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
	$et = sodium_crypto_box($CONTENT,$nonce,$kp);
	$package = array('encrypted_content' => $et, 'encrypt_nonce' => $nonce);
	return $package;
}
function pkDecrypt($SENDER_PUBLIC_KEY,$RECEIVER_PRIVATE_KEY,$CONTENT,$NONCE)
{
	$kp = sodium_crypto_box_keypair_from_secretkey_and_publickey($RECEIVER_PRIVATE_KEY,$SENDER_PUBLIC_KEY);
	$message = sodium_crypto_box_open($CONTENT,$NONCE,$kp);
	return $message;
}
