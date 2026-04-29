
<?php
global $wpdb;
$table = $wpdb->prefix . 'en13830_profiles';

$editing = false;
$profile = null;

if (isset($_GET['edit'])) {
    $editing = true;
    $edit_id = intval($_GET['edit']);
    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id));
}

$results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
?>

<div class="wrap">
    <h1><?php echo $editing ? 'Επεξεργασία Προφίλ' : 'Διαχείριση Προφίλ'; ?></h1>

    <form method="post" action="" enctype="multipart/form-data">
        <?php wp_nonce_field('save_en13830_profile', 'en13830_profile_nonce'); ?>
        <?php if ($editing): ?>
            <input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_id); ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr><th><label for="code">Κωδικός</label></th><td><input type="text" name="code" required value="<?php echo esc_attr($profile->code ?? ''); ?>"></td></tr>
            <tr><th><label for="type">Είδος</label></th><td>
                <select name="type">
                    <option value="Κολώνα" <?php selected($profile->type ?? '', 'Κολώνα'); ?>>Κολώνα</option>
                    <option value="Τραβέρσα" <?php selected($profile->type ?? '', 'Τραβέρσα'); ?>>Τραβέρσα</option>
                </select></td></tr>
            <tr><th><label for="system">Σύστημα</label></th><td><input type="text" name="system" required value="<?php echo esc_attr($profile->system ?? ''); ?>"></td></tr>
            <tr><th><label for="image">Εικόνα</label></th><td><input type="file" name="image" accept="image/*">
                <?php if (!empty($profile->image_url)): ?><br><img src="<?php echo esc_url($profile->image_url); ?>" width="80"><?php endif; ?></td></tr>
            <tr><th colspan="2"><strong>Γεωμετρικά χαρακτηριστικά</strong></th></tr>
            <tr><th>Πλάτος</th><td><input type="text" name="width" value="<?php echo esc_attr(number_format((float)($profile->width ?? 0), 3, '.', '')); ?>"></td></tr>
            <tr><th>Βάθος</th><td><input type="text" name="depth" value="<?php echo esc_attr(number_format((float)($profile->depth ?? 0), 3, '.', '')); ?>"></td></tr>
            <tr><th>Βάρος</th><td><input type="text" name="weight" value="<?php echo esc_attr(number_format((float)($profile->weight ?? 0), 3, '.', '')); ?>"></td></tr>
            <tr><th>Ix</th><td><input type="text" name="ix" value="<?php echo esc_attr(number_format((float)($profile->ix ?? 0), 3, '.', '')); ?>"></td></tr>
            <tr><th>Iy</th><td><input type="text" name="iy" value="<?php echo esc_attr(number_format((float)($profile->iy ?? 0), 3, '.', '')); ?>"></td></tr>
        </table>

        <p><input type="submit" name="save_profile" class="button-primary" value="<?php echo $editing ? 'Αποθήκευση Αλλαγών' : 'Αποθήκευση Προφίλ'; ?>"></p>
    </form>

    <?php if ($results): ?>
        <h2>Καταχωρημένα Προφίλ</h2>
        <table class="widefat striped">
            <thead><tr>
                <th>Εικόνα</th><th>Κωδικός</th><th>Είδος</th><th>Σύστημα</th>
                <th>Πλάτος</th><th>Βάθος</th><th>Βάρος</th><th>Ix</th><th>Iy</th><th>Ενέργειες</th>
            </tr></thead>
            <tbody>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?php echo $row->image_url ? '<img src="' . esc_url($row->image_url) . '" width="50">' : '-'; ?></td>
                    <td><?php echo esc_html($row->code); ?></td>
                    <td><?php echo esc_html($row->type); ?></td>
                    <td><?php echo esc_html($row->system); ?></td>
                    <td><?php echo $row->width; ?></td>
                    <td><?php echo $row->depth; ?></td>
                    <td><?php echo $row->weight; ?></td>
                    <td><?php echo $row->ix; ?></td>
                    <td><?php echo $row->iy; ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=en13830_profiles&edit=' . $row->id); ?>">✏️ Επεξεργασία</a> |
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=en13830_profiles&delete=' . $row->id), 'delete_profile_' . $row->id); ?>" onclick="return confirm('Είστε σίγουρος;')">🗑️ Διαγραφή</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
    