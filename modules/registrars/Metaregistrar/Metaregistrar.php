<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Domains\DomainLookup\SearchResult;

require_once 'Autoloader.php';
require_once 'classes/metaregistrar-epp-client/autoloader.php';

function Metaregistrar_MetaData() {
    return array(
        'DisplayName' => 'Metaregistrar BV',
        'APIVersion' => '1.2',
    );
}

function Metaregistrar_ClientAreaCustomButtonArray() {
    return array(
        'DNS reset' => 'DNSReset',
    );
}

function Metaregistrar_DNSReset ($params) {
    return \MetaregistrarModule\classes\Addon::ResetDNS($params);
}

function Metaregistrar_getConfigArray($params) {
    return \MetaregistrarModule\classes\Addon::getConfig($params);
}

function Metaregistrar_RegisterDomain($params) {
    return \MetaregistrarModule\classes\Addon::registerDomain($params);
}

function Metaregistrar_TransferDomain($params) {
    return \MetaregistrarModule\classes\Addon::transferDomain($params);
}

function Metaregistrar_Sync($params) {
    return \MetaregistrarModule\classes\Addon::sync($params);
}

function Metaregistrar_TransferSync($params) {
    return \MetaregistrarModule\classes\Addon::transferSync($params);
}

function Metaregistrar_RenewDomain($params) {
    return \MetaregistrarModule\classes\Addon::renewDomain($params);
}

function Metaregistrar_RequestDelete($params) {
    return \MetaregistrarModule\classes\Addon::deleteDomain($params);
}

function Metaregistrar_GetEPPCode($params) {
    return \MetaregistrarModule\classes\Addon::getEppCode($params);
}

function Metaregistrar_GetNameservers($params) {
    return \MetaregistrarModule\classes\Addon::getNameservers($params);
}

function Metaregistrar_SaveNameservers($params) {
    return \MetaregistrarModule\classes\Addon::saveNameservers($params);
}

function Metaregistrar_RegisterNameserver($params) {
    return \MetaregistrarModule\classes\Addon::registerNameserver($params);
}

function Metaregistrar_DeleteNameserver($params) {
    return \MetaregistrarModule\classes\Addon::deleteNameserver($params);
}

function Metaregistrar_ModifyNameserver($params) {
    return \MetaregistrarModule\classes\Addon::updateNameserver($params);
}

function Metaregistrar_GetContactDetails($params) {
    return \MetaregistrarModule\classes\Addon::getContactDetails($params);
}

function Metaregistrar_SaveContactDetails($params) {
    return \MetaregistrarModule\classes\Addon::saveContactDetails($params);
}

function Metaregistrar_CheckAvailability($params) {
    return \MetaregistrarModule\classes\Addon::checkAvailability($params);
}

function Metaregistrar_ClientArea($params) {
    return \MetaregistrarModule\classes\Addon::clientArea($params);
}

function Metaregistrar_GetRegistrarLock($params) {
    return \MetaregistrarModule\classes\Addon::getDomainLock($params);
}

function Metaregistrar_SaveRegistrarLock($params) {
    return MetaregistrarModule\classes\Addon::saveDomainLock($params);
}

function Metaregistrar_GetDNS($params) {
    return \MetaregistrarModule\classes\Addon::getDomainDNS($params);
}

function Metaregistrar_SaveDNS($params) {
    return  \MetaregistrarModule\classes\Addon::storeDomainDns($params);
}
