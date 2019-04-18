<?php

namespace MetaregistrarModule\classes;

class Hooks {
    static function adminAreaTable() {
        
        if(basename($_SERVER['PHP_SELF']) != "clientsdomains.php") {
            return;
        }
        
        if(!empty($_GET["id"])) {
            $domainId = $_GET["id"];
        } elseif(!empty($_GET["domainid"])) {
            $domainId = $_GET["domainid"];
        } else {
            $domainId = Helpers::getFirstDomainId($_GET["userid"]);
        }
        
        if(!Helpers::isMetaregistrarDomain($domainId)) {
            return;
        }
        
        $table = Helpers::createPollDataTable($domainId);
        
        $html = ''
        . ' <tr>'
        . '     <td class=\'fieldlabel\'>'
        . '         Poll Messages'
        . '     </td>'
        . '     <td colspan=\'3\' class=\'fieldarea\'>'
        .           $table
        . '     </td>'
        . ' </tr>';
        
        $script = ''
        . ' <script>'
        . '     var handle = $("#profileContent").children("form").eq(0).children().eq(1).children().eq(0).children().eq(13);'
        . '     handle.after("'.$html.'");'
        . ' </script>'
        . ' <script src=\'//code.jquery.com/ui/1.11.4/jquery-ui.js\'></script>'
        . ' <script src=\'../modules/registrars/Metaregistrar/templates/js/adminarea.js\'></script>'
        . ' <link href=\'//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css\' rel=\'stylesheet\' type=\'text/css\'>';
        
        return $script;
    }
}
