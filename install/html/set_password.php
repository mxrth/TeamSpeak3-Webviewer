<div id="password">
    <div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em; margin-bottom: 10px;"> 
        <p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
            <?php __e('Please insert a password for the interface once: ');?></p>
    </div>

    <form class="jqform" action="index.php?action=setpw" method="post">
        <input type="password" name="password" />
        <input type="submit" value="<?php __e('Set password');?>" />
    </form>
</div>