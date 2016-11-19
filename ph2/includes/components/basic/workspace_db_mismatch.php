<?php
    # prints warning if workspace type (live/dev) and db (live/test) mismatch.
    $mismatch = workspaceDbMismatch(); if($mismatch !== ''){ ?>
    <h1 align="center" style="font-size: x-large; color: crimson">
        <?php echo "$mismatch";?>
    </h1>
<?php }?>