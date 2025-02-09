<?php

// Add menu item under Tools
add_action('admin_menu', function() {
    if (current_user_can('administrator')) {
        add_management_page(
            'Workshop Scheduler', 
            'Workshop Scheduler',
            'administrator',
            'workshop-scheduler',
            'render_workshop_scheduler_page'
        );
    }
});

function render_workshop_scheduler_page() {
    // Handle reset request
    if (isset($_POST['reset_config'])) {
        delete_option('cf7_workshop_scheduler_config');
        echo '<div class="updated"><p>Configuration has been reset to defaults.</p></div>';
        // Reload page to show default config
        echo '<script>window.location.reload();</script>';
    } else {
        // Handle form submission
        if (isset($_POST['workshop_config'])) {
            // Validate JSON
            $config = json_decode(stripslashes($_POST['workshop_config']), true);
            if ($config === null) {
                echo '<div class="error"><p>Invalid JSON format</p></div>';
            } else {
                
                update_option('cf7_workshop_scheduler_config', wp_unslash(sanitize_text_field($_POST['workshop_config'])));
                echo '<div class="updated"><p>Configuration saved successfully!</p></div>';
            }
        }
    }

    // Get current config
    $config = get_option('cf7_workshop_scheduler_config');
    if (empty($config)) {
        // Load from JSON file if no config in DB
        $config_file = dirname(__FILE__) . '/config-sample.json';
        $config = file_get_contents($config_file);
    }
    // Pretty print the JSON with indentation
    $config = json_encode(json_decode($config), JSON_PRETTY_PRINT);


    ?>
    <div class="wrap">
        <h1>Workshop Scheduler Configuration</h1>
        <form method="post">
            <textarea name="workshop_config" style="width: 100%; height: 500px; font-family: monospace;"><?php echo esc_textarea($config); ?></textarea>
            <p>
                <input type="submit" class="button button-primary" value="Save Configuration">
                <button type="button" class="button" onclick="downloadConfig()">Backup Configuration</button>
                <script>
                function downloadConfig() {
                    const config = document.querySelector('textarea[name="workshop_config"]').value;
                    const blob = new Blob([config], {type: 'application/json'});
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'workshop-scheduler-config.json';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
                </script>
                <input type="submit" name="reset_config" class="button button-link-delete" value="Reset Configuration" onclick="return confirm('Are you sure you want to reset the configuration? This will remove all custom settings.');">
            </p>
        </form>
    </div>
    <?php
}
