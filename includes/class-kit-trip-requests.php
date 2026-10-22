<?php
/**
 * Staff requests for administrators to create a delivery truck / trip.
 *
 * @package CourierFinancePlugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIT_Trip_Requests
{
    const OPTION_KEY = 'kit_trip_creation_requests';
    const EMAIL_OPTION = 'kit_email_admin_notifications';
    const COOLDOWN_SECONDS = 1800; // 30 minutes per requester while pending

    /**
     * Whether trip-request emails to admins are enabled (Settings).
     * Default off so offline/local testing does not attempt SMTP.
     */
    public static function email_admin_enabled(): bool
    {
        return (bool) get_option(self::EMAIL_OPTION, 0);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function all(): array
    {
        $rows = get_option(self::OPTION_KEY, []);
        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function pending(): array
    {
        return array_values(array_filter(self::all(), static function ($row) {
            return is_array($row) && (($row['status'] ?? '') === 'pending');
        }));
    }

    /**
     * Persist full list.
     *
     * @param list<array<string,mixed>> $rows
     */
    private static function save(array $rows): void
    {
        update_option(self::OPTION_KEY, array_values($rows), false);
    }

    /**
     * Users who can create delivery trucks (administrators).
     *
     * @return list<WP_User>
     */
    public static function trip_creator_users(): array
    {
        $users = get_users([
            'role'   => 'administrator',
            'fields' => 'all',
            'number' => 100,
        ]);
        return is_array($users) ? $users : [];
    }

    /**
     * Create a pending request and notify trip creators.
     *
     * @param string $note Optional message from the requester.
     * @return array{success:bool,message:string,request_id?:string,emailed?:int}
     */
    public static function create_request(string $note = ''): array
    {
        if (!is_user_logged_in()) {
            return ['success' => false, 'message' => __('You must be logged in to request a trip.', '08600-services-quotations')];
        }

        if (class_exists('KIT_User_Roles') && KIT_User_Roles::can_create_delivery_truck()) {
            return ['success' => false, 'message' => __('You can create trips yourself — use Create Trip.', '08600-services-quotations')];
        }

        if (!class_exists('KIT_User_Roles') || !KIT_User_Roles::can_view_waybills()) {
            return ['success' => false, 'message' => __('You are not allowed to request a trip.', '08600-services-quotations')];
        }

        $user = wp_get_current_user();
        $uid  = (int) $user->ID;
        $now  = time();
        $note = sanitize_textarea_field($note);
        if (strlen($note) > 500) {
            $note = substr($note, 0, 500);
        }

        foreach (self::pending() as $existing) {
            if ((int) ($existing['requester_id'] ?? 0) === $uid) {
                $created = (int) ($existing['created_at'] ?? 0);
                if ($created > 0 && ($now - $created) < self::COOLDOWN_SECONDS) {
                    return [
                        'success' => false,
                        'message' => __('You already have a pending trip request. Management has been notified.', '08600-services-quotations'),
                    ];
                }
            }
        }

        $request_id = 'tr_' . $uid . '_' . $now;
        $display    = trim((string) $user->display_name);
        if ($display === '') {
            $display = (string) $user->user_login;
        }

        $row = [
            'id'            => $request_id,
            'requester_id'  => $uid,
            'requester_name'=> $display,
            'note'          => $note,
            'created_at'    => $now,
            'status'        => 'pending',
        ];

        $all   = self::all();
        $all[] = $row;
        // Keep the option lean — drop acknowledged entries older than 14 days.
        $cutoff = $now - (14 * DAY_IN_SECONDS);
        $all = array_values(array_filter($all, static function ($r) use ($cutoff) {
            if (!is_array($r)) {
                return false;
            }
            $status = (string) ($r['status'] ?? '');
            $created = (int) ($r['created_at'] ?? 0);
            if ($status === 'pending') {
                return true;
            }
            return $created >= $cutoff;
        }));
        self::save($all);

        $emailed = self::notify_trip_creators($row);

        return [
            'success'    => true,
            'message'    => __('Request sent. Administrators who can create trips have been notified.', '08600-services-quotations'),
            'request_id' => $request_id,
            'emailed'    => $emailed,
        ];
    }

    /**
     * Mark a request acknowledged (dismissed) by an admin.
     */
    public static function acknowledge(string $request_id): array
    {
        if (!class_exists('KIT_User_Roles') || !KIT_User_Roles::can_create_delivery_truck()) {
            return ['success' => false, 'message' => __('Only administrators can dismiss trip requests.', '08600-services-quotations')];
        }

        $request_id = sanitize_text_field($request_id);
        if ($request_id === '') {
            return ['success' => false, 'message' => __('Invalid request.', '08600-services-quotations')];
        }

        $found = false;
        $all = self::all();
        foreach ($all as $i => $row) {
            if (!is_array($row) || ($row['id'] ?? '') !== $request_id) {
                continue;
            }
            $all[$i]['status'] = 'acknowledged';
            $all[$i]['acknowledged_at'] = time();
            $all[$i]['acknowledged_by'] = get_current_user_id();
            $found = true;
            break;
        }

        if (!$found) {
            return ['success' => false, 'message' => __('Request not found.', '08600-services-quotations')];
        }

        self::save($all);
        return ['success' => true, 'message' => __('Request dismissed.', '08600-services-quotations')];
    }

    /**
     * In-app + optional email notify for trip creators.
     *
     * @param array<string,mixed> $row
     * @return int Number of emails successfully queued/sent.
     */
    private static function notify_trip_creators(array $row): int
    {
        $creators = self::trip_creator_users();
        $requester = (string) ($row['requester_name'] ?? __('A staff member', '08600-services-quotations'));
        $note = trim((string) ($row['note'] ?? ''));
        $request_id = (string) ($row['id'] ?? '');

        $create_url = function_exists('kit_employee_portal_url')
            ? kit_employee_portal_url('08600-trip-create')
            : admin_url('admin.php?page=08600-trip-create');

        $summary = sprintf(
            /* translators: %s: requester display name */
            __('%s requested that management create a delivery truck / trip.', '08600-services-quotations'),
            $requester
        );
        if ($note !== '') {
            $summary .= ' ' . sprintf(
                /* translators: %s: optional note */
                __('Note: %s', '08600-services-quotations'),
                $note
            );
        }

        foreach ($creators as $creator) {
            if (!$creator instanceof WP_User) {
                continue;
            }
            // Skip notifying the requester if they somehow have admin role.
            if ((int) $creator->ID === (int) ($row['requester_id'] ?? 0)) {
                continue;
            }
            $notices = get_user_meta($creator->ID, 'kit_trip_request_notices', true);
            if (!is_array($notices)) {
                $notices = [];
            }
            $notices[] = [
                'request_id' => $request_id,
                'message'    => $summary,
                'created_at' => (int) ($row['created_at'] ?? time()),
                'url'        => $create_url,
            ];
            // Cap stored notices.
            if (count($notices) > 20) {
                $notices = array_slice($notices, -20);
            }
            update_user_meta($creator->ID, 'kit_trip_request_notices', $notices);
        }

        if (!self::email_admin_enabled()) {
            return 0;
        }

        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] Trip creation requested', '08600-services-quotations'),
            wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)
        );

        $body_lines = [
            $summary,
            '',
            __('Open Create Trip:', '08600-services-quotations') . ' ' . $create_url,
            '',
            __('You received this because Email admin notifications is enabled in Settings.', '08600-services-quotations'),
        ];
        $body = implode("\n", $body_lines);
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        $sent = 0;
        foreach ($creators as $creator) {
            if (!$creator instanceof WP_User) {
                continue;
            }
            if ((int) $creator->ID === (int) ($row['requester_id'] ?? 0)) {
                continue;
            }
            $email = sanitize_email((string) $creator->user_email);
            if ($email === '' || !is_email($email)) {
                continue;
            }
            if (wp_mail($email, $subject, $body, $headers)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Button for staff who cannot create trips.
     */
    public static function render_request_button(array $options = []): string
    {
        if (class_exists('KIT_User_Roles') && KIT_User_Roles::can_create_delivery_truck()) {
            return '';
        }
        if (!is_user_logged_in()) {
            return '';
        }

        $label = $options['label'] ?? __('Request management to create a delivery truck', '08600-services-quotations');
        $nonce = wp_create_nonce('kit_request_trip_creation');

        return KIT_Commons::renderButton(
            $label,
            'warning',
            $options['size'] ?? 'md',
            [
                'type'         => 'button',
                'classes'      => trim('kit-request-trip-creation ' . ($options['classes'] ?? '')),
                'icon'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />',
                'iconPosition' => 'left',
                'ariaLabel'    => $label,
                'noLoading'    => false,
                'data-kit-request-trip' => '1',
                'data-nonce'   => $nonce,
            ]
        );
    }

    /**
     * Banner listing pending requests for trip creators.
     */
    public static function render_pending_banner(): string
    {
        if (!class_exists('KIT_User_Roles') || !KIT_User_Roles::can_create_delivery_truck()) {
            return '';
        }

        $pending = self::pending();
        if ($pending === []) {
            return '';
        }

        $create_url = function_exists('kit_using_employee_portal') && kit_using_employee_portal()
            ? kit_employee_portal_url('08600-trip-create')
            : admin_url('admin.php?page=08600-trip-create');

        $ack_nonce = wp_create_nonce('kit_acknowledge_trip_request');

        ob_start();
        ?>
        <div class="kit-trip-request-banner mb-4 border border-amber-300 bg-amber-50 text-amber-950 px-4 py-3" role="status">
            <p class="font-medium m-0 mb-2">
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: %d: pending count */
                        _n(
                            '%d pending trip creation request',
                            '%d pending trip creation requests',
                            count($pending),
                            '08600-services-quotations'
                        ),
                        count($pending)
                    )
                );
                ?>
            </p>
            <ul class="m-0 pl-4 list-disc space-y-2 text-sm">
                <?php foreach ($pending as $row) :
                    $who = (string) ($row['requester_name'] ?? __('Staff', '08600-services-quotations'));
                    $when = (int) ($row['created_at'] ?? 0);
                    $ago = $when > 0 ? human_time_diff($when, time()) : '';
                    $note = trim((string) ($row['note'] ?? ''));
                    $rid = (string) ($row['id'] ?? '');
                    ?>
                    <li class="kit-trip-request-item" data-request-id="<?php echo esc_attr($rid); ?>">
                        <span>
                            <?php
                            echo esc_html(
                                $ago !== ''
                                    ? sprintf(
                                        /* translators: 1: name, 2: relative time */
                                        __('%1$s requested a trip %2$s ago', '08600-services-quotations'),
                                        $who,
                                        $ago
                                    )
                                    : sprintf(
                                        /* translators: %s: name */
                                        __('%s requested a trip', '08600-services-quotations'),
                                        $who
                                    )
                            );
                            ?>
                            <?php if ($note !== '') : ?>
                                <span class="block text-amber-900/80 mt-0.5"><?php echo esc_html($note); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="inline-flex flex-wrap gap-2 mt-1">
                            <a class="underline font-medium" href="<?php echo esc_url($create_url); ?>">
                                <?php esc_html_e('Create Trip', '08600-services-quotations'); ?>
                            </a>
                            <button type="button"
                                class="kit-acknowledge-trip-request underline text-amber-900/80 hover:text-amber-950"
                                data-request-id="<?php echo esc_attr($rid); ?>"
                                data-nonce="<?php echo esc_attr($ack_nonce); ?>">
                                <?php esc_html_e('Dismiss', '08600-services-quotations'); ?>
                            </button>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Shared front-end script (once per page).
     */
    public static function render_script(): void
    {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;
        $ajax = admin_url('admin-ajax.php');
        ?>
        <script>
        (function () {
            if (window.KIT_TripRequestsBound) return;
            window.KIT_TripRequestsBound = true;

            var ajaxUrl = (typeof window.ajaxurl !== 'undefined' && window.ajaxurl)
                ? window.ajaxurl
                : <?php echo wp_json_encode($ajax); ?>;

            function toast(msg, type) {
                type = type || 'info';
                if (window.KITToast && typeof jQuery !== 'undefined') {
                    window.KITToast.show(msg, type, type === 'success' ? 'Success' : (type === 'error' ? 'Error' : 'Notice'));
                    return;
                }
                window.alert(msg);
            }

            function post(action, data) {
                var body = new FormData();
                body.append('action', action);
                Object.keys(data || {}).forEach(function (k) {
                    body.append(k, data[k]);
                });
                return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
                    .then(function (r) { return r.json(); });
            }

            document.addEventListener('click', function (e) {
                var reqBtn = e.target.closest('[data-kit-request-trip="1"]');
                if (reqBtn) {
                    e.preventDefault();
                    if (reqBtn.disabled) return;

                    var note = window.prompt(
                        <?php echo wp_json_encode(__('Optional note for management (route, urgency, etc.). Leave blank to send without a note:', '08600-services-quotations')); ?>,
                        ''
                    );
                    if (note === null) {
                        return; // cancelled
                    }

                    reqBtn.disabled = true;
                    post('kit_request_trip_creation', {
                        nonce: reqBtn.getAttribute('data-nonce') || '',
                        note: note || ''
                    }).then(function (res) {
                        var ok = !!(res && res.success);
                        var msg = (res && res.data && res.data.message) ? res.data.message
                            : (res && res.data && typeof res.data === 'string' ? res.data
                            : (ok ? 'Request sent.' : 'Could not send request.'));
                        toast(msg, ok ? 'success' : 'error');
                    }).catch(function () {
                        toast(<?php echo wp_json_encode(__('Could not send request. Check your connection.', '08600-services-quotations')); ?>, 'error');
                    }).finally(function () {
                        reqBtn.disabled = false;
                        if (window.KIT && KIT.Button && typeof KIT.Button.setLoading === 'function') {
                            KIT.Button.setLoading(reqBtn, false);
                        }
                    });
                    return;
                }

                var ackBtn = e.target.closest('.kit-acknowledge-trip-request');
                if (ackBtn) {
                    e.preventDefault();
                    var rid = ackBtn.getAttribute('data-request-id') || '';
                    post('kit_acknowledge_trip_request', {
                        nonce: ackBtn.getAttribute('data-nonce') || '',
                        request_id: rid
                    }).then(function (res) {
                        var ok = !!(res && res.success);
                        if (ok) {
                            var item = ackBtn.closest('.kit-trip-request-item');
                            if (item) item.remove();
                            var banner = document.querySelector('.kit-trip-request-banner');
                            if (banner && !banner.querySelector('.kit-trip-request-item')) {
                                banner.remove();
                            }
                        }
                        var msg = (res && res.data && res.data.message) ? res.data.message
                            : (ok ? 'Dismissed.' : 'Could not dismiss.');
                        toast(msg, ok ? 'success' : 'error');
                    }).catch(function () {
                        toast(<?php echo wp_json_encode(__('Could not dismiss request.', '08600-services-quotations')); ?>, 'error');
                    });
                }
            });
        })();
        </script>
        <?php
    }

    public static function ajax_request_trip_creation(): void
    {
        check_ajax_referer('kit_request_trip_creation', 'nonce');
        $note = isset($_POST['note']) ? wp_unslash((string) $_POST['note']) : '';
        $result = self::create_request($note);
        if (!empty($result['success'])) {
            wp_send_json_success($result);
        }
        wp_send_json_error($result);
    }

    public static function ajax_acknowledge_trip_request(): void
    {
        check_ajax_referer('kit_acknowledge_trip_request', 'nonce');
        $id = isset($_POST['request_id']) ? sanitize_text_field(wp_unslash((string) $_POST['request_id'])) : '';
        $result = self::acknowledge($id);
        if (!empty($result['success'])) {
            wp_send_json_success($result);
        }
        wp_send_json_error($result);
    }

    public static function init(): void
    {
        add_action('wp_ajax_kit_request_trip_creation', [self::class, 'ajax_request_trip_creation']);
        add_action('wp_ajax_kit_acknowledge_trip_request', [self::class, 'ajax_acknowledge_trip_request']);
    }
}
