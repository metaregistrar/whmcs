<?php
namespace MetaregistrarModule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function autoloader($class) {
    $file = str_replace(__NAMESPACE__."\\", '', $class);
    $file = str_replace("\\", DIRECTORY_SEPARATOR, $file);
    $file = $file.".php";
    
    $absolutePath = dirname(__FILE__).DIRECTORY_SEPARATOR.$file;
    if(file_exists($absolutePath)) {
        include_once $file;
    }
}
spl_autoload_register('MetaregistrarModule\autoloader');
