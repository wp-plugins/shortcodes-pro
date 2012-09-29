/**
* Shortcodes Pro Sort JS
*
* @package Shortcodes Pro
* @author Matt Varone
*/
var mv_shortcodespro_sort_js_params;jQuery(document).ready(function(b){var a=b(".target-row");a.each(function(){var d=b(this).attr("data"),c=b(this),e=b("#loading-"+d);
c.sortable({opacity:0.6,helper:"clone",placeholder:"ui-state-highlight",connectWith:"ul"}).bind("sortupdate",function(f,g){e.show();opts={url:ajaxurl,type:"POST",async:true,cache:false,dataType:"json",data:{action:"shortcodespro_sort",order:c.sortable("toArray").toString(),row:c.attr("data")},success:function(h){e.hide();
return;},error:function(i,j,h){alert(mv_shortcodespro_sort_js_params.in_error+h);e.hide();return;}};b.ajax(opts);});});b(".sep").draggable({connectToSortable:".target-row",helper:"clone",revert:"invalid"});
b(".target-row #separator").live("dblclick",(function(){var c=b(this).parent();b(this).fadeTo(200,0,function(){b(this).remove();c.trigger("sortupdate");
});}));});