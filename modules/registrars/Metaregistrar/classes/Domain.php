<?php

namespace MetaregistrarModule\classes;

use \Metaregistrar\EPP\eppConnection;
use \Metaregistrar\EPP\eppException;
use \Metaregistrar\EPP\eppIDNA;

use \Metaregistrar\EPP\eppContactHandle;
use \Metaregistrar\EPP\eppDomain;
use \Metaregistrar\EPP\eppCreateDomainRequest;
use \Metaregistrar\EPP\eppDeleteDomainRequest;
use \Metaregistrar\EPP\eppTransferRequest;
use \Metaregistrar\EPP\metaregEppTransferExtendedRequest;
use \Metaregistrar\EPP\eppRenewRequest;
use \Metaregistrar\EPP\eppInfoDomainRequest;
use \Metaregistrar\EPP\eppUpdateDomainRequest;
use \Metaregistrar\EPP\metaregEppAutorenewRequest;
use \Metaregistrar\EPP\eppCheckDomainRequest;

class Domain {
    static function register($domainData, $apiConnection) {
        try {
            $domain = new eppDomain($domainData["name"]);
            $domain->setRegistrant(new eppContactHandle($domainData["registrantId"],    eppContactHandle::CONTACT_TYPE_REGISTRANT));
            $domain->addContact(new eppContactHandle($domainData["adminId"],            eppContactHandle::CONTACT_TYPE_ADMIN));
            $domain->addContact(new eppContactHandle($domainData["techId"],             eppContactHandle::CONTACT_TYPE_TECH));
            $domain->addContact(new eppContactHandle($domainData["billingId"],          eppContactHandle::CONTACT_TYPE_BILLING));
            
            foreach($domainData["nameservers"] as $nameserver) {
                $domain->addHost(new \Metaregistrar\EPP\eppHost($nameserver));
            }
            
            $domain->setPeriod($domainData["period"]);
            $domain->setPeriodUnit(eppDomain::DOMAIN_PERIOD_UNIT_Y);
            $domain->setAuthorisationCode($domainData["authorisationCode"]);
            
            $request    = new eppCreateDomainRequest($domain);
            $response   = $apiConnection->request($request);
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function delete($domainData, $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domain = new eppDomain($idna->encode($domainData["name"]));
            
            $request    = new eppDeleteDomainRequest($domain);
            $response   = $apiConnection->request($request);
        
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function transfer($domainData, $apiConnection) {
        try {
            $domain = new eppDomain($domainData["name"]);
            $domain->setRegistrant(new eppContactHandle($domainData["registrantId"],    eppContactHandle::CONTACT_TYPE_REGISTRANT));
            $domain->addContact(new eppContactHandle($domainData["adminId"],            eppContactHandle::CONTACT_TYPE_ADMIN));
            $domain->addContact(new eppContactHandle($domainData["techId"],             eppContactHandle::CONTACT_TYPE_TECH));
            $domain->addContact(new eppContactHandle($domainData["billingId"],          eppContactHandle::CONTACT_TYPE_BILLING));
            
            foreach($domainData["nameservers"] as $nameserver) {
                $domain->addHost(new \Metaregistrar\EPP\eppHost($nameserver));
            }
            
            $domain->setPeriod($domainData["period"]);
            $domain->setPeriodUnit(eppDomain::DOMAIN_PERIOD_UNIT_Y);
            $domain->setAuthorisationCode($domainData["eppCode"]);
            
            $apiConnection->useExtension('command-ext-1.0');
            
            $request    = new \Metaregistrar\EPP\metaregEppTransferExtendedRequest(eppTransferRequest::OPERATION_REQUEST,$domain);
            $response   = $apiConnection->request($request);
        
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function renew($domainData, $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domain = new eppDomain($idna->encode($domainData["name"]));
            $domain->setPeriod($domainData["period"]);
            $domain->setPeriodUnit(eppDomain::DOMAIN_PERIOD_UNIT_Y);
            
            $request    = new eppRenewRequest($domain, $domainData["expDate"]);
            $response   = $apiConnection->request($request);
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function updateNameservers($domainData, $apiConnection) {
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
            
            $request    = new eppUpdateDomainRequest($domain, $domainAdd, $domainDel, null);
            $response   = $apiConnection->request($request);
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function setAutorenew($domainData, $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domain = new eppDomain($idna->encode($domainData["name"]));
            
            $apiConnection->useExtension('command-ext-1.0');
            
            $request    = new metaregEppAutorenewRequest($domain, $domainData["autorenew"]);
            $response   = $apiConnection->request($request);
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function getInfo($domainData, $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domain = new eppDomain($idna->encode($domainData["name"]));
            
            $request    = new eppInfoDomainRequest($domain);
            $response   = $apiConnection->request($request);
            
            $domain = $response->getDomain();
            foreach ($domain->getHosts() as $nameserver) {
                $domainDataRemote["nameservers"][] = $nameserver->getHostname();
            }

            $domainDataRemote["registrantId"] = $domain->getRegistrant();
            
            foreach ($domain->getContacts() as $contact) {
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
    
    static function isAvailable($domainData, $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domains = array($idna->encode($domainData["name"]));
            
            $request = new eppCheckDomainRequest($domains);
            $response = $apiConnection->request($request);
            
            $checkResult = $response->getCheckedDomains();
            
            return $checkResult[0]["available"];
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function isAvailableMulti($domainData, $apiConnection) {
        try {
            $idna = new eppIDNA();
            $domains = array();
            
            foreach($domainData["search"] as $search) {
                $name       = $search["searchTerm"].$search["tld"];
                $domains[]  = $idna->encode($name);
            }
            
            $request = new eppCheckDomainRequest($domains);
            $response = $apiConnection->request($request);
            
            $checkResult = $response->getCheckedDomains();
            
            foreach ($domainData["search"] as $index => $search) {
                $results[$search["tld"]] = $checkResult[$index]["available"];
            }
            
            return $results;
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function isRegistered($domainData, $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domain = new eppDomain($idna->encode($domainData["name"]));
            
            $request    = new eppInfoDomainRequest($domain);
            $response   = $apiConnection->request($request);
            
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
}
