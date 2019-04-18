<?php

namespace MetaregistrarModule\classes;

use \Metaregistrar\EPP\eppConnection;
use \Metaregistrar\EPP\eppException;
use \Metaregistrar\EPP\eppContactPostalInfo;
use \Metaregistrar\EPP\eppContact;
use \Metaregistrar\EPP\eppContactHandle;
use \Metaregistrar\EPP\eppCreateContactRequest;
use \Metaregistrar\EPP\eppDeleteContactRequest;
use \Metaregistrar\EPP\eppInfoContactRequest;
use \Metaregistrar\EPP\eppUpdateContactRequest;
use \Metaregistrar\EPP\metaregEppUpdateContactRequest;


class Contact {
    static function register($contactData, $apiConnection) {
        try {
            $postalInfo = new eppContactPostalInfo(
                $contactData["name"],
                $contactData["city"],
                $contactData["country"],
                $contactData["organization"],
                $contactData["adress"],
                $contactData["region"],
                $contactData["postCode"]
            );

            $contactInfo = new eppContact(
                $postalInfo,
                $contactData["email"], 
                $contactData["phone"],
                $contactData["fax"]
            );
            
            $contactInfo->setPassword($contactData["password"]);
            
            $request    = new eppCreateContactRequest($contactInfo);
            $response   = $apiConnection->request($request);
            
            return $response->getContactId();
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function update($contactData, $apiConnection) {
        try {
            
            $postalInfo = new eppContactPostalInfo(
                $contactData["name"],
                $contactData["city"],
                $contactData["country"],
                $contactData["organization"],
                $contactData["adress"],
                $contactData["region"],
                $contactData["postCode"]
            );

            $contactInfo = new eppContact(
                $postalInfo,
                $contactData["email"], 
                $contactData["phone"],
                $contactData["fax"]
            );
            
            $contactInfo->setPassword($contactData["password"]);
            
            $contactHandle = new eppContactHandle($contactData["id"]);
            
            $request    = new eppUpdateContactRequest($contactHandle, null, null, $contactInfo);
            $response   = $apiConnection->request($request);
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function addProperties($contactData, $apiConnection) {
        try {
            $contactHandle = new eppContactHandle($contactData["id"]);
            
            $apiConnection->useExtension('command-ext-1.0');
            
            $request    = new metaregEppUpdateContactRequest($contactHandle, null, null, null);
            
            foreach($contactData["properties"] as $propertyName => $propertyValue) {
                $request -> addContactProperty($contactData["registry"], $propertyName, $propertyValue);
            }
            
            $response   = $apiConnection->request($request);
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function remove($contactData, $apiConnection) {
        try {
            $contactHandle = new eppContactHandle($contactData["id"]);
            
            $request    = new eppDeleteContactRequest($contactHandle);
            $response   = $apiConnection->request($request);
        
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function getInfo($contactData, $apiConnection) {
        try {
            $contactHandle = new eppContactHandle($contactData["id"]);
            
            $request    = new eppInfoContactRequest($contactHandle);
            $response   = $apiConnection->request($request);
            
            $contactData["name"]            = $response->getContactName();
            $contactData["city"]            = $response->getContactCity();
            $contactData["country"]         = $response->getContactCountryCode();
            $contactData["organization"]    = $response->getContactCompanyName();
            $contactData["adress"]          = $response->getContactStreet();
            $contactData["region"]          = $response->getContactProvince();
            $contactData["postCode"]        = $response->getContactZipcode();
            $contactData["email"]           = $response->getContactEmail();
            $contactData["phone"]           = $response->getContactVoice();
            $contactData["fax"]             = $response->getContactFax();
            
            return $contactData;
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
