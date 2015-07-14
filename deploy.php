<?php

$appDir = (__DIR__);
include_once $appDir . '/GitDeploy.php';

try {

	$gitDeploy = new \gracerpro\gitdeploy\GitDeploy();
	$gitDeploy->deploy();

} catch (Exception $ex) {
	echo "Exception: {$ex->getMessage()}\n";
}
