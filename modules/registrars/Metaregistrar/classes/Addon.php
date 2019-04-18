<?php

namespace MetaregistrarModule\classes;
use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;

class Addon {
    static function getConfig($params) {
        
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        $query =  " CREATE TABLE IF NOT EXISTS MetaregistrarPollData("
                . "     id int PRIMARY KEY NOT NULL AUTO_INCREMENT, "
                . "     domainId int NOT NULL, "
                . "     messageId int NOT NULL, "
                . "     domain VARCHAR(255) NOT NULL, "
                . "     description VARCHAR(255) NOT NULL, "
                . "     date DATE NOT NULL "
                . " ) DEFAULT CHARSET=utf8 DEFAULT COLLATE utf8_unicode_ci;";
        
        $statement = $pdo->prepare($query);
        $statement->execute();
        $pdo->commit();
        
        return array(
            "apiHostname" => array (
                "FriendlyName" => "Host",
                "Type" => "text",
                "Size" => "25", 
                "Description" => "",
                "Default" => "",
            ),
            "apiPort" => array (
                "FriendlyName" => "Port",
                "Type" => "text",
                "Size" => "25", 
                "Description" => "",
                "Default" => "",
            ),
            "apiUsername" => array (
                "FriendlyName" => "Username",
                "Type" => "text",
                "Size" => "25", 
                "Description" => "",
                "Default" => "",
            ),
            "apiPassword" => array (
                "FriendlyName" => "Password",
                "Type" => "password",
                "Size" => "25",
                "Description" => "",
                "Default" => "",
            ),
            "debugMode" => array (
                "FriendlyName" => "Debug Mode",
                "Type" => "yesno",
                "Description" => "Tick to save API requests and responses in WHMCS module log",
            ),
            
        );
    }
    
    static function registerDomain($params) {
        try {
            $apiData        = Helpers::getApiData();
            $apiConnection  = Api::getApiConnection($apiData);
            $domainData     = Helpers::getDomainData($params);
            if(!Domain::isAvailable($domainData, $apiConnection)) {
                throw new \Exception("Domain already registered.");
            }

            $contactTypeArray = array(
                Helpers::CONTACT_TYPE_REGISTRANT,
                Helpers::CONTACT_TYPE_ADMIN,
                Helpers::CONTACT_TYPE_TECH,
                Helpers::CONTACT_TYPE_BILLING,
            );
            
            foreach($contactTypeArray as $contactType) {
                $contactData = Helpers::getContactData($params, $contactType);
                $domainData[$contactType."Id"] = Contact::register($contactData, $apiConnection);
                if(!empty($contactData["registry"])&&!empty($contactData["properties"])) {
                    $contactData["id"] = $domainData[$contactType."Id"];
                    Contact::addProperties($contactData, $apiConnection);
                }
            }
            logActivity("MetaregistrarModule: Domainregister", $_SESSION["uid"]);
            Domain::register($domainData, $apiConnection);
            //$domainData["autorenew"]    = false;
            //Domain::setAutorenew($domainData, $apiConnection);
            
            Api::closeApiConnection($apiConnection);
            
        } catch(\Exception $e) {  
            try {                                                               //if error occured we have to remove created contacts
                foreach($contactTypeArray as $contactType) {
                    if(isset($domainData[$contactType."Id"])) {
                        $contactData = array(
                            "id" => $domainData[$contactType."Id"],
                        );
                        Contact::remove($contactData, $apiConnection);
                    }
                }
            } catch(\Exception $e2) {
                $loggedWhmcsUserId = $_SESSION["uid"];
                logActivity("MetaregistrarModule: ".$e2->getMessage(), $loggedWhmcsUserId);
            }
            
            Api::closeApiConnection($apiConnection);
            throw $e;
        }
    }
    
    static function sync($params) {
        try {
            $apiData        = Helpers::getApiData();
            $apiConnection  = Api::getApiConnection($apiData);

            $domainData     = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                return array(
                    'active' => false,
                );
            }

            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            Api::closeApiConnection($apiConnection);

            return array(
                'expirydate' => $domainDataRemote["expDate"],
                'active' => (in_array("ok", $domainDataRemote["statuses"]))?true:false
            );
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function transferDomain($params) {
        try {
            $apiData        = Helpers::getApiData();
            $apiConnection  = Api::getApiConnection($apiData);

            $domainData     = Helpers::getDomainData($params);

            if(Domain::isAvailable($domainData, $apiConnection)) {
                throw new \Exception("Domain is free for registration and cannot be transferred.");
            }

            $contactTypeArray = array(
                Helpers::CONTACT_TYPE_REGISTRANT,
                Helpers::CONTACT_TYPE_ADMIN,
                Helpers::CONTACT_TYPE_TECH,
                Helpers::CONTACT_TYPE_BILLING,
            );
        
            foreach($contactTypeArray as $contactType) {
                $contactData = Helpers::getContactData($params, $contactType);
                $domainData[$contactType."Id"] = Contact::register($contactData, $apiConnection);
                if(!empty($contactData["registry"])&&!empty($contactData["properties"])) {
                    $contactData["id"] = $domainData[$contactType."Id"];
                    Contact::addProperties($contactData, $apiConnection);
                }
            }
            Domain::transfer($domainData, $apiConnection);
            
            Api::closeApiConnection($apiConnection);
            
        } catch(\Exception $e) {  
            try {                                                               //if error occured we have to remove created contacts
                foreach($contactTypeArray as $contactType) {
                    if(isset($domainData[$contactType."Id"])) {
                        $contactData = array(
                            "id" => $domainData[$contactType."Id"],
                        );
                        Contact::remove($contactData, $apiConnection);
                    }
                }
            } catch(\Exception $e2) {
                $loggedWhmcsUserId = $_SESSION["uid"];
                logActivity("MetaregistrarModule: ".$e2->getMessage(), $loggedWhmcsUserId);
            }
            
            Api::closeApiConnection($apiConnection);
            throw $e;
        }
    }
    
    static function transferSync($params) {
        try {
            $apiData        = Helpers::getApiData();
            $apiConnection  = Api::getApiConnection($apiData);

            $domainData     = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                return array();
            }

            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            Api::closeApiConnection($apiConnection);

            return array(
                'completed' => true,
                'expirydate' => $domainDataRemote["expDate"],
            );
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }
    }
    
    static function renewDomain($params) {
        try {
            $apiData        = Helpers::getApiData();
            $apiConnection  = Api::getApiConnection($apiData);

            $domainData     = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                throw new \Exception("Domain is not registered.");
            }

            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            $domainData["expDate"] = $domainDataRemote["expDate"];

            Domain::renew($domainData, $apiConnection);

            Api::closeApiConnection($apiConnection);
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function deleteDomain($params) {
        try {
            $apiData        = Helpers::getApiData();
            $apiConnection  = Api::getApiConnection($apiData);

            $domainData     = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                return;
            }

            Domain::delete($domainData, $apiConnection);

            Api::closeApiConnection($apiConnection);
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function getEppCode($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);

            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                throw new \Exception("Domain is not registered.");
            }

            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            Api::closeApiConnection($apiConnection);

            return array(
                'eppcode' => $domainDataRemote["eppCode"],
            );
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function getNameservers($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);

            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                return;
            }

            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            //$returnArray = array("success"=>true);
            $index = 1;
            foreach($domainDataRemote["nameservers"] as $nameserver) {
                $returnArray["ns".$index] = $nameserver;
                $index++;
            }
            
            Api::closeApiConnection($apiConnection);
            return $returnArray;
        
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function saveNameservers($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);

            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                throw new \Exception("Domain is not registered.");
            }

            Domain::updateNameservers($domainData, $apiConnection);

            Api::closeApiConnection($apiConnection);
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function registerNameserver($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);

            $hostData           = Helpers::getHostData($params);

            Host::register($hostData, $apiConnection);

            Api::closeApiConnection($apiConnection);
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function deleteNameserver($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);

            $hostData           = Helpers::getHostData($params);

            Host::delete($hostData, $apiConnection);

            Api::closeApiConnection($apiConnection);
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function updateNameserver($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);

            $hostData           = Helpers::getHostData($params);

            Host::update($hostData, $apiConnection);

            Api::closeApiConnection($apiConnection);
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function getContactDetails($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);

            $apiConnection->setTimeout(30);

            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                throw new \Exception("Domain is not registered.");
            }

            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            $registrantData["id"] = $domainDataRemote["registrantId"];
            $registrantDataRemote = Contact::getInfo($registrantData, $apiConnection);

            $adminData["id"] = $domainDataRemote["adminId"];
            $adminDataRemote = Contact::getInfo($adminData, $apiConnection);

            $techData["id"] = $domainDataRemote["techId"];
            $techDataRemote = Contact::getInfo($techData, $apiConnection);

            $billingData["id"] = $domainDataRemote["billingId"];
            $billingDataRemote = Contact::getInfo($billingData, $apiConnection);

            Api::closeApiConnection($apiConnection);

            return array(
                "Registrant"    => Helpers::formatContactData($registrantDataRemote),
                "Admin"         => Helpers::formatContactData($adminDataRemote),
                "Technical"     => Helpers::formatContactData($techDataRemote),
                "Billing"       => Helpers::formatContactData($billingDataRemote),
            );
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function saveContactDetails($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);

            $apiConnection->setTimeout(30);

            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                throw new \Exception("Domain is not registered.");
            }

            $domainDataRemote       = Domain::getInfo($domainData, $apiConnection);
            
            $contactTypeArray = array(
                Helpers::CONTACT_TYPE_REGISTRANT,
                Helpers::CONTACT_TYPE_ADMIN,
                Helpers::CONTACT_TYPE_TECH,
                Helpers::CONTACT_TYPE_BILLING,
            );
            
            foreach($contactTypeArray as $contactType) {
                $contactData = Helpers::getContactData($params, $contactType);
                $contactData["id"] = $domainDataRemote[$contactType."Id"];
                Contact::update($contactData, $apiConnection);
            }
            
            Api::closeApiConnection($apiConnection);
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }    
    }
    
    static function checkAvailability($params) {
        try {
            $results = new ResultsList();

            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);

            foreach ($params["tlds"] as $tld) {
                $domainData["search"][] = array(
                    "searchTerm" => $params["searchTerm"],
                    "tld" => $tld
                );
            }
            
            $apiResults = Domain::isAvailableMulti($domainData, $apiConnection);
            
            foreach ($apiResults as $tld => $isAvailable) {
                $searchResult = new SearchResult($params["searchTerm"], $tld);
                if($isAvailable) {
                    $status = SearchResult::STATUS_NOT_REGISTERED;
                } else {
                    $status = SearchResult::STATUS_REGISTERED;
                }
                $searchResult->setStatus($status);
                $results->append($searchResult);
            }
            
            Api::closeApiConnection($apiConnection);

            return $results;
        
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw new \Exception($e->getMessage());
        }    
    }
    
    static function clientArea($params) {
        
        $table = Helpers::createPollDataTable($params["domainid"]);
        
        $html = ''
        . ' <h3>Poll Messages</h3>'
        . ' <div>'
        .       $table
        . ' </div>'
        . ' '
        . ' <script src=\'//code.jquery.com/ui/1.11.4/jquery-ui.js\'></script>'
        . ' <script src=\'modules/registrars/Metaregistrar/templates/js/clientarea.js\'></script>'
        . ' <link href=\'//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css\' rel=\'stylesheet\' type=\'text/css\'>';
        
        return $html;
    }
}
