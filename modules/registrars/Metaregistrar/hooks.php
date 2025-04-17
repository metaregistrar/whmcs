<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once 'Autoloader.php';
require_once 'classes/metaregistrar-epp-client/autoloader.php';

function Hook_Metaregistrar_AdminAreaTable($params) {
    try {
        return \MetaregistrarModule\classes\Hooks::adminAreaTable();
    } catch (Exception $e) {
        logActivity("MetaregistrarModule: ".$e->getMessage(), $_SESSION["uid"]);
    }
}

function Hook_Metaregistrar_AutoAcceptOrders($params) {
    $settings = array(
        'apiuser'   => 'ewoutdegraaf', // one of the admins username
        'autosetup' => true, // determines whether product provisioning is performed
        'sendregistrar'	=> true, // determines whether domain automation is performed
        'sendemail'	=> true, // sets if welcome emails for products and registration confirmation emails for domains should be sent
        'ispaid'	=> true, // set to true if you want to accept only paid orders
        'paymentmethod'	=> array(), // set the payment method you want to accept automaticly (leave empty to use all payment methods) * example array('paypal','amazonsimplepay')
    );

    $ispaid = true;
    if ($params['InvoiceID']) {
        $result = localAPI('getinvoice', array('invoiceid'=>$params['InvoiceID'],), $settings['apiuser']);
        $ispaid = ($result['result'] == 'success' && $result['balance'] <= 0) ? true : false;
    }

    if((!sizeof($settings['paymentmethod']) || sizeof($settings['paymentmethod']) && in_array($params['PaymentMethod'], $settings['paymentmethod'])) && (!$settings['ispaid'] || $settings['ispaid'] && $ispaid)) {
        localAPI('acceptorder', array('orderid' => $params['OrderID'], 'autosetup' => $settings['autosetup'], 'sendregistrar' => $settings['sendregistrar'], 'sendemail' => $settings['sendemail'],), $settings['apiuser']);
    }
}


add_hook('AdminAreaFooterOutput', 1, 'Hook_Metaregistrar_AdminAreaTable');
//Enable to auto-accept domain registration orders
//add_hook('AfterShoppingCartCheckout', 0, 'Hook_Metaregistrar_AutoAcceptOrders');