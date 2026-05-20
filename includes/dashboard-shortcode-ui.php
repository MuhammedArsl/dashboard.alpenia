<?php
if (!defined('ABSPATH')) exit;

function alpenia_dashboard_logout_button() {
    ob_start();
    ?>
    <form method="post" class="alpenia-logout-form" style="display:inline;">
        <?php wp_nonce_field('alpenia_logout_action', 'alpenia_logout_nonce'); ?>
        <input type="hidden" name="alpenia_logout" value="1">
        <input type="hidden" name="alpenia_logout_intent" value="dashboard_logout">
        <button type="submit" class="btn-primary btn-logout"><?php echo esc_html(alpenia_travel_t("Logout")); ?></button>
    </form>
    <?php
    return ob_get_clean();
}

function alpenia_dashboard_sidebar_nav() {
    $items = [
        [
            'label' => alpenia_travel_t('Startseite'),
            'url' => alpenia_dashboard_link(),
            'active' => !isset($_GET['create_trip'], $_GET['add_participant'], $_GET['manage_users'], $_GET['view_trip'], $_GET['edit_participant'], $_GET['edit_trip']),
            'icon' => '⌂',
            'meta' => alpenia_travel_t('Übersicht'),
        ],
    ];

    if (alpenia_user_can_create_trip()) {
        $items[] = [
            'label' => alpenia_travel_t('Neue Reise erstellen'),
            'url' => alpenia_dashboard_link(['create_trip' => 1]),
            'active' => isset($_GET['create_trip']) || isset($_GET['edit_trip']),
            'icon' => '+',
            'meta' => alpenia_travel_t('Planung'),
        ];
    }

    $items[] = [
        'label' => alpenia_travel_t('Teilnehmer hinzufügen'),
        'url' => alpenia_dashboard_link(['add_participant' => 1]),
        'active' => isset($_GET['add_participant']) || isset($_GET['edit_participant']) || isset($_GET['view_trip']),
        'icon' => '👤',
        'meta' => alpenia_travel_t('Erfassung'),
    ];

    $items[] = [
        'label' => alpenia_travel_t('Papierkorb'),
        'url' => alpenia_dashboard_link(['trash_bin' => 1]),
        'active' => isset($_GET['trash_bin']),
        'icon' => '🗑',
        'meta' => alpenia_travel_t('Archiv'),
    ];

    if (alpenia_user_can_manage_users()) {
        $items[] = [
            'label' => alpenia_travel_t('Benutzerverwaltung'),
            'url' => alpenia_dashboard_link(['manage_users' => 1]),
            'active' => isset($_GET['manage_users']) || isset($_GET['dashboard_edit_user']),
            'icon' => '⚙',
            'meta' => alpenia_travel_t('Team'),
        ];
    }

    ob_start();
    ?>
    <aside class="dashboard-sidebar" aria-label="<?php echo esc_attr(alpenia_travel_t('Dashboard Navigation')); ?>">
        <div class="dashboard-sidebar__header dashboard-sidebar__header--compact">
            <span class="dashboard-sidebar__eyebrow"><?php echo esc_html(alpenia_travel_t('Menü')); ?></span>
        </div>
        <nav class="dashboard-sidebar__nav">
            <?php foreach ($items as $item) : ?>
                <a class="dashboard-sidebar__link <?php echo $item['active'] ? 'is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>">
                    <span class="dashboard-sidebar__icon" aria-hidden="true"><?php echo esc_html($item['icon']); ?></span>
                    <span class="dashboard-sidebar__copy">
                        <span><?php echo esc_html($item['meta']); ?></span>
                        <strong><?php echo esc_html($item['label']); ?></strong>
                    </span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <?php
    return ob_get_clean();
}

function alpenia_dashboard_language_switcher() {
    $de_url = alpenia_dashboard_link(array_merge($_GET, ['ui_lang' => 'de']));
    $tr_url = alpenia_dashboard_link(array_merge($_GET, ['ui_lang' => 'tr']));

    ob_start();
    ?>
    <div class="dashboard-language-switch" role="group" aria-label="<?php echo esc_attr(alpenia_travel_t('Plugin language switch')); ?>">
        <a class="lang-link <?php echo alpenia_travel_get_language() === 'de' ? 'active' : ''; ?>" href="<?php echo esc_url($de_url); ?>" title="DE" aria-label="DE">DE</a>
        <a class="lang-link <?php echo alpenia_travel_get_language() === 'tr' ? 'active' : ''; ?>" href="<?php echo esc_url($tr_url); ?>" title="TR" aria-label="TR">TR</a>
    </div>
    <?php
    return ob_get_clean();
}
