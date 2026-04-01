<?php

$include = [
    'composer.json',
    'LICENSE.txt',
    'autoload.php',
    'src/',
];

if (!Phar::canWrite()) {
    trigger_error(
        'Unable to operate with phar files set to readonly.',
        E_USER_ERROR
    );
}

chdir(__DIR__);

/** @noinspection PhpComposerExtensionStubsInspection */
$config = json_decode(file_get_contents(__DIR__.'/composer.json'));
$file = __DIR__.'/'.str_replace('/', '.', $config->name).'.phar';

if (file_exists($file)) {
    unlink($file);
}

$phar = new Phar($file);
foreach ($include as $add) {
    if (!is_dir($add)) {
        $phar->addFile($add);
        continue;
    }
    $phar->buildFromIterator(
        new CallbackFilterIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($add)),
            function ($current) {
                return is_file($current);
            }
        ),
        __DIR__
    );
}
$phar->setStub("<?php
Phar::mapPhar('self.phar');
if (count(debug_backtrace()) < 1) {
    http_response_code(500);
    exit(255);
}
require 'phar://self.phar/autoload.php';
__HALT_COMPILER();");

if (!file_exists($file)) {
    trigger_error(
        'Phar file not written.',
        E_USER_ERROR
    );
}

echo 'Build: '.$file.PHP_EOL;
require $file;
echo 'Version: '.\BjoernGoetschke\Psr7Cookies\Version::VERSION.PHP_EOL;
