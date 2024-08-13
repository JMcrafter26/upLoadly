<?php
$DIR = __DIR__ . '/';
$config = array(
	'db_connection' => "sqlite:" . __dir__ . "/upLoadly.sqlite3",
	'db_username' => '',
	'db_password' => '',
	'db_prefix' => '',
	'upload_path' => 'uploads',
	'max_filesize' => 100, // In MB
	'max_files' => 20,
	'expire' => 60 * 60 * 1, // After 12h
	'download_extend' => 1.5, // The expiration date will be extended by 1.5 of the expire time
);