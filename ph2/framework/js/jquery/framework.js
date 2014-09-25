/*
Phoenix2
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
This is the javascript framework loaded on each page view. It contains jQuery methods.
*/

/*
initialize the facebox plugin
*/
$(document).ready(function() {
	
  $('a[rel*=facebox]').fancybox({
	titleShow : false,
	showNavArrows: false 
  });
  /*{
	loadingImage : 'framework/js/facebox/loading.gif',
	closeImage   : 'framework/js/facebox/closelabel.png'
  }*/
})

// make sure only one modal is loaded into a facebox each time it is called
/*$(document).bind('beforeReveal.facebox', function() {
    $("#facebox .content").empty();
});*/


/* adds scrollstart and scrollstop events to jQuery */
/* see http://james.padolsey.com/javascript/special-scroll-events-for-jquery/ */

$(document).ready(function() {
	(function(){
	 
		var special = jQuery.event.special,
			uid1 = 'D' + (+new Date()),
			uid2 = 'D' + (+new Date() + 1);
	 
		special.scrollstart = {
			setup: function() {
	 
				var timer,
					handler =  function(evt) {
	 
						var _self = this,
							_args = arguments;
	 
						if (timer) {
							clearTimeout(timer);
						} else {
							evt.type = 'scrollstart';
							jQuery.event.handle.apply(_self, _args);
						}
	 
						timer = setTimeout( function(){
							timer = null;
						}, special.scrollstop.latency);
	 
					};
	 
				jQuery(this).bind('scroll', handler).data(uid1, handler);
	 
			},
			teardown: function(){
				jQuery(this).unbind( 'scroll', jQuery(this).data(uid1) );
			}
		};
	 
		special.scrollstop = {
			latency: 300,
			setup: function() {
	 
				var timer,
						handler = function(evt) {
	 
						var _self = this,
							_args = arguments;
	 
						if (timer) {
							clearTimeout(timer);
						}
	 
						timer = setTimeout( function(){
	 
							timer = null;
							evt.type = 'scrollstop';
							jQuery.event.handle.apply(_self, _args);
	 
						}, special.scrollstop.latency);
	 
					};
	 
				jQuery(this).bind('scroll', handler).data(uid2, handler);
	 
			},
			teardown: function() {
				jQuery(this).unbind( 'scroll', jQuery(this).data(uid2) );
			}
		};
	 
	})();
});

/*
show/hide the header div
*/
$(document).ready( function() {
	// hide show_header-button if necessary (hack)
	if($("#header_show").hasClass("hidden")) {
		$("#header_show").hide();
	}
	// hide header
	$("#header_hide").click( function() {
		$("#header_hide").fadeOut();
		$("#header").slideUp();
		$("#header_show").fadeIn();
		// send status to PHP session
		$.post("?action=jq&task=setSessionShowHeader", {"visible": 0});
	});
	// show header
	$("#header_show").click( function() {
		$("#header_show").fadeOut();
		$("#header").slideDown();
		$("#header_hide").fadeIn();
		// send status to PHP session
		$.post("?action=jq&task=setSessionShowHeader", {"visible": 1});
	});
});

/*
#top corpus selection
*/
$(document).ready( function() {
	var topCorpusMenu = $("#top_right .corpus_selection");
	var currentCorpusItem = topCorpusMenu.children("a.item.current");
	var currentCorpusName = currentCorpusItem.html();
	// toggle (Please select / Corpus Name)
	topCorpusMenu.mouseenter( function () {
		currentCorpusItem.html('Please select:');
	});
	topCorpusMenu.mouseleave( function () {
		currentCorpusItem.html(currentCorpusName);
	});
});

/*
highlight the current modulemenu item
*/
$(document).ready( function() {
	// get the current menuitem signature
	var current_menuitem_id = $("#jq_current_menuitem_id").text();
	// add the .current class to the matching submenu div
	$("#" + current_menuitem_id).addClass("current");
});

/*
assign selected class to selectable table rows
*/
$(document).ready( function() {
	// assign 'selected' to a tr inside the tbody of a table.selectable
	$("table.selectable.rstable tbody tr").click( function() {
		$(this).parent().children().removeClass('selected');
		$(this).addClass('selected');
	});
});

/*
ensure that .digit_and_points_only-elements end with a .-character
*/
$(document).ready( function() {
	$('.digits_and_points_only').bind( 'focusout', function() {
		if ($(this).val().length > 0 && $(this).val().substr(-1) != '.') {
			$(this).val( $(this).val() + '.' )
		}
	});
});

/*
tabbed modulebox
*/
$(document).ready( function() {
	// functions
	function selectCurrent (titleObject) {
		// marks the referenced title link as 'current'
		$(titleObject).parent().children().removeClass('current');
		$(titleObject).addClass('current');
		return $(titleObject).attr('rel');
	}
	
	function showTabBody (titleObject, currentTabIdentitier) {
		// shows the corresponding tab body
		var tab_body = $(titleObject).parent().parent().children(".body");
		$(tab_body).children().hide();
		$(tab_body).children("#" + currentTabIdentifier).show();
	}
	
	function selectTab (titleObject) {
		currentTabIdentifier = selectCurrent(titleObject);
		showTabBody(titleObject, currentTabIdentifier);
	}
	
	// at pageload, show first tab by default
	$(".modulebox.tabs > .title").each( function() {
		var first_tab = $("a:first", this)
    	selectTab(first_tab);
 	});
	
	// bind click event
	$('.modulebox.tabs > .title a').bind( 'click', function(e) {
		// mark this tab as active (current)
		e.preventDefault();
		selectTab(this);
	});
});

/* select_all checkbox */
$(document).ready( function() {
	$("input.select_all").each( function () {
		var alternative = false;
		if ($(this).hasClass('alternative_trigger')) {
			var alternative = true;
		}
		$(this).bind( "click", function () {
			var checked_status = this.checked;
			$("input[class=" + $(this).attr('rel') + "]").each(function()
			{
				if ($(this).attr('checked') && !checked_status) {
					if (alternative) {
						$(this).trigger('fire');
					} else {
						$(this).click();
					}
				}
				if (!$(this).attr('checked') && checked_status) {
					if (alternative) {
						$(this).trigger('fire');
					} else {
						$(this).click();
					}
				}
			});
		});
	});
});

/*
STATIC FUNCTIONS
*/
function include_js(script_filename) {
	// includes a java script by appending it to the document's head (DOM element)
    var html_doc = document.getElementsByTagName('head').item(0);
    var js = document.createElement('script');
    js.setAttribute('language', 'javascript');
    js.setAttribute('type', 'text/javascript');
    js.setAttribute('src', script_filename);
    html_doc.appendChild(js);
    return false;
}

function include_css(css_filename) {
	// includes a css link by appending it to the document's head (DOM element)
    var html_doc = document.getElementsByTagName('head').item(0);
    var css = document.createElement('link');
    css.setAttribute('href', css_filename);
    css.setAttribute('rel', 'stylesheet');
    css.setAttribute('type', 'text/css');
    html_doc.appendChild(css);
    return false;
}

function includeSyntaxHighlighter() {
	// includes google-code-prettify for use within the current page/module
	include_js ('framework/js/gcp/prettify.js');  // CAUTION: path is static
	include_css('framework/js/gcp/prettify.css'); // CAUTION: path is static
}

function highlightXML(objectID) {
	// styles one html object with PrettyPrintOne()
	$(objectID).val(prettyPrintOne($(objectID).val()));
}

// AJAX xml reload function
function reloadTextXML(parentObject, textID, showTags, showCompact, showColors, part, prettyPrint) {
	// standard values
	if (part == null) part = 'ALL';
	if (prettyPrint == null) prettyPrint = true;
	
	// compose query
	var query_string = "?action=jq&task=getXMLText&elemID=code";
	query_string += "&textID=" + textID;
	query_string += "&prettyPrint=" + prettyPrint;
	query_string += "&part=" + part;
	query_string += "&tags=" + showTags;
	query_string += "&compact=" + showCompact;
	query_string += "&colors=" + showColors;
	// ajax request
	$.get(query_string, function(result) {
		parentObject.html(result);
		// check if syntax highlighting is activated
		if (showColors && showTags) {
			code_container = parentObject.find("code");
			code_container.html(prettyPrintOne(code_container.html()));
		}
	});		
}

function findTableRowByInputValue (tableID, value) {
	// searches for a td that contains an input element with value=value in a table and returns the jQuer Object Reference to the row containing it.
	return $("table#" + tableID).find('input[value="' + value + '"]').parent().parent();
}

function removePreTag (object) {
	// removes a <pre>-child from an object, moving all pre-children one level upwards.
	// example: <pre><h2>hello!</h2></pre> => <h2>hello!</h2>
	var content = object.children('pre').html()
	object.html(content);
}

function addPreTag (object) {
	// adds <pre>..</pre> around the object html
	var content = object.html();
	object.html('<pre>' + content + '</pre>');
}

function togglePreTag (object) {
	if (object.children('pre').size() > 0) {
		removePreTag(object);
	} else {
		addPreTag(object);
	}
}

/*
simple delay function for e.g. keyup events
*/
var delay = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();

/*
jQuery UI extended
*/

(function($){
    $.widget( "ui.combobox", $.ui.autocomplete, 
        {
        options: { 
            /* override default values here */
            minLength: 2,
            /* the argument to pass to ajax to get the complete list */
            ajaxGetAll: {get: "all"}
        },

        _create: function(){
            if (this.element.is("SELECT")){
                this._selectInit();
                return;
            }

            $.ui.autocomplete.prototype._create.call(this);
            var input = this.element;
            input.addClass( "ui-widget ui-widget-content ui-corner-left" );

            this.button = $( "<button type='button'>&nbsp;</button>" )
            .attr( "tabIndex", -1 )
            .attr( "title", "Show All Items" )
            .insertAfter( input )
            .button({
                icons: { primary: "ui-icon-triangle-1-s" },
                text: false
            })
            .removeClass( "ui-corner-all" )
            .addClass( "ui-corner-right ui-button-icon" )
            .click(function(event) {
                // close if already visible
                if ( input.combobox( "widget" ).is( ":visible" ) ) {
                    input.combobox( "close" );
                    return;
                }
                // when user clicks the show all button, we display the cached full menu
                var data = input.data("combobox");
                clearTimeout( data.closing );
                if (!input.isFullMenu){
                    data._swapMenu();
                    input.isFullMenu = true;
                }
                /* input/select that are initially hidden (display=none, i.e. second level menus), 
                   will not have position cordinates until they are visible. */
                input.combobox( "widget" ).css( "display", "block" )
                .position($.extend({ of: input },
                    data.options.position
                    ));
                input.focus();
                data._trigger( "open" );
            });

            /* to better handle large lists, put in a queue and process sequentially */
            $(document).queue(function(){
                var data = input.data("combobox");
                if ($.isArray(data.options.source)){ 
                    $.ui.combobox.prototype._renderFullMenu.call(data, data.options.source);
                }else if (typeof data.options.source === "string") {
                    $.getJSON(data.options.source, data.options.ajaxGetAll , function(source){
                        $.ui.combobox.prototype._renderFullMenu.call(data, source);
                    });
                }else {
                    $.ui.combobox.prototype._renderFullMenu.call(data, data.source());
                }
            });
        },

        /* initialize the full list of items, this menu will be reused whenever the user clicks the show all button */
        _renderFullMenu: function(source){
            var self = this,
                input = this.element,
                ul = input.data( "combobox" ).menu.element,
                lis = [];
            source = this._normalize(source); 
            input.data( "combobox" ).menuAll = input.data( "combobox" ).menu.element.clone(true).appendTo("body");
            for(var i=0; i<source.length; i++){
                lis[i] = "<li class=\"ui-menu-item\" role=\"menuitem\"><a class=\"ui-corner-all\" tabindex=\"-1\">"+source[i].label+"</a></li>";
            }
            ul.append(lis.join(""));
            this._resizeMenu();
            // setup the rest of the data, and event stuff
            setTimeout(function(){
                self._setupMenuItem.call(self, ul.children("li"), source );
            }, 0);
            input.isFullMenu = true;
        },

        /* incrementally setup the menu items, so the browser can remains responsive when processing thousands of items */
        _setupMenuItem: function( items, source ){
            var self = this,
                itemsChunk = items.splice(0, 500),
                sourceChunk = source.splice(0, 500);
            for(var i=0; i<itemsChunk.length; i++){
                $(itemsChunk[i])
                .data( "item.autocomplete", sourceChunk[i])
                .mouseenter(function( event ) {
                    self.menu.activate( event, $(this));
                })
                .mouseleave(function() {
                    self.menu.deactivate();
                });
            }
            if (items.length > 0){
                setTimeout(function(){
                    self._setupMenuItem.call(self, items, source );
                }, 0);
            }else { // renderFullMenu for the next combobox.
                $(document).dequeue();
            }
        },

        /* overwrite. make the matching string bold */
        _renderItem: function( ul, item ) {
            var label = item.label.replace( new RegExp(
                "(?![^&;]+;)(?!<[^<>]*)(" + $.ui.autocomplete.escapeRegex(this.term) + 
                ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>" );
            return $( "<li></li>" )
                .data( "item.autocomplete", item )
                .append( "<a>" + label + "</a>" )
                .appendTo( ul );
        },

        /* overwrite. to cleanup additional stuff that was added */
        destroy: function() {
            if (this.element.is("SELECT")){
                this.input.remove();
                this.element.removeData().show();
                return;
            }
            // super()
            $.ui.autocomplete.prototype.destroy.call(this);
            // clean up new stuff
            this.element.removeClass( "ui-widget ui-widget-content ui-corner-left" );
            this.button.remove();
        },

        /* overwrite. to swap out and preserve the full menu */ 
        search: function( value, event){
            var input = this.element;
            if (input.isFullMenu){
                this._swapMenu();
                input.isFullMenu = false;
            }
            // super()
            $.ui.autocomplete.prototype.search.call(this, value, event);
        },

        _change: function( event ){
            abc = this;
            if ( !this.selectedItem ) {
                var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( this.element.val() ) + "$", "i" ),
                    match = $.grep( this.options.source, function(value) {
                        return matcher.test( value.label );
                    });
                if (match.length){
                    match[0].option.selected = true;
                }else {
                    // remove invalid value, as it didn't match anything
                    this.element.val( "" );
                    if (this.options.selectElement) {
                        this.options.selectElement.val( "" );
                    }
                }
            }                
            // super()
            $.ui.autocomplete.prototype._change.call(this, event);
        },

        _swapMenu: function(){
            var input = this.element, 
                data = input.data("combobox"),
                tmp = data.menuAll;
            data.menuAll = data.menu.element.hide();
            data.menu.element = tmp;
        },

        /* build the source array from the options of the select element */
        _selectInit: function(){
            var select = this.element.hide(),
            selected = select.children( ":selected" ),
            value = selected.val() ? selected.text() : "";
            this.options.source = select.children( "option[value!='']" ).map(function() {
                return { label: $.trim(this.text), option: this };
            }).toArray();
            var userSelectCallback = this.options.select;
            var userSelectedCallback = this.options.selected;
            this.options.select = function(event, ui){
                ui.item.option.selected = true;
                if (userSelectCallback) userSelectCallback(event, ui);
                // compatibility with jQuery UI's combobox.
                if (userSelectedCallback) userSelectedCallback(event, ui);
            };
            this.options.selectElement = select;
			// specific input element is always accessible via the id-attribute of the select element plus an '-input' suffix
            this.input = $( "<input>" ).insertAfter( select )
                .attr('id', select.attr('id') + '-input')
				.val( value ).combobox(this.options);
        },
	}
);
})(jQuery);

/* activate all comboboxes */
$(document).ready( function() {
	$('select.combobox').each( function() {
		$(this).combobox();
	});
});

/* toggle_details_container */
$(document).ready( function() {
	$('.toggle_details').each( function () {
		var input = $(this).children('input');
		/* hide unchecked fields by default */
		if (!input.attr('checked')) {
			input.parent().children('span.details').hide();
		}
		/* bind change event: show only span.details of checked input */
		input.change( function() {
			input.parent().parent().find('span.details').hide();
			input.parent().children('span.details').show();
		});
	});
});

/* push notification to status bar */
function pushNotification (type, text) {
	$('#mod_status').children('span').fadeOut();
	var html = '<span class="status' + type + '">' + text + "</span>";	
	$('#mod_status').html(html).children('span').hide().fadeIn('slow');
}

/* refreshes the HTML code of the graph selection combobox in the modulemenu */
function refreshGraphSelectionDropdown() {
	$.get('actions/php/ajax.php?action=getGraphSelectionDropdownHTML', function(new_html) {
		$('#active_grapheme_dropdown').html(new_html);
		$('#active_grapheme_dropdown select').combobox();
	});
}

function restoreForm(id) {
	$('#'+id).each(function(){
	        this.reset();
	});
}

/* validate form input (fields) */
function validateFormFields (html_form_id) {
	var _form = $(html_form_id);
	var valid = true;
	// required fields
	_form.find('.required').each( function() {
		if($(this).val()=='') {
			valid = false;
			$(this).addClass('invalid');
		} else {
			$(this).removeClass('invalid');
		}
	});
	// fields with digits and points only
	_form.find('.digits_and_points_only').each( function() {
		var regex=/(\d+\.)+/g;
		var matches = regex.exec($(this).val());
		if (matches && matches[0] == $(this).val()) {
			// all fine
			$(this).removeClass('invalid');
		} else {
			valid = false;
			$(this).addClass('invalid');
			//alert('Input invalid. Only digits and point characters are allowed in this field.');
		}
	});
	return valid;
}

array_remove_element = function(array, element_to_remove) {
	for(var i = array.length-1; i >= 0; i--) {
    	if (array[i] == element_to_remove) {
        	array.splice(i,1);
    	}
	}
}


/* prototype modulations */

String.prototype.trim = function(substring_to_remove) {
	return this.replace(substring_to_remove,'');
}


/* SCRATCH */
// jquery json request example
/*
	$.get("?action=jq&task=getSessionShowHeader", function(result) {
		alert(result);
	});
*/