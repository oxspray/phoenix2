<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Search
Module Signature: com.ph2.modules.ann.fnd
Description:
Search texts using various strategies/methods and select a set of search
results (= selected occurrences).
---
/*/
//! MODULE BODY

$session_filter_status = $ps->filterIsActive();


// hide all elements that are not visible for guests if applicable
if ($ps->getNickname() == 'guest') {
	echo '<script type="text/javascript">
	$(document).ready( function() {
	$(".invisible_for_guests").hide();
	});
	</script>';
}

?>
<script type="text/javascript">
	$(document).ready( function() {
		
		var searchController = PH2Controller.Search();
		var matchingOccurrences = PH2Component.OccContextBox('occbox1');
		var typeBrowser = PH2Component.TypeBrowser('typebrowser1', matchingOccurrences, searchController);
		// connect typeBrowser to searchController
		searchController.connect_component(typeBrowser);
		// graph assigner
		var lemmaAssigner = LemmaTab.LemmaAssigner(matchingOccurrences, searchController);
		var graphAssigner = GraphTab.GraphAssigner(matchingOccurrences);
		
		function init () {
			searchController.init();
		}
		
		// turn on or off the filter in the SESSION
		$('input[name=restriction]').click( function() {
			$.getJSON('actions/php/ajax.php?action=setFilterIsActive&active=' + $(this).val(), function() {} )
			.success( function() { init(); });
		});
		
		// bind clear results button
		$('#clear_button').click( function() {
			searchController.clear_results();
			cleanEmptyTokens();
		});

		// bind custom tab for types/lemmata browser
		$('.tablink_custom').click( function() {
			$('.tab_custom').hide();
			var target = '#' + $(this).attr('rel')
			$(target).fadeIn();
		});

		function cleanEmptyTokens () {
			$.ajax({
				url: 'actions/php/ajax.php?action=cleanEmptyTokens',
				type: 'POST',
				dataType: 'json',
				data: {},
				success: function(data) {
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
		}

	});
</script>
<div id="mod_top">
    <?php include PH2_WP_INC . '/modules/menus/ann/asg.modulemenu.php'; ?>
</div>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>
<div id="mod_body">

	<!-- RESTRICTIONS Selection -->
    <div style="padding: 20px 20px 0 20px;">
    <form>
    	<fieldset>
        	<input type="radio" value="false" name="restriction"<?php if( ! $session_filter_status) echo ' checked="checked"'; ?> /> All texts<br />
            <input type="radio" value="true" name="restriction"<?php if( $session_filter_status) echo ' checked="checked"'; ?> /> Selected texts only (see <a href="?action=redirect&module=ann.rst">Restrictions</a>)
        </fieldset>
    </form>
    </div>

	<!-- Occurrence Context Box -->
    <div class="w80">
        <div class="modulebox OccContextBox" id="occbox1">
            <div class="title">Occurrences</div>
            
            <div class="title_extension">
            	<a href="#" class="tablink invisible_for_guests" rel="tab1" id="assign_button-lemma" title="Assign Lemma to Selected Occurrences">+Lemma</a>
                <a href="#" class="tablink invisible_for_guests" rel="tab2" id="assign_button-morph" title="Assign PoS/Morphology to Selected Occurrences">+Morph</a>
                <a href="#" class="tablink invisible_for_guests" rel="tab3" id="assign_button-graph" title="Assign Graph to Selected Occurrences">+Graph</a>
                <a href="#" class="tablink" rel="tab4" id="export_button" title="Export selected Occurrences">Export</a>
                <a href="#" id="clear_button" title="Clear Occurrence Selection">Clear Results</a>
            </div>

            <div class="body">
            	<!-- tabs -->
                <div id="tab1" class="tab hidden">
                	<?php include('includes/components/tabs/assign_lemma.tab.php'); ?>
                </div>
                <div id="tab2" class="tab hidden">
                	<?php include('includes/components/tabs/assign_morph.tab.php'); ?>
                </div>
                <div id="tab3" class="tab hidden">
                	<?php include('includes/components/tabs/assign_graph.tab.php'); ?>
                </div>
                <div id="tab4" class="tab hidden">
                	<?php include('includes/components/tabs/export_search_results.tab.php'); ?>
                </div>
                <!-- end tabs -->
                
                <div id="occ_progress" class="hidden">loading <span id="current"></span>/<span id="total"></span></div>
                <table>
                    <thead>
                      <tr>
                        <td><input type="checkbox" class="select_all" rel="occ_selection" name=""/></td>
                        <th class="widest"><a href="#" class="sort" id="sort_by_text" title="Click to sort results by Cite Form (zitf).">CiteF</a></th>
                        <th class="wider"><a href="#" class="sort" id="sort_by_d0" title="Click to sort results by date &lt;d0&gt;.">Date</a></th>
                        <th class="widest"><a href="#" class="sort" id="sort_by_rd0" title="Click to sort results by editor &lt;rd0&gt;.">Red.</a></th>
                        <th><a href="#" class="tooltipp" title="Involved text division.">Div</a></th>
                        <!--<th class="widest"><a href="#" class="tooltipp" title="Text Section. Hover to display the corresponding description.">Sct</a></th>-->
                        <th class="padded">Context</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            
            	<div id="occ_matches_meta" class="h300">
                	<table>
                    	<!-- occ meta lines -->
                    </table>
                </div>
                
            	<div id="occ_matches" class="scrollbox h300">
                	<!-- occ context lines -->
              	</div>
                
            </div>
        </div>
    </div>
	
    <!-- Type Browser --> 
   	<div class="w20">
    	<div class="modulebox TypeBrowser" id="typebrowser1">
        	<div class="title">Browse</div>
            <div class="title_extension">
            	<a href="#" class="mode_selector" id="mode_selector_lemmata" title="Search Lemmata">Lemmata</a>
                <a href="#" class="mode_selector" id="mode_selector_types" title="Search Types">Types</a>
            	<div class="loading"><img src="ressources/icons/processing_small.gif" alt="loading" /></div>
            </div>
            <div class="body">
            
                <form id="typebrowser_form">
                    <p>Query (REGEX):</p>
                    <input type="text" title="Enter your search query (REGEX)" name="typeSearchBox" id="typebrowser_searchfield" class="text w65" style="float: left !important;" />
                    <input title="Search" type="submit" value="OK" name="typeSearchButton" id="typebrowser_searchbutton" class="button" style="margin-top: -1px;" />
                    <div id="select_type" style="clear:left;"></div>
                </form>
                <br />
                <div class="hidden" style="height:25px;" id="checkall">
                    <input type="checkbox" class="select_all alternative_trigger" rel="type_selection" name="checkall" value="checkall" /> <span style="font-size:120%">select all</span>
                </div>
                <div class="h250 scrollbox" id="typebrowser_results">
                    <ul class="typelist">
                        <!-- matching types -->
                    </ul>
                </div>          
            
            </div>
        </div>
    </div>


</div>