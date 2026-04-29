
<?php
class EN13830_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));

        if (is_admin() && isset($_POST['save_profile'])) {
            $this->handle_profile_save();
        }

        if (is_admin() && isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
            $this->handle_profile_delete();
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'EN 13830 Calculator Settings',
            'EN13830',
            'manage_options',
            'en13830-calculator',
            array($this, 'admin_page'),
            'dashicons-analytics',
            56
        );

        add_submenu_page(
            'en13830-calculator',
            'Ρυθμίσεις Υπολογιστή',
            'Ρυθμίσεις',
            'manage_options',
            'en13830-calculator',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'en13830-calculator',
            'Διαχείριση Προφίλ',
            'Διαχείριση Προφίλ',
            'manage_options',
            'en13830_profiles',
            array($this, 'render_profiles_page')
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>EN 13830 Curtain Wall Calculator</h1>
            <div class="card">
                <h2>Usage Instructions</h2>
                <p>Use the shortcode <code>[en13830_calculator]</code> to display the calculator on any page or post.</p>
                <h3>Features:</h3>
                <ul>
                    <li>Calculate required moment of inertia (Ix) values</li>
                    <li>Support for single, equal double, and unequal double span configurations</li>
                    <li>EN 13830 compliant deflection limits</li>
                    <li>Interactive span type selection</li>
                    <li>Real-time calculations</li>
                </ul>
                <h3>Input Parameters:</h3>
                <ul>
                    <li><strong>P:</strong> Design wind load (N/m² or Pa)</li>
                    <li><strong>L:</strong> Mullion length (m)</li>
                    <li><strong>A:</strong> Left portion width (m)</li>
                    <li><strong>B:</strong> Right portion width (m)</li>
                    <li><strong>H2:</strong> Biggest height of glazing panel (m)</li>
                    <li><strong>E:</strong> Young's Modulus (N/mm²)</li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function render_profiles_page() {
        include plugin_dir_path(__FILE__) . 'admin-profiles.php';
    }

    public function handle_profile_save() {
        if (!isset($_POST['en13830_profile_nonce']) || !wp_verify_nonce($_POST['en13830_profile_nonce'], 'save_en13830_profile')) return;
        if (!current_user_can('manage_options')) return;

        global $wpdb;
        $table = $wpdb->prefix . 'en13830_profiles';

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $image_url = '';
        if (!empty($_FILES['image']['name'])) {
            $uploaded = media_handle_upload('image', 0);
            if (!is_wp_error($uploaded)) {
                $image_url = wp_get_attachment_url($uploaded);
            }
        } elseif (!empty($_POST['edit_id'])) {
            $old = $wpdb->get_row($wpdb->prepare("SELECT image_url FROM $table WHERE id = %d", intval($_POST['edit_id'])));
            if ($old) {
                $image_url = $old->image_url;
            }
        }

        $data = [
            'code' => sanitize_text_field($_POST['code']),
            'type' => sanitize_text_field($_POST['type']),
            'system' => sanitize_text_field($_POST['system']),
            'image_url' => esc_url_raw($image_url),
            'width' => floatval($_POST['width']),
            'depth' => floatval($_POST['depth']),
            'weight' => floatval($_POST['weight']),
            'ix' => floatval($_POST['ix']),
            'iy' => floatval($_POST['iy']),
        ];

        if (!empty($_POST['edit_id'])) {
            $wpdb->update($table, $data, ['id' => intval($_POST['edit_id'])]);
        } else {
            $wpdb->insert($table, $data);
        }
    }

    public function handle_profile_delete() {
        $id = intval($_GET['delete']);
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_profile_' . $id)) return;
        if (!current_user_can('manage_options')) return;

        global $wpdb;
        $table = $wpdb->prefix . 'en13830_profiles';
        $wpdb->delete($table, ['id' => $id]);

        wp_redirect(admin_url('admin.php?page=en13830_profiles'));
        exit;
    }
}
new EN13830_Admin();
    