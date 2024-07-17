<?php
require_once __DIR__ . '/../bootstrap.php';

global $serverName;

$passkeyFile = ROOT_DIR . "/../../sites/$serverName/conf/passkey";
if (!file_exists($passkeyFile)) {
    // Return the file path (note that all ini files are in the conf/ directory)
    $methods = [
        'aes-256-gcm',
        'aes-128-gcm',
    ];
    foreach ($methods as $cipher) {
        if (in_array($cipher, openssl_get_cipher_methods())) {
            //Generate a 32 character password which will encode to 64 characters in hex notation
            $key = bin2hex(openssl_random_pseudo_bytes(32));
            break;
        }
    }
    $passkeyFhnd = fopen($passkeyFile, 'w');
    fwrite($passkeyFhnd, $cipher . ':' . $key);
    fclose($passkeyFhnd);

    //Make sure the file is not readable by anyone except the aspen user
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $runningOnWindows = true;
    } else {
        $runningOnWindows = false;
    }
    if (!$runningOnWindows) {
        exec('chown aspen:aspen_apache ' . $passkeyFile);
        exec('chmod 440 ' . $passkeyFile);
    }
}