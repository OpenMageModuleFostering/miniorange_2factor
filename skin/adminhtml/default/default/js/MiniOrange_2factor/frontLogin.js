var total = 3;
$j = jQuery.noConflict();
$j(document).ready(function() {
    $j("#label-progressBar").css("display", "block");
    $j("#label-progressBar-forgotphone").css("display", "none");
    $j('#error-cancel').click(function() {
        $error = "";
        $j(".mini-messages li").css("display", "none");
    });
    $j("#cancel").click(function() {
        $j('.collapse').css("display", "none");
        $j("#softoken").css("display", "none");
        $j("#QrCode").css("display", "none");
        $j("#forgotPhone").css("display", "none");
        $j("#otpoversms").css("display", "none");
        $j("#outofbandpush").css("display", "none");
        $j("#kbaSection").css("display", "none");
        $j("#progressBar").css("display", "block");
        $j("#label-progressBar").css("display", "block");
        $j("#label-progressBarWait").css("display", "none");
        $j("#label-progressBar-forgotphone").css("display", "none");
        $j("#authenticationfailed").submit();
    });
    $j("#offlinebutton").click(function() {
        $j("#QrCode").css("display", "none");
        $j("#outofbandpush").css("display", "none");
        $j("#label-progressBar").css("display", "block");
        $j("#progressBar").css("display", "block");
        $j("#label-progressBar-forgotphone").css("display", "none");
        $j("#label-progressBarWait").css("display", "none");
        $j("#enablesofttoken").submit();
    });
    $j("#forgotphonebutton").click(function() {
        $j("#QrCode").css("display", "none");
        $j("#softoken").css("display", "none");
        $j("#otpoversms").css("display", "none");
        $j("#outofbandpush").css("display", "none");
        $j("#label-progressBar").css("display", "none");
        $j("#label-progressBar-forgotphone").css("display", "block");
        $j("#label-progressBarWait").css("display", "none");
        $j("#progressBar").css("display", "block");
        $j("#enableforgotphone").submit();
    });
    $j("#authform").on('submit', function() {
        $j("#softoken").css("display", "none");
        $j("#forgotPhone").css("display", "none");
        $j("#otpoversms").css("display", "none");
        $j("#progressBar").css("display", "block");
        $j("#label-progressBar").css("display", "block");
        $j("#label-progressBar-forgotphone").css("display", "none");
        $j("#label-progressBarWait").css("display", "none");
    });
    $j("#kbaform").on('submit', function() {
        $j("#kbaSection").css("display", "none");
        $j("#progressBar").css("display", "block");
        $j("#label-progressBar").css("display", "block");
        $j("#label-progressBar-forgotphone").css("display", "none");
        $j("#label-progressBarWait").css("display", "none");
    });
    $j("#goBacklogin").click(function() {
        $j("#label-progressBar-forgotphone").css("display", "none");
        $j("#label-progressBarWait").css("display", "none");
        $j("#label-progressBar").css("display", "block");
        $j("#softoken").css("display", "none");
        $j("#forgotPhone").css("display", "none");
        $j("#mo_rba_device").css("display", "none");
        $j("#otpoversms").css("display", "none");
        $j("#outofbandpush").css("display", "none");
        $j("#kbaSection").css("display", "none");
        $j("#progressBar").css("display", "block");
        $j("#goBackLogin").submit();
    });
    $j("#slide").click(function() {
        $id = $j(this).data('option');
        $j("#slider" + $id + "").css("display", "block");
        $j("#logo").hide();
        if ($id == 1)
            $j("#QrCode").css("display", "none");
        else if ($id == 2)
            $j("#softoken").css("display", "none");
        else
            $j("#forgotPhone").css("display", "none");
        $j(".goBackPreview[data-slider=" + $id + "]").css("display", "block");
    });
    $j(".goBackPreview").click(function() {
        $id = $j(this).data('slider');
        $j("#slider" + $id + "").css("display", "none");
        $j("#logo").show();
        if ($id == 1)
            $j("#QrCode").css("display", "block");
        else if ($id == 2)
            $j("#softoken").css("display", "block");
        else
            $j("#forgotPhone").css("display", "block");
        $j(this).css("display", "none");
    });
});