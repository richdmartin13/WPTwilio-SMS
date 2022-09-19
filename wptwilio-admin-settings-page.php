<div style="display: flex; flex-flow: row nowrap; align-items: center; justify-content: center; height: 90vh;">
<div>
<form method="post" action='options.php'>
    <?php
        settings_fields($this->pluginName);
        do_settings_sections('wptwilio-settings-page');
        submit_button();
        ?>
</form>
</div>