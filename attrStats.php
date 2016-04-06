<html>
<head></head>
<body>
<?php
//first option to use the session variables
//is the issuer/idp somewhere?
$path_to_attribute_mapping = "/opt/shibboleth-sp-fastcgi/etc/shibboleth/attribute-map.xml";
$xml = simplexml_load_file($path_to_attribute_mapping) or die("Error: Cannot create object");
$xml->registerXPathNamespace("ns", "urn:mace:shibboleth:2.0:attribute-map");

$configured_names = array();
foreach($xml->xpath('//ns:Attribute') as $attribute){
    //echo $attribute['id'] . "=" . $attribute['name'];

    //best would be to record stats for 'name' instead of 'id'
    //but there can be one id for multiple names
    //and we only get the human readable 'id'
    //eppn=urn:mace:dir:attribute-def:eduPersonPrincipalName
    //eppn=urn:oid:1.3.6.1.4.1.5923.1.1.1.6
    $name = (string)$attribute['id'];
    $configured_names[$name] = 0;
}
$seen_names = array();
foreach(array_keys($configured_names) as $name){
    if(getenv($name)){
        //try the name as is
        array_push($seen_names, $name);
    }else if(getenv(strtoupper('http_'.$name))){
        //try HTTP_NAME
        array_push($seen_names, $name);
    }
}
var_dump($seen_names);
echo "<p />";
//var_dump($_SERVER);

//second option to access the actual assertion
$seen_names = array();
$assertion_count = 0;
$assertion_count_name = "Shib-Assertion-Count";
$assertion_count_name_upper = str_replace('-','_',strtoupper("http_".$assertion_count_name)); 

if(getenv($assertion_count_name)){
    $assertion_count = (int)getenv($assertion_count_name);
}else if(getenv($assertion_count_name_upper)){
    $assertion_count = (int)getenv($assertion_count_name_upper);
}
$assertion_link_attr_name = "Shib-Assertion-"; 
$assertion_link_attr_name_upper = str_replace('-','_',strtoupper("http_". "Shib-Assertion-")); 
//echo "<li>assertion_count " . $assertion_count; 
for($i=$assertion_count; $i > 0; $i--){
    //why would there be more than one assertion?
    $n = 0;
    if($i<10){
        $n = $n . $i;
    } else{
        $n = $i;
    }
    $assertion_link = "";
    if(getenv($assertion_link_attr_name . $n)){
        $assertion_link = getenv($assertion_link_attr_name . $n);
    }else if(getenv($assertion_link_attr_name_upper . $n)){
        $assertion_link = getenv($assertion_link_attr_name_upper . $n);
    }
    //echo "<li>assertion_link " . $assertion_link; 
    if(!empty($assertion_link)){
        $assertion_link = str_replace("https://" . getenv("SERVER_NAME") ,"https://localhost",$assertion_link);
        $xml = simplexml_load_file($assertion_link);
        $idp = (string)$xml->xpath('//*[local-name()="Issuer"]')[0];
        echo "<h2>" . $idp . "</h2>";
        foreach($xml->xpath('//*[local-name()="Attribute"]/@Name') as $name){
            array_push($seen_names, (string)$name);
        }
    }
}
var_dump($seen_names);
?>
<p><a id="return_link" href="<?php echo $_GET['return'] ?>">return</a>
<script type="text/javascript">
    window.setTimeout(document.getElementById("return_link").click(), 3000);
</script>
</body>
</html>
