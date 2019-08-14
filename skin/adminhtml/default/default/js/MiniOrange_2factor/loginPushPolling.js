var timeout;
var $t = jQuery.noConflict();

function pollPushValidation() {
    var jsonString = "{\"txId\":\"" + transId + "\"}";
    $t.ajax({
        url: postUrl,
        type: "POST",
        dataType: "json",
        data: jsonString,
        contentType: "application/json; charset=utf-8",
        success: function(result) {
            var status = JSON.parse(JSON.stringify(result)).status;
            if (status == 'SUCCESS') {
                $t("#outofbandpush").css("display", "none");
                $t("#progressBar").css("display", "block");
                $t("#label-progressBar").css("display", "block");
                $t("#label-progressBar-forgotphone").css("display", "none");
                $t('#mobile_validation_form').submit();
            } else if (status == 'ERROR' || status == 'FAILED') {
                $t('#backto_mo_loginform').submit();
            } else if (status == 'DENIED') {
                $t("#outofbandpush").css("display", "none");
                $t("#progressBar").css("display", "block");
                $t("#label-progressBar").css("display", "block");
                $t("#label-progressBar-forgotphone").css("display", "none");
                $t("#mo2f_denied_transaction").submit();
            } else {
                timeout = setTimeout(pollPushValidation, 3000);
            }
        }
    });
}
if (transId != "") {
    pollPushValidation();
}