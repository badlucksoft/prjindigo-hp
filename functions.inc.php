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
function PISynchronize($FORCE =  false)
{
	$GLOBALS['stmts']['get_site_data']->execute();
	$siteData = json_decode($GLOBALS['stmts']['get_site_data']->fetchColumn());
	$GLOBALS['stmts']['get_site_data']->closeCursor();
	$gamble = mt_rand(0,100);
	
	if( $siteData !== false && (is_bool($FORCE) && $FORCE === true || $gamble <= REPORT_PERCENT || is_null($siteData->last_sync_timestamp) || time() >= strtotime('+' . MAXIMUM_SYNC_WINDOW . ' seconds',strtotime($siteData->last_sync_timestamp)))) {
		ob_end_flush(); //make sure all output has been sent
		if( ! empty(PRJI_ACCOUNT_ID) && ! empty(PRJI_SECRET_HASH) && ! empty(PRJI_ENCRYPT_KEY) && ! empty(PRJI_SIGN_KEY) ) {
			//get server key and confirm
			$currentServerKeys = json_decode(sendAPIRequest(array('request_type' => 'getServerPK')),true);
			$serverKeys = array('encrypt' => null,  'sign' => null);
			if( ! empty($currentServerKeys) )
			{
				// verify signatures
				$mysigs = array(
					'encrypt_public_hash' => hash('sha256',base64_decode($currentServerKeys['encrypt_public'])),
					'sign_public_hash' => hash('sha256',base64_decode($currentServerKeys['sign_public']))
					);
				if( strcasecmp($mysigs['encrypt_public_hash'],$currentServerKeys['encrypt_public_key_hash']) == 0 && strcasecmp($mysigs['sign_public_hash'],$currentServerKeys['sign_public_key_hash']) == 0) {
					// keys match the signature that was transmitted with them, verify the signatures
					$serverKeys['sign'] = base64_decode($currentServerKeys['sign_public']);
					$serverKeys['encrypt'] = base64_decode($currentServerKeys['encrypt_public']);
					if( sodium_crypto_sign_verify_detached(base64_decode($currentServerKeys['sign_public_key_signature']),$serverKeys['sign'],$serverKeys['sign']) )
					{
						define('SERVER_SIGN_PUBLIC_KEY_SIGNATURE_MATCHES',true);
					}
					else define('SERVER_SIGN_PUBLIC_KEY_SIGNATURE_MATCHES',false);
					if( sodium_crypto_sign_verify_detached(base64_decode($currentServerKeys['encrypt_public_key_signature']),$serverKeys['encrypt'],$serverKeys['sign']) )
					{
						define('SERVER_ENCRYPT_PUBLIC_KEY_SIGNATURE_MATCHES',true);
					}
					else define('SERVER_ENCRYPT_PUBLIC_KEY_SIGNATURE_MATCHES',false);
				}
			}
			// check local access token
			if( SERVER_SIGN_PUBLIC_KEY_SIGNATURE_MATCHES && SERVER_ENCRYPT_PUBLIC_KEY_SIGNATURE_MATCHES && (is_null($siteData->prji_access_token) || time() >= strtotime($siteData->prji_token_expiry)) )
			{
				$siteData->prji_access_token = null;
				$siteData->prji_token_expiry = null;
				// perform handshake
				$handshake = array('request_type' => 'handshake','client_id' => PRJI_ACCOUNT_ID);
				$encrypted = pkEncrypt(base64_decode(PRJI_ENCRYPT_KEY),$serverKeys['encrypt'],PRJI_SECRET_HASH);
				$handshake['encrypted'] = base64_encode($encrypted['encrypted_content']);
				$handshake['encrypt_nonce'] = base64_encode($encrypted['encrypt_nonce']);
				$handshake['secret_signature'] = base64_encode(sodium_crypto_sign_detached($encrypted['encrypted_content'],base64_decode(PRJI_SIGN_KEY)));
				$response = sendAPIRequest($handshake);
				unset($handshake);
				$response = json_decode($response,true);
				if( sodium_crypto_sign_verify_detached(base64_decode($response['signature']),base64_decode($response['access_token']),$serverKeys['sign']) ) {
					$siteData->prji_token_expiry = $response['valid_until'];
					$siteData->prji_access_token = pkDecrypt($serverKeys['encrypt'],base64_decode(PRJI_ENCRYPT_KEY),base64_decode($response['access_token']),base64_decode($response['encrypt_nonce'])); 
				}
				unset($response);
			}
			//perform sync
			if( ! empty($siteData->prji_access_token) && time() <= strtotime($siteData->prji_token_expiry) ) {
				// encrypt our access token to go back to the server
				$accessToken['signature'] = base64_encode(sodium_crypto_sign_detached($siteData->prji_access_token,base64_decode(PRJI_SIGN_KEY)));
				$accessToken['token'] = $siteData->prji_access_token;
				
				// Get 404 attacks since $siteData->last_404_attack_id_synced
				$GLOBALS['stmts']['count_404_atks']->execute(array($siteData->last_404_attack_id_synced));
				$a404_count = $GLOBALS['stmts']['count_404_atks']->fetchColumn();
				$GLOBALS['stmts']['count_404_atks']->closeCursor();
				// report 404 attacks
				if( $a404_count > 0 )
				{
					$request = array('request_type' => 'Report404Attack', 'access_token' => $accessToken);
					$GLOBALS['stmts']['get_404_atks']->execute(array($siteData->last_404_attack_id_synced));
					$results = $GLOBALS['stmts']['get_404_atks']->fetchAll(PDO::FETCH_ASSOC);
					$atks = array();
					$GLOBALS['stmts']['get_404_atks']->closeCursor();
					foreach( $results as $record)
					{
						$atk = array(
							'id' => $record['a404_id'], // local ID isn't stored at Project Indigo, but is returned with a status indicator so success can be tracked
							'ip' => $record['addr'], // attacker's IP address
							'uri' => $record['requested_uri'],
							'timestamp'=> gmdate('c',strtotime($record['atk_timestamp'])),
							'referrer' => hasDangerousChars($record['referrer']) ? base64_encode($record['referrer']):$record['referrer'],
							'referrer_encoded' => hasDangerousChars($record['referrer']),
							'post' => hasDangerousChars($record['post_content']) ? base64_encode($record['post_content']):$record['post_content'],
							'post_encoded' => hasDangerousChars($record['post_content']),
							'get' => hasDangerousChars($record['get_content']) ? base64_encode($record['get_content']):$record['get_content'],
							'get_encoded' => hasDangerousChars($record['get_content']),
							'cookie' => hasDangerousChars($record['cookie_content']) ? base64_encode($record['cookie_content']):$record['cookie_content'],
							'cookie_encoded' => hasDangerousChars($record['cookie_content']),
							'user_agent' => hasDangerousChars($record['useragent']) ? base64_encode($record['useragent']):$record['useragent'],
							'user_agent_encoded' => hasDangerousChars($record['useragent'])
							);
						$atks[] = $atk;
						unset($atk);
					}
					$atkE = pkEncrypt(PRJI_ENCRYPT_KEY,$serverKeys['encrypt'],json_encode($atks));
					$request['reports'] = array('encrypted_content' => base64_encode($atkE['encrypted_content']), 'encrypt_nonce' => $atkE['encrypt_nonce']);
					$request['report_signature'] = base64_encode(sodium_crypto_sign_detached($atkE['encrypted_content'],base64_decode(PRJI_SIGN_KEY)));
					$response = sendAPIRequest($request);
					unset($request);
					$response = json_decode($response);
					if( sodium_crypto_sign_verify_detached(base64_decode($response->result_signature),base64_decode($response->result->encrypted_content),$serverKeys['sign_public']) ) {
						$result = pkDecrypt($serverKeys['encrypt_public'],base64_decode(PRJI_ENCRYPT_KEY),base64_decode($response->result->encrypted_content),base64_decode($response->result->encrypt_nonce));
						
						if( $result !== false && ! empty($result) )
						{
							$responseData = json_decode($result);
							$GLOBALS['db']->beginTransaction();
							foreach( $responseData as $rd)
							{
								if( $atk->success ) {
									$siteData->last_404_attack_id_synced = $rd->id;
									$GLOBALS['stmts']['delete_404']->execute(array($rd->id));
									$GLOBALS['stmts']['delete_404']->closeCursor();
								}
							}
							$GLOBALS['db']->commit();
						}
					}
				}
				// report login attacks
				$GLOBALS['stmts']['count_login_atks']->execute(array($siteData->last_404_attack_id_synced));
				$alogin_count = $GLOBALS['stmts']['count_login_atks']->fetchColumn();
				$GLOBALS['stmts']['count_login_atks']->closeCursor();
				// This is temporarily disabled as the feature isn't fully implemented on server yet.
				if( false && $alogin_count > 0 )
				{
					$request = array('request_type' => 'ReportLoginAttack', 'access_token' => $accessToken);
					$GLOBALS['stmts']['get_alogin_atks']->execute(array($siteData->last_404_attack_id_synced));
					$results = $GLOBALS['stmts']['get_alogin_atks']->fetchAll(PDO::FETCH_ASSOC);
					$atks = array();
					$GLOBALS['stmts']['get_alogin_atks']->closeCursor();
					foreach( $results as $record)
					{
						$atk = array(
							'id' => $record['alogin_id'], // local ID isn't stored at Project Indigo, but is returned with a status indicator so success can be tracked
							'ip' => $record['addr'], // attacker's IP address
							'username' => $record['un'],
							'password' => $record['pwd'],
							'timestamp'=> gmdate('c',strtotime($record['atk_timestamp'])),
							'referrer' => hasDangerousChars($record['referrer']) ? base64_encode($record['referrer']):$record['referrer'],
							'referrer_encoded' => hasDangerousChars($record['referrer']),
							'post' => hasDangerousChars($record['post_content']) ? base64_encode($record['post_content']):$record['post_content'],
							'post_encoded' => hasDangerousChars($record['post_content']),
							'get' => hasDangerousChars($record['get_content']) ? base64_encode($record['get_content']):$record['get_content'],
							'get_encoded' => hasDangerousChars($record['get_content']),
							'cookie' => hasDangerousChars($record['cookie_content']) ? base64_encode($record['cookie_content']):$record['cookie_content'],
							'cookie_encoded' => hasDangerousChars($record['cookie_content']),
							'user_agent' => hasDangerousChars($record['useragent']) ? base64_encode($record['useragent']):$record['useragent'],
							'user_agent_encoded' => hasDangerousChars($record['useragent'])
							);
						$atks[] = $atk;
						unset($atk);
					}
					$atkE = pkEncrypt(PRJI_ENCRYPT_KEY,$serverKeys['encrypt'],json_encode($atks));
					$request['reports'] = array('encrypted_content' => base64_encode($atkE['encrypted_content']), 'encrypt_nonce' => $atkE['encrypt_nonce']);
					$request['report_signature'] = base64_encode(sodium_crypto_sign_detached($atkE['encrypted_content'],base64_decode(PRJI_SIGN_KEY)));
					$response = sendAPIRequest($request);
					unset($request);
					$response = json_decode($response);
					if( sodium_crypto_sign_verify_detached(base64_decode($response->result_signature),base64_decode($response->result->encrypted_content),$serverKeys['sign_public']) ) {
						$result = pkDecrypt($serverKeys['encrypt_public'],base64_decode(PRJI_ENCRYPT_KEY),base64_decode($response->result->encrypted_content),base64_decode($response->result->encrypt_nonce));
						
						if( $result !== false && ! empty($result) )
						{
							$responseData = json_decode($result);
							$GLOBALS['db']->beginTransaction();
							foreach( $responseData as $rd)
							{
								if( $atk->success ) {
									$siteData->last_404_attack_id_synced = $rd->id;
									$GLOBALS['stmts']['delete_404']->execute(array($rd->id));
									$GLOBALS['stmts']['delete_404']->closeCursor();
								}
							}
							$GLOBALS['db']->commit();
						}
					}
				}
				
				//update database with new site data
				$siteData->last_sync_timestamp = gmdate('c');
				$GLOBALS['stmts']['update_site_data']->execute(array(json_encode($siteData)));
				$GLOBALS['stmts']['update_site_data']->closeCursor();
			}
		}
	}
}
function sendAPIRequest($ARR)
{
	$curl = curl_init('https://www.prjindigo.com/api/0/');
	curl_setopt($curl, CURLOPT_POST,true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($ARR));
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION,true);
	curl_setopt($curl, CURLOPT_USERAGENT,'PI API HP Agent');
	$result = curl_exec($curl);
	if( $result === false )
	{
		echo "error: " . curl_error($curl);
	}
	curl_close($curl);
	return $result;
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
