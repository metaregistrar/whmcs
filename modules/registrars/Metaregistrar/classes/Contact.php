<?php

namespace MetaregistrarModule\classes;

use \Metaregistrar\EPP\eppConnection;
use Metaregistrar\EPP\eppCreateContactResponse;
use \Metaregistrar\EPP\eppException;
use \Metaregistrar\EPP\eppContactPostalInfo;
use \Metaregistrar\EPP\eppContact;
use \Metaregistrar\EPP\eppContactHandle;
use \Metaregistrar\EPP\eppCreateContactRequest;
use \Metaregistrar\EPP\eppDeleteContactRequest;
use \Metaregistrar\EPP\eppInfoContactRequest;
use \Metaregistrar\EPP\eppInfoContactResponse;
use \Metaregistrar\EPP\eppUpdateContactRequest;
use \Metaregistrar\EPP\metaregEppUpdateContactRequest;


class Contact {
    static function register($contactData, eppConnection $apiConnection) {
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
            
            $response   = $apiConnection->request(new eppCreateContactRequest($contactInfo));
            /* @var $response eppCreateContactResponse */
            return $response->getContactId();
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function update($contactData, eppConnection $apiConnection) {
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
            
            $apiConnection->request(new eppUpdateContactRequest($contactHandle, null, null, $contactInfo));
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function addProperties($contactData, eppConnection $apiConnection) {
        try {
            $contactHandle = new eppContactHandle($contactData["id"]);
            $apiConnection->useExtension('command-ext-1.0');
            
            $request    = new metaregEppUpdateContactRequest($contactHandle, null, null, null);
            foreach($contactData["properties"] as $propertyName => $propertyValue) {
                $request -> addContactProperty($contactData["registry"], $propertyName, $propertyValue);
            }
            $apiConnection->request($request);
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function remove($contactData, eppConnection $apiConnection) {
        try {
            $contactHandle = new eppContactHandle($contactData["id"]);
            $apiConnection->request(new eppDeleteContactRequest($contactHandle));
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function getInfo($contactData, eppConnection $apiConnection) {
        try {
            $contactHandle = new eppContactHandle($contactData["id"]);
            
            $response   = $apiConnection->request(new eppInfoContactRequest($contactHandle));
            /* @var $response eppInfoContactResponse */
            $contactData["name"]            = $response->getContactName();
            $contactData["city"]            = $response->getContactCity();
            $contactData["country"]         = $response->getContactCountrycode();
            $contactData["organization"]    = $response->getContactCompanyname();
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
