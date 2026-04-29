
<?php
add_action('wp_ajax_get_profile_suggestions', 'en13830_get_profile_suggestions');
add_action('wp_ajax_nopriv_get_profile_suggestions', 'en13830_get_profile_suggestions');

function en13830_get_profile_suggestions() {
    if (!isset($_GET['required_ix']) || !is_numeric($_GET['required_ix'])) {
        wp_send_json_error(['message' => 'Invalid Ix value.']);
    }

    global $wpdb;
    $ix = floatval($_GET['required_ix']);

    $table = $wpdb->prefix . 'en13830_profiles';

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table
        WHERE ix >= %f
        ORDER BY ix ASC
        LIMIT 20
    ", $ix));

    $suggestions = [];
    foreach ($results as $row) {
        $utilization = $ix > 0 ? ($row->ix / $ix) * 100 : 0;
        $suggestions[] = [
            'code'        => $row->code,
            'system'      => $row->system,
            'width'       => $row->width,
            'depth'       => $row->depth,
            'ix'          => $row->ix,
            'iy'          => $row->iy,
            'utilization' => round($utilization, 1),
            'image_url'   => $row->image_url,
        ];
    }

    wp_send_json_success(['suggestions' => $suggestions]);
}
?>
