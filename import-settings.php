<?php
/*
Plugin Name: WP Settings Importer with Elementor Support
Description: Import theme settings, custom CSS, menus, widget data, and Elementor global settings from a JSON file.
Version: 1.0
Author: Your Name
*/

// Add a new menu item under Tools
function wpsi_add_admin_menu() {
    add_submenu_page('tools.php', 'Import Settings', 'Import Settings', 'manage_options', 'wp-settings-importer', 'wpsi_settings_page');
}
add_action('admin_menu', 'wpsi_add_admin_menu');

// The settings page content
function wpsi_settings_page() {
    ?>
    <div class="wrap">
        <h1>Import Settings</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="settings_file" required>
            <input type="hidden" name="wpsi_action" value="import_settings">
            <input type="submit" class="button button-primary" value="Import Settings">
        </form>
    </div>
    <?php
    if (isset($_POST['wpsi_action']) && $_POST['wpsi_action'] === 'import_settings' && !empty($_FILES['settings_file']['tmp_name'])) {
        wpsi_handle_import($_FILES['settings_file']['tmp_name']);
    }
}

// Handle the import logic
function wpsi_handle_import($file_path) {
    $json_data = file_get_contents($file_path);
    $settings = json_decode($json_data, true);

    if ($settings) {
        // Import theme mods, excluding handled separately
        foreach ($settings as $mod => $value) {
            if (!in_array($mod, ['custom_css', 'nav_menus', 'widgets', 'elementor_settings'])) {
                set_theme_mod($mod, $value);
            }
        }

        // Import custom CSS
        if (isset($settings['custom_css'])) {
            wp_update_custom_css_post($settings['custom_css']);
        }

        // Import menu locations
        if (isset($settings['nav_menus'])) {
            $locations = get_theme_mod('nav_menu_locations');
            foreach ($settings['nav_menus'] as $location => $menu_name) {
                $menu = wp_get_nav_menu_object($menu_name);
                if ($menu) {
                    $locations[$location] = $menu->term_id;
                }
            }
            set_theme_mod('nav_menu_locations', $locations);
        }

        // Import widgets
        if (isset($settings['widgets'])) {
            update_option('sidebars_widgets', $settings['widgets']);
        }

        // Import Elementor settings
        if (isset($settings['elementor_settings']) && did_action('elementor/loaded')) {
            $elementor_kit_manager = \Elementor\Plugin::$instance->kits_manager;
            $active_kit = $elementor_kit_manager->get_active_kit();
            if ($active_kit) {
                $elementor_kit_manager->save_kit_settings($active_kit->get_id(), $settings['elementor_settings']);
            }
        }

        echo '<div class="updated"><p>Settings imported successfully.</p></div>';
    } else {
        echo '<div class="error"><p>Failed to decode JSON. Please check the file and try again.</p></div>';
    }
}
?>
