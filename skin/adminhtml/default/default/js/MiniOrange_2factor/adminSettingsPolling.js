var $j = jQuery.noConflict();
var timeout;
function pollMobileRegistration() {
    var jsonString = "{\"txId\":\"" + transId + "\"}";
    $j.ajax({
        url: postUrl,
        type: "POST",
        dataType: "json",
        data: jsonString,
        contentType: "application/json; charset=utf-8",
        success: function(result) {
            var status = JSON.parse(JSON.stringify(result)).status;
            if (status == 'SUCCESS') {
                var content = url1;
                if (test != "1") {
                    $j("#displayQrCode").empty();
                    $j("#displayQrCode").append(content);
                } else if (testMobile == "1") {
                    $j("#testQrCode").empty();
                    $j("#testQrCode").append(content);
                }
                setTimeout(function() {
                    $j("#mobile_register_form").submit();
                }, 1000);
            } else if (status == 'ERROR' || status == 'FAILED') {
                var content = url2;
                if (test != "1") {
                    $j("#displayQrCode").empty();
                    $j("#displayQrCode").append(content);
                } else if (testMobile == "1") {
                    $j("#testQrCode").empty();
                    $j("#testQrCode").append(content);
                }
                setTimeout(function() {
                    $j("#mobile_register_failed").submit();
                }, 1000);
            } else {
                timeout = setTimeout(pollMobileRegistration, 3000);
            }
        }
    });
}
if (transId != "") {
    pollMobileRegistration();
}