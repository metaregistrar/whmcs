<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once 'Autoloader.php';
require_once 'classes/metaregistrar-epp-client/autoloader.php';

function Hook_Metaregistrar_AdminAreaTable($params) {
    try {
        
        return \MetaregistrarModule\classes\Hooks::adminAreaTable();
        
    } catch (Exception $e) {
        $loggedWhmcsUserId = $_SESSION["uid"];
        logActivity("MetaregistrarModule: ".$e->getMessage(), $loggedWhmcsUserId);
    }
}
add_hook('AdminAreaFooterOutput', 1, 'Hook_Metaregistrar_AdminAreaTable');
