<?php

include_once __DIR__ . '/GitDeploy.php';

try {

	$gitDeploy = new \gracerpro\gitdeploy\GitDeploy();
	$gitDeploy->deploy();

} catch (Exception $ex) {
	echo "Exception: {$ex->getMessage()}\n";
}
