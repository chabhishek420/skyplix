<?php

// ===== convert errors to exceptions =====

set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

// ===== set default php settings =====

http_response_code(500);
ini_set('error_reporting', E_ALL|E_STRICT);
ini_set('display_errors', (PHP_SAPI !== 'cli' && empty(Phar::running())));
ini_set('log_errors', true);
ini_set('html_errors', false);
ini_set('default_mimetype', null);
ini_set('default_charset', 'UTF-8');
/** @noinspection PhpComposerExtensionStubsInspection */
mb_detect_order(['UTF-8', 'ISO-8859-15', 'ISO-8859-1', 'CP1252', 'CP1251']);
/** @noinspection PhpComposerExtensionStubsInspection */
mb_internal_encoding('UTF-8');
/** @noinspection PhpComposerExtensionStubsInspection */
mb_regex_encoding('UTF-8');

// ===== initialize autoloader =====

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/mockFunctions.php';

// ===== include local bootstrap script =====

@include __DIR__.'/bootstrap.local.php';
