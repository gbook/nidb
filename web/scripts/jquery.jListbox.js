/*
 * jListbox jQuery plugin
 *
 * Copyright (c) 2009 Giovanni Casassa (senamion.com - senamion.it)
 *
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://www.senamion.com
 *
 */

jQuery.fn.jListbox = function(o) {

	o = jQuery.extend({
		selectText: "No option",
		viewText: true
	}, o);

	return this.each(function() {
		var el = $(this);

		name = (el.attr('name') || el.attr('id') || 'internalName') + '_jlb';

		el.hide();
		stropt = "";
		var els = el.children("option");        
		$.each(els, function(i,n) {
			text = ($(n).attr("rel") || '') + ' ' + (o.viewText ? $(n).text() : '');
			stropt += "<li rel='" + $(n).val() +"'>" + text + "</li>";
			if ($(n).attr("selected"))
				o.selectText = text;
		}); 

		el.after("<div id='" + name + "' class='jlb_class'><a id='a" + name + "' href='#'>" + o.selectText + "</a><ul>" + stropt + "</ul></div>");

		// CLICK ON TITLE
		$("div#" + name + " a").click(function(){
			$(this).next().slideToggle("fast");
			return false;
		});

		// CLICK ON ELEMENT
		$("div#" + name + " ul li").click(function(){
			listName = $(this).parent().parent().attr('id');
			listName = listName.substr(0, listName.length - 4);
			$('[name=' + listName + ']').val($(this).attr("rel"));
			$(this).parent().parent().children().eq(0).html($(this).html());
		});

		// CLICK OUTSIDE
		$("body").click(function(){
			$(".jlb_class ul").slideUp("fast");
		});
	});
};    
