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
use Metaregistrar\EPP\metaregCreateDnsRequest;
use \Metaregistrar\EPP\metaregEppTransferExtendedRequest;
use \Metaregistrar\EPP\eppRenewRequest;
use \Metaregistrar\EPP\eppInfoDomainRequest;
use \Metaregistrar\EPP\eppInfoDomainResponse;
use \Metaregistrar\EPP\eppUpdateDomainRequest;
use \Metaregistrar\EPP\metaregEppAutorenewRequest;
use \Metaregistrar\EPP\eppCheckDomainRequest;
use \Metaregistrar\EPP\eppCheckDomainResponse;
use Metaregistrar\EPP\metaregDeleteDnsRequest;
use \Metaregistrar\EPP\metaregInfoDnsRequest;
use \Metaregistrar\EPP\metaregInfoDnsResponse;
use \Metaregistrar\EPP\metaregUpdateDnsRequest;

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
            $response = $apiConnection->request(new metaregEppTransferExtendedRequest(eppTransferRequest::OPERATION_REQUEST,$domain));
            if (($response->getResultCode()!=1000) && ($response->getResultCode()!=1001)) {
                logActivity("TRANSFER ERROR ".$domainData['name'].": ".$response->getResultMessage());
                throw new \Exception($response->getResultMessage());
            }
        } catch (eppException $e) {
	        logActivity("TRANSFER ERROR ".$domainData['name'].": ".$response->getResultMessage());
            throw new \Exception($e->getMessage());
        }
    }
    
    static function renew($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            
            $domain = new eppDomain($idna->encode($domainData["name"]));
            $domain->setPeriod($domainData["period"]);
            $domain->setPeriodUnit(eppDomain::DOMAIN_PERIOD_UNIT_Y);
            
            $apiConnection->request(new eppRenewRequest($domain, $domainData["expirydate"]));

        } catch (eppException $e) {
			if ($e->getCode()==2105) {
				if (!str_contains($e->getMessage(), 'SIDN does not support explicit renews')) {
					throw new \Exception($e->getMessage());
				}
			} else {
				if ($e->getCode()==2304) {
					if (!str_contains($e->getMessage(), 'You cannot renew this manually')) {
						throw new \Exception($e->getMessage());
					}
				} else {
					throw new \Exception($e->getMessage());
				}
			}
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
            $apiConnection->request(new metaregEppAutorenewRequest($domain, $domainData["autorenew"]));
            
        } catch (eppException $e) {
            logActivity("ERROR SET AUTORENEW: ".$e->getMessage());
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
            list(,$tld) = explode('.',$domainData['name'],2);
            $domainDataRemote["name"] = $domainData["name"];
            $domainDataRemote["tld"] = $tld;
            $domainDataRemote["period"] = $domain->getPeriod();
            $domainDataRemote["eppCode"] = $domain->getAuthorisationCode();
            $domainDataRemote["expirydate"] = date("Y-m-d", strtotime($response->getDomainExpirationDate()));
            $domainDataRemote["status"] = implode(',',$response->getDomainStatuses());
            $domainDataRemote["transferLock"] = false;
            if (str_contains($domainDataRemote["status"],'TransferProhibited')) {
                $domainDataRemote["transferLock"] = true;
            }
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
                if ($status == eppDomain::STATUS_CLIENT_TRANSFER_PROHIBITED) {
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
                $add->addStatus(eppDomain::STATUS_CLIENT_TRANSFER_PROHIBITED);
            } else {
                $add = null;
                $rem = new eppDomain($idna->encode($domainData["name"]));
                $rem->addStatus(eppDomain::STATUS_CLIENT_TRANSFER_PROHIBITED);
            }
            $apiConnection->request(new eppUpdateDomainRequest($domain, $add, $rem, null));
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    static function getDNS($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            $domain = new eppDomain($idna->encode($domainData["name"]));
            $result = $apiConnection->request(new metaregInfoDnsRequest($domain));
            /* @var $result metaregInfoDnsResponse */
            $dnsresponse = [];
            foreach ($result->getContent() as $content) {
                if (in_array($content['type'],['A','AAAA','MX','CNAME','SPF','TXT'])) {
                    $dnsresponse[] = ['hostname'=>$content['name'],'type'=>$content['type'],'address'=>$content['content'],'priority'=>$content['priority']];
                }
            }
            return $dnsresponse;
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    static function deleteDNS($domainData, eppConnection $apiConnection) {
        try {
            $idna = new eppIDNA();
            $domain = new eppDomain($idna->encode($domainData["name"]));
            $result = $apiConnection->request(new metaregDeleteDnsRequest($domain));
            //if ($result->getResultCode() == 1000) {

            //}
        } catch (eppException $e) {
            if ($e->getCode() == 2303) {
                //This was expected, domain DNS MIGHT BE empty
            } else {
                throw new \Exception($e->getMessage());
            }

        }
    }

    static function resetDNS($domainData, eppConnection $eppConnection) {
        $domainname = $domainData["name"];
        $dnsrecords = [
            ['hostname'=>$domainname,'type'=>'NS','address'=>'ns1.yourdomainprovider.net','priority'=>'0','ttl'=>'86400'],
            ['hostname'=>$domainname,'type'=>'NS','address'=>'ns2.yourdomainprovider.net','priority'=>'0','ttl'=>'86400'],
            ['hostname'=>$domainname,'type'=>'NS','address'=>'ns3.yourdomainprovider.net','priority'=>'0','ttl'=>'86400'],
            ['hostname'=>$domainname,'type'=>'A','address'=>'213.249.71.101','priority'=>'0'],
            ['hostname'=>'*.'.$domainname,'type'=>'A','address'=>'213.249.71.101','priority'=>'0'],
            ['hostname'=>'mail.'.$domainname,'type'=>'A','address'=>'213.249.71.101','priority'=>'0'],
            ['hostname'=>$domainname,'type'=>'AAAA','address'=>'2a01:448:1005::101','priority'=>'0'],
            ['hostname'=>'*.'.$domainname,'type'=>'AAAA','address'=>'2a01:448:1005::101','priority'=>'0'],
            ['hostname'=>'mail.'.$domainname,'type'=>'AAAA','address'=>'2a01:448:1005::101','priority'=>'0'],
            ['hostname'=>$domainname,'type'=>'MX','address'=>'mail.'.$domainname,'priority'=>'10'],
            ['hostname'=>$domainname,'type'=>'TXT','address'=>'v=spf1 a mx ~all','priority'=>'0']
        ];
        Domain::saveDNS($domainData, $eppConnection, $dnsrecords);
    }

    static function saveDNS($domainData, eppConnection $apiConnection, $dns) {
        try {
            $idna = new eppIDNA();
            $domain = new eppDomain($idna->encode($domainData["name"]));
            $result = $apiConnection->request(new metaregInfoDnsRequest($domain));
            if ($result->getResultCode()==1000) {
                #logActivity("MetaregistrarDNS exists for ".$domainData["name"]);
                /* @var $result metaregInfoDnsResponse */
                $add = array();
                $rem = array();
				// Loop through all records that were received from the website
                foreach ($dns as $dnsrecord) {
                    // Check if the domain name is present in the DNS record
                    if ($dnsrecord['hostname'] == '') {
                        $dnsrecord['hostname'] = $domain->getDomainname();
                    } else {
                        if (strpos($dnsrecord['hostname'], $domain->getDomainname()) === false) {
                            $dnsrecord['hostname'] = $dnsrecord['hostname'] . '.' . $domain->getDomainname();
                        }
                    }
                    if ($dnsrecord['priority'] == 'N/A') {
                        $dnsrecord['priority'] = null;
                    }
                    //logActivity("DNS record found: ".implode(',',$dnsrecord), $_SESSION["uid"]);
	                if ($dnsrecord['type']=='DEL') {
                        //logActivity("MetaregistrarDNS remove this: ".implode(',',$dnsrecord), $_SESSION["uid"]);
                        // This record must be removed
                        $found = false;
                        foreach ($result->getContent() as $content) {
                            //logActivity("MetaregistrarDNS searching in: ".implode(',',$content), $_SESSION["uid"]);
							//logActivity("Hostname: ".$dnsrecord['hostname']."=".$content['name']);
	                        //logActivity("Content: ".$dnsrecord['address']."=".$content['content']);
	                        //logActivity("Priority: ".$dnsrecord['priority']."=".$content['priority']);
                            if (($dnsrecord['hostname'] == $content['name']) && ($dnsrecord['content'] == $content['address']) && ($dnsrecord['priority'] == $content['priority'])) {
                                $found = true;
                                //logActivity("MetaregistrarDNS rem: ".implode(',',$dnsrecord), $_SESSION["uid"]);
                                $foundcontent = $content['content'];
								$foundtype = $content['type'];
								$foundhostname = $content['name'];
								$foundttl = $content['ttl'];
								$foundpriority = $content['priority'];
                            }
                        }
                        if ($found) {
                            $rem[] = array('name' => $foundhostname,'type' =>$foundtype, 'content' => $foundcontent, 'ttl' =>$foundttl, 'priority' => $foundpriority);
                        }
                    } else {
						if ($dnsrecord['type'] != 'NONE') {
							// Check if there are records to be added
							$found = false;
							//logActivity("MetaregistrarDNS compare dnsrecord: ".implode(',',$dnsrecord), $_SESSION["uid"]);
							// Loop through all current DNS records that were received from the registrar
							foreach ($result->getContent() as $content) {
								//logActivity("MetaregistrarDNS compare with: ".implode(',',$content), $_SESSION["uid"]);
								if ($content['priority'] == '') {
									$content['priority'] = 0;
								}
								if (($dnsrecord['hostname'] == $content['name']) && ($dnsrecord['type'] == $content['type']) && ($dnsrecord['address'] == $content['content']) && ($dnsrecord['priority'] == $content['priority'])) {
									$found = true;
								} else {
									if (($dnsrecord['hostname'] == $content['name']) && ($dnsrecord['type'] == $content['type']) && ($dnsrecord['priority'] == $content['priority'])) {
										$rem[] = $content;
									}
									if (($dnsrecord['hostname'] == $content['name']) && ($dnsrecord['type'] == $content['type']) && ($dnsrecord['address'] == $content['content'])) {
										$rem[] = $content;
									}
								}
							}
							if (!$found) {
								//logActivity("MetaregistrarDNS add: ".implode(',',$dnsrecord), $_SESSION["uid"]);
								if (strpos(strtolower($dnsrecord['hostname']), strtolower($domainData["name"])) === false) {
									throw new \Exception("Hostname MUST contain the domain name " . $domainData['name']);
								}
								// In case of MXE, add MX record and A record
								if ($dnsrecord['type'] == 'MXE') {
									$add[] = array('name' => 'mail.' . $dnsrecord['hostname'], 'type' => 'A', 'content' => $dnsrecord['address'], 'ttl' => 3600, 'priority' => 0);
									$add[] = array('name' => $dnsrecord['hostname'], 'type' => 'MX', 'content' => 'mail.' . $dnsrecord['hostname'], 'ttl' => 3600, 'priority' => 10);
								} else {
									if ($dnsrecord['type'] == 'MX') {
										if ($dnsrecord['priority'] == '') {
											$dnsrecord['priority'] = 10;
										}
										$add[] = array('name' => $dnsrecord['hostname'], 'type' => $dnsrecord['type'], 'content' => $dnsrecord['address'], 'ttl' => 3600, 'priority' => $dnsrecord['priority']);
									} else {
										$add[] = array('name' => $dnsrecord['hostname'], 'type' => $dnsrecord['type'], 'content' => $dnsrecord['address'], 'ttl' => 3600, 'priority' => 0);
									}
								}
							}
						}
                    }
                }
                //logActivity("DNS ADD: ".json_encode($add));
                //logActivity("DNS REM: ".json_encode($rem));
                if ((count($add) > 0) || (count($rem) > 0)) {
                    $apiConnection->request(new metaregUpdateDnsRequest($domain, $add, $rem, null));
                }
            }
        } catch (eppException $e) {
            if ($e->getCode() == 2303) {
                // Domain not on DNS yet, create it!
                //logActivity("MetaregistrarDNS does not exist");
                $add = array();
                foreach ($dns as $dnsrecord) {
                    if ($dnsrecord['hostname']=='') {
                        $dnsrecord['hostname'] = $domain->getDomainname();
                    } else {
                        if (strpos($dnsrecord['hostname'],$domain->getDomainname())===false) {
                            $dnsrecord['hostname'] = $dnsrecord['hostname'].'.'.$domain->getDomainname();
                        }
                    }
                    if ($dnsrecord['type']=='MX') {
                        if ($dnsrecord['priority']=='') {
                            $dnsrecord['priority']=10;
                        }
                        $add[] = array('name'=>$dnsrecord['hostname'],'type'=>$dnsrecord['type'],'content'=>$dnsrecord['address'],'ttl'=>3600,'priority'=>$dnsrecord['priority']);
                    } else {
                        $add[] = array('name'=>$dnsrecord['hostname'],'type'=>$dnsrecord['type'],'content'=>$dnsrecord['address'],'ttl'=>3600,'priority'=>0);
                    }
                }
                $apiConnection->request(new metaregCreateDnsRequest($domain,$add));
                return null;
            } else {
                logActivity("ERROR INFODNS: ".$e->getMessage());
                throw new \Exception($e->getMessage());
            }
        }
    }
}
