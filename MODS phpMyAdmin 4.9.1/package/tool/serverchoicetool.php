#!/usr/bin/env php
<?php
const JSONFILEPATH = '/var/services/web/phpMyAdmin/synology_server_choice.json';

$config_M5 = array(
	"verbose" => "MariaDB 5",
	"auth_type" => "cookie",
	"host" => "localhost",
	"connect_type" => "socket",
	"socket" => "/run/mysqld/mysqld.sock",
	"compress" => false,
	"AllowNoPassword" => false
);
$config_M10 = array(
	"verbose" => "MariaDB 10",
	"auth_type" => "cookie",
	"host" => "localhost",
	"connect_type" => "socket",
	"socket" => "/run/mysqld/mysqld10.sock",
	"compress" => false,
	"AllowNoPassword" => false
);

function openFileWithLock($filename) {
	$file = fopen($filename, "r+");
	if (!$file) {
		return null;
	}
	if (!flock($file, LOCK_EX)) {
		fclose($file);
		return null;
	}
	return $file;
}

function closeFileWithLock($file) {
	flock($file, LOCK_UN);
	fclose($file);
}

function addToJson($new_server) {
	$jsonFile = openFileWithLock(JSONFILEPATH);
	if (is_null($jsonFile)) {
		return 1;
	}
	$servers = json_decode(file_get_contents(JSONFILEPATH), true);
	foreach ($servers as $server) {
		if ($server["verbose"] == $new_server["verbose"]) {
			closeFileWithLock($jsonFile);
			return 0;
		}
	}
	array_push($servers, $new_server);
	ftruncate($jsonFile, 0);
	fwrite($jsonFile, json_encode($servers));
	fflush($jsonFile);
	closeFileWithLock($jsonFile);
	return 0;
}

function deleteFromJson($del_server) {
	$jsonFile = openFileWithLock(JSONFILEPATH);
	if (is_null($jsonFile)) {
		return 1;
	}
	$old_servers = json_decode(file_get_contents(JSONFILEPATH), true);
	$new_servers = [];
	foreach ($old_servers as $server) {
		if ($server["verbose"] != $del_server["verbose"]) {
			array_push($new_servers, $server);
		}
	}
	ftruncate($jsonFile, 0);
	fwrite($jsonFile, json_encode($new_servers));
	closeFileWithLock($jsonFile);
	return 0;
}

switch ($argv[1]) {
	case "INIT":
		$jsonFile = fopen(JSONFILEPATH, "w");
		if (!$jsonFile) {
			$RET = 1;
		} else {
			fwrite($jsonFile, "[]");
			fclose($jsonFile);
		}
		$RET = 0;
		break;
	case "ADDM5":
		$RET = addToJson($config_M5);
		break;
	case "DELM5":
		$RET = deleteFromJson($config_M5);
		break;
	case "ADDM10":
		$RET = addToJson($config_M10);
		break;
	case "DELM10":
		$RET = deleteFromJson($config_M10);
		break;
	default:
		$RET = 1;
}
exit($RET);
?>
