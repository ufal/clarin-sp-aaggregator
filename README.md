# clarin-sp-aaggregator
The script accesses raw saml assertions as received by the sp, ie. before any mapping or filtering takes place. It cherry picks the attribute names, puts them into an array and sends them together with Issuer (idp entity name) to a collector service.

Currently send means GET `$url?data=$payload` where `$payload=base64_encode(json_encode($o))` where `$o` is a map of idp entity id to array of seen attributes, ie. `$o[$idp]["seen_attribute_names"]` is an array listing received attribute names for `$idp` (entityID). eg.:
```
{"https:\/\/cas.cuni.cz\/idp\/shibboleth":
   {"seen_attribute_names":
      ["urn:oid:1.3.6.1.4.1.5923.1.1.1.1","urn:oid:1.3.6.1.4.1.5923.1.1.1.5","urn:oid:2.5.4.10",
      "urn:oid:1.3.6.1.4.1.5923.1.1.1.9","urn:oid:2.5.4.4","urn:oid:2.5.4.42",
      "urn:oid:1.3.6.1.4.1.5923.1.1.1.3","http:\/\/www.mefanet.cz\/mefaperson\/",
      "http:\/\/eduid.cz\/attributes\/commonName#ASCII","urn:oid:2.5.4.3",
      "urn:oid:1.3.6.1.4.1.5923.1.1.1.7","urn:oid:1.2.840.113549.1.9.2","urn:oid:1.3.6.1.4.1.5923.1.1.1.8",
      "urn:oid:1.2.840.113549.1.9.1","urn:oid:1.3.6.1.4.1.5923.1.1.1.10","urn:oid:1.3.6.1.4.1.5923.1.1.1.4",
      "urn:oid:0.9.2342.19200300.100.1.3","urn:oid:1.3.6.1.4.1.25178.1.2.9"]
    }
}
```

##shibboleth2.xml
* SPConfig->RequestMapper[type="XML"]->RequestMap->Host set attribute `exportAssertion="true"`
* SPConfig->ApplicationDefaults set attribute `sessionHook="/php/aa-statistics.php"` (the path to the script)
* SPConfig->ApplicationDefaults->Sessions set attributes `exportLocation="/GetAssertion"` and `exportACL="127.0.0.1"`
 
e.g.:
```
  1 <SPConfig xmlns="urn:mace:shibboleth:2.0:native:sp:config"
  2     xmlns:conf="urn:mace:shibboleth:2.0:native:sp:config"
  3     xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
  4     xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"    
  5     xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
  6     logger="syslog.logger" clockSkew="180">
  7 
  8 
  9     <OutOfProcess logger="shibd.logger" />
 10     <InProcess logger="native.logger" />
 11     <RequestMapper type="XML">
 13       <RequestMap>
 14         <Host name="ufal-point-dev.ms.mff.cuni.cz"
 15               authType="shibboleth"
 16               requireSession="true"
 17               exportAssertion="true"
 18               redirectToSSL="443">
 19         </Host>
 20       </RequestMap>
 21     </RequestMapper>
 22 
...
 24     <ApplicationDefaults entityID="https://ufal-point-dev.ms.mff.cuni.cz/shibboleth/eduid/sp"
 25                          REMOTE_USER="eppn persistent-id targeted-id mail"
 26                          sessionHook="/php/aa-statistics.php"
 27              signing="true" encryption="true">
...
 38         <Sessions relayState="ss:mem"
 39                   checkAddress="false" cookieProps="https"
 40                   exportLocation="/GetAssertion"
 41                   exportACL="127.0.0.1">
 42 


```

##web servers
make sure the shibboleth headers are present, ie. enforce existing session.

###nginx
In our [nginx setup](https://github.com/ufal/lindat-dspace/wiki/Using-Nginx) this magical bit helps
```
169   location /php/aa-statistics.php {
170     shib_request /shibauthorizer;
171     include proxy_params;
172     proxy_pass http://apache;
173   }
```

###apache
Please start by reading [Secure Use of the RequestMapper on Apache](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPApacheConfig), then make sure there is a session
```
<Location /php/aa-statistics.php>
    AuthType shibboleth
    ShibRequestSetting requireSession 1
    Require valid-user
 </Location>
 ```

[NativeSPAssertionExport](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPAssertionExport)
