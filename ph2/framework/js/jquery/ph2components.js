/*
Phoenix2
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
This is the javascript framework providing the PH2Component functionality. All Components are bound to the Namespace PH2Component.
===
Usage: 
$(document).ready( function() {
		var matchingOccurrences = PH2Component.OccContextBox('the_html_object_id');
});
*/

//DEFAULT ROUTINE for all PH2Components
$(document).ready(function() {
	$('.modulebox').each( function() {
		PH2Component._Tabber(this);
	});
});

// general functions
/* replaces json 'null' values by 0 */
function escapeNull(string) {
	if (string == null) {
		return '0';
	} else {
		return string;
	}
}

var PH2Component = {
	
	//Internal Object (pseudo-private)
	_Tabber : function(ModuleboxRef) {
	/* Adds tabbing functionality to all HTMLObjects of class .modulebox
	** The title_extension section of a modulebox may contain links with rel=(tab_id) and class .tablink
	** These Links toggle .class div boxes in the modulebox body-section
	*/
		var _container = $(ModuleboxRef);
		var _title = _container.children('.title');
		var _std_title_text = _title.html();
		var _all_tabs = _container.find('.tab');
		
		// hide all tabs, except for except_id
		var _hideAllTabs = function (except_id) {
			_all_tabs.each( function() {
				if ($(this).attr('id') != except_id) { 
					$(this).slideUp();
				}
			});
		}
		
		// bind tabs to buttons (extended title)
		_container.find('a.tablink').each( function() {
			$(this).bind('click', function() {
				// only perform action if tabber-button is not disabled
				if ( ! $(this).hasClass('disabled') ) {
					// hide all other tabs
					_hideAllTabs($(this).attr('rel'));
					$("#" + $(this).attr('rel')).slideToggle();
					// set title
					if (_title.html() == $(this).attr('title')) {
						// restore default title
						_title.html(_std_title_text);
					} else {
						_title.html($(this).attr('title'));
					}
				}
			});
		});
	},
	
	//Object
	// ****************
	// OccContextBox
	// ****************
	OccContextBox : function(HTMLObjectID) {
	/* Adds functionality to an OccContextBox HTML skeleton. */
		
		//PRIVATE
		//Object references
		var container = $('#' + HTMLObjectID);
		var occ_matches_meta = container.find('div#occ_matches_meta table');
		var occ_matches = container.find('div#occ_matches');
		var selected_occurrences_identifier = 'input.occ_selection:checked:visible';
		var occ_progress = container.find('div#occ_progress');
		var occ_progress_current = occ_progress.children('#current');
		var occ_progress_total = occ_progress.children('#total');
		var _sort_by_text_button = container.find('#sort_by_text');
		var _sort_by_d0_button = container.find('#sort_by_d0');
		var _sort_by_rd0_button = container.find('#sort_by_rd0');
		var _export_csv_button = container.find('#export_csv');
		var _export_xls_button = container.find('#export_xls');
		var _number_of_context_chars = 221;
		var _context_placeholder = '';
		var _displayed_occurrences = []; //the OccurrenceID of each line that is displayed
		var _occ_matches_meta_empty_container = occ_matches_meta.clone();
		var _occ_matches_empty_container = occ_matches.clone();
		var _show_lemmatized_occurrences = true;
		var _view_text_url;
		var _view_text_url_target;
		var _view_text_rel;
		
		//Private methods
		// toggle selected/not selected for a matching occurrence line
		var toggleOccSelection = function (id) {
			// @param check (bool): whether to set the corresponding checkbox to 'checked'
			// select span
			var span = $("span.match#" + id);
			var checkbox = $("#checkbox-" + id);
			if (span.hasClass('selected')) {
				if (checkbox.attr('checked')) checkbox.attr('checked', false);
				span.removeClass('selected');
			} else {
				if (!checkbox.attr('checked')) checkbox.attr('checked', true);
				span.addClass('selected');
			}
		}
		// add an entry to the meta section
		var addMetaLine = function(occurrenceID, txtZitf, d0, rd0, divID, pending) {
			var status = ''
			if (pending == true) {
				status = ' class="pending"';
			}
			if (rd0.length > 7) {
				rd0Short = rd0.substring(0,5) + '..';
				rd0Long = rd0;
			} else {
				rd0Long = rd0;
				rd0Short = rd0;
			}
			occ_matches_meta.append('<tr' + status + '> <td><input type="checkbox" class="occ_selection" name="selected_occ[]" id="checkbox-' + occurrenceID + '" /></td> <td class="txtZitf widest"><a href="#">' + txtZitf + '</a></td> <td class="d0 wider"><a class="tooltipp" href="#">' + d0 + '</a></td> <td class="rd0 widest"> <a class="tooltipp" href="#" title="' + rd0Long + '">' + rd0Short + '</a></td> <td class="divID">' + divID + '</td> </tr>');
		}
		// add an occurrence-context-line
		var addOccLine = function(occurrenceID, occurrenceSurface, leftContext, rightContext, pending) {
			var status = ''
			if (pending == true) {
				status = ' pending';
			}
			occ_matches.append('<pre class="occ_line' + status + '"><span class="leftContext">' + leftContext + '</span><span id="' + occurrenceID + '" class="match">' + occurrenceSurface + '</span> <span class="rightContext">' + rightContext + '</span> </pre>');
		}
		
		// add an occurrence to the resultset with a pending context (to be loaded; status=pending)
		var addPendingOccurrence = function (occurrence_id) {
			// add the meta line
			addMetaLine(occurrence_id, '...', '...', '...', '...', '...', true);
			// add the occurrence line
			addOccLine(occurrence_id, '(loading)', _context_placeholder, _context_placeholder, true);
		}
		
		// load all occurrence contexts and details
		var _load_pending_occurrences = function () {
			occ_matches.find('pre.pending').each( function() {
				_load_occurrence(this);
			});
		}
		
		// load occurrences in current viewport
		var _load_pending_visible_occurrences = function() {
			occ_matches.trigger('scrollstop');
		}
		
		// load a pending occurrence
		var _load_occurrence = function (occ_line) {
			var current_context_line = $(occ_line);
				var occurrence_id = current_context_line.children('span.match').attr('id');
				$.getJSON('actions/php/ajax.php?action=getOccurrenceContextAJAX&id=' + occurrence_id, function(data) {
					var meta = data.meta[0];
					var match = data.match[0];
					// update context
					current_context_line.children('span.match').html(match.surface);
					current_context_line.children('span.leftContext').html(match.leftContext);
					current_context_line.children('span.rightContext').html(match.rightContext);
					current_context_line.removeClass('pending');
					// update meta information
					var current_meta_line = occ_matches_meta.find('input#checkbox-' + occurrence_id).parent().parent();
					current_meta_line.children('td.corpusID').html(meta.corpusID);
					current_meta_line.find('td.txtZitf a')
						.html(meta.zitfShort)
						.attr('href', _view_text_url + meta.textID + '&occ_order_number=' + meta.order)
						.attr('title',"Click to view " + meta.zitfFull)
						.attr('rel', _view_text_rel)
						.attr('target', _view_text_url_target)
					if (_view_text_rel == 'facebox') {
						current_meta_line.find('td.txtZitf a').fancybox( { 'titleShow':false, 'showNavArrows':false } );
					}
					current_meta_line.find('td.rd0 a').attr('title', meta.rd0Full).html(meta.rd0Short);
					current_meta_line.children('td.divID').html(meta.divID);
					current_meta_line.children('td.d0').html(meta.d0);
					current_meta_line.children('td.rd0').html(meta.rd0);
					// options for lemmatized forms
					if (meta.lemma != null) {
						// mark occurrence as lemmatized if applicable
						_mark_as_lemmatized(occurrence_id, meta.lemma);
						if (_show_lemmatized_occurrences == false && meta.lemma != null) {
							// hide occurrence if it is assigned to a lemma and lemmatized forms are currently excluded from the search results
							current_context_line.hide();
							current_meta_line.hide();
						}
					}
				});
		}
		
		// mark an occurrence as lemmatized
		var _mark_as_lemmatized = function (occurrence_id, lemma) {
			// select relevant HTML containers
			var occurrence_match = occ_matches.find('span.match#' + occurrence_id);
			// add lemmatized class
			occ_matches_meta.find('input#checkbox-' + occurrence_id).parent().parent().addClass('lemmatized');
			occurrence_match.parent().addClass('lemmatized');
			occurrence_match.addClass('lemmatized');
			occurrence_match.attr('title', lemma + ' (Lemma)');
		}
		
		
		// sorts all displayed Lines according to ajax.php?sortOccurrencesByText
		var _sort_results = function ( field ) {
			var ordered_occ_ids = null;
			$.ajax({
				url: 'actions/php/ajax.php?action=sortOccurrences',
				type: 'POST',
				dataType: 'json',
				data: {field: field, occurrenceIDs: _displayed_occurrences},
				success: function(data) {
					ordered_occ_ids = data;
					pushNotification(1, 'Occurrences sorted by: ' + field );
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
			// create new (empty) containers
			//alert(occ_matches_meta.html());
			//alert(''+_occ_matches_meta_empty_container.html());
			var new_matches_container = _occ_matches_empty_container.clone();
			var new_meta_container = _occ_matches_meta_empty_container.clone();
			//alert(''+new_meta_container.html());
			// fill in old data ordered according to retreived list
			for (var i in ordered_occ_ids) {
				var id = ordered_occ_ids[i];
				var occ_matches_meta_checkbox = occ_matches_meta.find('input#checkbox-' + id)
				// find in current listing
				var current_meta_line = occ_matches_meta_checkbox.parent().parent();
				var current_context_line = occ_matches.find('pre span.match#' + id).parent();
				// add to new listing
				new_meta_container.append(current_meta_line.clone());
				new_matches_container.append(current_context_line.clone());
				// check meta checkbox if applicable
				if (occ_matches_meta_checkbox.attr('checked')) {
					new_meta_container.find('input#checkbox-' + id).attr('checked', true);
				}
			}
			// replace the old with the new listing
			occ_matches_meta.hide().html(new_meta_container.html()).fadeIn();
			occ_matches.hide().html(new_matches_container.html()).fadeIn();
			// re-bind fancybox text-popups
			occ_matches_meta.find('td.txtZitf a').each( function() {
				$(this).fancybox( { 'titleShow':false, 'showNavArrows':false } );
			});
			// re-bind checkbox crosslinks
			for (var i in ordered_occ_ids) {
				var id = ordered_occ_ids[i];
				bindCheckboxToOccSpan(id);
			}
			// replace _displayed_occurrences with the current order
			_displayed_occurrences = ordered_occ_ids;
		// make sure contexts are loaded for matches that are still pending
			_load_pending_visible_occurrences();
		}
		
		// takes an ID of an Occurrence stored in the database and shows it among its content
		var _add = function (occurrence_id) {
			_displayed_occurrences.push(occurrence_id+''); //conversion to string for php/mysql handling
			if (!surface) {
				var surface = ''; //#TODO: implement ajax request to get surface from database if not provided
			}
			addPendingOccurrence(occurrence_id);
			bindCheckboxToOccSpan(occurrence_id);
			//centerView();
			/*$.ajax({
				url: 'actions/php/ajax.php?action=getOccurrenceContextAJAX&id=' + occurrence_id,
				type: 'GET',
				async: false,
				dataType: 'json',
				cache: true,
				success: function(data) {
					var meta = data.meta[0];
					var match = data.match[0];
				}
			});*/
		}
		
		var centerView = function () {
			// center horizontal 
			occ_matches.scrollTo('50%', {axis:'x'});
		}
		
		var bindCheckboxToOccSpan = function (id) {
		// bind checkbox and matching occurrence span on click event
			// checkbox click-event
			$("input#checkbox-" + id).bind( "click", function () {
				var span = $("span.match#" + id);
				if (span.hasClass('selected')) {
					span.removeClass('selected');
				} else {
					span.addClass('selected');
				}
			});
			// span click-event
			$("span.match#" + id).bind( "click", function () {
				toggleOccSelection(id);
			});
		}
		
		var _getSelected = function () {
			// returns a JSON-list of all currently selected occurrences in the box
			var result = [];
			$(selected_occurrences_identifier).each( function(i) {
				result[i] = ($(this).attr('id').trim('checkbox-'));
			});
			return(JSON.stringify(result));
		}
		
		var _removeOccurrence = function (occurrence_id) {
			// remove an occurrence (identified by its ID) from this box
			$("span.match#" + occurrence_id).parent().fadeOut('200ms', function() {$(this).remove()});
			$("#checkbox-" + occurrence_id).parent().parent().fadeOut('200ms', function() {$(this).remove()});
		}
		
		var _removeSelected = function () {
			// removes all selected occurrences from this box
			var selected = _getSelected();
			$.each($.parseJSON(selected), function() {
				_removeOccurrence(this);
			});
		}
		
		var _handleExportButton = function (selfref, e, action) {
			var selected_lines = _getSelected();
			if (selected_lines != '[]') {
				form_data = selected_lines.replace(/["\[\]]/g,'');
				var form = $('<form method="post" id="export" action="?action=' + action + '"><input type="hidden" name="occurrenceIDs" value="' + form_data + '"/><input type="hidden" name="submitted" value="true"/></form>');
				$(document.body).append(form);
				form.submit();
				//$(selfref).attr('href', '?action=' + action + '&occurrenceIDs=' + selected_lines);
			} else {
				alert('Please select at least one match to be exportet');
				e.preventDefault();
			}
		}
		
		var _hide_lemmatized_results = function () {
			occ_matches.find('pre span.lemmatized').each(function() {
				$(this).parent().fadeOut();
			});
			occ_matches_meta.find('tr.lemmatized').each(function() {
				$(this).fadeOut();
			});
		}
		
		var _show_lemmatized_results = function () {
			occ_matches.find('pre span.lemmatized').each(function() {
				$(this).parent().fadeIn();
			});
			occ_matches_meta.find('tr.lemmatized').each(function() {
				$(this).fadeIn();
			});
		}
		
		//Default routine / behaviour
		// center context window
		centerView();
		// bind scroll-behaviour of #occ_matches_meta to #occ_matches
		occ_matches.bind( "scroll", function () {
			$("#occ_matches_meta").scrollTop(occ_matches.scrollTop());
			$("#monitor").html($("#occ_matches_meta").scrollTop())
		});
		for (var i = 0; i < _number_of_context_chars; i++){
			_context_placeholder += ' ';
		}
		// bind sorting functionality
		_sort_by_text_button.click( function() {
			_sort_results( 'citeform' );
		});
		_sort_by_d0_button.click( function() {
			_sort_results( 'd0' );
		});
		_sort_by_rd0_button.click( function() {
			_sort_results( 'rd0' );
		});
		// loads occurrences if they are visible in the container's viewport
		occ_matches.bind('scrollstop', function() {
			window_height = occ_matches.height() + 500; // to load some extra results that are just besides the boarder
			// see if pending elements are in viewport
			occ_matches.find('pre.pending').each( function() {
				elem_position_top = $(this).position().top;
				//alert(window_height + ', ' + window_scrollTop + ', ' + elem_position_top);
				if ( (elem_position_top < window_height ) && (elem_position_top > 0) ) {
					_load_occurrence(this);
				}
			});
		});
		// bind csv export button
		_export_csv_button.click( function(e) {
			_handleExportButton(this, e, 'ExportCSV');
		});
		// bind xls export button
		_export_xls_button.click( function(e) {
			_handleExportButton(this, e, 'ExportXLS');
		});
		// bind the resize event of occ_matches to occ_matches_meta
		occ_matches.resize( function() {
			$("#occ_matches_meta").height( occ_matches.height() );
		});		
		// determine the url for displaying whole texts
		$.get("actions/php/ajax.php?action=isGuest", function(isGuest) {
			if (isGuest == 'true') {
				_view_text_url = 'http://www.rose.uzh.ch/phoenix/workspace/web/charte.php?t=';
				_view_text_url_target = '_blank';
				_view_text_rel = '';
			} else {
				_view_text_url = 'modal.php?modal=view_text&textID=';
				_view_text_url_target = '_self';
				_view_text_rel = 'facebox';
			}
		});
		
		//PUBLIC
		return {
			// takes an ID of an Occurrence stored in the database and shows it among its content
			add : function (occurrence_id) {
				_add(occurrence_id);
			},
			addMultiple : function (json_list_of_occ_ids) {
			// takes an collection of OccurrenceIDs and loads them via this.add()
				// display progress
				if (json_list_of_occ_ids.length > 5) {
					occ_progress_total.html(json_list_of_occ_ids.length);
					// show the progress div
					occ_progress.show();
				}
				$.each(json_list_of_occ_ids, function(i) {
					_add(this);
					occ_progress_current.html(i);
				});
				// load contexts
				_load_pending_visible_occurrences();
				// hide the progress div
				occ_progress.hide();
				centerView();
			},
			clear : function () {
			// empties this OccContextBox, i.e. all displayed occurrences and their contexts are cleared
				container.find('.select_all').removeAttr('checked');
				occ_matches_meta.html('');
				occ_matches.html('');
				centerView();
			},
			getSelected : function () {
				return _getSelected();
			},
			removeSelected : function () {
				_removeSelected();
			},
			removeMultiple : function (json_list_of_occ_ids) {
				$.each(json_list_of_occ_ids, function(i) {
					_removeOccurrence(this);
				});
				setTimeout(function() {
    				_load_pending_visible_occurrences();
				}, 1000)
			},
			hideLemmatizedResults : function () {
				_show_lemmatized_occurrences = false;
				_hide_lemmatized_results();
			},
			showLemmatizedResults : function () {
				_show_lemmatized_occurrences = true;
				_show_lemmatized_results();
			},
			markSelectedAsLemmatized : function (lemma) {
				// changes the color of selected occurrences (adds the lemmatized class)
				selected_occurrence_ids = JSON.parse(_getSelected())
				for (var i = 0; i < selected_occurrence_ids.length; i++) {
					_mark_as_lemmatized(selected_occurrence_ids[i], lemma);
				}
			}
			
		}
	},
	
	//Object
	// ****************
	// TypeBrowser
	// ****************
	TypeBrowser : function(HTMLObjectID, associatedDisplay, associatedController) {
	/* Adds functionality to a TypeBrowser HTML skeleton.
	** @param associatedDisplay: The PH2Component to receive all matching OccurrenceIDs when clicking on a type
	*/
		
		//PRIVATE
		//Object references
		var _container = $('#' + HTMLObjectID);
		var _form = _container.find('#typebrowser_form');
		var _search_field = _container.find('#typebrowser_searchfield');
		var _search_button = _container.find('#typebrowser_searchbutton');
		var _type_selector = _container.find('#select_type');
		var _result_box = _container.find('#typebrowser_results ul');
		var _select_all_box = _container.find('#checkall');
		var _associatedDisplay = associatedDisplay;
		var _associatedController = associatedController;
		var _current_counting_request;
		var _selected_item_ids = new Array();
		var _loading_indicator = _container.find('.loading');
		var _MODE;
		var _mode_selector_types = _container.find('#mode_selector_types');
		var _mode_selector_lemmata = _container.find('#mode_selector_lemmata');
		var _show_lemmatized_occurrences = true; //by default
		
		//Private methods
		// disable search functions (status 0)
		function _disable() {
			$('.mode_selector').hide();
			_loading_indicator.show();
			$(_search_field).attr("disabled", "disabled");
			_result_box.html('').hide();
			_select_all_box.hide();
		}
		
		// enable search functions (status 1)
		function _enable() {
			_set_mode(_MODE);
			$(_search_field).removeAttr("disabled");
			_loading_indicator.fadeOut( 'fast', function() {
				$('.mode_selector').fadeIn();
			});
		}
		
		function _set_mode ( mode ) {
			// store the new mode
			_MODE = mode;
			// clear all search results (this is currently necessary to avoid wrong checked-markings for items (tokenIDs vs. lemmaIDs)
			_associatedController.clear_results();
			_result_box.html('').hide();
			_select_all_box.hide();
			// highlight the active selector button
			$('.mode_selector').removeClass('active');
			if (_MODE == 'TYPE') {
				_mode_selector_types.addClass('active');
			} else if (_MODE == 'LEMMA') {
				_mode_selector_lemmata.addClass('active');
			}
			// reload the type selection
			_load_type_selection();
		}
		
		// load the type selection
		function _load_type_selection () {
			$(_type_selector).html('Include: ');
			if (_MODE == 'TYPE') {
				$(_type_selector).append('&nbsp;'); //cheap hack to align checkboxes in TYPE mode
			}
			var types = _associatedController.getTypes(_MODE);
			var first = ' checked="checked"';
			for (var i in types) {
				var t_id = types[i][0];
				var t_name = types[i][1];
				var t_descr = types[i][2];
				var html_string = '<input type="checkbox" name="type[]" value="' + t_id + '"' + first +'>';
				html_string += '<a href="#" class="tooltipp" title="' + t_descr + '">' + t_name + '</a> ';
				$(_type_selector).append(html_string);
				if (_MODE == 'TYPE') {
					first = ''; //only check first type by default
				}
			}
			// add 'include lemmatized occurrences' checkbox
			if (_MODE == 'TYPE') {
				html_string = '<br />Options: <input type="checkbox" checked="checked" name="include_lemmatized_occ" id="include_lemmatized_occ" value=""><a href="#" class="tooltipp" title="If this option is selected, search results will include occurrences that have already been assigned a lemma.">show lemmatized occ.</a>';
				$(_type_selector).append(html_string);
			}
		}
		
		// load counts for regex type results
		function _load_counts () {
			//stop running request
			if (_current_counting_request) {
				_current_counting_request.abort();
			}
			// load new counts
			var item_ids = [];
			_result_box.find('a.matching_token').each( function() {
				item_ids.push($(this).attr('id').trim('token-'));
			});
			var type;
			var exclude_lemmatized = false;
			if (_MODE == 'TYPE') {
				type = 'token';
				if (_show_lemmatized_occurrences == false) {
					exclude_lemmatized = true;
				}
			} else if (_MODE == 'LEMMA') {
				type = 'lemma';
			}
			_current_counting_request = $.ajax({
					url: 'actions/php/ajax.php?action=getNumberOfOccurrencesByEntityList',
					type: 'POST',
					async: true,
					dataType: 'json',
					data: {'type': type, 'ids': item_ids, 'exclude_lemmatized': exclude_lemmatized},
					cache: true,
					success: function (data) {
						$.each(data, function() {
							_result_box.find('a.matching_token#token-' + this.id).parent().find('.count').html(' (' + this.count + ')');
						});
					},
					error: function (error) {alert(JSON.stringify(error))}
			});
		}
		
		// get an array containing all selected types
		function _get_selected_types () {
			var types = [];
			_container.find("input[name^='type[]']:checked:enabled").each( function() {
				types.push($(this).val());
			});
			return types;
		}
		
		// search types by regex
		function _searchTypes (query) {			
			if (query == '') {
				_result_box.html('');
				_select_all_box.hide();
			} else {
				var results = _associatedController.find( _MODE, query, _get_selected_types() )
				// clear previous results
				_result_box.html('').hide();
				_select_all_box.hide();
				if (results.length > 0) {
					// show checkall option if there are more than two search results
					if (results.length >= 2) {
						_select_all_box.show()
					}
					for (var i in results) {
						var type_id = results[i][0];
						var type_surface = results[i][1];
						// write results into list
						var checked = '';
						//check the box if the token is allready loaded
						if (_selected_item_ids.indexOf(type_id) != -1) {
							checked = 'checked="checked"';
						}
						_result_box.append('<li><input type="checkbox" class="type_selection" id="token-' + type_id +'" ' + checked + '> <a href="#" class="matching_token" id="token-' + type_id + '">' + type_surface + '</a> <span class="count"></span></li>')
						//_bindActionToSearchResult(type_id)
					}
					// load the counts
					_load_counts();
				} else {
					// no matching results found
					var mode;
					if (_MODE == 'TYPE') {
						mode = 'types';
					} else if (_MODE == 'LEMMA') {
						mode = 'lemmata';
					}
					_result_box.append('<li class="bold">No matching ' + mode + ' found.</li>')
				}
				// show results
				_result_box.fadeIn();
			}
		}
		
		function _hide_lemmatized_results () {
			_associatedDisplay.hideLemmatizedResults();
		}
		
		function _show_lemmatized_results () {
			_associatedDisplay.showLemmatizedResults();
		}
		
		// bind loading of occurrences to specific link (= single search result)
		/*function _bindActionToSearchResult (token_id) {
			_container.find('a#token-' + token_id).bind( 'click', function() {
				_toggleCheckbox(token_id, true);
			});
			_container.find('input#token-' + token_id).bind( 'click', function() {
				_toggleCheckbox(token_id, false);
			});
		}*/
		
		$('input.type_selection').live('click', function() {
			var token_id = $(this).attr('id').trim('token-');
			_toggleCheckbox(token_id, false);
		});
		
		$('input.type_selection').live('fire', function() {
			// for external call of reversed click trigger
			var token_id = $(this).attr('id').trim('token-');
			_toggleCheckbox(token_id, true);
		});
		
		$('a.matching_token').live( 'click', function() {
			var token_id = $(this).attr('id').trim('token-');
			_toggleCheckbox(token_id, true);
		});
		
		$('#include_lemmatized_occ').live('click', function() {
			if (_show_lemmatized_occurrences == true) {
				_show_lemmatized_occurrences = false;
				_hide_lemmatized_results();
			} else {
				_show_lemmatized_occurrences = true;
				_show_lemmatized_results();
			}
			$('a.matching_token').parent().each(function() {
				$(this).find('.count').html('');
			});
			_load_counts(); // refresh token counts
		});
		
		_mode_selector_types.bind( 'click', function() {
			_set_mode('TYPE');
		});
		
		_mode_selector_lemmata.bind( 'click', function() {
			_set_mode('LEMMA');
		});
		
		// toggle checkbox event
		function _toggleCheckbox (item_id, reversed) {
			token_elem = _container.find('input#token-' + item_id);
			is_checked = token_elem[0].checked;
			if (reversed==true) {
				if (is_checked==true) {
					is_checked = false;
				} else {
					is_checked = true
				}
			}
			if (is_checked==false) {
				_removeAssociatedOccurrences(item_id);
				array_remove_element(_selected_item_ids, item_id);
				token_elem.prop("checked", false);
			} else {
				_loadAssociatedOccurrences(item_id, false);
				_selected_item_ids.push(item_id);
				token_elem.prop("checked", true);
			}
		}
		
		// load occurrences into associatedDisplay (i.e. OccContextBox)
		function _loadAssociatedOccurrences (item_id, clearExistingItems) {
			var param;
			if (_MODE == 'TYPE') {
				param = 'tokenID';
			} else if (_MODE == 'LEMMA') {
				param = 'lemmaID';
			}
			$.ajax({
					url: 'actions/php/ajax.php?action=getOccurrences&' + param + '=' + item_id,
					type: 'POST',
					async: true,
					dataType: 'json',
					cache: true,
					success: function(list_of_occurrence_ids) {
						if (clearExistingItems) {
								_associatedDisplay.clear();
							}
						// pass the list of OccurrenceIDs to the display
						_associatedDisplay.addMultiple(list_of_occurrence_ids);
					}
			});
		}
		
		// remove occurrences from associatedDisplay
		function _removeAssociatedOccurrences (item_id) {
			var param;
			if (_MODE == 'TYPE') {
				param = 'tokenID';
			} else if (_MODE == 'LEMMA') {
				param = 'lemmaID';
			}
			$.ajax({
					url: 'actions/php/ajax.php?action=getOccurrences&' + param + '=' + item_id,
					type: 'POST',
					async: true,
					dataType: 'json',
					cache: true,
					success: function(list_of_occurrence_ids) {
						// pass the list of OccurrenceIDs to the display
						_associatedDisplay.removeMultiple(list_of_occurrence_ids);
					}
			});
		}
		
		//Default routine / behaviour
		// block submission of pseudo-form (form element)
		_form.submit( function(e) {
			e.preventDefault();
		});
		
		// bind type search box to keypress events
		/*
		_search_field.bind( "keyup", function () {
			delay( function() { _searchTypes(_search_field.val()); }, 500 );
		});
		*/
		
		// bind type search box to "ok"-button (search)
		_search_button.bind( "click", function () {
			_searchTypes(_search_field.val());
		});
		
		// disable component at startup (enabled by ph2ontroller when ready)
		_disable();
		
		// set mode to TYPE at startup
		_MODE = 'TYPE';
		
		//PUBLIC
		return {
			set_status : function (status) {
				if (status==0) {
					// disable search functions
					_disable();
				} else if (status==1) {
					// enable search functions
					_enable();
				}
			},
			
			clear : function () {
				// remove all TokenIDs
				_selected_item_ids = new Array();
				// untick all checkboxes (types)
				_select_all_box.children().removeAttr('checked');
				$('.type_selection').removeAttr('checked');
				// delete Occurrences in context view
				_associatedDisplay.clear();
			}
		}
		
	},
	
	
	// ****************
	// GroupSelectorGraphvariants
	// ****************
	GroupSelectorGraphvariants : function(HTMLObjectID, associatedDisplay, associatedDetailsWindow) {
	/* Adds functionality to a GroupSelectorGraphvariants HTML skeleton.
	** @param associatedDisplay: The PH2Component to receive all matching OccurrenceIDs when clicking on a group
	** @param associatedDetailsWindow: The PH2Component to receive a fieldset-refference (#TODO) when clicking on a group (i.e. a window that shows the group details).
	*/
		
		//PRIVATE
		//Object references
		var _container = $('#' + HTMLObjectID);
		var _display = associatedDisplay;
		var _details_window = associatedDetailsWindow;
		var _tbody = _container.find('table#groups tbody');
		var _add_variant_form = _container.find('#add_variant_form');
		var _delete_variant_form = _container.find('#delete_variant_form');
		var _delete_variant_tab_button = _container.find('#delete_variant_tab_button');
		var _graph_id; // the graphgroup id this GroupSelector is assigned to. Loaded within the _init()-procedure
		var _active_graphvariant_id; // the variant id that is currently active
		
		//Private methods
		// load all groups assigned to the lemma
		var _init = function () {
			// load lemma that is active in the current session
			$.ajax({
				url: 'actions/php/ajax.php?action=getActiveGraphemeID',
				async: false,
				dataType: 'json',
				success: function(session_graph_id) {
					if (session_graph_id == parseInt(session_graph_id,10)) {
						_graph_id = session_graph_id;
						_loadAll();
						_load(reload=false,'all');
					} else {
						return; // break if no LemmaID is invalid or no Lemma is Active in the Session
					}
				}
			});
		}
		// load all variants and counts from the database
		var _load = function( reload, active_variant_id ) {
			// add special row for [all] selection to the table
			_tbody.html('<tr class="last special" id="all"><td></td><td class="clickable">»</td><td class="clickable">all variants</td><td id="all-occ_count" class="clickable">...</td><td id="all-txt_count" class="clickable">...</td><td id="all-crp_count" class="clickable">...</td></tr>');
			// load variants from database
			$.ajax({
				url: 'actions/php/ajax.php?action=getGraphgroupsFromGraphID&graphID=' + _graph_id,
				async: false,
				dataType: 'json',
				success: function(graphvariants) {
					var all_occ_count = 0;
					var bindings = [];
					$.each(graphvariants, function(i, variant) {
						// sum up occurrence count
						all_occ_count += parseInt(escapeNull(variant.CountOcc));
						// add row to table (=variant entry)
						_tbody.html(_tbody.html() + '<tr id="group-' + variant.GraphgroupID + '"><td><a class="icon ok" id="assign-icon-' + variant.GraphgroupID + '" title="Add selected occurrences (above) to this group" href="#"></a></td><td class="clickable">' + variant.Number + '</td><td class="clickable variant_name">' + variant.Name + '</td><td class="clickable">' + escapeNull(variant.CountOcc) + '</td><td class="clickable">...</td><td class="clickable">...</td></tr>');
						// bind row (variant) to select event
						if (reload!=true) {
							_bindVariantToSelectEvent(variant.GraphgroupID);
							_active_graphvariant_id = 'all';
						}
						// mark active variant as selected (CSS)
						_tbody.find('tr').removeClass('selected');
						if (active_variant_id == 'all') {
							_tbody.find('#all').addClass('selected');
						} else {
							_tbody.find('#group-' + active_variant_id).addClass('selected');
						}
					});
					// write total number of occurrences to (all)
					_tbody.find('#all-occ_count').html(all_occ_count);
					// new_variant option
					if (reload=='new_variant') {
						_closeAddVariantTab();
						_bindVariantToSelectEvent(active_variant_id);
						_selectEvent(active_variant_id);
					}
				}
			});
		}
		
		// event handler for onclick on group items
		var _selectEvent = function (groupID) {
			_active_graphvariant_id = groupID;
			// reset occurrences
			_display.clear();
			// reset active css class for selected variant
			_tbody.find('tr').removeClass('selected');
			// redirect to loading function
			if (groupID == 'all') {
				_tbody.find('#all').addClass('selected');
				_loadAll();
			} else {
				_tbody.find('#group-' + groupID).addClass('selected');
				_loadOccurrencesByGraphgroup(groupID);
			}
		}
		
		// loads all Occurrences assigned to the graph
		var _loadAll = function () {
			$.getJSON('actions/php/ajax.php?action=getOccurrenceIDsByGrapheme&graphID=' + _graph_id, function(occurrence_ids) {
				_display.addMultiple(occurrence_ids);
			});
			_details_window.hide();
			_delete_variant_tab_button.addClass('disabled');	
		}
		
		// loads all Occurrences that are assigned to Graphgroup @param graphgroupID
		var _loadOccurrencesByGraphgroup = function (graphgroupID) {
			$.getJSON('actions/php/ajax.php?action=getOccurrenceIDsByGraphgroup&graphgroupID=' + graphgroupID, function(occurrence_ids) {
				_display.addMultiple(occurrence_ids);
			});
			_details_window.show();
			_details_window.load('&graphgroupID=' + graphgroupID);
			// update delete_variant tab
			_delete_variant_tab_button.removeClass('disabled');
			variant_name = $('#group-' + graphgroupID + ' td.variant_name').html();
			$('#active_variant_name').html(variant_name);
			_delete_variant_form.find('#delete_button').val('Delete ' + variant_name);
		}
		
		// assigns all occurrences selected in _display to Graphgroup @param newGraphgroupID
		// current assignment is deleted
		var _reassignOccurrences = function (newGraphgroupID) {
			$.ajax({
				url: 'actions/php/ajax.php?action=assignOccurrencesToGraphgroup',
				type: 'POST',
				dataType: 'json',
				data: {graphgroupID: newGraphgroupID, occurrenceIDs: _display.getSelected()},
				success: function(data) {
					_display.removeSelected();
					_load(reload=true, _active_graphvariant_id); //reload variants/counts
				}
			});
		}
		
		// binds a variant's html row to its select event
		var _bindVariantToSelectEvent = function (graphgroupID) {
			$('#group-' + graphgroupID + ' td.clickable').live( 'click', function() { _selectEvent(graphgroupID); } );
			$('#assign-icon-' + graphgroupID).live( 'click', function() { _reassignOccurrences(graphgroupID); } );
		}
		
		// adds a new variant to the database (add_variant_form)
		var _addVariant = function (new_variant_number, new_variant_name) {
			$.getJSON('actions/php/ajax.php?action=createGraphgroup&graphID=' + _graph_id + '&graphgroupNumber=' + new_variant_number + '&graphgroupVariantName=' + new_variant_name, function(data) {
				if (data=='number_exists') {
					// graphgroup with given number allready exists for this graph; abort
					pushNotification(2, 'The given variant\'s number conflicts with an existing variant. Please provide another number.');
					_add_variant_form.find('input[name=new_Number]').addClass('invalid');
				} else {
					pushNotification(1, 'Graphgroup ' + new_variant_name + ' (' + new_variant_number + ') created successfully.');
					_load(reload='new_variant', data.graphgroupID);
				}
			});
		}
		
		// deletes a variant (delete_variant_form)
		var _deleteVariant = function (graphgroup_id) {
			$.ajax({
				url: 'actions/php/ajax.php?action=deleteGraphgroup&graphID=' + _graph_id + '&graphgroupID=' + graphgroup_id,
				async: false,
				dataType: 'json',
				success: function() {
					pushNotification(1, 'The selected graphgroup has been deleted successfully.');
					_closeDeleteVariantTab();
					_load(reload=true, 'all');
					_selectEvent('all');
				}
			});
		}
		
		// closes the add-variant tab
		var _closeAddVariantTab = function () {
			$('#add_variant_tab_button').trigger('click');
			restoreForm('add_variant_form');
		}
		
		// closes the delete-variant tab
		var _closeDeleteVariantTab = function () {
			$('#delete_variant_tab_button').trigger('click');
		}
		
		//Default routine / behaviou
		// ***
		_init();
		// bind the 'all variants' select event
		_tbody.find('tr#all').live( 'click', function() { _selectEvent('all'); });
		// bind save event of details window to reload event
		$(_details_window.getHTMLObjectID).bind('saved', function() {
			_load(reload=true, _active_graphvariant_id);
		});
		// bind add event for new variant
		_add_variant_form.find('#submit_button').bind('click', function() {
			// validate Fields
			if (validateFormFields(_add_variant_form) == true) {
				// get values for new variant
				new_variant_number = _add_variant_form.find('input[name=new_Number]').val();
				new_variant_name = _add_variant_form.find('input[name=new_Name]').val();
				// create variant
				_addVariant(new_variant_number, new_variant_name);
			} else {
				pushNotification(2, 'Invalid form input. Please check the highlighted fields.');
			}
		});
		// bind cancel button in new_variant_form
		_add_variant_form.find('#cancel_button').bind('click', function() {
			_closeAddVariantTab();
		});
		// bind delete event for delete-tab
		_delete_variant_form.find('#delete_button').bind('click', function() {
			_deleteVariant(_active_graphvariant_id);
		});
		// bind cancel button in delete-tab
		_delete_variant_form.find('#cancel_delete_button').bind('click', function() {
			_closeDeleteVariantTab();
		});
		
		//PUBLIC
		return {
			
			getActiveGraphID : function () {
				return _graph_id;
			},
			
			getActiveGraphgroupID : function () {
				return _active_graphvariant_id;
			},
			
			reload : function () {
				_load(reload=true, _active_graphvariant_id);
			}
		}
				
	},
	
	//Object #INCOMPLETE
	// ****************
	// GroupSelector
	// ****************
	GroupSelectorMorphology : function(HTMLObjectID, associatedDisplay, associatedDetailsWindow) {
	/* Adds functionality to a GroupSelectorMorphology HTML skeleton.
	** @param associatedDisplay: The PH2Component to receive all matching OccurrenceIDs when clicking on a group
	** @param associatedDetailsWindow: The PH2Component to receive a fieldset-refference (#TODO) when clicking on a group (i.e. a window that shows the group details).
	*/
		
		//PRIVATE
		//Object references
		var _container = $('#' + HTMLObjectID);
		var _tbody = _container.find('table#groups tbody');
		var _lemma_id; // the lemma id this GroupSelector is assigned to. Loaded within the _init()-procedure
		
		//Private methods
		// load all groups assigned to the lemma
		var _init = function () {
			// load lemma that is active in the current session
			$.get('?action=jq&task=getSessionActiveLemma', function(activeLemmaID) {
				if (activeLemmaID == parseInt(activeLemmaID,10)) {
					_lemma_id = activeLemmaID;
				} else {
					return; // break if no LemmaID is invalid or no Lemma is Active in the Session
				}
			});
		}
		
		// event handler for onclick on group items
		var _selectEvent = function (groupID) {
			
		}
		
		// loads all Occurrences assigned to the lemma
		var _loadAllOccurrences = function () {
			
		}
		
		// loads all Occurrences that are assigned to the lemma but not to a Morphology Group
		var _loadUnassignedOccurrences = function () {
			
		}
		
		// loads all Occurrences that are assigned to Morphology Group @param MorphGroupID
		var _loadOccurrencesByGroup = function (MorphGroupID) {
			
		}
		
		//Default routine / behaviour
		// #TODO
		_init();
		
		//PUBLIC
		return {
			
		}
				
	},
	
	//Object #INCOMPLETE
	// ****************
	// LexRefBox
	// ****************
	LexRefBox : function(HTMLObjectID) {
	/* Adds the possibility to add n reference fields to a lexical reference box */
		
		//PRIVATE
		//Object references
		var _container = $('#' + HTMLObjectID);
		var _add_button = _container.find('a#add_lexref');
		var _ref_table = _container.find('table#references');
		var _counter = 0;
		var _new_ref_prototype = _ref_table.find('tbody tr:last-child').clone();
		
		//Private methods
		/* Add a new reference field (i.e. table row) to the reference table
		** Copies the last row of the current table and replaces relevant id-numbers (counting only)
		*/
		var _addRefField = function () {
		/* adds a blank reference field to the existing table of references.
		** all fields have an id and class ending with -new-x, where x is a new (temporar) order number
		** current fields must be loaded (php/html) with a -cur suffix (for 'current', i.e. foo-cur-1).
		*/
			// copy current last field (row)
			var new_ref_field = _new_ref_prototype.clone();
			//var old_ref_id = parseInt(new_ref_field.attr('id').trim('ref-'));
			_increaseCounter();
			var new_ref_id = 'new-' + _counter // user new here for later processing
			new_ref_field.attr('id', 'ref-' + new_ref_id);
			new_ref_field.find('input, select').each( function () {
				$(this).attr('id', $(this).attr('id').slice(0, -5) + new_ref_id);
				$(this).attr('name', $(this).attr('name').slice(0, -5) + new_ref_id);
			});
			// insert as new row
			_ref_table.children('tbody').append(new_ref_field);
			// bind delete icon
			_bindDeleteIcon(new_ref_field.find('a.icon.delete'));
		}
		
		var _increaseCounter = function () {
			_counter += 1;
		}
		
		var _bindDeleteIcon = function (objectReference) {
			$(objectReference).click( function () {
				var entry = $(this).parent().parent();
				// mark -new- or -cur- with -del- for later processing
				/* dynamically inserted fields will only have -del signature, whereas existing entries
				** (i.e. already stored in the db) will be signed as -del-x (where x= list ID)
				*/
				entry.find('input, select').each( function () {
					if ($(this).attr('id').indexOf("new") != -1) {
						$(this).attr('id', $(this).attr('id').slice(0, -2));
						$(this).attr('name', $(this).attr('name').slice(0, -2));
					}
					$(this).attr('id', $(this).attr('id').replace(/-cur-|-new/i, '-del-'));
					$(this).attr('name', $(this).attr('name').replace(/-cur-|-new/i, '-del-'));
				});
				entry.fadeOut('fast');
			});
		}
		
		//Default routine / behaviour
		// bind delete buttons
		_container.find('a.icon.delete').each( function() {
			_bindDeleteIcon(this);
		});
		// bind add-botton
		_add_button.click( function() {
			_addRefField();
		});
		
	},
	
	//Object
	// ****************
	// DetailsWindow
	// ****************
	DetailsWindow : function(HTMLObjectID, loadFieldsFunction, saveFieldsFunction, isVisibleOnLoad) {
	/* Adds load/save/restore functionality to a details window html skeleton, consisting of a form 1:n fields */
		
		//PRIVATE
		// Object references
		var _container = $('#' + HTMLObjectID);
		var _HTMLObjectID = HTMLObjectID;
		var _form = _container.find('form.mainform');
		var _saveButton = _container.find('a.save_button');
		var _restoreButton = _container.find('a.restore_button');
		// AJAX connectors
		var _AJAXBasePath = 'actions/php/ajax_forms.php?action=';
		var _loadFieldsFunction = _AJAXBasePath + loadFieldsFunction;
		var _saveFieldsFunction = _AJAXBasePath + saveFieldsFunction;
		// Instance variables
		var _visible = isVisibleOnLoad;
		var _activeIdentifier = null;
		var _hasChanged = false;
		
		
		//Private methods
		/* Add a new reference field (i.e. table row) to the reference table
		** Copies the last row of the current table and replaces relevant id-numbers (counting only)
		*/
		var _init = function () {
			// hide window if isVisibleOnLoad == true
			if (_visible == false) {
				_container.hide();
			}
			// restore button is disabled by default
			_restoreButton.addClass('disabled');
		}
		
		var _resetFields = function () {
			// resets all fields to empty values
			// #TODO: include non-input html objects
			_form.find('input').each( function() {
				$(this).val('');
			});
		}
		
		var _load = function () {
			ajax_url = _loadFieldsFunction + _activeIdentifier;
			// set all field values to ''
			_resetFields();
			// load field values
			$.getJSON(ajax_url, function(field_data) {
				// put loaded values into fields
				// pre: field_data structure like [ {name:field1_name, value:field1_value}, ... ]
				$.each(field_data, function() {
					$( 'input[name=' + this.name + ']' ).val( this.value );
					$( 'textarea[name=' + this.name + ']' ).val( this.value );
					$( 'select[name=' + this.name + ']' ).val( this.value );
				});
			});
			_restoreButton.addClass('disabled');
		}
		
		var _save = function () {
			// before doing anything, validate the fields and abort if some values are incorrect
			_validate_fields();
			// ROUTINE
			ajax_url = _saveFieldsFunction + _activeIdentifier;
			// serialize form data
			var form_data = _form.serialize();
			// submit form data
            $.ajax({
                type: "POST",
                url: ajax_url,
                data: form_data,
				success: function(success_msg) {
					if (!success_msg) {
						success_msg = 'Changes saved successfully.'; //default message
					}
					pushNotification(1, success_msg);
					// trigger saved event notification for external bindings
					_container.trigger('saved');
				},
				error: function() {
					pushNotification(3, 'Error: Data could not be saved.');
				}
            });
		}
		
		var _validate_fields = function() {
			validateFormFields(_form);
		}
		
		//Default routine / behaviour
		_init();
		// bind save button to save function
		_saveButton.bind('click', function(e) {
			e.preventDefault();
			_save();
		});		
		// bind change events of all form inputs to restore button (enabled/disabled)
		_form.bind('change', function() {
			_restoreButton.removeClass('disabled');
		});
		// bind restore button to load event (load current _activeIdentifier)
		_restoreButton.bind('click', function(e) {
			e.preventDefault();
			if(_restoreButton.hasClass('disabled')!=true) {
				_load();
			}
		});
		
		//PUBLIC
		return {
			
			show : function() {
				if (_visible == false) {
					_container.fadeIn();
					_visible = true;
				}
			},
			
			hide : function() {
				if (_visible == true) {
					_container.fadeOut();
					_visible = false;
				}
			},
			
			load : function(identifier) {
				/* The field contents of this component are loaded by calling an AJAX script (_loadFieldsFunction)
				** with a given identifier (@param identifier), e.g., an ID of an entity to be loaded.
				** The loading request is composed as follows: (_AJAXBasePath +) _loadFieldsFunction + identifier
				*/
				if (_activeIdentifier != identifier) {
					_activeIdentifier = identifier;
					_load();
				}
			},
			
			getHTMLObjectID : function() {
				return _HTMLObjectID;
			}
			
		}
	}
	
}