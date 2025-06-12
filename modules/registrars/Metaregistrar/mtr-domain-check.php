<?php
CONST USERNAME = 'muvoeppluser';
CONST PASSWORD = '3ozdMgB7m%9yZ8#f';

require_once 'classes/metaregistrar-epp-client/autoloader.php';

use Metaregistrar\EPP\eppConnection;
use Metaregistrar\EPP\eppException;
use Metaregistrar\EPP\eppCheckDomainRequest;
use Metaregistrar\EPP\eppCheckDomainResponse;

if (is_array($_GET)) {
    if (count($_GET)>0) {
        $domainname = strtolower($_GET['domain']);
        $domainchecks = loaddomaincheckcache();
        if (isset($domainchecks[$domainname])) {
            showresult($domainname,$domainchecks[$domainname],true);
        } else {
            list($name,$extension) = explode('.',$domainname,2);
            if ($extension == 'nl') {
                checkdomainviais($domainname);
            } else {
                $result = checkdomainviadns($domainname);
                if ($result == 'TAKEN') {
                    showresult($domainname,$result);
                } else {
                    checkdomainviaepp($domainname);
                }
            }
        }
    }
}

function loaddomaincheckcache() {
    $domainchecks = [];
    $cache = file('domaincheckresults.csv',FILE_IGNORE_NEW_LINES);
    foreach ($cache as $line) {
        list($name,$result,$time) = explode("\t",$line);
        // cache time = 10 days
        // 10 days old results do not count anymore
        // 10 days = 10 * 60 * 60 * 24 seconds = 864000 seconds
        if ((time() - $time) <= 864000) {
            $domainchecks[$name] = $result;
        }
    }
    return $domainchecks;
}

function showresult($domainname, $result, $cached = false):void {
    echo "$domainname is $result";
    if (!$cached) {
        file_put_contents('domaincheckresults.csv',"$domainname\t$result\t".time()."\n",FILE_APPEND);
    }
}

/**
 * @param $domainname string
 */
function checkdomainviais(string $domainname):void {
    if ($connection = fsockopen('whois.domain-registry.nl', 43)) {
        fputs($connection, "is $domainname\r\n");
        $data = '';
        while (!feof($connection)) {
            $data .= fgets($connection, 128);
        }
        fclose($connection);
        if (str_contains($data,'is active')) {
            showresult($domainname,'TAKEN');
        } else {
            showresult($domainname,'FREE');
        }
        unset($data);
    }
}

/**
 * @param $domainname string
 * @return string|NULL
 */
function checkdomainviadns(string $domainname): string|NULL {
    $results = null;
    exec("dig +short $domainname SOA",$output);
    if (count($output)>0) {
        $results = 'TAKEN';
    }
    return $results;
}


/**
 * @param $domainname string
 */
function checkdomainviaepp(string $domainname):void {
    try {
        $eppl = new eppConnection();
        $eppl->setHostname('ssl://eppl.metaregistrar.com');
        $eppl->setPort(7000);
        $eppl->setUsername(USERNAME);
        $eppl->setPassword(PASSWORD);
        if ($eppl->login()) {
            // Create request to be sent to EPP service
            $domains[] = $domainname;
            $check = new eppCheckDomainRequest($domains);
            // Write request to EPP service, read and check the results
            if ($response = $eppl->request($check)) {
                /* @var $response eppCheckDomainResponse */
                // Walk through the results
                $checks = $response->getCheckedDomains();
                foreach ($checks as $check) {
                    showresult($check['domainname'],($check['available'] ? 'FREE' : 'TAKEN'));
                }
            }
            $eppl->logout();
        } else {
            echo "EPP LOGIN FAIL\n";
        }
    } catch (eppException $e) {
        echo "ERROR: ".$e->getMessage();
    }
}
