<?php
if (!defined('ABSPATH')) exit;

add_action('admin_init', 'alpenia_register_participant_drafts_settings');
function alpenia_register_participant_drafts_settings() {
    register_setting('alpenia_participant_drafts_settings', 'alpenia_participant_drafts_api_key', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);

    register_setting('alpenia_participant_drafts_settings', 'alpenia_participant_drafts_default_status', [
        'type'              => 'string',
        'sanitize_callback' => 'alpenia_sanitize_participant_drafts_default_status_setting',
        'default'           => 'draft',
    ]);

    register_setting('alpenia_participant_drafts_settings', 'alpenia_participant_drafts_zapier_source', [
        'type'              => 'string',
        'sanitize_callback' => 'alpenia_sanitize_participant_drafts_source_setting',
        'default'           => 'tally_zapier',
    ]);
}

function alpenia_sanitize_participant_drafts_default_status_setting($status) {
    $status = sanitize_key($status);
    return in_array($status, alpenia_participant_draft_statuses(), true) ? $status : 'draft';
}

function alpenia_sanitize_participant_drafts_source_setting($source) {
    $source = sanitize_key($source);
    return $source !== '' ? $source : 'tally_zapier';
}

function alpenia_render_participant_drafts_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'alpenia-travel'));
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Teilnehmer-Entwürfe Einstellungen', 'alpenia-travel'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('alpenia_participant_drafts_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="alpenia_participant_drafts_api_key"><?php echo esc_html__('Zapier API-Key', 'alpenia-travel'); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="alpenia_participant_drafts_api_key" name="alpenia_participant_drafts_api_key" value="<?php echo esc_attr(alpenia_get_participant_drafts_api_key()); ?>" autocomplete="off">
                        <p class="description"><?php echo esc_html__('Zapier muss diesen Wert im Header X-API-Key senden.', 'alpenia-travel'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="alpenia_participant_drafts_default_status"><?php echo esc_html__('Standard-Status', 'alpenia-travel'); ?></label></th>
                    <td>
                        <select id="alpenia_participant_drafts_default_status" name="alpenia_participant_drafts_default_status">
                            <?php foreach (alpenia_participant_draft_statuses() as $status) : ?>
                                <option value="<?php echo esc_attr($status); ?>" <?php selected(alpenia_get_participant_drafts_default_status(), $status); ?>><?php echo esc_html($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="alpenia_participant_drafts_zapier_source"><?php echo esc_html__('Standard-Quelle für Zapier', 'alpenia-travel'); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="alpenia_participant_drafts_zapier_source" name="alpenia_participant_drafts_zapier_source" value="<?php echo esc_attr(alpenia_get_participant_drafts_zapier_source()); ?>">
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Einstellungen speichern', 'alpenia-travel')); ?>
        </form>
    </div>
    <?php
}
