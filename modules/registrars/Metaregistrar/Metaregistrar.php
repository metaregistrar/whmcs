<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Domains\DomainLookup\SearchResult;

require_once 'Autoloader.php';
require_once 'classes/metaregistrar-epp-client/autoloader.php';

function Metaregistrar_getConfigArray($params) {
    try {
        
        return \MetaregistrarModule\classes\Addon::getConfig($params);
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_RegisterDomain($params) {
    try {
        \MetaregistrarModule\classes\Addon::registerDomain($params);
        return array('status'=>'success');
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_TransferDomain($params) {
    try {
        
        \MetaregistrarModule\classes\Addon::transferDomain($params);
        return array('status'=>'success');
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_Sync($params) {
    try {
        
        return \MetaregistrarModule\classes\Addon::sync($params);
        
     } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_TransferSync($params) {
    try {
        
        return \MetaregistrarModule\classes\Addon::transferSync($params);
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_RenewDomain($params) {
    try {
        
        \MetaregistrarModule\classes\Addon::renewDomain($params);
        return array('status'=>'success');
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_RequestDelete($params) {
    try {
        
        \MetaregistrarModule\classes\Addon::deleteDomain($params);
        return array('status'=>'success');
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_GetEPPCode($params) {
    try {
        
        return \MetaregistrarModule\classes\Addon::getEppCode($params);
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_GetNameservers($params) {
    try {
        return \MetaregistrarModule\classes\Addon::getNameservers($params);
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_SaveNameservers($params) {
    try {
        
        \MetaregistrarModule\classes\Addon::saveNameservers($params);
        return array('status'=>'success');
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_RegisterNameserver($params) {
    try {
        
        return \MetaregistrarModule\classes\Addon::registerNameserver($params);
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_DeleteNameserver($params) {
    try {
        
        return \MetaregistrarModule\classes\Addon::deleteNameserver($params);
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_ModifyNameserver($params) {
    try {
        
        return \MetaregistrarModule\classes\Addon::updateNameserver($params);
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_GetContactDetails($params) {
    try {
        
        return \MetaregistrarModule\classes\Addon::getContactDetails($params);
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_SaveContactDetails($params) {
    try {
        
        \MetaregistrarModule\classes\Addon::saveContactDetails($params);
        return array('status'=>'success');
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_CheckAvailability($params) {
    try {
        
        return \MetaregistrarModule\classes\Addon::checkAvailability($params);
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_ClientArea($params) {
    try {
        
        return \MetaregistrarModule\classes\Addon::clientArea($params);
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_GetRegistrarLock($params) {
    try {
        return \MetaregistrarModule\classes\Addon::getDomainLock($params);
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_SaveRegistrarLock($params) {
    try {
        MetaregistrarModule\classes\Addon::saveDomainLock($params);
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

function Metaregistrar_GetDNS($params) {
    try {
        return \MetaregistrarModule\classes\Addon::getDomainDNS($params);
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }

}

function Metaregistrar_SaveDNS($params) {
    try {
        \MetaregistrarModule\classes\Addon::storeDomainDNS($params);
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}
