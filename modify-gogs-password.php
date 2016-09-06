#!/usr/bin/env php
<?php

list($phpSelf, $dbFile, $userName) = $argv;

if (empty($argv) || in_array(strtolower($argv[1]), ['-?', '-h', '--help'])){
    echo <<<HELP_MESSAGE
Usage: php {$phpSelf} <db-file> <user-name>
Help yourself change the password of gogs.
HELP_MESSAGE;
    die;
}

if (!is_file($dbFile)){
    die('Error: cannot find the db-file: ' . $dbFile  . PHP_EOL);
}

if (!$userName){
    die('Error: you must specify a user name!' . PHP_EOL);
}

$db = new PDO(sprintf('sqlite:%s', realpath($dbFile)));
if (!$db){
    die('Error: cannot open db!' . PHP_EOL);
}

echo "Please enter your new password: ";
$passwd = (function_exists('readline') ? readline() : fgets(STDIN));


$cmd = $db->prepare('SELECT salt FROM user WHERE name=:name');
if (!$cmd){
    die("Error: cannot prepare select sql!" . PHP_EOL);
}

$cmd->execute([':name' => $userName]);
$data = $cmd->fetch(PDO::FETCH_ASSOC);
$salt = $data['salt'];
if (!$salt){
    die("Error: do not get the salt of user {$userName}! Did you enter a exists user?" . PHP_EOL);
}

$cmd = $db->prepare('UPDATE user SET passwd=:passwd WHERE name=:name');
if (!$cmd){
    die("Error: cannot prepare update sql!" . PHP_EOL);
}

$done = $cmd->execute([
    ':passwd' => bin2hex(hash_pbkdf2('sha256', $passwd, $salt, 10000, 50, true)),
    ':name' => $userName,
]);

echo $done . " Done." . PHP_EOL;


