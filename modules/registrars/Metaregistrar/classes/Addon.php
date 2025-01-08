<?php

namespace MetaregistrarModule\classes;
use Illuminate\Database\Capsule\Manager as Capsule;
use mysql_xdevapi\Exception;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;

class Addon {

    static function getConfig($params) {
        try {
            /*
            $pdo = Capsule::connection()->getPdo();
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
            */
            return array(
                "apiUsername" => array (
                    "FriendlyName" => "EPP Username",
                    "Type" => "text",
                    "Size" => "25",
                    "Description" => "Enter the EPP API username of your Metaregistrar account",
                    "Default" => "",
                ),
                "apiPassword" => array (
                    "FriendlyName" => "EPP Password",
                    "Type" => "password",
                    "Size" => "25",
                    "Description" => "Enter the EPP API password of your Metaregistrar account",
                    "Default" => "",
                ),
                "autoRenewMode" => array (
                    "FriendlyName" => "Auto renew domains",
                    "Type" => "yesno",
                    "Description" => "When selected, all created domain names will auto-renew automatically",
                    "Default" => 1,
                ),
                "debugMode" => array (
                    "FriendlyName" => "Debug Mode",
                    "Type" => "yesno",
                    "Description" => "Tick to save API requests and responses in WHMCS module log",
                    "Default" => 0,
                ),
            );
        } catch (Exception $e) {
            return array('result'=>'error', 'message' => $e->getMessage());
        }

    }
    
    static function registerDomain($params) {
        try {
            $apiData        = Helpers::getApiData();
            $apiConnection  = Api::getApiConnection($apiData);
            $domainData     = Helpers::getDomainData($params);

            if(!Domain::isAvailable($domainData, $apiConnection)) {
                throw new \Exception("Domain is already registered.");
            }
            if ($apiData["debugMode"]==1) {
                logActivity("MetaregistrarModule register ".$domainData["name"]);
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
            Domain::register($domainData, $apiConnection);

            if (!$apiData["autoRenewMode"]) {
                $domainData["autorenew"]    = false;
                Domain::setAutorenew($domainData, $apiConnection);
            }
            // Setup default DNS for this domain
            Domain::resetDNS($domainData, $apiConnection);
            // Setup default DNS for this domain

            Api::closeApiConnection($apiConnection);
            return array('result'=>'success');
        } catch(\Exception $e) {  
            try {                 //if error occured we have to remove created contacts
                foreach($contactTypeArray as $contactType) {
                    if(isset($domainData[$contactType."Id"])) {
                        $contactData = array(
                            "id" => $domainData[$contactType."Id"],
                        );
                        Contact::remove($contactData, $apiConnection);
                    }
                }
            } catch(\Exception $e2) {
                logActivity("MetaregistrarModule: ".$e2->getMessage(),$_SESSION["uid"]);
            }
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }
    }
    
    static function sync($params) {
        try {
            $apiData        = Helpers::getApiData();
            $apiConnection  = Api::getApiConnection($apiData);
            $domainData     = Helpers::getDomainData($params);
            // Check if the domain name is still in our portfolio
            // If not, the domain is transferred out
            if(!Domain::isRegistered($domainData, $apiConnection)) {
                return array(
                    'active' => false,
                    'transferredAway' => true
                );
            }

            if ($apiData["debugMode"]==1) {
                logActivity("MetaregistrarModule sync ".$domainData["name"]);
            }
            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            Api::closeApiConnection($apiConnection);

            return array(
                'expirydate' => $domainDataRemote["expirydate"],
                'active' => (in_array("ok", $domainDataRemote["statuses"]))?true:false,
                'cancelled' => (in_array("pendingDelete", $domainDataRemote["statuses"]))?true:false,
                'transferredAway' => false
            );
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
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
            return array('result'=>'success');

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
            return array('result'=>'error', 'message' => $e->getMessage());
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
            if ($apiData["debugMode"]==1) {
                logActivity("MetaregistrarModule transfersync " . $domainData["name"]);
            }
            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            Api::closeApiConnection($apiConnection);

            return array(
                'completed' => true,
                'expirydate' => $domainDataRemote["expirydate"],
            );
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
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
            if ($apiData["debugMode"]==1) {
                logActivity("MetaregistrarModule renew " . $domainData["name"]);
            }
            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            $domainData["expirydate"] = $domainDataRemote["expirydate"];

            Domain::renew($domainData, $apiConnection);

            Api::closeApiConnection($apiConnection);
            return array('result'=>'success');

        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
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
            return array('result'=>'success');

        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }    
    }
    
    static function getEppCode($params) {
        try {
            $apiData        = Helpers::getApiData();
            $apiConnection  = Api::getApiConnection($apiData);
            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                throw new \Exception("Domain is not registered.");
            }
            if ($apiData["debugMode"]==1) {
                logActivity("MetaregistrarModule geteppcode " . $domainData["name"]);
            }
            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            Api::closeApiConnection($apiConnection);

            return array('eppcode' => $domainDataRemote["eppCode"],);
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }    
    }
    
    static function getNameservers($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);
            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                return null;
            }
            if ($apiData["debugMode"]==1) {
                logActivity("MetaregistrarModule getnameservers " . $domainData["name"]);
            }
            $domainDataRemote   = Domain::getInfo($domainData, $apiConnection);

            $returnArray = array();
            $index = 1;
            foreach($domainDataRemote["nameservers"] as $nameserver) {
                $returnArray["ns".$index] = $nameserver;
                $index++;
            }
            
            Api::closeApiConnection($apiConnection);
            return $returnArray;
        
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
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
            return array('result'=>'success');

        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }    
    }
    
    static function registerNameserver($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);
            $hostData           = Helpers::getHostData($params);

            Host::register($hostData, $apiConnection);

            Api::closeApiConnection($apiConnection);
            return array('result'=>'success');
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }    
    }
    
    static function deleteNameserver($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);
            $hostData           = Helpers::getHostData($params);

            Host::delete($hostData, $apiConnection);

            Api::closeApiConnection($apiConnection);
            return array('result'=>'success');
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }    
    }
    
    static function updateNameserver($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);
            $hostData           = Helpers::getHostData($params);

            Host::update($hostData, $apiConnection);

            Api::closeApiConnection($apiConnection);
            return array('result'=>'success');
            
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
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
            if ($apiData["debugMode"]==1) {
                logActivity("MetaregistrarModule getcontactdetails " . $domainData["name"]);
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
            return array('result'=>'error', 'message' => $e->getMessage());
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
            if ($apiData["debugMode"]==1) {
                logActivity("MetaregistrarModule savecontactdetails " . $domainData["name"]);
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
            return array('result'=>'success');

        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }    
    }

    static function getDomainLock($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);
            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                throw new \Exception("Domain is not registered.");
            }
            if (Domain::isLocked($domainData,$apiConnection)) {
                Api::closeApiConnection($apiConnection);
                return 'locked';
            } else {
                Api::closeApiConnection($apiConnection);
                return 'unlocked';
            }
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }

    }

    static function saveDomainLock($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);
            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                throw new \Exception("Domain is not registered.");
            }
            Domain::setDomainLock($domainData, $apiConnection, ($params['lockenabled']=='locked' ? true : false));
            Api::closeApiConnection($apiConnection);
            return array('result'=>'success');

        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }
    }

    static function getDomainDNS($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);
            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                throw new \Exception("Domain is not registered.");
            }
            $dns = Domain::getDNS($domainData,$apiConnection);
            Api::closeApiConnection($apiConnection);
            return $dns;
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }
    }

    static function storeDomainDns($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);
            $domainData         = Helpers::getDomainData($params);

            if(!Domain::isRegistered($domainData, $apiConnection)) {
                throw new \Exception("Domain is not registered.");
            }
            Domain::saveDNS($domainData, $apiConnection, $params['dnsrecords']);
            Api::closeApiConnection($apiConnection);
            return array('result'=>'success');
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
        }
    }

    static function checkAvailability($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);
            $results = new ResultsList();

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
            return array('result'=>'error', 'message' => $e->getMessage());
        }    
    }

    static function ResetDNS($params) {
        try {
            $apiData            = Helpers::getApiData();
            $apiConnection      = Api::getApiConnection($apiData);
            $domainData         = Helpers::getDomainData($params);

            $domainname = $domainData['name'];
            if ($apiData["debugMode"]==1) {
                logActivity("RESET DNS FOR $domainname", $_SESSION["uid"]);
            }
            if(!Domain::isRegistered($domainData, $apiConnection)) {
                logActivity("IS NOT REGISTERED: $domainname",$_SESSION["uid"]);
                throw new \Exception("Domain is not registered.");
            }
            Domain::deleteDNS($domainData, $apiConnection);
            Domain::resetDNS($domainData, $apiConnection);
            return array('result'=>'success');
        } catch (\Exception $e) {
            logActivity("ERROR RESETTING DNS: ".$e->getMessage(),$_SESSION["uid"]);
            Api::closeApiConnection($apiConnection);
            return array('result'=>'error', 'message' => $e->getMessage());
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
        $html = '<h3></h3><div></div>'
            . ' <script src=\'//code.jquery.com/ui/1.11.4/jquery-ui.js\'></script>'
            . ' <script src=\'modules/registrars/Metaregistrar/templates/js/clientarea.js\'></script>'
            . ' <link href=\'//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css\' rel=\'stylesheet\' type=\'text/css\'>';
        return $html;
    }
}
