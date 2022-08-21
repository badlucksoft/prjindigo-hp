<?php
try {
	$GLOBALS['db'] = new PDO('sqlite' . ':' . DATABASE_FILENAME,null,null);
	$GLOBALS['db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$GLOBALS['db']->exec( 'PRAGMA foreign_keys = ON;' );
	$GLOBALS['stmts'] = array();
	$GLOBALS['stmts']['table_exists'] = $GLOBALS['db']->prepare('select name from sqlite_master where type=\'table\' and name= ?');
	$tables = array('site','addrs','a404','aweb','alogin');
	foreach($tables as $table)
	{
		$GLOBALS['stmts']['table_exists']->execute(array($table));
		$name = $GLOBALS['stmts']['table_exists']->fetchColumn();
		$GLOBALS['stmts']['table_exists']->closeCursor();
		if( empty($name) || $name === false || strcmp($name,$table) != 0 )
			createTable($table);
	}
	prepareStatements();

}
catch(PDOException $e) {
	die($e->getMessage());
}

function prepareStatements(){
	switch(DATABASE_TYPE) {
		case DATABASE_TYPE_POSTGRES: {
		}break;
		case DATABASE_TYPE_SQLITE:
		default: {
		$GLOBALS['stmts']['insert_site_default'] = $GLOBALS['db']->prepare('insert into site(info) values (?)');
		$GLOBALS['stmts']['get_site_data'] = $GLOBALS['db']->prepare('select info from site');
		$GLOBALS['stmts']['get_addr_id'] = $GLOBALS['db']->prepare('select addr_id from addrs where addr = ?');
		$GLOBALS['stmts']['insert_addr'] = $GLOBALS['db']->prepare('insert into addrs (addr) values (?)');
		$GLOBALS['stmts']['update_site_data'] = $GLOBALS['db']->prepare('update site set info = ?');
		$GLOBALS['stmts']['count_404_atks'] = $GLOBALS['db']->prepare('select count(a404_id) from a404 where a404_id > ?');
		$GLOBALS['stmts']['get_404_atks'] = $GLOBALS['db']->prepare('select * from a404 a4 inner join addrs ad on a4.addr_id = ad.addr_id where a404_id > ? order by atk_timestamp limit ' . PRJI_SUBMISSION_LIMIT);
		$GLOBALS['stmts']['delete_404'] = $GLOBALS['db']->prepare('delete from a404 where a404_id = ?');
		$GLOBALS['stmts']['insert_404_atk'] = $GLOBALS['db']->prepare('insert into a404 (requested_uri,cookie_content,get_content,post_content,useragent,referrer,addr_id) values (:requested_uri,:cookie_content,:get_content,:post_content,:useragent,:referrer,:addr_id)');
		$GLOBALS['stmts']['insert_login_atk'] = $GLOBALS['db']->prepare('insert into alogin (un,pw,cookie_content,get_content,post_content,useragent,referrer,addr_id) values (:un,:pw,:cookie_content,:get_content,:post_content,:useragent,:referrer,:addr_id)');
		$GLOBALS['stmts']['insert_web_atk'] = $GLOBALS['db']->prepare('insert into aweb (request_uri,addr_id,cookie_content,get_content,post_content,referrer,useragent) values (:requested_uri,:addr_id,:cookie_content,:get_content,:post_content,:referrer,:useragent)');
		$GLOBALS['stmts']['count_login_atks'] = $GLOBALS['db']->prepare('select count(alogin_id) from alogin where alogin_id > ?');
		$GLOBALS['stmts']['get_alogin_atks'] = $GLOBALS['db']->prepare('select * from alogin al inner join addrs ad on al.addr_id = ad.addr_id where alogin_id > ? order by atk_timestamp limit ' . PRJI_SUBMISSION_LIMIT);
		$GLOBALS['stmts']['get_last_insert'] = $GLOBALS['db']->prepare('select last_insert_rowid()');
		$GLOBALS['stmts']['delete_alogin'] = $GLOBALS['db']->prepare('delete from alogin where alogin_id = ?');
		}
	}
	if( defined('INSERT_SITE_DEFAULTS') && INSERT_SITE_DEFAULTS)
	{
			$defaults = array('db_creation_timestamp' => gmdate('c'),'last_sync_timestamp' => null,'sync_count' => 0, 'last_login_attack_id_synced' => 0, 'last_404_attack_id_synced' => 0, 'last_attack_id_synced' => 0, 'last_addrs_id_synced' => 0,'prji_access_token' => null, 'prji_token_expiry' => null);
			$GLOBALS['stmts']['insert_site_default']->execute(array(json_encode($defaults)));
			$GLOBALS['stmts']['insert_site_default']->closeCursor();
	}
	
}
function createTable($TABLE) {
	switch($TABLE) {
		case 'site':
			$GLOBALS['db']->exec('create table site (id integer primary key autoincrement,info text)');
			define('INSERT_SITE_DEFAULTS',true);
			break;
		case 'addrs':
			$GLOBALS['db']->exec('create table addrs (addr_id integer primary key autoincrement,addr varchar(48) not null)');
			break;
		case 'a404':
			$GLOBALS['db']->exec('create table a404 (a404_id integer primary key autoincrement, requested_uri text not null,cookie_content text,get_content text,post_content text,useragent text,referrer text,atk_timestamp datetime not null default CURRENT_TIMESTAMP, addr_id integer not null, foreign key(addr_id) references addrs (addr_id) on delete cascade on update cascade)');
			break;
		case 'alogin':
			$GLOBALS['db']->exec('create table alogin (alogin_id integer primary key autoincrement,un varchar(40),pw varchar(40),cookie_content text,get_content text,post_content text,useragent text,referrer text,atk_timestamp datetime not null default CURRENT_TIMESTAMP,addr_id integer not null, foreign key(addr_id) references addrs (addr_id) on delete cascade on update cascade)');
			break;
		case 'aweb':
			$GLOBALS['db']->exec('create table aweb (aweb_id integer primary key autoincrement,request_uri text not null,cookie_content text, get_content text,post_content text,useragent text,referrer text,atk_timestamp datetime not null default CURRENT_TIMESTAMP,addr_id integer not null, foreign key(addr_id) references addrs (addr_id) on delete cascade on update cascade)');
			break;
	}
}
