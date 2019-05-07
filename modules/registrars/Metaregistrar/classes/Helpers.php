<?php

namespace MetaregistrarModule\classes;

use Illuminate\Database\Capsule\Manager as Capsule;

class Helpers {
    const CONTACT_TYPE_REGISTRANT   = 'registrant';
    const CONTACT_TYPE_ADMIN        = 'admin';
    const CONTACT_TYPE_TECH         = 'tech';
    const CONTACT_TYPE_BILLING      = 'billing';
    
    static function getApiData() {
        $query =  "SELECT setting, value FROM tblregistrars WHERE registrar = 'Metaregistrar'";
        
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $statement->execute();
        $rows = $statement->fetchAll();
        $pdo->commit();
        
        $apiData = array();
        
        foreach($rows as $row) {
            $apiData[$row["setting"]] = self::whmcsDecodeString($row["value"]);
        }
        
        return array(
            "host"      => "ssl://eppl.metaregistrar.com",
            "port"      => 7000,
            "username"  => $apiData["apiUsername"],
            "password"  => $apiData["apiPassword"],
            "autoRenewMode" => ($apiData["autoRenewMode"]=="on")?true:false,
            "debugMode" => ($apiData["debugMode"]=="on")?true:false
        );
    }
    
    static function getContactData($params, $contactType) {
        if(isset($params["contactdetails"])) {
            switch ($contactType) {
                case self::CONTACT_TYPE_REGISTRANT:
                    $contactTypeTmp = "Registrant";
                    break;
                case self::CONTACT_TYPE_ADMIN:
                    $contactTypeTmp = "Admin";
                    break;
                case self::CONTACT_TYPE_TECH:
                    $contactTypeTmp = "Technical";
                    break;
                case self::CONTACT_TYPE_BILLING:
                    $contactTypeTmp = "Billing";
                    break;
            }
            if(isset($params["contactdetails"][$contactTypeTmp]["Full Name"])) {
                $contactData = array(
                    "name"          => $params["contactdetails"][$contactTypeTmp]["Full Name"],
                    "organization"  => $params["contactdetails"][$contactTypeTmp]["Organisation Name"],
                    "adress"        => $params["contactdetails"][$contactTypeTmp]["Address 1"]." ".$params["contactdetails"][$contactTypeTmp]["Address 2"],
                    "city"          => $params["contactdetails"][$contactTypeTmp]["City"],
                    "region"        => $params["contactdetails"][$contactTypeTmp]["Region"],
                    "country"       => $params["contactdetails"][$contactTypeTmp]["Country"],
                    "postCode"      => $params["contactdetails"][$contactTypeTmp]["Postcode"],
                    "email"         => $params["contactdetails"][$contactTypeTmp]["Email Address"],
                    "phone"         => $params["contactdetails"][$contactTypeTmp]["Phone Number"],
                    "password"      => self::getRandomString(),
                );
            } else {
                $contactData = array(
                    "name"          => $params["contactdetails"][$contactTypeTmp]["Name"],
                    "organization"  => $params["contactdetails"][$contactTypeTmp]["Organization"],
                    "adress"        => $params["contactdetails"][$contactTypeTmp]["Adress"],
                    "city"          => $params["contactdetails"][$contactTypeTmp]["City"],
                    "region"        => $params["contactdetails"][$contactTypeTmp]["Region"],
                    "country"       => $params["contactdetails"][$contactTypeTmp]["Country Code"],
                    "postCode"      => $params["contactdetails"][$contactTypeTmp]["Postcode"],
                    "email"         => $params["contactdetails"][$contactTypeTmp]["Email"],
                    "phone"         => $params["contactdetails"][$contactTypeTmp]["Phone"],
                    "fax"           => $params["contactdetails"][$contactTypeTmp]["Fax"],
                    "password"      => self::getRandomString(),
                );
            }
        } else {
            $contactTypeTmp = ($contactType == self::CONTACT_TYPE_REGISTRANT)?"":"admin";
            $contactData = array(
                "name"          => $params[$contactTypeTmp."firstname"]." ".$params[$contactTypeTmp."lastname"],
                "organization"  => $params[$contactTypeTmp."companyname"],
                "adress"        => $params[$contactTypeTmp."address1"]." ".$params[$contactTypeTmp."address2"],
                "city"          => $params[$contactTypeTmp."city"],
                "region"        => $params[$contactTypeTmp."fullstate"],
                "country"       => $params[$contactTypeTmp."country"],
                "postCode"      => $params[$contactTypeTmp."postcode"],
                "email"         => $params[$contactTypeTmp."email"],
                "phone"         => ($contactTypeTmp=="")?$params["phonenumberformatted"]:$params["adminfullphonenumber"],
                "password"      => self::getRandomString(),
            );
        }
        
        $tld = $params["tld"];
        $registry = AdditionalProperties::getRelatedRegistry($tld);
        if(!empty($registry)) {
            $contactData["registry"] = $registry;
            foreach($params["additionalfields"] as $propertyNameRaw => $propertyValue) {
                $temp = explode("__", $propertyNameRaw);
                $propertyName   = $temp[0];
                $forContctType  = isset($temp[1])?$temp[1]:self::CONTACT_TYPE_REGISTRANT;
                if($forContctType == $contactType || $forContctType == "all") {
                    $propertyValue = AdditionalProperties::translateTickboxValue($propertyName, $propertyValue);
                    if(AdditionalProperties::isExpectedProperty($propertyNameRaw, $registry) && !empty($propertyValue)) {
                        $contactData["properties"][$propertyName] = $propertyValue;
                    }
                }
            }
        } 
        return $contactData;
    }
    
    static function getDomainData($params) {
        $domainData  = array (
            "name"              => isset($params["domainname"])?$params["domainname"]:$params["domain"],
            "period"            => $params["regperiod"],
            "authorisationCode" => self::getRandomString(),
            "eppCode"           => $params["transfersecret"]
        );
        for($i=1;$i<=5;$i++) {
            if(!empty($params["ns".$i])) {
                $domainData["nameservers"][] = $params["ns".$i];
            }
        }
        $pdo = Capsule::connection()->getPdo();
        $query = "SELECT expirydate FROM tbldomains WHERE domain = '".$params["domainname"]."'";
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $statement->execute();
        $row = $statement->fetch();
        $pdo->commit();
        
        $domainData["expirydate"] = $row["expirydate"];
        
        return $domainData;
    }
    
    static function getHostData($params) {
        $hostData  = array (
            "name"          => $params['nameserver'],
            "ip"            => $params['ipaddress'],
            "ipCurrent"     => $params['currentipaddress'],
            "ipNew"         => $params['newipaddress'],
        );
        
        return $hostData;
    }
    
    static function formatContactData($contactData) {
        $contactDataFormated = array(
                "Name"          => $contactData["name"],
                "Organization"  => $contactData["organization"],
                "Adress"        => $contactData["adress"],
                "City"          => $contactData["city"],
                "Region"        => $contactData["region"],
                "Country Code"  => $contactData["country"],
                "Postcode"      => $contactData["postCode"],
                "Email"         => $contactData["email"],
                "Phone"         => $contactData["phone"],
                "Fax"           => $contactData["fax"],
            );
        return $contactDataFormated;
    }
    
    static function formatDate($date) {
        $date = strtotime($date);
        $date = date("d/m/Y", $date);
        return $date;
    }
    
    static function saveMessage($message, $domainId) {
        
        $query = "INSERT INTO MetaregistrarPollData (domainId, messageId, description, domain, date) VALUES (\"".$domainId."\", \"".$message["id"]."\", \"".$message["description"]."\", \"".$message["domain"]."\", \"".$message["date"]."\")";
        
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $statement->execute();
        $pdo->commit();
        
    }
    
    static function getMessages($domainId) {
        
        $query = "SELECT * FROM MetaregistrarPollData WHERE domainId = '".$domainId."'";
        
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $statement->execute();
        $rows = $statement->fetchAll();
        $pdo->commit();
        
        return $rows;
    }
     
    static function getDomainIdArray($domainName) {
        
        $query = "SELECT id FROM tbldomains WHERE domain = '".$domainName."' AND registrar = 'Metaregistrar'";
        
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $statement->execute();
        $rows = $statement->fetchAll();
        $pdo->commit();
        
        $domainIdArray = array();
                
        foreach ($rows as $row) {
            $domainIdArray[] = $row["id"];
        }
        
        return $domainIdArray;
    }
    
    static function getFirstDomainId($userId) {
        
        $query = "SELECT id FROM tbldomains WHERE userid = '".$userId."' ORDER BY domain ASC;";
        
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $statement->execute();
        $row = $statement->fetch();
        $pdo->commit();
        
        return $row["id"];
    }
    
    static function isMetaregistrarDomain($domainId) {
        
        $query = "SELECT registrar FROM tbldomains WHERE id = '".$domainId."'";
        
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $statement->execute();
        $row = $statement->fetch();
        $pdo->commit();
        
        if($row["registrar"] == "Metaregistrar") {
            return true;
        }
        return false;
    }
    
    static function createPollDataTable($domainId) {
        
        $messages = self::getMessages($domainId);
        
        $output .= '</br>'
        . ' <table class=\'table table-striped table-bordered\' style=\'width:100%\' id=\'poll-data-table\'>'
        . '     <thead>'
        . '         <tr>'
        . '             <th class=\'table-header\' id=\'table-header-date\' style=\'width: 100px;\'>'
        . '                 Time'
        . '             </th>'
        . '             <th class=\'table-header\' id=\'table-header-desc\'>'
        . '                 Description'
        . '             </th>'
        . '         </tr>'
        . '     </thead>'
        . '     <tbody>';
        
        foreach($messages as $messge) {
            $output .= ''
            . '     <tr>'
            . '         <td>'
            .               Helpers::formatDate($messge['date'])
            . '         </td>'
            . '         <td>'
            .               $messge['description']
            . '         </td>'
            . '     </tr>';
        }
        
        $output .= ''
        . '     </tbody>'
        . ' </table>';
        
        return $output;
    }
    
    private static function whmcsDecodeString($encodedString) {
        $command = 'DecryptPassword';
        $postData = array(
            'password2' => $encodedString,
        );
        
        $results = localAPI($command, $postData);
        
        return $results["password"];
    }
    
    static function getRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
