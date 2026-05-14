<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', 'alpenia_register_participant_drafts_rest_routes');
function alpenia_register_participant_drafts_rest_routes() {
    register_rest_route('mein-plugin/v1', '/participant-drafts', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'alpenia_rest_create_participant_draft',
        'permission_callback' => 'alpenia_rest_participant_drafts_permission_check',
    ]);
}

function alpenia_rest_participant_drafts_permission_check(WP_REST_Request $request) {
    $configured_key = alpenia_get_participant_drafts_api_key();
    $provided_key = (string) $request->get_header('x-api-key');

    if ($configured_key === '' || $provided_key === '' || !hash_equals($configured_key, $provided_key)) {
        return new WP_Error('invalid_api_key', 'Invalid or missing API key.', ['status' => 401]);
    }

    return true;
}

function alpenia_rest_create_participant_draft(WP_REST_Request $request) {
    $payload = $request->get_json_params();
    if (!is_array($payload)) {
        $payload = $request->get_body_params();
    }
    if (!is_array($payload)) {
        $payload = [];
    }

    $data = alpenia_sanitize_participant_draft_data($payload, alpenia_get_participant_drafts_zapier_source());
    $data['source'] = alpenia_get_participant_drafts_zapier_source();
    $errors = alpenia_validate_participant_draft_data($data);

    if (!empty($errors)) {
        return new WP_Error('participant_draft_validation_failed', implode(' ', $errors), ['status' => 400]);
    }

    $result = alpenia_save_participant_draft($data, $payload);
    if (is_wp_error($result)) {
        return $result;
    }

    return rest_ensure_response([
        'success'  => true,
        'message'  => $result['updated'] ? 'Existing participant draft updated.' : 'Participant draft created.',
        'draft_id' => $result['id'],
    ]);
}
