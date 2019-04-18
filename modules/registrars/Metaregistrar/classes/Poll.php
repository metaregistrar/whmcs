<?php

namespace MetaregistrarModule\classes;

use Metaregistrar\EPP\eppConnection;
use Metaregistrar\EPP\eppException;
use Metaregistrar\EPP\eppPollRequest;
use Metaregistrar\EPP\eppPollResponse;

class Poll {
    static function getMessage(eppConnection $apiConnection) {
        try {
            
            $response   = $apiConnection->request(new eppPollRequest(eppPollRequest::POLL_REQ, 0));

            /* @var $response eppPollResponse */
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
    
    static function ackMessage($message, eppConnection $apiConnection) {
        try {
            
            $apiConnection->request(new eppPollRequest(eppPollRequest::POLL_ACK, $message["id"]));
            
        } catch (eppException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
