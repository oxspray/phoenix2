<?php
/* Phoenix2
** Modal Window
==
This dialogue adds a new corpus to the currently active project (according to the $ps session) and redirects to a given site.
*/

$corpus_ids = (array)$_GET['corpus_id'];
$corpus_word = 'corpus';
$have_word = 'has';
$number_of_corpora = count($corpus_ids);
if ( $number_of_corpora > 1) {
	$corpus_word = 'corpora';
	$have_word = 'have';
}

?>

<script type="text/javascript">
$(document).ready( function() {
	
	$('#confirm').click( function() {
		$('#step1').hide();
		$('#step2').show();
		var corpus_ids = JSON.parse( $('#corpus_ids').html() );
		// for each corpus...
		$.each(corpus_ids, function() {
			var corpus_id = this;
			// update name of corpus that is currently being deleted
			$.ajax({
				url: 'actions/php/ajax.php?action=getCorpusName&id=' + corpus_id,  //server script to process data
				type: 'POST',
				async: false,
				success: function(corpus_name) {
					$('#current_corpus').html(corpus_name);
					// delete corpus
					$.ajax({
						url: 'actions/php/ajax.php?action=deleteCorpus&id=' + corpus_id,  //server script to process data
						type: 'POST',
						async: false,
						success: function() {}
					});
				}
			});		
		});
		$('#step2').hide();
		$('#step3').show();
	});
	
});
</script>

<h1>Delete <?php echo ucwords($corpus_word); ?></h1>
<span class="hidden" id="corpus_ids"><?php echo json_encode($corpus_ids); ?></span>

<div id="step_error_none_selected" <?php if ($number_of_corpora != 0) echo 'class="hidden"'; ?>>

	<p>Please select at least one corpus.</p>
    <br />
    <input type="button" class="button" value="OK" name="cancel" onclick="parent.$.fancybox.close()" />

</div>

<div id="step1"<?php if ($number_of_corpora == 0) echo 'class="hidden"'; ?>>

    <p>Are you sure you want to delete the following <?php echo $corpus_word; ?>?</p>
    <ul class="listing">
    	<?php foreach($corpus_ids as $corpus_id) {
			$corpus = new Corpus( (int)$corpus_id );
			echo '<li class="bold">' . $corpus->getName() . '</li>';
		} ?>
    </ul>
    <br />
    <p>All assigned texts will also be deleted. This action is irreversible.</p>
    <br />
    <br />
    <form method="post" action="">
        <input type="button" id="confirm" class="button" value="Delete <?php echo ucwords($corpus_word); ?>" name="delete_corpus" />
        <input type="button" class="button" value="Cancel" name="cancel" onclick="parent.$.fancybox.close()" />
    </form>

</div>

<div id="step2" class="hidden">

	<table class="w50 midalign">
    <tbody>
        <tr>
            <td style="text-align: center;"><img src="ressources/icons/processing.gif" /></td><td>Deleting <span id="current_corpus" class="bold"></span></td>
        </tr>
    </tbody>
    </table>

</div>

<div id="step3" class="hidden">

	<!-- final confirmation -->
    <table class="w100 midalign">
    <tbody>
        <tr>
            <td style="text-align: center;"><img src="ressources/icons/001_06.png" /></td><td>The selected <?php echo $corpus_word . ' ' . $have_word ; ?> been deleted.<br />Click OK to continue.</td>
        </tr>
    </tbody>
    </table>

	<form method="post" action="">
        <input type="submit" class="button" value="OK" name="end" />
    </form>

</div>

