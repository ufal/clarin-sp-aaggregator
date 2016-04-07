# clarin-sp-aaggregator

##shibboleth2.xml
* SPConfig->RequestMapper[type="XML"]->RequestMap->Host set attribute `exportAssertion="true"`
* SPConfig->ApplicationDefaults set attribute `sessionHook="/php/attrStats.php"` (the path to the script)
* SPConfig->ApplicationDefaults->Sessions set attributes `exportLocation="/GetAssertion"` and `exportACL="127.0.0.1"`

##web server
make sure the shibboleth headers are present

In our nginx setup this magical bit helps
```
169   location /php/attrStats.php {
170     shib_request /shibauthorizer;
171     include proxy_params;
172     proxy_pass http://apache;
173   }
```

[NativeSPAssertionExport](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPAssertionExport)
