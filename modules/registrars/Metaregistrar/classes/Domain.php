<?php

namespace MetaregistrarModule\classes;

use \Metaregistrar\EPP\eppConnection;
use \Metaregistrar\EPP\eppException;
use \Metaregistrar\EPP\eppIDNA;
use \Metaregistrar\EPP\eppContactHandle;
use \Metaregistrar\EPP\eppDomain;
use \Metaregistrar\EPP\eppHost;
use \Metaregistrar\EPP\eppCreateDomainRequest;
use \Metaregistrar\EPP\eppDeleteDomainRequest;
use \Metaregistrar\EPP\eppTransferRequest;
use \Metaregistrar\EPP\metaregEppTransferExtendedRequest;
use \Metaregistrar\EPP\eppRenewRequest;
use \Metaregistrar\EPP\eppInfoDomainRequest;
use \Metaregistrar\EPP\eppInfoDomainResponse;
use \Metaregistrar\EPP\eppUpdateDomainRequest;
use \Metaregistrar\EPP\metaregEppAutorenewRequest;
use \Metaregistrar\EPP\eppCheckDomainRequest;
use \Metaregistrar\EPP\eppCheckDomainResponse;

class Domain {
    static function register($domainData, eppConnection $apiConnection) {
        try {
            $domain = new eppDomain($domainData["name"]);
            $domain->setRegistrant(new eppContactHandle($domainData["registrantId"],    eppContactHandle::CONTACT_TYPE_REGISTRANT));
            $domain->addContact(new eppContactHandle($domainData["adminId"],            eppContactHandle::CONTACT_TYPE_ADMIN));
            $domain->addContact(new eppContactHandle($domainData["techId"],             eppContactHandle::CONTACT_TYPE_TECH));
            $domain->addContact(new eppContactHandle($domainData["billingId"],          eppContactHandle::CONTACT_TYPE_BILLING));
            
            foreach($domainData["nameservers"] as $nameserver) {
                $domain->addHost(new eppHost($nameserver));
            }
            
            $domain->setPeriod($domainData["period"]);
            $domain->setPeriodUnit(eppDomain::DOMAIN_PERIOD_UNIT_Y);
            $domain->setAuthorisationCode($domainData["authorisationCode"]);
            
            $apiConnection->request(new eppCreateDomainRequest($domain));
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function delete($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            $domain = new eppDomain($idna->encode($domainData["name"]));
            $apiConnection->request(new eppDeleteDomainRequest($domain));
        
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function transfer($domainData, eppConnection $apiConnection) {
        try {
            $domain = new eppDomain($domainData["name"]);
            $domain->setRegistrant(new eppContactHandle($domainData["registrantId"],    eppContactHandle::CONTACT_TYPE_REGISTRANT));
            $domain->addContact(new eppContactHandle($domainData["adminId"],            eppContactHandle::CONTACT_TYPE_ADMIN));
            $domain->addContact(new eppContactHandle($domainData["techId"],             eppContactHandle::CONTACT_TYPE_TECH));
            $domain->addContact(new eppContactHandle($domainData["billingId"],          eppContactHandle::CONTACT_TYPE_BILLING));
            
            foreach($domainData["nameservers"] as $nameserver) {
                $domain->addHost(new eppHost($nameserver));
            }
            
            $domain->setPeriod($domainData["period"]);
            $domain->setPeriodUnit(eppDomain::DOMAIN_PERIOD_UNIT_Y);
            $domain->setAuthorisationCode($domainData["eppCode"]);
            
            $apiConnection->useExtension('command-ext-1.0');
            
            $apiConnection->request(new metaregEppTransferExtendedRequest(eppTransferRequest::OPERATION_REQUEST,$domain));
        
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function renew($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domain = new eppDomain($idna->encode($domainData["name"]));
            $domain->setPeriod($domainData["period"]);
            $domain->setPeriodUnit(eppDomain::DOMAIN_PERIOD_UNIT_Y);
            
            $apiConnection->request(new eppRenewRequest($domain, $domainData["expDate"]));

        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function updateNameservers($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domain = new eppDomain($idna->encode($domainData["name"]));
            
            $domainDataRemote = self::getInfo($domainData, $apiConnection);
            
            $domainDel  = new eppDomain($domainData["name"]);
            foreach($domainDataRemote["nameservers"] as $nameserver) {
                if(!in_array($nameserver, $domainData["nameservers"])) {
                    $domainDel->addHost(new eppHost($nameserver));
                }
            }
            
            $domainAdd  = new eppDomain($domainData["name"]);
            foreach($domainData["nameservers"] as $nameserver) {
                if(!in_array($nameserver, $domainDataRemote["nameservers"])) {
                    $domainAdd->addHost(new eppHost($nameserver));
                }
            }
            
            $apiConnection->request(new eppUpdateDomainRequest($domain, $domainAdd, $domainDel, null));
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function setAutorenew($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domain = new eppDomain($idna->encode($domainData["name"]));
            $apiConnection->useExtension('command-ext-1.0');
            $apiConnection->request(new metaregEppAutorenewRequest($domain, $domainData["autorenew"]));
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function getInfo($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domain = new eppDomain($idna->encode($domainData["name"]));
            
            $response   = $apiConnection->request(new eppInfoDomainRequest($domain));
            /* @var $response eppInfoDomainResponse */
            $domain = $response->getDomain();
            foreach ($domain->getHosts() as $nameserver) {
                /* @var $nameserver eppHost */
                $domainDataRemote["nameservers"][] = $nameserver->getHostname();
            }

            $domainDataRemote["registrantId"] = $domain->getRegistrant();
            
            foreach ($domain->getContacts() as $contact) {
                /* @var $contact eppContactHandle */
                $domainDataRemote[$contact->getContactType()."Id"] = $contact->getContactHandle();
            }
            $domainDataRemote["name"] = $domainData["name"];
            $domainDataRemote["period"] = $domain->getPeriod();
            $domainDataRemote["eppCode"] = $domain->getAuthorisationCode();
            $domainDataRemote["expDate"] = substr($response->getDomainExpirationDate(),0,10);
            $domainDataRemote["statuses"] = $response->getDomainStatuses();
            return $domainDataRemote;

        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function isAvailable($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domains = array($idna->encode($domainData["name"]));
            
            $response = $apiConnection->request(new eppCheckDomainRequest($domains));
            /* @var $response eppCheckDomainResponse */
            $checkResult = $response->getCheckedDomains();
            
            return $checkResult[0]["available"];
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function isAvailableMulti($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            $domains = array();
            
            foreach($domainData["search"] as $search) {
                $name       = $search["searchTerm"].$search["tld"];
                $domains[]  = $idna->encode($name);
            }
            
            $response = $apiConnection->request(new eppCheckDomainRequest($domains));
            /* @var $response eppCheckDomainResponse */
            $checkResult = $response->getCheckedDomains();
            $results = array();
            foreach ($domainData["search"] as $index => $search) {
                $results[$search["tld"]] = $checkResult[$index]["available"];
            }
            
            return $results;
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function isRegistered($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            $domain = new eppDomain($idna->encode($domainData["name"]));
            $apiConnection->request(new eppInfoDomainRequest($domain));
            
            return true;
            
        } catch (eppException $e) {
            $errorMessage = $e->getMessage();
            if($errorMessage == "Error 2303: Object does not exist") {
                return false;
            } else {
                throw new \Exception($e->getMessage());
            }
        }
    }

    static function isLocked($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            $domain = new eppDomain($idna->encode($domainData["name"]));
            $result = $apiConnection->request(new eppInfoDomainRequest($domain));
            /* @var $result eppInfoDomainResponse */
            foreach ($result->getDomainStatuses() as $status) {
                if ($status == 'clientUpdateProhibited') {
                    return true;
                }
            }
            return false;
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    static function setDomainLock($domainData, eppConnection $apiConnection, $locked) {
        try {
            $idna = new eppIDNA();
            $domain = new eppDomain($idna->encode($domainData["name"]));
            if ($locked) {
                $rem = null;
                $add = new eppDomain($idna->encode($domainData["name"]));
                $add->addStatus(eppDomain::STATUS_CLIENT_UPDATE_PROHIBITED);
            } else {
                $add = null;
                $rem = new eppDomain($idna->encode($domainData["name"]));
                $rem->addStatus(eppDomain::STATUS_CLIENT_UPDATE_PROHIBITED);
            }
            $apiConnection->request(new eppUpdateDomainRequest($domain, $add, $rem, null));
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
