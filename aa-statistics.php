<?php

// redirect to the correct destination
header('Location: ' . $_GET['return'], true, 302);


// This script finds exported header names from shibboleth and sends them to an aggregator.
//
// The main goal is to monitor attribute release and provide a central place where "bad" IdPs can be
// easily blackmailed...
//
// See: https://github.com/ufal/clarin-sp-aaggregator
// by lindat-dev team (ok, jm)
//


// REST api of the aggregator
$aggregator_url='https://clarin-aa.ms.mff.cuni.cz/aaggreg/v1/got';
$sp='unknown';


/**
 * Build the request url and execute curl on it.
 * @todo test asynch...
 */
function send_info($idp, $sp, $ts, $attrs, $suspicious) 
{
    global $aggregator_url, $sp;
    $attributes_encoded = "attributes[]=" . implode('&attributes[]=', array_map('urlencode', $attrs));
    // -g does not allow curl to interpret []{}
    $cmd = "curl -g '$aggregator_url?idp=$idp&sp=$sp&timestamp=$ts&$attributes_encoded&warn=$suspicious'";
    $cmd .= " > /dev/null 2>&1 &";
    echo '<pre>'.$cmd.'</pre>';

    exec($cmd, $output, $exit);
    return $exit == 0;
}

/**
 * Array version of getenv - corner cases for 0, null etc.
 */
function getenvs($envarray, &$ret)
{
    foreach($envarray as $value)
    {
        $ret = getenv($value);
        if ($ret) {
            return true;
        }
    }
    return false;
}


$idps = array();

// we need the count so we can iterate over the exported assertions
// - the env variable can be exposed under different names!
//
$assertion_count = 0;
$assertion_count_name = "Shib-Assertion-Count";
if (!getenvs(
        array($assertion_count_name, str_replace('-','_',strtoupper("http_".$assertion_count_name))), 
        $assertion_count
    ))
{
    http_response_code(400);
    echo("$assertion_count_name not found");
    exit;
}
$assertion_count = (int)$assertion_count;


// idp that was used to authenicate
$idp = null;
// timestamp
$ts = date("c");

// obtain the assertions
// - the env variable can be exposed under different names!
//
$assertion_link_attr_name = "Shib-Assertion-"; 
$assertion_link_attr_name_upper = str_replace('-','_',strtoupper("http_". "Shib-Assertion-")); 
for ($i=$assertion_count; 0 < $i; --$i) 
{
    // why would there be more than one assertion?
    $n = str_pad($i, 2, "0", STR_PAD_LEFT);

    //$n is 00, or 01
    $assertion_link = "";
    getenvs(array($assertion_link_attr_name . $n, $assertion_link_attr_name_upper . $n), $assertion_link);
    
    if(!empty($assertion_link))
    {
        $assertion_link = str_replace("https://" . getenv("SERVER_NAME"), "https://localhost", $assertion_link);
        //TODO add a timeout
        $xml = simplexml_load_file($assertion_link);
        echo $xml->asXML()."=====\n\n";
        $idp = (string)$xml->xpath('//*[local-name()="Issuer"]')[0];
        if (!array_key_exists($idp, $idps)) 
        {
            $idps[$idp] = array();
        }
        foreach ($xml->xpath('//*[local-name()="Attribute"]/@Name') as $name)
        {
            array_push($idps[$idp], (string)$name);
        }
        $sp = (string)$xml->xpath('//@SPNameQualifier')[0];
    }
}

// anything strange?
$suspicious = "";
if (1 < count($idps)) {
    $suspicious = "more than 1 idp";
}

// aggregate the info
send_info($idp, $sp, $ts, $idps[$idp], $suspicious);

exit;
