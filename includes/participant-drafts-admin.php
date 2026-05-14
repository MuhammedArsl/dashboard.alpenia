<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'alpenia_register_participant_drafts_admin_menu');
function alpenia_register_participant_drafts_admin_menu() {
    add_menu_page(
        __('Teilnehmer-Entwürfe', 'alpenia-travel'),
        __('Teilnehmer-Entwürfe', 'alpenia-travel'),
        'manage_options',
        'alpenia-participant-drafts',
        'alpenia_render_participant_drafts_admin_page',
        'dashicons-groups',
        26
    );

    add_submenu_page(
        'alpenia-participant-drafts',
        __('Einstellungen', 'alpenia-travel'),
        __('Einstellungen', 'alpenia-travel'),
        'manage_options',
        'alpenia-participant-drafts-settings',
        'alpenia_render_participant_drafts_settings_page'
    );
}

add_action('admin_post_alpenia_delete_participant_draft', 'alpenia_handle_delete_participant_draft');
function alpenia_handle_delete_participant_draft() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to perform this action.', 'alpenia-travel'));
    }

    $draft_id = isset($_GET['draft_id']) ? absint($_GET['draft_id']) : 0;
    if (!$draft_id || !isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'alpenia_delete_participant_draft_' . $draft_id)) {
        wp_die(esc_html__('Sicherheitsfehler. Bitte erneut versuchen.', 'alpenia-travel'));
    }

    global $wpdb;
    $wpdb->delete(alpenia_participant_drafts_table_name(), ['id' => $draft_id], ['%d']);

    wp_safe_redirect(add_query_arg([
        'page' => 'alpenia-participant-drafts',
        'alpenia_message' => 'deleted',
    ], admin_url('admin.php')));
    exit;
}

function alpenia_render_participant_drafts_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'alpenia-travel'));
    }

    $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : 'list';
    $draft_id = isset($_GET['draft_id']) ? absint(wp_unslash($_GET['draft_id'])) : 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alpenia_participant_draft_action'])) {
        alpenia_handle_participant_draft_form_submission();
    }

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">' . esc_html__('Teilnehmer-Entwürfe', 'alpenia-travel') . '</h1> ';
    echo '<a href="' . esc_url(add_query_arg(['page' => 'alpenia-participant-drafts', 'action' => 'new'], admin_url('admin.php'))) . '" class="page-title-action">' . esc_html__('Neu erstellen', 'alpenia-travel') . '</a>';
    echo '<hr class="wp-header-end">';

    alpenia_render_participant_drafts_admin_notice();

    if ($action === 'new') {
        alpenia_render_participant_draft_form();
    } elseif ($action === 'edit' && $draft_id) {
        alpenia_render_participant_draft_form($draft_id);
    } else {
        alpenia_render_participant_drafts_table();
    }

    echo '</div>';
}

function alpenia_render_participant_drafts_admin_notice() {
    $message = isset($_GET['alpenia_message']) ? sanitize_key(wp_unslash($_GET['alpenia_message'])) : '';
    $messages = [
        'created' => __('Teilnehmer-Entwurf wurde erstellt.', 'alpenia-travel'),
        'updated' => __('Teilnehmer-Entwurf wurde gespeichert.', 'alpenia-travel'),
        'deleted' => __('Teilnehmer-Entwurf wurde gelöscht.', 'alpenia-travel'),
    ];

    if (isset($messages[$message])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$message]) . '</p></div>';
    }
}

function alpenia_get_participant_draft($draft_id) {
    global $wpdb;
    return $wpdb->get_row(
        $wpdb->prepare('SELECT * FROM ' . alpenia_participant_drafts_table_name() . ' WHERE id = %d', $draft_id),
        ARRAY_A
    );
}

function alpenia_handle_participant_draft_form_submission() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to perform this action.', 'alpenia-travel'));
    }

    $form_action = sanitize_key(wp_unslash($_POST['alpenia_participant_draft_action']));
    $draft_id = isset($_POST['draft_id']) ? absint(wp_unslash($_POST['draft_id'])) : 0;
    $nonce_action = $draft_id ? 'alpenia_save_participant_draft_' . $draft_id : 'alpenia_create_participant_draft';

    if (!isset($_POST['alpenia_participant_draft_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alpenia_participant_draft_nonce'])), $nonce_action)) {
        wp_die(esc_html__('Sicherheitsfehler. Bitte erneut versuchen.', 'alpenia-travel'));
    }

    $data = alpenia_sanitize_participant_draft_data(wp_unslash($_POST), $draft_id ? 'manual' : 'manual');
    $errors = alpenia_validate_participant_draft_data($data);

    if (!empty($errors)) {
        echo '<div class="notice notice-error"><p>' . esc_html(implode(' ', $errors)) . '</p></div>';
        return;
    }

    global $wpdb;
    $table_name = alpenia_participant_drafts_table_name();
    $now = current_time('mysql');

    $existing_draft = $draft_id ? alpenia_get_participant_draft($draft_id) : null;
    $source = $draft_id && $existing_draft ? $existing_draft['source'] : 'manual';

    $db_data = [
        'first_name'       => $data['first_name'],
        'last_name'        => $data['last_name'],
        'email'            => $data['email'],
        'phone'            => $data['phone'],
        'birthdate'        => $data['birthdate'],
        'gender'           => $data['gender'],
        'address'          => $data['address'],
        'city'             => $data['city'],
        'zip'              => $data['zip'],
        'country'          => $data['country'],
        'desired_trip'     => $data['desired_trip'],
        'program'          => $data['program'],
        'assigned_trip_id' => $data['assigned_trip_id'],
        'status'           => $data['status'],
        'source'           => $source,
        'notes'            => $data['notes'],
        'updated_at'       => $now,
    ];
    $formats = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s'];

    if ($form_action === 'edit' && $draft_id) {
        $wpdb->update($table_name, $db_data, ['id' => $draft_id], $formats, ['%d']);
        $message = 'updated';
    } else {
        $db_data['created_at'] = $now;
        $formats[] = '%s';
        $wpdb->insert($table_name, $db_data, $formats);
        $draft_id = (int) $wpdb->insert_id;
        $message = 'created';
    }

    wp_safe_redirect(add_query_arg([
        'page' => 'alpenia-participant-drafts',
        'action' => 'edit',
        'draft_id' => $draft_id,
        'alpenia_message' => $message,
    ], admin_url('admin.php')));
    exit;
}

function alpenia_render_participant_drafts_table() {
    global $wpdb;
    $table_name = alpenia_participant_drafts_table_name();
    $drafts = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 200", ARRAY_A);
    ?>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('ID', 'alpenia-travel'); ?></th>
                <th><?php echo esc_html__('Name', 'alpenia-travel'); ?></th>
                <th><?php echo esc_html__('E-Mail', 'alpenia-travel'); ?></th>
                <th><?php echo esc_html__('Telefonnummer', 'alpenia-travel'); ?></th>
                <th><?php echo esc_html__('gewünschte Reise', 'alpenia-travel'); ?></th>
                <th><?php echo esc_html__('Programm', 'alpenia-travel'); ?></th>
                <th><?php echo esc_html__('zugewiesene Reise-ID', 'alpenia-travel'); ?></th>
                <th><?php echo esc_html__('Status', 'alpenia-travel'); ?></th>
                <th><?php echo esc_html__('Quelle', 'alpenia-travel'); ?></th>
                <th><?php echo esc_html__('Erstellungsdatum', 'alpenia-travel'); ?></th>
                <th><?php echo esc_html__('Aktionen', 'alpenia-travel'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($drafts)) : ?>
                <tr><td colspan="11"><?php echo esc_html__('Noch keine Teilnehmer-Entwürfe vorhanden.', 'alpenia-travel'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($drafts as $draft) : ?>
                    <?php
                    $edit_url = add_query_arg(['page' => 'alpenia-participant-drafts', 'action' => 'edit', 'draft_id' => (int) $draft['id']], admin_url('admin.php'));
                    $delete_url = wp_nonce_url(admin_url('admin-post.php?action=alpenia_delete_participant_draft&draft_id=' . (int) $draft['id']), 'alpenia_delete_participant_draft_' . (int) $draft['id']);
                    ?>
                    <tr>
                        <td><?php echo esc_html((string) $draft['id']); ?></td>
                        <td><?php echo esc_html(trim($draft['first_name'] . ' ' . $draft['last_name'])); ?></td>
                        <td><a href="mailto:<?php echo esc_attr($draft['email']); ?>"><?php echo esc_html($draft['email']); ?></a></td>
                        <td><?php echo esc_html($draft['phone']); ?></td>
                        <td><?php echo esc_html($draft['desired_trip']); ?></td>
                        <td><?php echo esc_html($draft['program']); ?></td>
                        <td><?php echo esc_html(alpenia_format_assigned_trip_label($draft['assigned_trip_id'])); ?></td>
                        <td><?php echo esc_html($draft['status']); ?></td>
                        <td><?php echo esc_html($draft['source']); ?></td>
                        <td><?php echo esc_html($draft['created_at']); ?></td>
                        <td>
                            <a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html__('Bearbeiten', 'alpenia-travel'); ?></a> |
                            <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(__('Diesen Teilnehmer-Entwurf wirklich löschen?', 'alpenia-travel')); ?>');"><?php echo esc_html__('Löschen', 'alpenia-travel'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}

function alpenia_render_participant_draft_form($draft_id = 0) {
    $draft = $draft_id ? alpenia_get_participant_draft($draft_id) : null;
    if ($draft_id && !$draft) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Teilnehmer-Entwurf nicht gefunden.', 'alpenia-travel') . '</p></div>';
        return;
    }

    $defaults = [
        'id' => 0,
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'birthdate' => '',
        'gender' => '',
        'address' => '',
        'city' => '',
        'zip' => '',
        'country' => '',
        'desired_trip' => '',
        'program' => '',
        'assigned_trip_id' => '',
        'status' => alpenia_get_participant_drafts_default_status(),
        'source' => 'manual',
        'notes' => '',
    ];
    $draft = wp_parse_args($draft ?: [], $defaults);
    $nonce_action = $draft_id ? 'alpenia_save_participant_draft_' . $draft_id : 'alpenia_create_participant_draft';
    ?>
    <form method="post">
        <?php wp_nonce_field($nonce_action, 'alpenia_participant_draft_nonce'); ?>
        <input type="hidden" name="alpenia_participant_draft_action" value="<?php echo esc_attr($draft_id ? 'edit' : 'create'); ?>">
        <input type="hidden" name="draft_id" value="<?php echo esc_attr((string) $draft_id); ?>">
        <?php if (!$draft_id) : ?>
            <input type="hidden" name="source" value="manual">
        <?php endif; ?>
        <table class="form-table" role="presentation">
            <?php alpenia_render_participant_draft_text_field('first_name', __('Vorname', 'alpenia-travel'), $draft['first_name'], true); ?>
            <?php alpenia_render_participant_draft_text_field('last_name', __('Nachname', 'alpenia-travel'), $draft['last_name'], true); ?>
            <?php alpenia_render_participant_draft_text_field('email', __('E-Mail', 'alpenia-travel'), $draft['email'], true, 'email'); ?>
            <?php alpenia_render_participant_draft_text_field('phone', __('Telefonnummer', 'alpenia-travel'), $draft['phone']); ?>
            <?php alpenia_render_participant_draft_text_field('birthdate', __('Geburtsdatum', 'alpenia-travel'), $draft['birthdate']); ?>
            <?php alpenia_render_participant_draft_text_field('gender', __('Geschlecht', 'alpenia-travel'), $draft['gender']); ?>
            <?php alpenia_render_participant_draft_textarea_field('address', __('Adresse', 'alpenia-travel'), $draft['address']); ?>
            <?php alpenia_render_participant_draft_text_field('city', __('Stadt', 'alpenia-travel'), $draft['city']); ?>
            <?php alpenia_render_participant_draft_text_field('zip', __('PLZ', 'alpenia-travel'), $draft['zip']); ?>
            <?php alpenia_render_participant_draft_text_field('country', __('Land', 'alpenia-travel'), $draft['country']); ?>
            <?php alpenia_render_participant_draft_text_field('desired_trip', __('gewünschte Reise', 'alpenia-travel'), $draft['desired_trip']); ?>
            <?php alpenia_render_participant_draft_text_field('program', __('Programm', 'alpenia-travel'), $draft['program']); ?>
            <?php alpenia_render_participant_draft_trip_select($draft['assigned_trip_id']); ?>
            <?php alpenia_render_participant_draft_status_field($draft['status']); ?>
            <?php alpenia_render_participant_draft_textarea_field('notes', __('Notizen', 'alpenia-travel'), $draft['notes']); ?>
        </table>
        <?php submit_button($draft_id ? __('Entwurf speichern', 'alpenia-travel') : __('Entwurf erstellen', 'alpenia-travel')); ?>
    </form>
    <?php
}

function alpenia_render_participant_draft_text_field($name, $label, $value, $required = false, $type = 'text') {
    ?>
    <tr>
        <th scope="row"><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?><?php echo $required ? ' *' : ''; ?></label></th>
        <td><input type="<?php echo esc_attr($type); ?>" class="regular-text" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" <?php required($required); ?>></td>
    </tr>
    <?php
}

function alpenia_render_participant_draft_textarea_field($name, $label, $value) {
    ?>
    <tr>
        <th scope="row"><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label></th>
        <td><textarea class="large-text" rows="4" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>"><?php echo esc_textarea($value); ?></textarea></td>
    </tr>
    <?php
}

function alpenia_render_participant_draft_status_field($value) {
    ?>
    <tr>
        <th scope="row"><label for="status"><?php echo esc_html__('Status', 'alpenia-travel'); ?></label></th>
        <td>
            <select id="status" name="status">
                <?php foreach (alpenia_participant_draft_statuses() as $status) : ?>
                    <option value="<?php echo esc_attr($status); ?>" <?php selected($value, $status); ?>><?php echo esc_html($status); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <?php
}

function alpenia_render_participant_draft_trip_select($assigned_trip_id) {
    $trips = get_posts([
        'post_type' => 'group_trip',
        'post_status' => ['publish', 'draft', 'private'],
        'numberposts' => 200,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    ?>
    <tr>
        <th scope="row"><label for="assigned_trip_id"><?php echo esc_html__('zugewiesene Reise-ID', 'alpenia-travel'); ?></label></th>
        <td>
            <select id="assigned_trip_id" name="assigned_trip_id">
                <option value=""><?php echo esc_html__('Keine Reise zugewiesen', 'alpenia-travel'); ?></option>
                <?php foreach ($trips as $trip) : ?>
                    <option value="<?php echo esc_attr((string) $trip->ID); ?>" <?php selected((int) $assigned_trip_id, $trip->ID); ?>>
                        <?php echo esc_html($trip->ID . ' - ' . get_the_title($trip)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php echo esc_html__('Die Reise-Zuweisung erstellt noch keine finale Teilnehmerbuchung.', 'alpenia-travel'); ?></p>
        </td>
    </tr>
    <?php
}

function alpenia_format_assigned_trip_label($assigned_trip_id) {
    $assigned_trip_id = absint($assigned_trip_id);
    if (!$assigned_trip_id) {
        return '';
    }

    $title = get_the_title($assigned_trip_id);
    if ($title !== '') {
        return $assigned_trip_id . ' - ' . $title;
    }

    return (string) $assigned_trip_id;
}
