<div class="modulemenu">
    <div class="mainmodule">
        <a class="moduletitle" href="#">System &amp; User Management</a>
        <a class="item" href="<?php modal('add_user'); ?>" rel="facebox">Create User</a>
        <a class="item" href="?action=redirect&module=home">Exit</a>
    </div>
    <div id="jq_current_menuitem_id" class="invisible"><?php echo str_replace('.', '-', $ps->getCurrentModule()); ?></div>
    <a id="sys-prf" class="submodule" href="?action=redirect&module=sys.prf">System Settings</a>
    <a id="sys-usr" class="submodule" href="?action=redirect&module=sys.usr">User Management</a>        
</div>