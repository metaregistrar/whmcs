<?php

namespace MetaregistrarModule\classes;

use \Metaregistrar\EPP\eppConnection;
use \Metaregistrar\EPP\eppException;

class Api {
    static function getApiConnection($apiData){
        try  {
            $connection = new eppConnection();
            if ($apiData["LiveServer"]==1) {
                $connection->setHostname('ssl://eppl.metaregistrar.com');
            } else {
                $connection->setHostname('ssl://eppl-ote.metaregistrar.com');
            }
            $connection->setPort(7000);
            $connection->setUsername($apiData["username"]);
            $connection->setPassword($apiData["password"]);
            $connection->useExtension('dns-ext-1.0');
            $connection->useExtension('secDNS-1.1');
            $connection->useExtension('command-ext-1.0');

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
