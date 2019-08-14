$j = jQuery.noConflict();
$j(document).ready(function() {
    $j("#phone").intlTelInput();
    $j('#goBack').click(function() {
        $j('#twofactor-content').hide();
        $j('#progressBar').show();
        $j('#gobacktologin').submit();
    });
    $j('#error-cancel').click(function() {
        $j(".mini-messages li").css("display", "none");
    });
    $j("input[name='twofactor']").change(function() {
        $j('#twofactor-content').hide();
        $j('#progressBar').show();
        $j('#choose_method').submit();
    });
    $j('form[name="f"]').submit(function() {
        $j('#twofactor-content').hide();
        $j('#progressBar').show();
    });
    $j(".btn-link").click(function() {
        $j(".collapse").slideUp("slow");
        if (!$j(this).next("div").is(':visible')) {
            $j(this).next("div").slideDown("slow");
            $j("#GAQr").slideUp("slow");
        } else {
            $j(this).next("div").slideUp("slow");
            $j("#GAQr").slideDown("slow");
        }
    });
});