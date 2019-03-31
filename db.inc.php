<?php
try {
	$GLOBALS['db'] = new PDO('sqlite' . ':' . DATABASE_FILENAME,null,null);
	$GLOBALS['db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$GLOBALS['db']->exec( 'PRAGMA foreign_keys = ON;' );
	$GLOBALS['stmts'] = array();
	$GLOBALS['stmts']['table_exists'] = $GLOBALS['db']->prepare('select name from sqlite_master where type=\'table\' and name= ?');
	$tables = array('site','addrs','a404','aweb');
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
	$GLOBALS['stmts']['insert_site_default'] = $GLOBALS['db']->prepare('insert into site(info) values (?)');
	$GLOBALS['stmts']['get_site_data'] = $GLOBALS['db']->prepare('select info from site');
	$GLOBALS['stmts']['get_addr_id'] = $GLOBALS['db']->prepare('select addr_id from addrs where addr = ?');
	$GLOBALS['stmts']['insert_addr'] = $GLOBALS['db']->prepare('insert into addrs (addr) values (?)');
	if( defined('INSERT_SITE_DEFAULTS') && INSERT_SITE_DEFAULTS)
	{
			$defaults = array('db_creation_timestamp' => gmdate('c'),'last_sync_timestamp' => null,'sync_count' => 0, 'last_login_attack_id_synced' => 0, 'last_404_attack_id_synced' => 0, 'last_attack_id_synced' => 0, 'last_addrs_id_synced' => 0);
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
