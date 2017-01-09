function failedGet(url){
    return function(err){
        console.error("Failed to get " + url +"\n" + "Got " + err.status + " " + err.statusText );
    }
}

//FILL THESE
var shibbolethSessionUrl = "/Shibboleth.sso/Session",
    spEntityID = "unknown",
    aggregator_url='https://clarin-aa.ms.mff.cuni.cz/aaggreg/v1/got';

jQuery.get(shibbolethSessionUrl).
    done(function(data){
       var session = jQuery(data);
       var lookFor = "strong:contains('Identity Provider:')";
       if(session.find(lookFor).length > 0){
            var idp = session.find(lookFor)[0].nextSibling.nodeValue.trim();
            var attrs = session.find("u:contains('Attributes')").nextAll().map(function(index, el){ return encodeURIComponent(el.innerHTML)}).get();
            if(idp){
                    var ts = new Date().toISOString();
                    attributes_encoded = "attributes[]=" + attrs.join("&attributes[]=");
                    var logUrl = aggregator_url + '?idp=' + idp + '&sp=' + spEntityID + '&timestamp=' + ts + '&' + attributes_encoded + '&source=js_aaggr'; 
                    jQuery.get(logUrl).done(function(){
                        console.log("Succcessfully sent " + logUrl);
                    }).fail(failedGet(logUrl));
            }
       }else{
           console.error("Did not find session details in the response of " + shibbolethSessionUrl);
       }

}).fail(failedGet(shibbolethSessionUrl));
