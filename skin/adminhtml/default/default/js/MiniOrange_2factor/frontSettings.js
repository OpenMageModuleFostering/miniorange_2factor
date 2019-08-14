var total = 7;
var $m = jQuery.noConflict();
$m(document).ready(function() {
    $m("#phone").intlTelInput();
    $m("#additional_phone").intlTelInput();
    if ($email != "") {
        voiddisplay("#twofactorsetup");
        setactive('two_factor_setup');
    } else {
        setactive('account_setup');
    }
    if ($downloaded == "1") {
        $m('#showDownload').prop('checked', true);
        $m("#showDownload").val(1);
    }
    if ($m("#showDownload").is(":checked")) {
        $m("#configureqr").css("display", "block");
        $m("#downloadscreen").css("display", "none");
    } else {
        $m("#configureqr").css("display", "none");
        $m("#downloadscreen").css("display", "block");
    }
    if ($twofactortype != "") {
        $selectedMethod = $twofactortype.toLowerCase().replace(/ /g, "_");
        $m(".mo2f_thumbnail").removeClass("method_active_box");
        $m("input[name='mo2f_selected_2factor_method']").removeAttr("checked");
        $m(".mo2f_click").removeClass("method_active");
        $m('#' + $selectedMethod).addClass("method_active_box");
        $m('#' + $selectedMethod).find(".mo2f_click").addClass("method_active");
        $m('#' + $selectedMethod).find("input").prop("checked", true);
        $m('#' + $selectedMethod).find("input").attr("disabled", true);
    }
    $m(".mo2f_works a").click(function(e) {
        e.preventDefault();
    });
    $m(".mo2f_faqs a").click(function(e) {
        e.preventDefault();
    });
    $m(".navbar a").click(function() {
        $id = $m(this).parent().attr('id');
        setactive($id);
        $href = $m(this).data('method');
        voiddisplay($href);
    });
    $m("#current_page").click(function() {
        $m(this).next("ul").slideToggle("slow");
        if (!$m("#nav-toggle span").hasClass("navbaractive")) {
            $m("#nav-toggle span").addClass("navbaractive");
        } else {
            $m("#nav-toggle span").removeClass("navbaractive");
        }
    });
    $m(".btn-link").click(function() {
        $m(".collapse").slideUp("slow");
        if (!$m(this).next("div").is(':visible')) {
            $m(this).next("div").slideDown("slow");
        }
    });
    $m('#showDownload').change(function() {
        if ($m(this).attr('checked')) {
            $m(this).val(0);
            $m(this).attr('checked', false);
        } else {
            $m(this).val(1);
            $m(this).attr('checked', true);
            $m("#configmobilebutton").click();
        }
        $m("#downloadscreen").slideToggle();
        $m("#configureqr").slideToggle();
        document.location.href = "#displayQrCode";
    });
    $m('#preview1').click(function() {
        $m("a[data-method='#howitworks']").click();
        $m("#offline-preview").click();
        document.location.href = "#slider3";
    });
    $m('#preview2').click(function() {
        $m("a[data-method='#howitworks']").click();
        $m("#phonelost-preview").click();
        document.location.href = "#slider4";
    });
    $m('#preview3').click(function() {
        $m("a[data-method='#howitworks']").click();
        $m("#reconfigure-preview").click();
        document.location.href = "#slider5";
    });
    $m('.preview4').click(function() {
        $m("a[data-method='#howitworks']").click();
        $m("#register-preview").click();
        document.location.href = "#slider2";
    });
    $m('.preview5').click(function() {
        $m("a[data-method='#howitworks']").click();
        $m("#register-preview-2").click();
        document.location.href = "#slider7";
    });
    $m(".mo2f_click").click(function() {
        if (!$m(this).hasClass("method_active")) {
            $m(this).find("input").prop("checked", true);
            $m(".mo2f_thumbnail").hide();
            $m("#twofactorselect").show();
            $m("#mo2f_2factor_form").submit();
        }
    });
    $m(".reconfigure").click(function() {
        $m(".mo2f_thumbnail").hide();
        $m("#twofactorselect").show();
        if ($m(this).data("method") == "SMS" || $m(this).data("method") == "PHONE VERIFICATION") {
            $m("#phone_reconfigure").val($m(this).data("method"));
            $m("#mo2f_2factor_reconfigure_phone_form").submit();
        } else if ($m(this).data("method") == "GOOGLE AUTHENTICATOR") {
            $m("#mo2f_2factor_reconfigure_ga_form").submit();
        } else if ($m(this).data("method") == "KBA") {
            $m("#mo2f_2factor_reconfigure_kba_form").submit();
        } else {
            $m("#reconfigure_mobile").val(1);
            $m("#mo2f_2factor_reconfigure_mobile_form").submit();
        }
    });
    $m(".test").click(function() {
        $m(".mo2f_thumbnail").hide();
        $m("#twofactorselect").show();
        $m("#test_2factor").val($m(this).data("method"));
        $m("#mo2f_2factor_test_form").submit();
    });
    $m('form[name="f"]').submit(function() {
        $m('.mo_page').hide();
        $m('#progressBar').show();
    });
});

function setactive($id) {
    $m(".navbar-tabs li").removeClass("active");
    $id = '#' + $id;
    $m($id).addClass("active");
}

function voiddisplay($href) {
    $m(".mo_page").css("display", "none");
    $m($href).css("display", "block");
}