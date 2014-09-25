<div class="modulemenu">
    <div class="mainmodule">
        <a class="moduletitle" href="#">Manage Texts</a>
        <a class="item" href="?action=redirect&module=home">Exit</a>
    </div>
    <div id="jq_current_menuitem_id" class="invisible"><?php echo str_replace('.', '-', $ps->getCurrentModule()); ?></div>
    <a id="chg-new" class="submodule" href="?action=redirect&module=chg.new">Add</a>
    <a id="chg-chg" class="submodule" href="?action=redirect&module=chg.chg">Change</a>
    <a id="chg-chg" class="submodule" href="?action=redirect&module=chg.del">Delete</a>         
</div>