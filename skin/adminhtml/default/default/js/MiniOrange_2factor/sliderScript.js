var $j = jQuery.noConflict();
$j(document).ready(function($j) {
    for (var i = 1; i <= total; i++) {
        var slideCount = $j('#slider' + i + ' ul li').length;
        var slideWidth = $j('#slider' + i + ' ul li').width();
        var slideHeight = $j('#slider' + i + ' ul li').height();
        var sliderUlWidth = slideCount * slideWidth;
        var no = 1;
        $j('.control_info[data-slider=' + i + ']').append(no);
        $j('#slider' + i + '').css({
            width: slideWidth,
            height: slideHeight
        });
        $j('#slider' + i + ' ul').css({
            width: sliderUlWidth,
            marginLeft: -slideWidth
        });
        $j('#slider' + i + ' ul li:last-child').prependTo('#slider' + i + ' ul');
    }

    function moveLeft(id) {
        $j('ul[data-slider=' + id + ']').fadeOut("slow", function() {
            $j('ul li[data-slider=' + id + ']:last-child').prependTo('ul[data-slider=' + id + ']');
            $j('ul[data-slider=' + id + ']').css('left', '');
            $j('ul[data-slider=' + id + ']').fadeIn("slow");
            no = $j('ul li[data-slider=' + id + ']').next().data('pos');
            $j('.control_info[data-slider=' + id + ']').empty();
            $j('.control_info[data-slider=' + id + ']').append(no);
        });
    };

    function moveRight(id) {
        $j('ul[data-slider=' + id + ']').fadeOut("slow", function() {
            $j('ul li[data-slider=' + id + ']:first-child').appendTo('ul[data-slider=' + id + ']').fadeIn("slow");
            $j('ul[data-slider=' + id + ']').css('left', '');
            $j('ul[data-slider=' + id + ']').fadeIn("slow");
            no = $j('ul li[data-slider=' + id + ']').next().data('pos');
            $j('.control_info[data-slider=' + id + ']').empty();
            $j('.control_info[data-slider=' + id + ']').append(no);
        });
    };
    $j('.control_prev').click(function() {
        moveLeft($j(this).data('slider'));
    });
    $j('.control_next').click(function() {
        moveRight($j(this).data('slider'));
    });
});