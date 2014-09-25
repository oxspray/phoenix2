<?php
/* Phoenix2
** Modal Window
==
This is the user interface to formulate searches on occurrences.
*/
?>
<script type="text/javascript">

$(document).ready( function() {
	/*
	occurrence filter component
	*/
	
	// init: filter criteria
	var component = new Object();
	component.container = $("#filter");
	component.step1 = $("#filter_step1");
	component.step2 = $("#filter_step2");
	component.step3 = $("#filter_step3");
	component.step3a = $("#filter_step3a");
	component.step3b = $("#filter_step3b");
	component.step3c = $("#filter_step3c");
	component.select_lemma = $("#select_lemma");
	component.select_lemma.combobox();
	component.select_graph = $("#select_graph");
	component.select_graph.combobox();
	component.select_type = $("#select_type");
	component.select_type.combobox();
	component.add_button = $("#filter_add_button");
	component.cancel_button = $("#filter_cancel_button");
	
	component.reset = function () {
		
		component.active_step = 1;
		component.restriction = null; // 1: has, 0: has not
		component.type = null; // 'LEMMA' or 'GRAPH'
		component.item_id = null;
		
		// hide steps 2 and 3
		component.step1.children()
			.removeClass('selected')
			.removeClass('current')
			.show();
		component.step2.children()
			.removeClass('selected')
			.removeClass('current')
			.show();
		component.step2.hide();
		component.step3.hide();
		component.step3a.hide();
		component.step3b.hide();
		component.step3c.hide();
		
		// reset comboboxes
		component.container.find(".ui-autocomplete-input").val('');
		
		// remove active / current css classes
		component.container.find().removeClass('selected');
		component.container.find().removeClass('current');
	}
	
	// init: filter list
	var filter = new Object();
	filter.list = $("#filter_list");
	
	filter.reset = function () {
		filter.filters = [];
		filter.id_counter = 0;
	}
	
	filter._getId = function () {
		filter.id_counter += 1;
		return filter.id_counter;
	}
	
	filter._filterExists = function (f) {
	// checks whether a filter is allready stored in the instance
		for (var key in filter.filters) {
			var comp = filter.filters[key];
			if (comp.entityID == f.entityID && comp.type == f.type) {
				return true;
			}
		}
		return false;
	}
	
	filter.add = function (restriction, type, id, name) {
		f = new Object();
		f.id = filter._getId();
		f.restriction = restriction;
		f.type = type;
		f.entityID = id;
		f.name = name;
		// store the filter in the instance
		if (filter._filterExists(f)) {
			alert('This filter is allready stored or interferes another filter.');
		} else {
			filter.filters.push(f);
		}
		// update html (display filter list)
		filter.updateHtml();
	}
	
	filter.remove = function (id) {
	// removes a filter from this object if its id matches the submitted id
		var new_filters = [];
		for (var key in filter.filters) {
			var comp = filter.filters[key];
			if (comp.id != id) {
				new_filters.push(comp);
			}
		}
		filter.filters = new_filters;
	}
	
	filter.updateHtml = function () {
		filter.list.html(''); // clear filters at first
		for (var key in filter.filters) {
			var f = filter.filters[key];
			// compose html hidden field for passing of lemma/graph id
			html_hidden = '<input type="hidden" name="';
			html_hidden += (f.restriction) ? 'has_' : 'not_';
			switch (f.type) {
				case 'LEMMA': html_hidden += 'lemma';
					break;
				case 'GRAPH': html_hidden += 'graph';
					break;
				case 'TYPE' : html_hidden += 'type';
					break;
			}
			html_hidden += '[]" value="'
			html_hidden += f.entityID;
			html_hidden += '"/>';
			// compose html <li> element
			html  = '<li class="filter" id="filter-' + f.id + '">';
			// place hidden form field inside li-element, so it is also deleted if a filter is removed later
			html += html_hidden;
			html += '<a href="#" class="delete_filter cross" id="filter-' + f.id + '" title="remove this filter"></a>'; // delete-link
			html += (f.restriction) ? 'has ' : 'has not ';
			switch (f.type) {
				case 'LEMMA': html += 'lemma';
					break;
				case 'GRAPH': html += 'graph';
					break;
				case 'TYPE' : html += 'type';
					break;
			}
			html += ' <span class="name">';
			html += f.name;
			html += '</span>';
			html += '</li>';
			filter.list.append(html);
		}
		// bind delete filter buttons (cross)
		$("#filter_list a.delete_filter").bind( 'click', function () {
			var id = $(this).attr('id').trim('filter-');
			filter.remove(id);
			filter.updateHtml();
		});
	}
	
	
	/*
	** ROUTINE
	*/
	
	component.reset();
	filter.reset();
	
	// step 1
	component.step1.children("a").click( function() {
		if (component.active_step == 1) {
			component.step1.children().hide();
			$(this).show();
			$(this).addClass('selected');
			$(this).addClass('current');
			//store selection
			component.restriction = ($(this).attr('id') == 'filter_restriction_has') ? 1 : 0;
			//proceed to step 2
			component.active_step = 2;
			component.step2.show();
		}
	});
	
	// step 2
	component.step2.children("a").click( function() {
		if (component.active_step == 2) {
			component.step2.children().hide();
			$(this).show();
			$(this).addClass('selected');
			$(this).addClass('current');
			//store selection
			switch ($(this).attr('id')) {
				case 'filter_restriction_lemma': component.type = 'LEMMA';
					break;
				case 'filter_restriction_graph': component.type = 'GRAPH';
					break;
				case 'filter_restriction_type' : component.type = 'TYPE';
					break;
			}
			// alter step 1 css
			component.step1.children().removeClass('current');
			//proceed to step 3
			component.active_step = 3;
			component.step3.show();
			if (component.type == 'LEMMA') component.step3a.show().find(".ui-autocomplete-input").focus();
			else if (component.type == 'GRAPH') component.step3b.show().find(".ui-autocomplete-input").focus();
			else if (component.type == 'TYPE') component.step3c.show().find(".ui-autocomplete-input").focus();
		}
	});
	
	// step 3
	
	// add filter button (submits the filter)
	component.add_button.bind( 'click', function(e) {
		e.preventDefault();
		var span;
		switch (component.type) {
			 case 'LEMMA': span = component.step3a;
			 	break;
			 case 'GRAPH': span = component.step3b;
			 	break;
			 case 'TYPE' : span = component.step3c;
			 	break;
		}
		if (span.find(".ui-autocomplete-input").val() != '') {
			// selected value
			var selected = span.find("option:selected");
			var selected_id = selected.val();
			var selected_name = selected.html();
			// add filter to this component
			filter.add(component.restriction, component.type, selected_id, selected_name);
			// clear textfield (add more filters of same type)
			span.find('.ui-autocomplete-input').val('').focus();
		} else {
			alert("Cannot add empty filter. Please select a Lemma or Graph from the list.");
		}
	});
	
	// cancel button (resets the filter)
	component.cancel_button.bind( 'click', function(e) {
		e.preventDefault();
		component.reset();
	});
	
});

</script>
<h1>Search Occurrences</h1>
<p>Please specify the search criteria below:</p>
<form id="search_occurrences_form" method="post" action="?action=SearchOccurrences&next=<?php echo $_GET['next']; ?>">
        
    <div id="columns" class="modal-w600">
    
        <div id="left_column" class="w50">
          <div class="inner10">
        
        	<fieldset>
                <legend class="required">Query</legend>
                <p>Specify restrictions on the occurrence surface. Regular Expressions (see <a href="http://www.tin.org/bin/man.cgi?section=7&topic=REGEX" target="_blank" title="Manual on Regular Expressions (Linux man page transcript)">Regex.7</a>) are supportet.</p>
                <input name="query" id="query" type="text" class="text w66 bigger" />
                <br />
                <br />
                
                <legend class="required">Involved corpora</legend>
                <p>Select at least one corpus to query:</p>
                <?php htmlCheckboxCorporaSelection( array('corpora'), TRUE ); ?>
                <br />
            </fieldset>
            
            <input type="submit" class="button" value="Search" name="search_occurrences" />
            <input type="submit" class="button" value="Save" name="save_search" />
            <input type="button" class="button" value="Reset" name="reset" />
        
          </div>
        </div>
        
        <div id="right_column" class="w50">
          <div class="inner10">
        
        	<fieldset>
                <legend>Filters</legend>
                
                <div id="filter" class="lemma_graph_filter">
                
                <ul id="filter_list"></ul>
                
                <p>To in- or exclude specific Lemma or Graph assignments, please add a filter:</p>
                
                    <span id="filter_step1">
                        <a href="#" class="filtertext arrow" id="filter_restriction_has" title="restrict search to matches that allready have a specific lemma or graph assignment">has</a>
                        <a href="#" class="filtertext arrow" id="filter_restriction_has_not" title="restrict search to matches that do NOT allready have a specific lemma or graph assignment">has not</a>
                    </span>
                    
                    <span id="filter_step2" class="hidden">
                        <a href="#" class="filtertext" id="filter_restriction_lemma">Lemma</a>
                        <input type="hidden" name="has_lemma[]" id="has_lemma" />
                        
                        <a href="#" class="filtertext" id="filter_restriction_graph">Graph</a>
                        
                        <a href="#" class="filtertext" id="filter_restriction_type">Type</a>
                    </span>
                    
                    <br />
                
                	<span id="filter_step3" class="hidden">
                    
                        <span id="filter_step3a" class="hidden">
                            <?php echo htmlLemmaSelectionDropdown($ps->getActiveProject(), 'lemma_id', NULL, 'select_lemma'); ?>
                        </span>
                        
                        <span id="filter_step3b" class="hidden">
                            <?php echo htmlGraphSelectionDropdown($ps->getActiveProject(), 'graph_id', NULL, 'select_graph'); ?>
                        </span>
                        
                        <span id="filter_step3c" class="hidden">
                            <?php echo htmlTypeSelectionDropdown($ps->getActiveProject(), 'token_id', NULL, 'select_type'); ?>
                        </span>
                        
                    <br />
                    <input type="submit" class="button" value="Add filter" name="add_filter" id="filter_add_button" />
                    <input type="button" class="button" value="Cancel" name="cancel_filter" id="filter_cancel_button" />
                    
                    </span>
                
                </div>
            
            </fieldset>
          
          </div>
        </div>
        
   </div>
        
    </fieldset>
</form>