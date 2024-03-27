<?php

namespace MetaregistrarModule\classes;

use \Metaregistrar\EPP\eppConnection;
use \Metaregistrar\EPP\eppException;

class Api {
    static function getApiConnection($apiData){
        try  {
            $connection = new eppConnection();
            $connection->setHostname('ssl://eppl.metaregistrar.com');
            $connection->setPort(7000);
            $connection->setUsername($apiData["username"]);
            $connection->setPassword($apiData["password"]);

            //if($apiData["debugMode"]) {
            //    $connection->enableLogging();
            //}
            if($connection->login()) {
                return $connection;
            } else {
                throw new \Exception("API Connection Login Failed");
            }
        } catch (eppException $e) {
            logActivity("MetaregistrarModule: ".$e->getMessage(), $_SESSION["uid"]);
            throw new \Exception($e->getMessage());
        }
    }
    
    static function closeApiConnection(eppConnection $apiConnection) {
        try {
            if(isset($apiConnection)) {
                $apiConnection->logout();
            }
        } catch (eppException $e) {
            logActivity("MetaregistrarModule: ".$e->getMessage(), $_SESSION["uid"]);
            throw new \Exception($e->getMessage());
        }
    }
}
