<?php

namespace MetaregistrarModule\classes;

use \Metaregistrar\EPP\eppConnection;
use \Metaregistrar\EPP\eppException;

class Api {
    static function getApiConnection($apiData){
        $connection = new eppConnection();
        $connection->setHostname($apiData["host"]);
        $connection->setPort($apiData["port"]);
        $connection->setUsername($apiData["username"]);
        $connection->setPassword($apiData["password"]);
        if($apiData["debugMode"]) {
            $connection->enableWhmcsLogging();
        }
        if($connection->login()) {
            return $connection;
        } else {
            throw new \Exception("API Connection Failed");
        }
    }
    
    static function closeApiConnection($apiConnection) {
        try {
            if(isset($apiConnection)) {
                $apiConnection->logout();
            }
        } catch (eppException $e) {
            $loggedWhmcsUserId = $_SESSION["uid"];
            logActivity("MetaregistrarModule: ".$e->getMessage(), $loggedWhmcsUserId);
        }
    }
}
