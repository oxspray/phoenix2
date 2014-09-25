<?php
/* Phoenix2
** Modal Window
==
This is the user interface to formulate searches on occurrences.
*/

// Session

function echoMorphSelector( $XMLTagName )
{
	echo htmlMorphSelectionDropdown($XMLTagName, "morph_$XMLTagName_id", array('to_combobox'), "morph_$XMLTagName");
}

?>
<script type="text/javascript">

$(document).ready( function() {
	
	$(".to_combobox").each( function () {
		$(this).combobox();
	});
	
});

</script>
<div class="h300">
    <p>Please enter morphological information below. Existing morphological annotations will be overwritten.</p>
    <form id="search_occurrences_form" method="post" action="?action=SearchOccurrences&next=<?php echo $_GET['next']; ?>">
            
        <div id="columns">
        
            <div id="left_column" class="w50">
              <div class="inner10">
            
                <fieldset>
                    <legend class="required">Part of Speech</legend>
                    <?php echoMorphSelector('pos'); ?>
                    <br />
                </fieldset>
                
                <input type="button" class="button" value="Assign to selected Occurrences" name="assign" />
                <input type="button" class="button" value="Cancel" name="cancel" />
            
              </div>
            </div>
            
            <div id="right_column" class="w50">
              <div class="inner10">
            
                <fieldset>
                    <legend>Morphology</legend>
                    <table>
                    <tbody>
                    <?php
						// list all morphcategories
						$dao = new Table('MORPHCATEGORY');
						foreach( $dao->get() as $row) {
							if ($row['XMLTagName'] != 'pos') { // except for Part-of-Speech
								echo '<tr><td style="text-align:left; width:100px;">' . $row['Name'] . '</td><td>';
								echoMorphSelector($row['XMLTagName']);
								echo '</td></tr>';
							}
						}
					?>
                    </tbody>
                    </table>
                    
                </fieldset>
              
              </div>
            </div>
            
       </div>
    </form>
</div>