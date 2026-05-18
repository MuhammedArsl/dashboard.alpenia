<?php
if (!defined('ABSPATH')) exit;

function alpenia_dashboard_business_money($amount) {
    return '€ ' . number_format((float) $amount, 2, ',', '.');
}

function alpenia_dashboard_business_participant_name($participant_id) {
    $name = trim(
        alpenia_get_secure_meta($participant_id, 'first_name', true) . ' ' .
        alpenia_get_secure_meta($participant_id, 'last_name', true)
    );

    return $name !== '' ? $name : get_the_title($participant_id);
}

function alpenia_dashboard_compare_desc($left, $right) {
    if ($left === $right) {
        return 0;
    }

    return $left < $right ? 1 : -1;
}

function alpenia_dashboard_compare_asc($left, $right) {
    if ($left === $right) {
        return 0;
    }

    return $left < $right ? -1 : 1;
}

function alpenia_dashboard_sort_by_participants($a, $b) {
    if ((int) $b['participants'] === (int) $a['participants']) {
        return alpenia_dashboard_compare_desc((float) $a['revenue'], (float) $b['revenue']);
    }

    return alpenia_dashboard_compare_desc((int) $a['participants'], (int) $b['participants']);
}

function alpenia_dashboard_get_business_overview_data($trips, $participants, $limit = 5) {
    $limit = max(1, (int) $limit);
    $now = current_time('timestamp');
    $current_month = date('Y-m', $now);
    $previous_month = date('Y-m', strtotime('-1 month', $now));

    $data = [
        'totals' => [
            'revenue' => 0.0,
            'paid' => 0.0,
            'open' => 0.0,
            'capacity' => 0,
            'occupied' => 0,
            'occupancy' => 0,
            'current_month_participants' => 0,
            'previous_month_participants' => 0,
            'current_month_revenue' => 0.0,
            'previous_month_revenue' => 0.0,
            'participant_delta' => 0,
            'revenue_delta' => 0,
        ],
        'status_counts' => [
            'neu' => 0,
            'in_pruefung' => 0,
            'vollstaendig' => 0,
            'other' => 0,
        ],
        'missing_docs_by_type' => [],
        'priority_payments' => [],
        'trip_actions' => [],
        'type_breakdown' => [],
        'location_breakdown' => [],
        'guide_breakdown' => [],
        'marketing_focus' => [],
    ];

    $trip_stats = [];
    foreach ((array) $trips as $trip) {
        $trip_id = (int) $trip->ID;
        $capacity = (int) get_post_meta($trip_id, 'max_people', true);
        $guide_id = (int) get_post_meta($trip_id, 'assigned_guide', true);

        $trip_stats[$trip_id] = [
            'trip_id' => $trip_id,
            'title' => $trip->post_title,
            'status' => get_post_meta($trip_id, 'trip_status', true),
            'type' => get_post_meta($trip_id, 'trip_type', true),
            'country' => get_post_meta($trip_id, 'country', true),
            'city' => get_post_meta($trip_id, 'city', true),
            'guide_id' => $guide_id,
            'participants' => 0,
            'capacity' => max(0, $capacity),
            'revenue' => 0.0,
            'paid' => 0.0,
            'open' => 0.0,
            'missing_docs' => 0,
            'open_payments' => 0,
            'in_review' => 0,
            'new_participants' => 0,
            'occupancy' => 0,
        ];

        if ($capacity > 0) {
            $data['totals']['capacity'] += $capacity;
        }
    }

    foreach ((array) $participants as $participant) {
        $participant_id = (int) $participant->ID;
        $trip_id = (int) get_post_meta($participant_id, 'trip_id', true);

        if (!$trip_id || !isset($trip_stats[$trip_id]) || !alpenia_user_can_access_trip($trip_id)) {
            continue;
        }

        $payment_total = (float) get_post_meta($participant_id, 'payment_total', true);
        $payment_paid = (float) get_post_meta($participant_id, 'payment_paid', true);
        $payment_open = alpenia_get_participant_payment_open($participant_id);
        $participant_status = sanitize_key((string) get_post_meta($participant_id, 'participant_status', true));
        $month_key = !empty($participant->post_date) ? date('Y-m', strtotime($participant->post_date)) : '';

        $data['totals']['revenue'] += $payment_total;
        $data['totals']['paid'] += $payment_paid;
        $data['totals']['open'] += $payment_open;

        if ($month_key === $current_month) {
            $data['totals']['current_month_participants']++;
            $data['totals']['current_month_revenue'] += $payment_total;
        } elseif ($month_key === $previous_month) {
            $data['totals']['previous_month_participants']++;
            $data['totals']['previous_month_revenue'] += $payment_total;
        }

        if (isset($data['status_counts'][$participant_status])) {
            $data['status_counts'][$participant_status]++;
        } else {
            $data['status_counts']['other']++;
        }

        $trip_stats[$trip_id]['participants']++;
        $trip_stats[$trip_id]['revenue'] += $payment_total;
        $trip_stats[$trip_id]['paid'] += $payment_paid;
        $trip_stats[$trip_id]['open'] += $payment_open;

        if ($participant_status === 'in_pruefung') {
            $trip_stats[$trip_id]['in_review']++;
        } elseif ($participant_status === 'neu') {
            $trip_stats[$trip_id]['new_participants']++;
        }

        $missing_docs = alpenia_get_participant_doc_score($participant_id) !== 'complete'
            ? alpenia_get_missing_docs_details($participant_id)
            : [];

        if (!empty($missing_docs)) {
            $trip_stats[$trip_id]['missing_docs']++;
            foreach ($missing_docs as $doc_label) {
                if (!isset($data['missing_docs_by_type'][$doc_label])) {
                    $data['missing_docs_by_type'][$doc_label] = 0;
                }
                $data['missing_docs_by_type'][$doc_label]++;
            }
        }

        if ($payment_open > 0) {
            $trip_stats[$trip_id]['open_payments']++;
            $data['priority_payments'][] = [
                'participant_id' => $participant_id,
                'trip_id' => $trip_id,
                'trip_title' => get_the_title($trip_id),
                'participant_name' => alpenia_dashboard_business_participant_name($participant_id),
                'payment_open' => $payment_open,
            ];
        }
    }

    foreach ($trip_stats as $trip_id => $stats) {
        if ($stats['capacity'] > 0) {
            $trip_stats[$trip_id]['occupancy'] = min(100, (int) round(($stats['participants'] / $stats['capacity']) * 100));
            $data['totals']['occupied'] += min($stats['participants'], $stats['capacity']);
        }
    }

    $data['totals']['occupancy'] = $data['totals']['capacity'] > 0
        ? (int) round(($data['totals']['occupied'] / $data['totals']['capacity']) * 100)
        : 0;
    $data['totals']['participant_delta'] = $data['totals']['previous_month_participants'] > 0
        ? (int) round((($data['totals']['current_month_participants'] - $data['totals']['previous_month_participants']) / $data['totals']['previous_month_participants']) * 100)
        : ($data['totals']['current_month_participants'] > 0 ? 100 : 0);
    $data['totals']['revenue_delta'] = $data['totals']['previous_month_revenue'] > 0
        ? (int) round((($data['totals']['current_month_revenue'] - $data['totals']['previous_month_revenue']) / $data['totals']['previous_month_revenue']) * 100)
        : ($data['totals']['current_month_revenue'] > 0 ? 100 : 0);

    foreach ($trip_stats as $stats) {
        if ($stats['participants'] <= 0 && $stats['revenue'] <= 0) {
            continue;
        }

        $action_score = ($stats['missing_docs'] * 3) + ($stats['open_payments'] * 2) + $stats['in_review'] + $stats['new_participants'];
        if ($action_score > 0) {
            $stats['action_score'] = $action_score;
            $data['trip_actions'][] = $stats;
        }

        if ($stats['status'] === 'open' && $stats['capacity'] > 0 && $stats['occupancy'] < 65) {
            $data['marketing_focus'][] = $stats;
        }

        $type_label = alpenia_display_value($stats['type']);
        if (!isset($data['type_breakdown'][$type_label])) {
            $data['type_breakdown'][$type_label] = ['label' => $type_label, 'participants' => 0, 'revenue' => 0.0, 'trips' => 0];
        }
        $data['type_breakdown'][$type_label]['participants'] += $stats['participants'];
        $data['type_breakdown'][$type_label]['revenue'] += $stats['revenue'];
        $data['type_breakdown'][$type_label]['trips']++;

        $location_label = alpenia_display_value($stats['country']) . ' / ' . alpenia_display_value($stats['city']);
        if (!isset($data['location_breakdown'][$location_label])) {
            $data['location_breakdown'][$location_label] = ['label' => $location_label, 'participants' => 0, 'revenue' => 0.0];
        }
        $data['location_breakdown'][$location_label]['participants'] += $stats['participants'];
        $data['location_breakdown'][$location_label]['revenue'] += $stats['revenue'];

        $guide_label = $stats['guide_id'] > 0 ? get_the_author_meta('display_name', $stats['guide_id']) : alpenia_travel_t('Nicht zugewiesen');
        if (!isset($data['guide_breakdown'][$guide_label])) {
            $data['guide_breakdown'][$guide_label] = ['label' => $guide_label, 'participants' => 0, 'revenue' => 0.0, 'trips' => 0];
        }
        $data['guide_breakdown'][$guide_label]['participants'] += $stats['participants'];
        $data['guide_breakdown'][$guide_label]['revenue'] += $stats['revenue'];
        $data['guide_breakdown'][$guide_label]['trips']++;
    }

    arsort($data['missing_docs_by_type']);
    usort($data['priority_payments'], function($a, $b) { return alpenia_dashboard_compare_desc((float) $a['payment_open'], (float) $b['payment_open']); });
    usort($data['trip_actions'], function($a, $b) { return alpenia_dashboard_compare_desc((int) $a['action_score'], (int) $b['action_score']); });
    usort($data['marketing_focus'], function($a, $b) { return alpenia_dashboard_compare_asc((int) $a['occupancy'], (int) $b['occupancy']); });
    usort($data['type_breakdown'], 'alpenia_dashboard_sort_by_participants');
    usort($data['location_breakdown'], 'alpenia_dashboard_sort_by_participants');
    usort($data['guide_breakdown'], 'alpenia_dashboard_sort_by_participants');

    $data['missing_docs_by_type'] = array_slice($data['missing_docs_by_type'], 0, $limit, true);
    $data['priority_payments'] = array_slice($data['priority_payments'], 0, $limit);
    $data['trip_actions'] = array_slice($data['trip_actions'], 0, $limit);
    $data['marketing_focus'] = array_slice($data['marketing_focus'], 0, $limit);
    $data['type_breakdown'] = array_slice($data['type_breakdown'], 0, $limit);
    $data['location_breakdown'] = array_slice($data['location_breakdown'], 0, $limit);
    $data['guide_breakdown'] = array_slice($data['guide_breakdown'], 0, $limit);

    return $data;
}

function alpenia_dashboard_render_metric_card($label, $value, $hint = '') {
    $html = '<article class="business-metric-card">';
    $html .= '<span>' . esc_html(alpenia_travel_t($label)) . '</span>';
    $html .= '<strong>' . esc_html($value) . '</strong>';
    if ($hint !== '') {
        $html .= '<small>' . esc_html($hint) . '</small>';
    }
    $html .= '</article>';

    return $html;
}

function alpenia_dashboard_render_business_overview($trips, $participants) {
    $data = alpenia_dashboard_get_business_overview_data($trips, $participants, 5);
    $totals = $data['totals'];

    ob_start();
    ?>
    <section class="business-overview" aria-label="<?php echo esc_attr(alpenia_travel_t('Firmensteuerung')); ?>">
        <style>
            .business-overview{margin:0 0 30px;padding:22px;border:1px solid rgba(120,180,150,.24);border-radius:24px;background:linear-gradient(145deg,rgba(255,255,255,.97),rgba(246,251,248,.98));color:#123f34;box-shadow:0 22px 55px rgba(10,37,30,.14)}
            .business-overview__header{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:18px}.business-overview__header h2{margin:0;color:#0b3329;font-size:clamp(26px,3vw,38px);font-weight:900}.business-overview__header p{margin:8px 0 0;color:#466357;font-weight:700}.business-metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:16px}.business-metric-card{display:grid;gap:8px;padding:16px;border:1px solid rgba(47,125,99,.14);border-radius:18px;background:#fff}.business-metric-card span{color:#617a70;font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.business-metric-card strong{color:#123f34;font-size:clamp(22px,2.3vw,32px);font-weight:900;line-height:1.05;overflow-wrap:anywhere}.business-metric-card small{color:#466357;font-weight:800}.business-overview-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.business-list-card{min-width:0;padding:18px;border:1px solid rgba(47,125,99,.14);border-radius:20px;background:#f8fcfa}.business-list-card h3{margin:0 0 12px;color:#123f34;font-size:20px;font-weight:900}.business-list{display:grid;gap:10px}.business-list article{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:13px;border:1px solid rgba(47,125,99,.12);border-radius:15px;background:#fff}.business-list article div{display:grid;gap:4px;min-width:0}.business-list a,.business-list strong{color:#123f34;font-weight:900;text-decoration:none}.business-list a:hover{text-decoration:underline}.business-list span{color:#617a70;font-size:13px;font-weight:800}.business-list em{color:#7a4b0c;font-style:normal;font-weight:900;text-align:right;white-space:nowrap}.business-empty{margin:0;color:#466357;font-weight:800}@media(max-width:1100px){.business-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.business-overview-grid{grid-template-columns:1fr}}@media(max-width:767px){.business-overview{padding:16px;border-radius:20px}.business-overview__header{flex-direction:column}.business-metric-grid{grid-template-columns:1fr}.business-list article{align-items:flex-start;flex-direction:column}.business-list em{text-align:left}}
        </style>
        <div class="business-overview__header">
            <div>
                <span class="overview-card__eyebrow"><?php echo esc_html(alpenia_travel_t('Firmensteuerung')); ?></span>
                <h2><?php echo esc_html(alpenia_travel_t('Wachstum, Finanzen & Operations')); ?></h2>
                <p><?php echo esc_html(alpenia_travel_t('Kompakte Kennzahlen für Skalierung, Zahlungsfokus und Backoffice-Prioritäten.')); ?></p>
            </div>
        </div>

        <div class="business-metric-grid">
            <?php
            echo alpenia_dashboard_render_metric_card(alpenia_travel_t('Geplanter Umsatz'), alpenia_dashboard_business_money($totals['revenue']));
            echo alpenia_dashboard_render_metric_card(alpenia_travel_t('Bereits bezahlt'), alpenia_dashboard_business_money($totals['paid']));
            echo alpenia_dashboard_render_metric_card(alpenia_travel_t('Noch offen'), alpenia_dashboard_business_money($totals['open']));
            echo alpenia_dashboard_render_metric_card(alpenia_travel_t('Auslastung'), $totals['occupancy'] . '%', sprintf(alpenia_travel_t('%1$d von %2$d Plätzen'), $totals['occupied'], $totals['capacity']));
            echo alpenia_dashboard_render_metric_card(alpenia_travel_t('Anmeldungen aktueller Monat'), (string) $totals['current_month_participants'], sprintf(alpenia_travel_t('%+d%% zum Vormonat'), $totals['participant_delta']));
            echo alpenia_dashboard_render_metric_card(alpenia_travel_t('Umsatz aktueller Monat'), alpenia_dashboard_business_money($totals['current_month_revenue']), sprintf(alpenia_travel_t('%+d%% zum Vormonat'), $totals['revenue_delta']));
            echo alpenia_dashboard_render_metric_card(alpenia_travel_t('In Prüfung'), (string) $data['status_counts']['in_pruefung']);
            echo alpenia_dashboard_render_metric_card(alpenia_travel_t('Neu'), (string) $data['status_counts']['neu']);
            ?>
        </div>

        <div class="business-overview-grid">
            <div class="business-list-card">
                <h3><?php echo esc_html(alpenia_travel_t('Top Reisearten')); ?></h3>
                <?php if (!empty($data['type_breakdown'])) : ?>
                    <div class="business-list">
                        <?php foreach ($data['type_breakdown'] as $item) : ?>
                            <article><div><strong><?php echo esc_html(alpenia_travel_translate_label($item['label'])); ?></strong><span><?php echo esc_html(sprintf(alpenia_travel_t('%1$d Teilnehmer · %2$d Reisen'), $item['participants'], $item['trips'])); ?></span></div><em><?php echo esc_html(alpenia_dashboard_business_money($item['revenue'])); ?></em></article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?><p class="business-empty"><?php echo esc_html(alpenia_travel_t('Noch keine Daten vorhanden.')); ?></p><?php endif; ?>
            </div>

            <div class="business-list-card">
                <h3><?php echo esc_html(alpenia_travel_t('Priorisierte Zahlungen')); ?></h3>
                <?php if (!empty($data['priority_payments'])) : ?>
                    <div class="business-list">
                        <?php foreach ($data['priority_payments'] as $item) : ?>
                            <article><div><span><?php echo esc_html($item['trip_title']); ?></span><a href="<?php echo esc_url(alpenia_dashboard_link(['edit_participant' => $item['participant_id']])); ?>"><?php echo esc_html($item['participant_name']); ?></a></div><em><?php echo esc_html(alpenia_dashboard_business_money($item['payment_open'])); ?></em></article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?><p class="business-empty"><?php echo esc_html(alpenia_travel_t('Aktuell keine offenen Zahlungen.')); ?></p><?php endif; ?>
            </div>

            <div class="business-list-card">
                <h3><?php echo esc_html(alpenia_travel_t('Backoffice-Fokus')); ?></h3>
                <?php if (!empty($data['trip_actions'])) : ?>
                    <div class="business-list">
                        <?php foreach ($data['trip_actions'] as $item) : ?>
                            <article><div><a href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $item['trip_id']])); ?>"><?php echo esc_html($item['title']); ?></a><span><?php echo esc_html(sprintf(alpenia_travel_t('%1$d Unterlagen · %2$d Zahlungen · %3$d in Prüfung'), $item['missing_docs'], $item['open_payments'], $item['in_review'])); ?></span></div><em><?php echo esc_html($item['action_score']); ?></em></article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?><p class="business-empty"><?php echo esc_html(alpenia_travel_t('Aktuell keine priorisierten Aufgaben.')); ?></p><?php endif; ?>
            </div>

            <div class="business-list-card">
                <h3><?php echo esc_html(alpenia_travel_t('Marketing-Fokus')); ?></h3>
                <?php if (!empty($data['marketing_focus'])) : ?>
                    <div class="business-list">
                        <?php foreach ($data['marketing_focus'] as $item) : ?>
                            <article><div><a href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $item['trip_id']])); ?>"><?php echo esc_html($item['title']); ?></a><span><?php echo esc_html(sprintf(alpenia_travel_t('%1$d%% Auslastung · %2$d/%3$d Plätze'), $item['occupancy'], $item['participants'], $item['capacity'])); ?></span></div><em><?php echo esc_html(alpenia_travel_t('Push')); ?></em></article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?><p class="business-empty"><?php echo esc_html(alpenia_travel_t('Keine offenen Reisen mit niedriger Auslastung.')); ?></p><?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
