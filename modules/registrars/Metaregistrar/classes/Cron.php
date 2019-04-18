<?php

namespace MetaregistrarModule\classes;

class Cron {
    static function poll() {
        try {
            $apiData        = Helpers::getApiData();
            $apiConnection  = Api::getApiConnection($apiData);

            $apiConnection->setTimeout(30);

            do {
                set_time_limit(120);
                $message = Poll::getMessage($apiConnection);

                if($message === false) {
                    break;
                }

                if(!empty($message["domain"])) {
                    $domainIdArray = Helpers::getDomainIdArray($message["domain"]);

                    foreach ($domainIdArray as $domainId) {
                        Helpers::saveMessage($message, $domainId);
                    }
                }
                Poll::ackMessage($message, $apiConnection);

            } while(true);

            Api::closeApiConnection($apiConnection);
        
        } catch (\Exception $e) {
            Api::closeApiConnection($apiConnection);
            throw $e;
        }
    }
}
