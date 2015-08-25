# Phpgitdeploy

Deploy your project with *php* and *git*!
This is not full deploy application, like [deployer](http://deployer.org/ "deployer"),
this is simple script for deploy your source code, only changes (delta), to remote server.

Run this script with arguments like "-name value" or "--name value".

## Version

1.0

## Features

* Get the changes of your source code and copy their to remote server.
* Show uploading progress, warnings and errors.
* Console application.

## Dependences

* [PHP](http://php.net/ "PHP") 5.4+
* [PHP FTP](http://php.net/manual/en/book.ftp.php "PHP FTP") functions,
* [git](https://git-scm.com/ "git") 1.9.4+
