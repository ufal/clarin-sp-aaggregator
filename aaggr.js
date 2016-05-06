var shibbolethSessionUrl = "https://ufal-point-dev.ms.mff.cuni.cz/Shibboleth.sso/Session",
    spEntityID = "https://ufal-point-dev.ms.mff.cuni.cz/shibboleth/eduid/sp";
jQuery.get(shibbolethSessionUrl).
    done(function(data){
       var session = jQuery(data);
       var idp = session.find("strong:contains('Identity Provider:')")[0].nextSibling.nodeValue.trim();
       var attrs = session.find("u:contains('Attributes')").nextAll().map(function(index, el){ return el.innerHTML}).get();
       if(idp){
            var obj = {spEntityID: spEntityID, idpEntityID:idp, attrs:attrs};
            window.alert(JSON.stringify(obj));
       }

});
