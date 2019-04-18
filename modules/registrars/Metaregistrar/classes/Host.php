<?php

namespace MetaregistrarModule\classes;

use \Metaregistrar\EPP\eppConnection;
use \Metaregistrar\EPP\eppException;

use \Metaregistrar\EPP\eppHost;
use \Metaregistrar\EPP\eppCreateHostRequest;
use \Metaregistrar\EPP\eppDeleteHostRequest;
use \Metaregistrar\EPP\eppUpdateHostRequest;

class Host {
    static function register($hostData, $apiConnection) {
        try {
            $host = new eppHost($hostData["name"], $hostData["ip"]);
            
            $request    = new eppCreateHostRequest($host);
            $response   = $apiConnection->request($request);
            
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function delete($hostData, $apiConnection) {
        try {
            $host = new eppHost($hostData["name"], $hostData["ip"]);
            
            $request    = new eppDeleteHostRequest($host);
            $response   = $apiConnection->request($request);
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function update($hostData, $apiConnection) {
        try {
            $host = new eppHost($hostData["name"], $hostData["ip"]);
            
            $hostAdd = new eppHost($hostData["name"], $hostData["ipNew"]);
            $hostDel = new eppHost($hostData["name"], $hostData["ipCurrent"]);
            
            $request    = new eppUpdateHostRequest($host, $hostAdd, $hostDel, null);
            $response   = $apiConnection->request($request);
            
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
