<?php

require dirname(dirname(dirname(__DIR__))).'/init.php';
require_once 'Autoloader.php';
require_once 'classes/metaregistrar-epp-client/autoloader.php';

try {
    
    \MetaregistrarModule\classes\Cron::poll();
    
} catch (Exception $e) {
    logActivity("MetaregistrarModule: ".$e->getMessage(), $_SESSION["uid"]);
}

