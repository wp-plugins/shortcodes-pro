/**
* Shortcodes Pro Sort JS
*
* @package Shortcodes Pro
* @author Matt Varone
*/
var mv_shortcodespro_sort_js_params;
jQuery(document).ready(function($) {
	var shortcodesRows = $(".target-row");
	shortcodesRows.each(function () {
		var data = $(this).attr("data"),
			sortList = $(this),
			loading = $("#loading-" + data);
		sortList.sortable({
			opacity: 0.6,
			helper: "clone",
			placeholder: "ui-state-highlight",
			connectWith: "ul"
		}).bind("sortupdate", function (event, ui) {
			loading.show();
			opts = {
				url: ajaxurl,
				type: "POST",
				async: true,
				cache: false,
				dataType: "json",
				data: {
					action: "shortcodespro_sort",
					order: sortList.sortable("toArray").toString(),
					row: sortList.attr("data")
				},
				success: function (response) {
					loading.hide();
					return;
				},
				error: function (xhr, textStatus, e) {
					alert(mv_shortcodespro_sort_js_params.in_error + e);
					loading.hide();
					return;
				}
			};
			$.ajax(opts);
		});
	});
	$(".sep").draggable({
		connectToSortable: ".target-row",
		helper: "clone",
		revert: "invalid"
	});
	$(".target-row #separator").live("dblclick", (function () {
		var parent = $(this).parent();
		$(this).fadeTo(200, 0, function () {
			$(this).remove();
			parent.trigger("sortupdate");
		});
	}));
});