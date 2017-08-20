<?php

error_reporting(E_ALL);

include_once dirname(__DIR__).'/vendor/autoload.php';
include_once __DIR__.'/Base.php';

if (class_exists('PHPUnit\Framework\Error\Notice')) {
    PHPUnit\Framework\Error\Notice::$enabled = true;
} else {
    PHPUnit_Framework_Error_Notice::$enabled = true;
}
