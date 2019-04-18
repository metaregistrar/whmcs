<?php

namespace MetaregistrarModule\classes;

use Metaregistrar\EPP\eppConnection;
use Metaregistrar\EPP\eppException;
use Metaregistrar\EPP\eppPollRequest;
use Metaregistrar\EPP\eppPollResponse;
use Metaregistrar\EPP\eppResponse;

class Poll {
    static function getMessage($apiConnection) {
        try {
            
            $request    = new eppPollRequest(eppPollRequest::POLL_REQ, 0);
            $response   = $apiConnection->request($request);
            $msgCount =  $response->getMessageCount();
                
            if($msgCount == 0) {
                return false;
            }

            return array(
                "id"            => $response->getMessageId(),
                "description"   => $response->getMessage(),
                "date"          => $response->getMessageDate(),
                "domain"        => $response->getDomainName(),
            );
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    static function ackMessage($message, $apiConnection) {
        try {
            
            $request    = new eppPollRequest(eppPollRequest::POLL_ACK, $message["id"]);
            $response   = $apiConnection->request($request);
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
