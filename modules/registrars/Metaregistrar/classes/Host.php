<?php

namespace MetaregistrarModule\classes;

use \Metaregistrar\EPP\eppConnection;
use \Metaregistrar\EPP\eppException;
use \Metaregistrar\EPP\eppHost;
use \Metaregistrar\EPP\eppCreateHostRequest;
use \Metaregistrar\EPP\eppDeleteHostRequest;
use \Metaregistrar\EPP\eppUpdateHostRequest;

class Host {
    static function register($hostData, eppConnection $apiConnection) {
        try {
            $host = new eppHost($hostData["name"], $hostData["ip"]);
            $apiConnection->request(new eppCreateHostRequest($host));
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function delete($hostData, eppConnection $apiConnection) {
        try {
            $host = new eppHost($hostData["name"], $hostData["ip"]);
            $apiConnection->request(new eppDeleteHostRequest($host));
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function update($hostData, eppConnection $apiConnection) {
        try {
            $host = new eppHost($hostData["name"], $hostData["ip"]);
            
            $hostAdd = new eppHost($hostData["name"], $hostData["ipNew"]);
            $hostDel = new eppHost($hostData["name"], $hostData["ipCurrent"]);
            
            $apiConnection->request(new eppUpdateHostRequest($host, $hostAdd, $hostDel, null));
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
