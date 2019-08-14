var $t = jQuery.noConflict();
var timeout;

function pollMobileValidation() {
    var jsonString = "{\"txId\":\"" + tranxId + "\"}";
    $t.ajax({
        url: postUrl,
        type: "POST",
        dataType: "json",
        data: jsonString,
        contentType: "application/json; charset=utf-8",
        success: function(result) {
            var status = JSON.parse(JSON.stringify(result)).status;
            if (status == 'SUCCESS') {
                var content = "<div id='success'><center><img src='" + url1 + "'/></center></div>";
                $t("#showQrCode").empty();
                $t("#showQrCode").append(content);
                setTimeout(function() {
                    $t("#twofactor-content").hide();
                    $t("#progressBar").show();
                    $t("#mobile_registration_success").submit();
                }, 1000);
            } else if (status == 'ERROR' || status == 'FAILED') {
                var content = "<div id='error'><center><img src='" + url2 + "' /></center></div>";
                $t("#showQrCode").empty();
                $t("#features").hide();
                $t("#showQrCode").append(content);
                setTimeout(function() {
                    $t("#twofactor-content").hide();
                    $t("#progressBar").show();
                    $t('#mobile_registration_failed').submit();
                }, 1000);
            } else {
                timeout = setTimeout(pollMobileValidation, 3000);
            }
        }
    });
}
if (tranxId != "") {
    pollMobileValidation();
}