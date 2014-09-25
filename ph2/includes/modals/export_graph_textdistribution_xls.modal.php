<?php
/* Phoenix2
** Modal Window
==
This dialogue adds a new corpus to the currently active project (according to the $ps session) and redirects to a given site.
*/
?>
<h1>Export XLS data for grapheme/text distribution</h1>
<p>Please select the Grapheme groups that should be included:</p>
<form method="post" action="?action=ExportGraphemeTextDistributionXLS&next=<?php echo $_GET['next']; ?>">
    <fieldset>
    	<?php
		$dao = new Table('GRAPH');
		foreach ($dao->get() as $graph) {
			echo('<input type="checkbox" name="graphIDs[]" value="' . $graph['GraphID'] . '"> ' . $graph['Name'] . "<br />\n");
		}
		?>
    </fieldset>
    <input type="submit" class="button" value="Export" name="export" />
</form>