<?php
/* Phoenix2
** Modal Window
==
This is the user interface to formulate searches on occurrences.
*/

# MOCKUP! This is a mockup prototype.
# TODO: convert setters into functions.

?>
<script type="text/javascript">

// ADD EXISTING GRAPHEME
var GraphTab = {

	GraphAssigner : function (occ_selection_box, search_controller) {

		// selectors
		var existing_graph_selector = $('#graph_selector');
		existing_graph_selector.combobox(); // convert to combobox
		var existing_graphgroup_selector = $('#graphgroup_selector');
		// grapheme
		var new_graph_identifier = $('#new_graph_identifier');
		var graph_description = $('#short_description');
		var new_graph_button = $('#new_graph_button');
		var choose_existing_graph_button = $('#choose_existing_graph_button');
		var cancel_existing_graph_button = $('#cancel_existing_graph_button');
		// graphgroup
		var new_graphgroup_name = $('#new_graphgroup_name');
		var new_graphgroup_number = $('#new_graphgroup_number');
		var new_graphgroup_button = $('#new_graphgroup_button');
		var choose_existing_graphgroup_button = $('#choose_existing_graphgroup_button');
		var cancel_existing_graphgroup_button = $('#cancel_existing_graphgroup_button');
		// main buttons (assign & reset)
		var reset_all_button = $('#reset_all_button');
		var assign_button = $('#assign_button');
		var TEST_button = $('#TEST_button');

		// "form elements" to build the components for the final assignment.
		var chosen_graph_name_or_id = null;
		var chosen_descr = null;
		var chosen_graphgroup_number = null;
		var chosen_graphgroup_name = null;
		// TODO: include VARIANTS
		// var chosen_variant = null;

		resetForm();

		// hide the "new" fields
		// TODO: make "reset all function -> use it whenever I want"

		TEST_button.click( function(e) {
			e.preventDefault();
			console.log("chosen_graph_name_or_id:",chosen_graph_name_or_id, "chosen_descr:", chosen_descr, "chosen_graphgroup_number:", chosen_graphgroup_number, "chosen_graphgroup_name:", chosen_graphgroup_name);
		});

		reset_all_button.click( function(e) {
			e.preventDefault();
			resetForm();
		});

		assign_button.click( function(e) {
			e.preventDefault();

			var selected_occurrences = occ_selection_box.getSelected();
			// select to correct field to get the values from for the final assignment

			if ( chosen_graph_name_or_id == null && new_graph_identifier.val() != "" ) {
				chosen_graph_name_or_id = new_graph_identifier.val();
			}

			if ( chosen_descr == null && graph_description.val() != "" ) {
				chosen_descr = graph_description.val();
			}

			if ( chosen_graphgroup_name == null && new_graphgroup_name.val() != "" ) {
				chosen_graphgroup_name = new_graphgroup_name.val();
			}

			if ( chosen_graphgroup_number == null && new_graphgroup_number.val() != "" ) {
				chosen_graphgroup_number = new_graphgroup_number.val();
			}

			console.log("graph_name_id:", chosen_graph_name_or_id, "occ:", selected_occurrences, "descr:", chosen_descr, "gg_name:", chosen_graphgroup_name, "ggnum:", chosen_graphgroup_number);

			if ( selected_occurrences != '[]' ) {
				var countAssignedOccurrences = countExistingGraphAssignments(selected_occurrences);
				if ( countAssignedOccurrences > 0 ) {
					// display warning if at least one occurrence is already assigned to a Grapheme
					if ( confirm( countAssignedOccurrences + " of the selected Occurrences are already assigned to a Grapheme. Press OK to overwrite their assignments with the selected Grapheme.") ) {
						created_graph_id = addOccurrencesToGraph(chosen_graph_name_or_id, selected_occurrences, chosen_descr);
						created_graphgroup_id = addOccurrencesToGraphgroup(chosen_graphgroup_number, chosen_graphgroup_name, selected_occurrences, created_graph_id);
						console.log("cr_gr_id:", created_graph_id, "cr_gg_id:", created_graphgroup_id);
					}
				} else {
					created_graph_id = addOccurrencesToGraph(chosen_graph_name_or_id, selected_occurrences, chosen_descr);
					created_graphgroup_id = addOccurrencesToGraphgroup(chosen_graphgroup_number, chosen_graphgroup_name, selected_occurrences, created_graph_id);
					console.log("cr_gr_id:", created_graph_id, "cr_gg_id:", created_graphgroup_id);
				}
			} else {
				alert('Please select at least one occurrence.');
			}
		});

		choose_existing_graph_button.click( function(e) {
			e.preventDefault();
			console.log(selectionIsValid(existing_graph_selector));
			// TODO: insert validation routine
			new_graph_identifier.attr('disabled', 'disabled');
			graph_description.attr('disabled', 'disabled');
			new_graph_button.attr('disabled', 'disabled');
			chosen_graph_name_or_id = existing_graph_selector.val();
			// update the graphgroup selection according to the chosen graph
			updateGraphgroupSelectionWithID(chosen_graph_name_or_id);
			$('#existing_graphgroup_field').fadeIn();
			$('#approved_existing_graph').fadeIn();
		});

		new_graph_button.click( function(e) {
			e.preventDefault();
			var chosen_graph_name_or_id = null;
			var chosen_descr = null;
			choose_existing_graph_button.attr('disabled', 'disabled');
			$('#new_graph_field').fadeIn();
			$('#new_graphgroup_field').fadeIn();
			$('#approved_new_graph').fadeIn();
		});

		cancel_existing_graph_button.click( function(e) {
			e.preventDefault();
			chosen_graph_name_or_id = null;
			$('#approved_new_graph').fadeOut();
			$('#new_graph_field').fadeOut();
			$('#existing_graphgroup_field').fadeOut();
			$('#new_graphgroup_field').fadeOut();
			$('#approved_existing_graph').fadeOut();
			new_graph_identifier.removeAttr('disabled');
			graph_description.removeAttr('disabled');
			new_graph_button.removeAttr('disabled');
			choose_existing_graph_button.removeAttr('disabled');
		});

		choose_existing_graphgroup_button.click( function(e) {
			e.preventDefault();
			chosen_graphgroup_number = existing_graphgroup_selector.val();
			chosen_graphgroup_name = existing_graphgroup_selector.children('option:selected').text();
			console.log(selectionIsValid(existing_graphgroup_selector));
			console.log("num:", chosen_graphgroup_number, "name:", chosen_graphgroup_name)
			$('#new_graphgroup_field').fadeOut();
			new_graphgroup_name.attr('disabled', 'disabled');
			new_graphgroup_number.attr('disabled', 'disabled');
			new_graphgroup_button.attr('disabled', 'disabled');
			$('#approved_existing_graphgroup').fadeIn();
		});

		new_graphgroup_button.click( function(e) {
			e.preventDefault();
			chosen_graphgroup_number = null;
			chosen_graphgroup_name = null;
			$('#new_graphgroup_field').fadeIn();
			$('#approved_new_graphgroup').fadeIn();
			choose_existing_graphgroup_button.attr('disabled', 'disabled');
			new_graphgroup_button.removeAttr('disabled');
		});

		cancel_existing_graphgroup_button.click( function(e) {
			e.preventDefault();
			$('#new_graphgroup_field').fadeOut();
			$('#approved_new_graphgroup').fadeOut();
			$('#approved_existing_graphgroup').fadeOut();
			chosen_graphgroup_name = null;
			chosen_graphgroup_number = null;
			new_graphgroup_name.removeAttr('disabled');
			new_graphgroup_number.removeAttr('disabled');
			new_graphgroup_button.removeAttr('disabled');
			choose_existing_graphgroup_button.removeAttr('disabled');
		});


		function lemmaExists ( identifier, concept ) {
			var exists = null;
			$.ajax({
				url: 'actions/php/ajax.php?action=lemmaExists&identifier=' + identifier + '&concept=' + concept,
				type: 'GET',
				dataType: 'json',
				success: function(data) {
					exists = data;
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
			return exists;
		}

		function findRelevantGraphgroupsByGraphID ( graph_id ) {
			var graphgroup_ids = null;
			$.ajax({
				url: 'actions/php/ajax.php?action=findRelevantGraphgroupsByGraphID&graphID=' + graph_id,
				type: 'GET',
				dataType: 'json',
				success: function(data) {
					graphgroup_ids = data; // list of all graphgroups with the given graphID
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
			return graphgroup_ids;
		}

		function graphgroupExists ( identifier ) {
			var exists = null;
			$.ajax({
				url: 'actions/php/ajax.php?action=graphgroupExists&identifier=' + identifier,
				type: 'GET',
				dataType: 'json',
				success: function(data) {
					exists = data;
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
			return exists;
		}


		function graphExists ( identifier ) {
			var exists = null;
			$.ajax({
				url: 'actions/php/ajax.php?action=graphExists&identifier=' + identifier,
				type: 'GET',
				dataType: 'json',
				success: function(data) {
					exists = data;
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
			return exists;
		}

		function addOccurrencesToGraphgroup ( graphgroup_number, graphgroup_name, occurrence_ids, graph_identifier ) {
			// identifier is either a graphgroupID or a Number.
			if ( graphgroup_number.endsWith('.') ) {
				console.log("number ends with '.' -- ok");
			}
			var created_id = null;
			$.ajax({
				url: 'actions/php/ajax.php?action=assignOccurrencesToGraphgroup',
				type: 'POST',
				dataType: 'json',
				data: {graphgroupNumber: graphgroup_number, graphgroupName: graphgroup_name, occurrenceIDs: occurrence_ids, graphIdentifier: graph_identifier},
				success: function(data) {
					console.log("graphgroup_number: ", graphgroup_number, "occ_ID: ", occurrence_ids, "graphID", graph_identifier);
					created_id = data;
					pushNotification(1, 'Assignment successful: ' + $.parseJSON(occurrence_ids).length + ' Occurrences assigned to Graphgroup «' + graphgroup_number + '»');
					// occ_selection_box.markSelectedAsLemmatized(lemma_identifier);
					// search_controller.refresh_lemmata();
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: true
			});
			return created_id;
		}

		function addOccurrencesToGraph ( identifier, occurrence_ids, description="undefined") {
			var created_id = null;
			$.ajax({
				url: 'actions/php/ajax.php?action=assignOccurrencesToGraph',
				type: 'POST',
				dataType: 'json',
				data: {graphIdentifier: identifier, occurrenceIDs: occurrence_ids, descr: description},
				success: function(data) {
					graph_identifier = new_graph_identifier.val();
					pushNotification(1, 'Assignment successful: ' + $.parseJSON(occurrence_ids).length + ' Occurrences assigned to Graph «' + graph_identifier + '»');
					created_id = data;
					// search_controller.refresh_lemmata();
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
			return created_id;
		}

		function updateGraphgroupSelectionWithID (graphID ) {
			$.ajax({
        url: 'actions/php/ajax.php?action=updateGraphgroupSelectionWithID&graphID=' + graphID,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          console.log("data:", data);
          existing_graphgroup_selector.html(data);
        },
        error: function(data) {
          alert('error: ' + JSON.stringify(data));
        },
        async: false
      });
		}

		function countExistingGraphAssignments ( occurrence_ids ) {
			var count;
			$.ajax({
				url: 'actions/php/ajax.php?action=countGraphAssignments',
				type: 'POST',
				dataType: 'json',
				data: {occurrenceIDs: occurrence_ids},
				success: function(data) {
					count = data;
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
			return count;
		}

		function checkNameValidity ( table_name, graph_id, name ) {
			var val = false;
			$.ajax({
				url: 'actions/php/ajax.php?action=checkNameValidity',
				type: 'POST',
				dataType: 'json',
				data: {table: table_name, graphID: graph_id, name: name},
				success: function(data) {
					console.log("d:", data);
					val = data;
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				}
			});
			return val;
		}

		function selectionIsValid ( selector ) {
			selector_value =  selector.val();
			selector_text = existing_graph_selector.children('option:selected').text();
			if (selector_value != null && selector_text != null) {
				return true;
			} else {
				return false;
			}
		}

		function resetForm () {
			$('#approved_new_graph').hide();
			$('#approved_new_graphgroup').hide();
			$('#existing_graphgroup_field').hide();
			$('#new_graph_field').hide();
			$('#new_graphgroup_field').hide();
			$('#approved_existing_graph').hide();
			$('#approved_existing_graphgroup').hide();
			chosen_graph_name_or_id = null;
			chosen_descr = null;
			chosen_graphgroup_number = null;
			chosen_graphgroup_name = null;
			new_graphgroup_button.removeAttr('disabled');
			new_graph_button.removeAttr('disabled');
			new_graph_identifier.removeAttr('disabled');
			graph_description.removeAttr('disabled');
			choose_existing_graph_button.removeAttr('disabled');
			new_graph_identifier.val("");
			graph_description.val("");
			new_graphgroup_name.val("");
			new_graphgroup_number.val("");

			//evtl das noch
			// new_graphgroup_name.removeAttr('disabled');
			// new_graphgroup_number.removeAttr('disabled');
			// new_graphgroup_button.removeAttr('disabled');
			// choose_existing_graphgroup_button.removeAttr('disabled');
		}

	}
}

</script>
<div class="h300">
<!-- <div class="min-h100"> -->
    <form id="assign_graph_form" method="post" action="#">

        <div id="columns">

            <div id="left_column" class="w50">
              <div class="inner10">

                <fieldset id="existing_graph_field">

                    <legend class="required">
											Existing Grapheme
											<img id="approved_existing_graph" src="ressources/icons/001_06.png" style="width: 15px; height: 15px;"/>
											<img id="approved_new_graph" src="ressources/icons/001_01.png" style="width: 15px; height: 15px;"/>
										</legend>

                    <?php echo htmlGraphSelectionDropdown($ps->getActiveProject(), 'graph_id', NULL, 'graph_selector'); ?>

                    <!-- <br/><br/> -->
                    <!-- <input type="button" id="ok_button_existing" class="existing_graph_button" value="Assign Existing Grapheme" name="assign" /> -->
										<br/>
										<input type="button" id="choose_existing_graph_button" class="choose_button" value="Choose Existing Grapheme" name="assign" />
										<input type="button" id="new_graph_button" class="new_button" value="New" name="assign" />
										<input type="button" id="cancel_existing_graph_button" class="cancel_button" value="Cancel" name="assign" />
                </fieldset>

              </div>

							<div class="inner10">

                <fieldset id ="existing_graphgroup_field">
                    <legend class="required">
											Existing Graphgroup
											<img id="approved_existing_graphgroup" src="ressources/icons/001_06.png" style="width: 15px; height: 15px;"/>
											<img id="approved_new_graphgroup" src="ressources/icons/001_01.png" style="width: 15px; height: 15px;"/>

										</legend>

                    <?php echo htmlGraphgroupSelectionDropdown($ps->getActiveProject(), selected_graph ,'graphgroup_id', NULL, 'graphgroup_selector'); ?>

                    <br/>
                    <input type="button" id="choose_existing_graphgroup_button" class="choose_button" value="Choose Existing Graphgroup" name="assign" />
										<input type="button" id="new_graphgroup_button" class="new_button" value="New" name="assign" />
										<input type="button" id="cancel_existing_graphgroup_button" class="cancel_button" value="Cancel" name="assign" />
                    <br/><br/>
                    <!-- <input type="button" id="ok_button_existing" class="existing_graph_button" value="Assign Existing Grapheme Group" name="assign" /> -->
                </fieldset>

                <!-- <fieldset>
                    <legend class="required">Existing Variant</legend>

                    <?php //echo htmlGraphgroupSelectionDropdown($ps->getActiveProject(), selected_graph ,'graphgroup_id', NULL, 'graphgroup_id'); ?>

                    <br/><br/>
                </fieldset> -->

              </div>

            </div>

            <div id="right_column" class="w50">
              <div class="inner10">

                <fieldset id ="new_graph_field">

                    <legend>New Grapheme</legend>
                    <input name="new_graph_identifier" id="new_graph_identifier" type="text" class="text small-normal inline" title="New Grapheme (Identifier)"/>
										<br/>
										<label>Short Description:</label>
										<input name="short_description" id="short_description" type="text" class="text w80" title="Short Description"/>
										<br/><br/>
								</fieldset>
								<fieldset id ="new_graphgroup_field">
										<legend>New Graphgroup</legend>
										<label>Number:</label>
										<input name="new_graphgroup_number" id="new_graphgroup_number" type="text" class="text small-normal inline" title="New Graphgroup Number"/>
										<label>Name:</label>
										<input name="new_graphgroup_name" id="new_graphgroup_name" type="text" class="text small-normal inline" title="New Graphgroup Name"/>
										<br/>
										<!-- <legend>New Variant</legend>
										<input name="new_variant_number" id="new_variant_number" type="text" class="text small-normal inline" title="New Graphgroup Variant"/>
										<br /> -->
								</fieldset>

              </div>
							<!-- ASSIGN & RESET buttons -->
							<input type="button" id="assign_button" class="button" value="ASSIGN" name="assign" title="Press button to create the new Assignment" style="display: block; width: 300;" />
							<input type="button" id="reset_all_button" class="button" value="RESET" name="assign" title="Press button to reset the form" style="display: block; width: 300;" />
							<input type="button" id="TEST_button" class="button" value="TEST" name="assign" title="Press button to reset the form" style="display: block; width: 300;" />

            </div>

       </div>

    </form>

</div>
