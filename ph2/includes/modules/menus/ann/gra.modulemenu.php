<div class="modulemenu">
    <div class="mainmodule">
        <a class="moduletitle" href="#">Annotation: Graphematics</a>
        <a class="item" href="?action=redirect&module=ann.lex.lem">Lexicon</a>
        <a class="item" href="<?php modal('export_graph_textdistribution_xls'); ?>" rel="facebox">Export Graph/Text-Distribution</a>
        <a class="item" href="?action=redirect&module=home">Exit</a>
    </div>
    <div class="top_lemma_selector">
    	<form id="tls_form" method="post" action="?action=SetActiveGrapheme">
			<label class="inline">Active Grapheme:</label><span id="active_grapheme_dropdown"><?php echo htmlGraphSelectionDropdown($ps->getActiveProject(), 'graph_id', array('modulefield', 'text', 'small', 'combobox'), 'select_graph', $ps->getActiveGrapheme()); ?></span>
            <input type="submit" class="button" id="load" value="Load" name="load" />
            <input type="button" class="button" id="new_grapheme" value="+" name="new_grapheme" />
        </form> 
    </div>
    <div id="jq_current_menuitem_id" class="invisible"><?php echo str_replace('.', '-', $ps->getCurrentModule()); ?></div>
    <a id="ann-gra-gra" class="submodule" href="?action=redirect&module=ann.gra.gra">Grapheme Head</a>
    <a id="ann-gra-grp" class="submodule" href="?action=redirect&module=ann.gra.grp">Variants</a>
</div>