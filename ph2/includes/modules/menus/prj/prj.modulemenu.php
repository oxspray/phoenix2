<div class="modulemenu">
    <div class="mainmodule">
        <a class="moduletitle" href="#">Project &amp; Corpus Management</a>
        <a class="item" href="<?php modal('add_project'); ?>" rel="facebox">Create Project</a>
        <a class="item" href="<?php modal('add_corpus'); ?>" rel="facebox">Create Corpus in Current Project</a>
        <a class="item" href="<?php modal('import'); ?>" rel="facebox">Import Text or Corpus (XML)</a>
        <a class="item" href="?action=redirect&module=home">Exit</a>
    </div>
    <div id="jq_current_menuitem_id" class="invisible"><?php echo str_replace('.', '-', $ps->getCurrentModule()); ?></div>
    <a id="prj-crp" class="submodule" href="?action=redirect&module=prj.crp">Texts &amp; Corpora</a>
    <!--<a id="prj-dyc" class="submodule" href="?action=redirect&module=prj.dyc">Dynamic Corpora</a>-->
    <a id="prj-exp" class="submodule" href="?action=redirect&module=prj.exp">Exports</a>
    <a id="prj-prj" class="submodule" href="?action=redirect&module=prj.prj">Projects</a>        
</div>