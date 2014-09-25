<div class="modulemenu">
    <div class="mainmodule">
        <a class="moduletitle" href="#">Annotation: Lexicon</a>
        <a class="item" href="?action=redirect&module=ann.gra.gra">Graphematics</a>
        <a class="item" href="?action=redirect&module=home">Exit</a>
    </div>
    <div class="top_lemma_selector">
    	<form id="tls_form" method="post" action="">
			<label class="inline">Active Lemma:</label> <?php echo htmlLemmaSelectionDropdown($ps->getActiveProject(), 'lemma_id', array('modulefield', 'text', 'small', 'combobox'), 'select_lemma'); ?>
            <input type="button" class="button" id="load" value="Load" name="load" />
            <input type="button" class="button" id="new_lemma" value="+" name="new_lemma" />
        </form> 
    </div>
    <div id="jq_current_menuitem_id" class="invisible"><?php echo str_replace('.', '-', $ps->getCurrentModule()); ?></div>
    <a id="ann-lex-lem" class="submodule" href="?action=redirect&module=ann.lex.lem">Lexical Head</a>
    <a id="ann-lex-mrp" class="submodule" href="?action=redirect&module=ann.lex.mrp">Morphology</a>
    <a id="ann-lex-sem" class="submodule" href="?action=redirect&module=ann.lex.sem">Semantics</a>
</div>