<?php
if (!defined('ABSPATH')) exit;

function alpenia_dashboard_get_per_page($key, $default = 20, $allowed = [10, 20, 50, 100]) {
    $value = isset($_GET[$key]) ? (int) $_GET[$key] : (int) $default;
    return in_array($value, $allowed, true) ? $value : (int) $default;
}

function alpenia_dashboard_trip_days_left($trip_id) {
    $start_date = (string) get_post_meta((int) $trip_id, 'start_date', true);
    if ($start_date === '') {
        return null;
    }

    $start_timestamp = strtotime($start_date);
    if ($start_timestamp === false) {
        return null;
    }

    $today_timestamp = strtotime(wp_date('Y-m-d'));
    return (int) floor(($start_timestamp - $today_timestamp) / DAY_IN_SECONDS);
}

function alpenia_dashboard_trip_timeline_badge($trip_id) {
    $days_left = alpenia_dashboard_trip_days_left($trip_id);
    if ($days_left === null) {
        return '';
    }

    if ($days_left < 0) {
        return '<span class="timeline-badge timeline-badge--overdue">' . esc_html(alpenia_travel_t('Gestartet')) . '</span>';
    }

    if ($days_left <= 14) {
        return '<span class="timeline-badge timeline-badge--urgent">' . esc_html(sprintf(alpenia_travel_t('%d Tage übrig'), $days_left)) . '</span>';
    }

    return '<span class="timeline-badge timeline-badge--planned">' . esc_html(sprintf(alpenia_travel_t('%d Tage bis Start'), $days_left)) . '</span>';
}

function alpenia_dashboard_render_pagination($current_page, $max_pages, $base_args, $page_arg) {
    $current_page = max(1, (int) $current_page);
    $max_pages = max(1, (int) $max_pages);

    if ($max_pages <= 1) {
        return '';
    }

    $base_args = array_filter((array) $base_args, function($value) {
        return $value !== '' && $value !== null;
    });

    $window_start = max(1, $current_page - 2);
    $window_end = min($max_pages, $current_page + 2);

    ob_start();
    ?>
    <nav class="alpenia-pagination" aria-label="<?php echo esc_attr(alpenia_travel_t('Seitennavigation')); ?>">
        <?php if ($current_page > 1) : ?>
            <a href="<?php echo esc_url(alpenia_dashboard_link(array_merge($base_args, [$page_arg => $current_page - 1]))); ?>"><?php echo esc_html(alpenia_travel_t('Zurück')); ?></a>
        <?php endif; ?>

        <?php if ($window_start > 1) : ?>
            <a href="<?php echo esc_url(alpenia_dashboard_link(array_merge($base_args, [$page_arg => 1]))); ?>">1</a>
            <?php if ($window_start > 2) : ?><span>…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($page = $window_start; $page <= $window_end; $page++) : ?>
            <?php if ($page === $current_page) : ?>
                <span class="is-active"><?php echo esc_html($page); ?></span>
            <?php else : ?>
                <a href="<?php echo esc_url(alpenia_dashboard_link(array_merge($base_args, [$page_arg => $page]))); ?>"><?php echo esc_html($page); ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($window_end < $max_pages) : ?>
            <?php if ($window_end < $max_pages - 1) : ?><span>…</span><?php endif; ?>
            <a href="<?php echo esc_url(alpenia_dashboard_link(array_merge($base_args, [$page_arg => $max_pages]))); ?>"><?php echo esc_html($max_pages); ?></a>
        <?php endif; ?>

        <?php if ($current_page < $max_pages) : ?>
            <a href="<?php echo esc_url(alpenia_dashboard_link(array_merge($base_args, [$page_arg => $current_page + 1]))); ?>"><?php echo esc_html(alpenia_travel_t('Weiter')); ?></a>
        <?php endif; ?>
    </nav>
    <?php
    return ob_get_clean();
}
