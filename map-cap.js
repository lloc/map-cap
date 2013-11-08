jQuery(document).ready(function($) {
	$(".mc-accordion").accordion({
		autoHeight: false
    });
	$("#mc-tabs").tabs().bind("tabshow", function(event, ui) {
		$(".mc-accordion").accordion("resize");
	});
});