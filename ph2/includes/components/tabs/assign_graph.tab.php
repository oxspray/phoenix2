<?php
/* Phoenix2
** Modal Window
==
This is the user interface to formulate searches on occurrences.
*/

# MOCKUP! This is a mockup prototype.

?>
<script type="text/javascript">

var GraphTab = {
	
	/*#TODO: By now, an occurrence can be assigned to multiple Graphgroups of a Grapheme, which shouldn't be possible in the final release */
	
	GraphAssigner : function (occ_selection_box) {
		
		// define targets
		var graph_selector = $("#select_graph");
		var graphgroup_selector = $("#select_graphgroup");
		var graphDescriptionField = $('#short_description');
		var graphVariantField = $('#variant_name');
		var ok_button = $('#ok_button');
		var cancel_button = $('#cancel_button');
		var occ_selection_box = occ_selection_box
		
		var graph_status = 'existing';
		var graphgroup_status = 'empty';
		
		// focus behaviour
		graphDescriptionField.bind('focusout', function() {
			graphgroup_selector.next().focus();
		});
		graphVariantField.bind('focusout', function() {
			ok_button.focus();
		});
		
		function setGraphDescription( variant_name ) {
			// triggers the field field for the graphgroup variant
			// the field will be updated and disabled
			graph_status = 'existing';
			graphDescriptionField.attr("disabled", "disabled");
			graphDescriptionField.val(variant_name);
			$('#graph_new').hide();
			$('#graph_existing').show();
			graphgroup_selector.next('input').focus();
		}
		
		function clearGraphDescription ( ) {
			// triggers the field field for the graph description
			// the field will be activated and cleared
			graph_status = 'new';
			graphDescriptionField.val('');
			graphDescriptionField.removeAttr("disabled");
			$('#graph_new').show();
			$('#graph_existing').hide();
			graphDescriptionField.focus();
		}
		
		function setGraphgroupVariant( variant_name ) {
			// triggers the field field for the graphgroup variant
			// the field will be updated and disabled
			graphgroup_status = 'existing';
			graphVariantField.attr("disabled", "disabled");
			graphVariantField.val(variant_name);
			ok_button.removeAttr("disabled");
			$('#subgroup_new').hide();
			$('#subgroup_existing').show();
			$('#subgroup_invalidvalue').hide();
			ok_button.focus();
		}
		
		function clearGraphgroupVariant ( ) {
			// triggers the field field for the graphgroup variant
			// the field will be activated and cleared
			graphgroup_status = 'new';
			graphVariantField.val('');
			graphVariantField.removeAttr("disabled");
			ok_button.removeAttr("disabled");
			$('#subgroup_new').show();
			$('#subgroup_existing').hide();
			$('#subgroup_invalidvalue').hide();
			graphVariantField.focus();
		}
		
		function invalidGraphgroupVariant ( ) {
			graphgroup_status = 'invalid';
			graphVariantField.attr("disabled", "disabled");
			graphVariantField.val('');
			ok_button.attr("disabled", "disabled");
			$('#subgroup_new').hide();
			$('#subgroup_existing').hide();
			$('#subgroup_invalidvalue').show();
			graphgroup_selector.next('input').focus();
		}
		
		function resetGraphgroupSelector ( ) {
			// clear selection
			graphgroup_selector.html('');
			graphgroup_selector.next().val('');
			$('input.available_graphgroups').remove();
			//#TODO: Clear previously selected subgroup
		}
		
		$("#select_graph")
			.combobox()
			// get value selection event
			.bind("change", function() {
				// ajax function: load graph-matrix: {"description":descr,"graphgroups":[(id, number, name), ...]}
				$.getJSON('actions/php/ajax.php?action=getGraphDetails&id=' + $("#select_graph").val(), function(graph_details) {
					// use graph_details variable (array)
				})
				.success( function(graph_details) {
					
					resetGraphgroupSelector();
					
					// get graphgroups and update corresponding combobox
					$.each(graph_details.graphgroups, function() {
						var new_graphgroup_item = '<option value="' + this.ID + '">' + this.number + '</option>';
						graphgroup_selector.append(new_graphgroup_item);
						graphgroup_selector.parent().append('<input class="available_graphgroups" type="hidden" name="' + this.ID + '" value="' + this.name + '" />');
					});
					
					// show/hide description
					setGraphDescription(graph_details.description);
					
				});
			})
			.trigger("change")
			.bind("new", function() {
				// new graphgroup
				clearGraphDescription();
				resetGraphgroupSelector();
			});
		
		$("#select_graphgroup")
			.combobox()
			.addClass('ensureDigitsOnly')
			.addClass('ensureEndsWithPoint')
			.bind("change", function() {
				selected_graphgroup_id = $(this).val();
				var variant_name = $('input.available_graphgroups[name="' + selected_graphgroup_id + '"]');
				// existing graphgroup; display variant name and make field unwritable
				setGraphgroupVariant(variant_name.val());
			})
			.bind("new", function() {
				// new graphgroup
				clearGraphgroupVariant();
			})
			.bind("emptyselection", function() {
				graphgroup_status = 'empty';
			})
			.bind("invalidvalue", function() {
				invalidGraphgroupVariant();
			});
		
		cancel_button.bind("click", function() {
			$('#assign_button-graph').trigger('click');
		});
		
		ok_button.bind("click", function() {
			var final_graph_id = 0;
			var final_graphgroup_id = 0;
			var selected_occurrences = occ_selection_box.getSelected();
			
			//fix delay
			if (graph_selector.next().val() != graph_selector.children(':selected').html()) {
				graph_status = 'new';
			}
			
			if (graphgroup_status == 'invalid') {
				alert ('Invalid graphgroup name.');
				return false;
			} else if (graphgroup_selector.next().val() == '') {
				alert('No graphgroup selected');
				return false;
			} else if (selected_occurrences == '[]') {
				alert('Please select at least one Occurrence');
				return false;
			} else {
				// valid form data
				if (graph_status == 'new') {
					//create graph
					var action_url = 'actions/php/ajax.php?action=createGraph';
					action_url += '&graphName=' + graph_selector.next().val();
					action_url += '&graphDescr=' + $('#short_description').val();
					action_url += '&graphgroupNumber=' + graphgroup_selector.next().val();
					action_url += '&graphgroupVariantName=' + $('#variant_name').val();
					$.ajax({
						url: action_url,
						dataType: 'json',
						success: function(data) {
							final_graph_id = data.graphID;
							final_graphgroup_id = data.graphgroupID;
						},
						async: false // #TODO must be fixed in near future: does not allow change while occurrence contexts are loading
					});
				} else {
					final_graph_id = graph_selector.val();
				}
				if (graphgroup_status == 'new' && graph_status == 'existing') {
					//create graphgroup
					var action_url = 'actions/php/ajax.php?action=createGraphgroup';
					action_url += '&graphID=' + final_graph_id;
					action_url += '&graphgroupNumber=' + graphgroup_selector.next().val();
					action_url += '&graphgroupVariantName=' + $('#variant_name').val();
					$.ajax({
						url: action_url,
						dataType: 'json',
						success: function(data) {
							final_graphgroup_id = data.graphgroupID;
						},
						async: false // #TODO must be fixed in near future: does not allow change while occurrence contexts are loading
					});
				} else if (!final_graphgroup_id) {
						final_graphgroup_id = graphgroup_selector.val();
				}
				
				// final assignment
				var action_url = 'actions/php/ajax.php?action=assignOccurrencesToGraphgroup';
				//action_url += '&graphgroupID=' + final_graphgroup_id;
				//action_url += '&occurrenceIDs=' + selected_occurrences;
				$.ajax({
						url: action_url,
						type: 'POST',
						dataType: 'json',
						data: {graphgroupID: final_graphgroup_id, occurrenceIDs: selected_occurrences},
						success: function(data) {
							pushNotification(1, 'Assignment successful: ' + $.parseJSON(selected_occurrences).length + ' Occurrences assigned to Grapheme «' + graph_selector.next().val() + '», Subgroup ' + graphgroup_selector.next().val());
						},
						error: function(data) {
							alert('error: ' + JSON.stringify(data));
						},
						async: true
					});
				
				// also make the new Grapheme active in the current session
				$.get('actions/php/ajax.php?action=setActiveGraphemeID&graphID=' + final_graph_id);
			}
		});
	}
}

</script>
<div style="height:180px;">
    <p>Please select whether to assign a new or existing lemma and/or morphological information to the selected Occurrences:</p>
    <form id="search_occurrences_form" method="post" action="?action=SearchOccurrences&next=<?php echo $_GET['next']; ?>">
            
        <div id="columns">
        
            <div id="left_column" class="w50">
              <div class="inner10">
            
                <fieldset>
                    <legend class="required">Grapheme</legend>
                    <!--<p>Select an existing Grapheme from the list or create a new Entry by entering a name below:</p>-->
                    <?php echo htmlGraphSelectionDropdown($ps->getActiveProject(), 'graph_id', NULL, 'select_graph'); ?>
                    <div class="spacer" style="margin:30px;display:inline;"></div>
                    <span id="graph_existing" class="hidden"></span>
                    <span id="graph_new" class="hidden"><a href="#" class="tooltipp" title="This grapheme does not exist yet and will thus be created upon assignment.">new</a></span>
                    <br />
                    <label>Short Description:</label>
                    <input name="short_description" id="short_description" type="text" class="text w80" title="Short Desctiption"/>
                    <br />
                </fieldset>
                
                <input type="button" id="ok_button" class="button" value="Assign to selected Occurrences" name="assign" />
                <input type="button" id="cancel_button" class="button" value="Cancel" name="cancel" />
            
              </div>
            </div>
            
            <div id="right_column" class="w50">
              <div class="inner10">
            
                <fieldset>
                    <legend>Subgroup</legend>
                    <!--<p>Please select a subgroup of the selected Grapheme. You can create a new subgroup by filling in a new subgroup number. All numbers must be concluded by a point character, e.g. 2.1.</p>-->
                    <select name="select_graphgroup" id="select_graphgroup">
                    </select>
                    <div class="spacer" style="margin:30px;display:inline;"></div>
                    <span id="subgroup_existing" class="hidden"></span>
                    <span id="subgroup_new" class="hidden"><a href="#" class="tooltipp" title="This subgroup does not exist yet and will thus be created upon assignment.">new</a></span>
                    <span id="subgroup_invalidvalue" class="hidden"><a href="#" class="tooltipp" title="Subgroup names can only consist of digits and point characters, e.g. 1.16.2.">invalid</a></span>
                    <br />
                    <label>Variant:</label>
                    <input name="variant_name" id="variant_name" type="text" class="text w25" title="Variant" />
                    
                </fieldset>
              
              </div>
            </div>
            
       </div>
    </form>
</div>