<?php
try {
	$GLOBALS['db'] = new PDO('sqlite' . ':' . DATABASE_FILENAME,null,null);
	$GLOBALS['db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	prepareStatements();
	$tables = array('site','addrs');
	foreach($tables as $table)
	{
		$GLOBALS['stmts']['table_exists']->execute(array($table));
		$exists = $GLOBALS['stmts']['table_exists']->fetchColumn();
		$GLOBALS['stmts']['table_exists']->closeCursor();
		if( $exists !== true )
			createTable($table);
	}

}
catch(PDOException $e) {
	die($e->getMessage());
}

function prepareStatements(){
	$GLOBALS['stmts'] = array();
	$GLOBALS['stmts']['table_exists'] = $GLOBASL['db']->prepare('select true from sqlite_master where type=\'table\' and name= ?');
}
function createTable($TABLE) {
	switch($TABLE) {
		case 'site':
			$GLOBALS['db']->exec('create table site (id integer primary key autoincrement,info text)');
			break;
		case 'addrs':
			$GLOBALS['db']->exec('create table addrs (addr_id integer primary key autoincrement,addr varchar(48) not null)');
			break;
		case '
	}
}
