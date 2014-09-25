<?php
/* Phoenix2
** Modal Window
==
This dialogue appears when the "checkout"-button has been pressed for a text or corpus, intending to confirm this user action (or cancel it instead).
*/

if ($_GET['type'] == 'corpus') {
	$type = 'corpus';
	$description = '<h4>Exporting Corpora</h4><p>By exporting a corpus, you can download all texts assigned to it in a single XML file. Individual texts can be edited within this file and will be updated when the corpus is re-imported into Phoenix2. Note that it is not possible to delete or add texts in a corpus file; please use the respecting functions inside Phoenix2 for deleting or adding texts beforehand.</p>';
	$action_url = '?action=CheckoutXMLCorpus&corpus_id=' . $_GET['id'];
} else {
	$type = 'text';
	$description = '<h4>Exporting Corpora</h4><p>By exporting a single text, you can download it in an XML format for external editing. This file can be opened in an XML editor of your choice. Once the desired changes have been applied, the text can be re-imported into Phoenix2 by selecting the respective option in Projects &amp; Corpus management – just click the suitcase symbol next to an exported text to re-import it.</p>';
	$action_url = '?action=CheckoutXMLText&text_id=' . $_GET['id'];
}

?>

<script type="text/javascript">
$(document).ready( function() {
	
	// enable 'more info' link
	$('#learn_more').click( function() {
		$(this).hide();
		$('#more_info').slideDown();
	});
	
	// actions for submit button
	$('#export_button').click( function() {
		//TODO: Show loading icon while corpus is generated (to make the user aware that something is being prepared in the background)
		//$('#loading').show();
	});
	
	// actions for cancel button
	$('#cancel_button').click( function() {
		var checkout_link = $('#checkout-<?php echo $type; ?>-<?php echo $_GET['id']; ?>');
		checkout_link.show();
		checkout_link.toggleClass('invisible');
		checkout_link.parent().find('a.checkin').toggleClass('invisible');
	});
	
});
</script>

<h1>Export <?php echo ucwords($type); ?></h1>
<p>Do you want to export <b><?php echo $_GET['name']; ?></b> for external editing?</p>
<p id="learn_more"><a href="#" title="Click to learn more about editing texts outside of Phoenix2">Learn more...</a></p>
<div id="more_info" class="hidden">
	<br />
	<?php echo $description; ?>
    <br />
    <h4>Validation</h4>
    <p>When re-importing edited texts, Phoenix2 will check whether your externally edited file complies with the Phoenix2 XML Schema Definition (XSD) in order to prevent the usage of unknown tags, etc. You may want to validate your text or corpus in an XML editor prior to the re-import. Please use the XSD available at <a href="http://www.rose.uzh.ch/phoenix/schema/edit.xsd" target="_blank">http://www.rose.uzh.ch/phoenix/schema/edit.xsd</a>.
</div>
<br />
<br />
<form name="form_export" method="post" action="<?php echo $action_url; ?>">
	<input type="button" class="button" value="Cancel" name="cancel" onclick="parent.$.fancybox.close()" id="cancel_button" />
    <input type="submit" class="button" value="Export" name="export" onclick="parent.$.fancybox.close()" id="export_button" />
</form>