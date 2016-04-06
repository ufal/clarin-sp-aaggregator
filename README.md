# clarin-sp-aaggregator

##shibboleth2.xml
SPConfig->RequestMapper[type="XML"]->RequestMap->Host set attribute `exportAssertion="true"`
SPConfig->ApplicationDefaults set attribute `sessionHook="/php/attrStats.php"` (the path to the script)
SPConfig->ApplicationDefaults->Sessions set attributes `exportLocation="/GetAssertion"` and `exportACL="127.0.0.1"`

[NativeSPAssertionExport](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPAssertionExport)
