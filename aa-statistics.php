<?php
//set this to the collector
$url='https://clarin-aa.ms.mff.cuni.cz/';

function request($url, $payload) {
    #$cmd = "curl -X POST -H 'Content-Type: application/json'";
    #$cmd .= " -d '" . $payload . "' " . "'" . $url . "'";
    $payload = base64_encode($payload);
    $cmd = "curl '$url?data=$payload'";
    $cmd .= " > /dev/null 2>&1 &";

    exec($cmd, $output, $exit);
    return $exit == 0;
}

$idps = array();
$assertion_count = 0;
$assertion_count_name = "Shib-Assertion-Count";
//different name in some envs
$assertion_count_name_upper = str_replace('-','_',strtoupper("http_".$assertion_count_name)); 

if(getenv($assertion_count_name)){
    $assertion_count = (int)getenv($assertion_count_name);
}else if(getenv($assertion_count_name_upper)){
    $assertion_count = (int)getenv($assertion_count_name_upper);
}else{
    http_response_code(400);
    echo("$assertion_count_name not fount");
    exit;
}
$assertion_link_attr_name = "Shib-Assertion-"; 
//different name in some envs
$assertion_link_attr_name_upper = str_replace('-','_',strtoupper("http_". "Shib-Assertion-")); 
for($i=$assertion_count; $i > 0; $i--){
    //why would there be more than one assertion?
    $n = 0;
    if($i<10){
        $n = $n . $i;
    } else{
        $n = $i;
    }
    //$n is usually 00, or 01
    $assertion_link = "";
    if(getenv($assertion_link_attr_name . $n)){
        $assertion_link = getenv($assertion_link_attr_name . $n);
    }else if(getenv($assertion_link_attr_name_upper . $n)){
        $assertion_link = getenv($assertion_link_attr_name_upper . $n);
    }
    if(!empty($assertion_link)){
        $assertion_link = str_replace("https://" . getenv("SERVER_NAME") ,"https://localhost",$assertion_link);
        //TODO add a timeout
        $xml = simplexml_load_file($assertion_link);
        $idp = (string)$xml->xpath('//*[local-name()="Issuer"]')[0];
        if(!array_key_exists($idp, $idps)){
            $idps[$idp] = array();
            $idps[$idp]["seen_attribute_names"] = array();  
        }
        foreach($xml->xpath('//*[local-name()="Attribute"]/@Name') as $name){
            array_push($idps[$idp]["seen_attribute_names"], (string)$name);
        }
        request($url, json_encode($idps));
    }
}
header('Location: ' . $_GET['return'], true, 302);
exit;
?>
