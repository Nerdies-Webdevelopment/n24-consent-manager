<?php
/**
 * Plugin Name: N24 Consent Manager
 * Description: DSGVO Consent-Banner mit einstellbaren Farben, Icon, Texten und Cookie-Einstellungen.
 * Version: 1.8.43
 * Author: Nerdies24
 * Text Domain: n24-consent-manager
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

final class N24_Consent_Manager
{
    private const VERSION = '1.8.43';
    private const TEXT_DOMAIN = 'n24-consent-manager';
    private const OPTION_NAME = 'n24_consent_manager_options';
    private const LOG_TABLE_VERSION = '1.0';
    private const LOG_TABLE_VERSION_OPTION = 'n24_consent_manager_log_table_version';
    private const LEGACY_OPTION_NAMES = [
        'conset_manager_options',
        'conny_consent_manager_options',
    ];

    private const TEXT_FIELDS = [
        'banner_version',
        'privacy_policy_version',
        'dialog_title',
        'tab_overview',
        'tab_details',
        'tab_history',
        'intro_text',
        'necessary_label',
        'necessary_info',
        'statistics_label',
        'statistics_inactive_label',
        'statistics_info',
        'statistics_inactive_info',
        'marketing_label',
        'marketing_inactive_label',
        'marketing_info',
        'marketing_inactive_info',
        'external_media_label',
        'external_media_inactive_label',
        'external_media_info',
        'external_media_inactive_info',
        'info_default',
        'details_intro',
        'history_intro',
        'consent_id_label',
        'history_empty',
        'reject_button',
        'accept_all_button',
        'save_button',
        'customize_button',
        'settings_link',
        'floating_aria_label',
        'service_always_on',
        'service_description_label',
        'service_provider_label',
        'service_cookies_label',
        'service_privacy_label',
        'service_cookie_policy_label',
        'service_legal_basis_label',
        'service_third_country_label',
        'service_recipient_country_label',
        'service_safeguards_label',
        'service_count_single',
        'service_count_plural',
        'cookie_name_label',
        'cookie_expiry_label',
        'cookie_purpose_label',
        'history_date_label',
        'history_status_label',
        'necessary_service_name',
        'necessary_service_purpose',
        'necessary_cookie_expiry',
        'necessary_cookie_type',
        'necessary_cookie_purpose',
        'content_blocker_title',
        'content_blocker_text',
        'content_blocker_button',
        'content_blocker_always_button',
        'content_blocker_missing_service_text',
    ];

    private const COLOR_FIELDS = [
        'color_overlay',
        'color_modal_background',
        'color_panel_background',
        'color_text_primary',
        'color_text_secondary',
        'color_text_muted',
        'color_accent',
        'color_accent_light',
        'color_accent_dark',
        'color_border',
        'color_border_hover',
        'color_button_text',
        'color_floating_background',
        'color_floating_hover_background',
        'color_box_icon',
        'color_floating_icon',
        'content_blocker_link_color',
        'content_blocker_link_hover_color',
        'content_blocker_primary_button_background',
        'content_blocker_primary_button_text',
        'content_blocker_primary_button_hover_background',
        'content_blocker_primary_button_hover_text',
        'content_blocker_secondary_button_background',
        'content_blocker_secondary_button_text',
        'content_blocker_secondary_button_hover_background',
        'content_blocker_secondary_button_hover_text',
    ];

    private const BOOLEAN_FIELDS = [
        'plugin_enabled',
        'login_enabled',
        'statistics_service_enabled',
        'marketing_service_enabled',
        'external_media_service_enabled',
        'content_blocker_enabled',
        'content_blocker_facebook_enabled',
        'content_blocker_youtube_enabled',
        'content_blocker_vimeo_enabled',
        'content_blocker_google_maps_enabled',
        'content_blocker_instagram_enabled',
        'content_blocker_openstreetmap_enabled',
        'content_blocker_soundcloud_enabled',
        'content_blocker_spotify_enabled',
        'content_blocker_x_enabled',
    ];

    private const SERVICE_COLLECTION_FIELDS = [
        'statistics_services',
        'marketing_services',
        'external_media_services',
    ];

    private const ARRAY_FIELDS = [
        'deleted_preset_keys',
        'content_blocker_service_settings',
    ];

    private const DEPRECATED_SERVICE_PRESET_KEYS = [
        'microsoft_clarity',
    ];

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('wp_head', [$this, 'print_anti_flicker_script'], 0);
        add_action('login_head', [$this, 'print_login_anti_flicker_script'], 0);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('login_enqueue_scripts', [$this, 'enqueue_login_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('init', [$this, 'maybe_install_consent_log_table']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_shortcode('n24_consent_settings', [$this, 'render_cookie_settings_shortcode']);
        add_shortcode('conset_cookie_settings', [$this, 'render_cookie_settings_shortcode']);
        add_shortcode('conny_cookie_settings', [$this, 'render_cookie_settings_shortcode']);
        add_shortcode('n24_instagram', [$this, 'render_instagram_shortcode']);
        add_filter('the_content', [$this, 'filter_blocked_content'], 20);
        add_filter('widget_text', [$this, 'filter_blocked_content'], 20);
        add_filter('widget_text_content', [$this, 'filter_blocked_content'], 20);
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    private function is_plugin_enabled(): bool
    {
        $options = get_option(self::OPTION_NAME, []);

        if (!is_array($options)) {
            return true;
        }

        return ($options['plugin_enabled'] ?? '1') === '1';
    }

    private function is_login_enabled(): bool
    {
        $options = get_option(self::OPTION_NAME, []);

        if (!is_array($options)) {
            return false;
        }

        return $this->is_plugin_enabled() && ($options['login_enabled'] ?? '0') === '1';
    }

    public static function activate(): void
    {
        self::install_consent_log_table();

        if (get_option(self::OPTION_NAME)) {
            return;
        }

        foreach (self::LEGACY_OPTION_NAMES as $legacy_option_name) {
            $legacy = get_option($legacy_option_name, []);

            if (is_array($legacy) && $legacy) {
                add_option(self::OPTION_NAME, wp_parse_args($legacy, self::default_options()));
                return;
            }
        }

        add_option(self::OPTION_NAME, self::default_options());
    }

    public function maybe_install_consent_log_table(): void
    {
        if (get_option(self::LOG_TABLE_VERSION_OPTION) !== self::LOG_TABLE_VERSION) {
            self::install_consent_log_table();
        }
    }

    private static function get_consent_log_table_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'n24_consent_manager_logs';
    }

    private static function install_consent_log_table(): void
    {
        global $wpdb;

        $table_name = self::get_consent_log_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta(
            "CREATE TABLE {$table_name} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                uid varchar(64) NOT NULL,
                banner_version varchar(64) NOT NULL DEFAULT '',
                privacy_policy_version varchar(128) NOT NULL DEFAULT '',
                consent_time datetime NOT NULL,
                categories longtext NULL,
                services longtext NULL,
                consent_json longtext NULL,
                ip_hash char(64) NOT NULL DEFAULT '',
                user_agent_hash char(64) NOT NULL DEFAULT '',
                is_latest tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY uid (uid),
                KEY consent_time (consent_time),
                KEY is_latest (is_latest)
            ) {$charset_collate};"
        );

        update_option(self::LOG_TABLE_VERSION_OPTION, self::LOG_TABLE_VERSION, false);
    }

    public function enqueue_assets(): void
    {
        if (is_admin()) {
            return;
        }

        if (!$this->is_plugin_enabled()) {
            return;
        }

        $this->enqueue_frontend_assets();
    }

    public function enqueue_login_assets(): void
    {
        if (!$this->is_login_enabled()) {
            return;
        }

        $this->enqueue_frontend_assets();
    }

    private function enqueue_frontend_assets(): void
    {
        wp_enqueue_style(
            'n24-consent-manager',
            plugin_dir_url(__FILE__) . 'assets/css/n24-consent-manager.css',
            [],
            self::VERSION
        );

        wp_add_inline_style('n24-consent-manager', $this->build_inline_css());

        wp_enqueue_script(
            'n24-consent-manager',
            plugin_dir_url(__FILE__) . 'assets/js/n24-consent-manager.js',
            [],
            self::VERSION,
            true
        );

        wp_localize_script(
            'n24-consent-manager',
            'N24ConsentManagerSettings',
            $this->get_frontend_settings()
        );
    }

    public function enqueue_admin_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'settings_page_n24-consent-manager') {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_media();
    }

    public function print_anti_flicker_script(): void
    {
        if (is_admin()) {
            return;
        }

        if (!$this->is_plugin_enabled()) {
            return;
        }

        $settings = $this->get_frontend_settings();
        $this->print_anti_flicker_markup($settings);
    }

    public function print_login_anti_flicker_script(): void
    {
        if (!$this->is_login_enabled()) {
            return;
        }

        $settings = $this->get_frontend_settings();
        $this->print_anti_flicker_markup($settings);
    }

    private function print_anti_flicker_markup(array $settings): void
    {
        ?>
<style id="n24-consent-manager-anti-flicker">
html.consent-pending body > :not(#consent-banner):not(#wpadminbar):not(script):not(style) {
    visibility: hidden !important;
}

html.consent-pending #consent-banner,
html.consent-pending #consent-banner * {
    visibility: visible !important;
}
</style>
<script>
(function () {
    var settings = <?php echo wp_json_encode($settings); ?>;
    var path = window.location.pathname;
    var isLegal = (settings.legalPathSlugs || []).some(function (slug) {
        return slug && path.indexOf(slug) > -1;
    });

    try {
        if (!window.localStorage.getItem(settings.storageKey) && !isLegal) {
            document.documentElement.classList.add('consent-pending');
        }
    } catch (error) {
        if (!isLegal) {
            document.documentElement.classList.add('consent-pending');
        }
    }
})();
</script>
        <?php
    }

    public function add_settings_page(): void
    {
        add_options_page(
            __('N24 Consent Manager', self::TEXT_DOMAIN),
            __('N24 Consent Manager', self::TEXT_DOMAIN),
            'manage_options',
            'n24-consent-manager',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'n24_consent_manager',
            self::OPTION_NAME,
            [
                'sanitize_callback' => [$this, 'sanitize_options'],
                'default' => self::default_options(),
            ]
        );
    }

    public function register_rest_routes(): void
    {
        register_rest_route(
            'n24-consent-manager/v1',
            '/consent-log',
            [
                'methods' => 'POST',
                'callback' => [$this, 'handle_consent_log_request'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function handle_consent_log_request(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $payload = $request->get_json_params();

        if (!is_array($payload)) {
            return new WP_REST_Response(['success' => false], 400);
        }

        $uid = sanitize_key((string) ($payload['uid'] ?? ''));

        if ($uid === '') {
            return new WP_REST_Response(['success' => false], 400);
        }

        self::install_consent_log_table();

        $categories = [
            'necessary' => !empty($payload['settings']['necessary']),
            'statistics' => !empty($payload['settings']['statistics']),
            'marketing' => !empty($payload['settings']['marketing']),
            'external_media' => !empty($payload['settings']['external_media']),
        ];
        $services = is_array($payload['services'] ?? null) ? $payload['services'] : [];
        $consent_time = sanitize_text_field((string) ($payload['timestamp'] ?? ''));
        $timestamp = strtotime($consent_time);

        if (!$timestamp) {
            $timestamp = time();
        }

        $ip_hash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? '') . wp_salt('nonce'));
        $user_agent_hash = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '') . wp_salt('auth'));
        $table_name = self::get_consent_log_table_name();

        $wpdb->update($table_name, ['is_latest' => 0], ['uid' => $uid], ['%d'], ['%s']);
        $inserted = $wpdb->insert(
            $table_name,
            [
                'uid' => $uid,
                'banner_version' => sanitize_text_field((string) ($payload['bannerVersion'] ?? '')),
                'privacy_policy_version' => sanitize_text_field((string) ($payload['privacyPolicyVersion'] ?? '')),
                'consent_time' => gmdate('Y-m-d H:i:s', $timestamp),
                'categories' => wp_json_encode($categories),
                'services' => wp_json_encode($this->sanitize_consent_service_map($services)),
                'consent_json' => wp_json_encode($this->sanitize_consent_log_payload($payload)),
                'ip_hash' => $ip_hash,
                'user_agent_hash' => $user_agent_hash,
                'is_latest' => 1,
                'created_at' => current_time('mysql', true),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s",
                gmdate('Y-m-d H:i:s', strtotime('-180 days'))
            )
        );

        return new WP_REST_Response(['success' => (bool) $inserted], $inserted ? 201 : 500);
    }

    public function sanitize_options(array $input): array
    {
        $defaults = self::default_options();
        $output = [];

        foreach ($defaults as $key => $value) {
            if (in_array($key, self::SERVICE_COLLECTION_FIELDS, true)) {
                $output[$key] = $this->sanitize_service_collection($input[$key] ?? []);
                continue;
            }

            if (in_array($key, self::ARRAY_FIELDS, true)) {
                $output[$key] = $key === 'content_blocker_service_settings'
                    ? $this->sanitize_content_blocker_service_settings($input[$key] ?? [])
                    : $this->sanitize_key_list($input[$key] ?? []);
                continue;
            }

            if ($key === 'content_blocker_embed_images') {
                $output[$key] = $this->sanitize_url_map($input[$key] ?? []);
                continue;
            }

            if ($key === 'content_blocker_embed_titles') {
                $output[$key] = $this->sanitize_text_map($input[$key] ?? []);
                continue;
            }

            if (in_array($key, self::BOOLEAN_FIELDS, true)) {
                $output[$key] = !empty($input[$key]) ? '1' : '0';
                continue;
            }

            if (in_array($key, self::COLOR_FIELDS, true)) {
                $output[$key] = $this->sanitize_color($input[$key] ?? $value, $value);
                continue;
            }

            if (in_array($key, self::TEXT_FIELDS, true)) {
                $output[$key] = sanitize_text_field($input[$key] ?? $value);
                continue;
            }

            $output[$key] = sanitize_text_field($input[$key] ?? $value);
        }

        $output['privacy_url'] = esc_url_raw($input['privacy_url'] ?? $defaults['privacy_url']);
        $output['imprint_url'] = esc_url_raw($input['imprint_url'] ?? $defaults['imprint_url']);
        $output['provider_name'] = sanitize_text_field($input['provider_name'] ?? $defaults['provider_name']);
        $output['provider_address'] = sanitize_text_field($input['provider_address'] ?? $defaults['provider_address']);
        $output['storage_key'] = sanitize_key($input['storage_key'] ?? $defaults['storage_key']) ?: $defaults['storage_key'];
        $output['icon_svg'] = $this->sanitize_icon_svg($input['icon_svg'] ?? $defaults['icon_svg']);
        $output['box_icon_svg'] = $this->sanitize_icon_svg($input['box_icon_svg'] ?? $input['icon_svg'] ?? $defaults['box_icon_svg']);
        $output['floating_icon_svg'] = $this->sanitize_icon_svg($input['floating_icon_svg'] ?? $input['icon_svg'] ?? $defaults['floating_icon_svg']);
        $output['statistics_service_privacy_url'] = esc_url_raw($input['statistics_service_privacy_url'] ?? $defaults['statistics_service_privacy_url']);
        $output['marketing_service_privacy_url'] = esc_url_raw($input['marketing_service_privacy_url'] ?? $defaults['marketing_service_privacy_url']);
        $output['external_media_service_privacy_url'] = esc_url_raw($input['external_media_service_privacy_url'] ?? $defaults['external_media_service_privacy_url']);
        $output['deleted_preset_keys'] = array_values(array_diff(
            $output['deleted_preset_keys'] ?? [],
            $this->get_present_preset_keys($output)
        ));
        $output['content_blocker_embed_images'] = $this->sanitize_url_map($input['content_blocker_embed_images'] ?? []);
        $output['content_blocker_embed_titles'] = $this->sanitize_text_map($input['content_blocker_embed_titles'] ?? []);
        $output = $this->normalize_service_categories($output);

        return wp_parse_args($output, $defaults);
    }

    private function sanitize_consent_service_map(array $services): array
    {
        $clean = [];

        foreach ($services as $service_id => $allowed) {
            $clean_id = sanitize_key((string) $service_id);

            if ($clean_id === '') {
                continue;
            }

            $clean[$clean_id] = (bool) $allowed;
        }

        return $clean;
    }

    private function sanitize_consent_log_payload(array $payload): array
    {
        return [
            'uid' => sanitize_key((string) ($payload['uid'] ?? '')),
            'timestamp' => sanitize_text_field((string) ($payload['timestamp'] ?? '')),
            'bannerVersion' => sanitize_text_field((string) ($payload['bannerVersion'] ?? '')),
            'privacyPolicyVersion' => sanitize_text_field((string) ($payload['privacyPolicyVersion'] ?? '')),
            'settings' => [
                'necessary' => !empty($payload['settings']['necessary']),
                'statistics' => !empty($payload['settings']['statistics']),
                'marketing' => !empty($payload['settings']['marketing']),
                'external_media' => !empty($payload['settings']['external_media']),
            ],
            'services' => $this->sanitize_consent_service_map(is_array($payload['services'] ?? null) ? $payload['services'] : []),
        ];
    }

    private function has_active_consent_plugin_conflict(): bool
    {
        $active_plugins = (array) get_option('active_plugins', []);
        $known_conflicts = [
            'complianz-gdpr/complianz-gpdr.php',
            'complianz-gdpr/complianz-gdpr.php',
            'cookie-law-info/cookie-law-info.php',
            'cookiebot/cookiebot.php',
            'usercentrics/usercentrics.php',
            'real-cookie-banner-pro/index.php',
            'real-cookie-banner/index.php',
        ];

        return (bool) array_intersect($known_conflicts, $active_plugins);
    }

    private function normalize_service_categories(array $options): array
    {
        $external_media_services = $options['external_media_services'] ?? [];
        $marketing_services = $options['marketing_services'] ?? [];

        if (!is_array($external_media_services)) {
            $external_media_services = [];
        }

        if (!is_array($marketing_services)) {
            $marketing_services = [];
        }

        foreach ($marketing_services as $index => $service) {
            if (!is_array($service) || !self::is_external_media_preset_key($service['preset_key'] ?? '')) {
                continue;
            }

            $external_media_services[] = $service;
            unset($marketing_services[$index]);
        }

        $options['marketing_services'] = $this->unique_services_by_identity(array_values($marketing_services));
        $options['external_media_services'] = $this->unique_services_by_identity(array_values($external_media_services));

        return $options;
    }

    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options = $this->get_options();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('N24 Consent Manager', self::TEXT_DOMAIN); ?></h1>
            <?php if ($this->has_active_consent_plugin_conflict()) : ?>
                <div class="notice notice-warning">
                    <p><strong><?php echo esc_html__('Hinweis:', self::TEXT_DOMAIN); ?></strong> <?php echo esc_html__('Es ist mindestens ein weiteres Consent-Plugin aktiv. Mehrere Consent-Manager können sich gegenseitig beeinflussen und externe Dienste trotz korrekter Einstellungen laden oder doppelt blockieren. Für einen verlässlichen Test sollte nur ein Consent-Manager aktiv sein.', self::TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('n24_consent_manager'); ?>
                <div class="n24cm-deleted-preset-keys">
                    <?php foreach (($options['deleted_preset_keys'] ?? []) as $deleted_preset_key) : ?>
                        <input type="hidden" class="n24cm-deleted-preset-key" name="<?php echo esc_attr(self::OPTION_NAME); ?>[deleted_preset_keys][]" value="<?php echo esc_attr($deleted_preset_key); ?>">
                    <?php endforeach; ?>
                </div>

                <div class="n24-consent-manager-layout">
                    <div class="n24-consent-manager-main">
                        <div class="n24-consent-manager-tabs">
                            <h2 class="nav-tab-wrapper n24-consent-manager-tab-nav" role="tablist">
                                <button type="button" class="nav-tab nav-tab-active" id="n24cm-tab-general" role="tab" aria-selected="true" aria-controls="n24cm-panel-general" data-tab="general"><?php echo esc_html__('Grundeinstellungen', self::TEXT_DOMAIN); ?></button>
                                <button type="button" class="nav-tab" id="n24cm-tab-statistics" role="tab" aria-selected="false" aria-controls="n24cm-panel-statistics" data-tab="statistics"><?php echo esc_html__('Statistik', self::TEXT_DOMAIN); ?></button>
                                <button type="button" class="nav-tab" id="n24cm-tab-marketing" role="tab" aria-selected="false" aria-controls="n24cm-panel-marketing" data-tab="marketing"><?php echo esc_html__('Marketing', self::TEXT_DOMAIN); ?></button>
                                <button type="button" class="nav-tab" id="n24cm-tab-external-media" role="tab" aria-selected="false" aria-controls="n24cm-panel-external-media" data-tab="external_media"><?php echo esc_html__('Externe Medien', self::TEXT_DOMAIN); ?></button>
                                <button type="button" class="nav-tab" id="n24cm-tab-templates" role="tab" aria-selected="false" aria-controls="n24cm-panel-templates" data-tab="templates"><?php echo esc_html__('Vorlagen', self::TEXT_DOMAIN); ?></button>
                                <button type="button" class="nav-tab" id="n24cm-tab-cookie-box-layout" role="tab" aria-selected="false" aria-controls="n24cm-panel-cookie-box-layout" data-tab="cookie_box_layout"><?php echo esc_html__('Cookie Box Layout', self::TEXT_DOMAIN); ?></button>
                            </h2>

                            <div class="n24-consent-manager-tab-panel is-active" id="n24cm-panel-general" role="tabpanel" aria-labelledby="n24cm-tab-general" data-panel="general">
                                <table class="form-table" role="presentation">
                                    <?php
                                    $this->render_checkbox_row('plugin_enabled', __('N24 Consent Manager einschalten', self::TEXT_DOMAIN), $options);
                                    $this->render_checkbox_row('login_enabled', __('Auch für Login-Seite wp-admin / wp-login.php einschalten', self::TEXT_DOMAIN), $options);
                                    $this->render_input_row('privacy_url', __('URL Datenschutzerklärung', self::TEXT_DOMAIN), $options);
                                    $this->render_input_row('imprint_url', __('URL Impressum', self::TEXT_DOMAIN), $options);
                                    $this->render_input_row('provider_name', __('Anbietername', self::TEXT_DOMAIN), $options);
                                    $this->render_input_row('provider_address', __('Anbieteradresse', self::TEXT_DOMAIN), $options);
                                    $this->render_input_row('storage_key', __('Storage-/Cookie-Name', self::TEXT_DOMAIN), $options);
                                    $this->render_input_row('banner_version', __('Banner-Version', self::TEXT_DOMAIN), $options, __('Erhöhe diese Version, wenn du Banner-Texte, Kategorien, Dienste oder die Einwilligungslogik wesentlich änderst. So lässt sich später nachvollziehen, welcher Banner-Version zugestimmt wurde.', self::TEXT_DOMAIN));
                                    $this->render_input_row('privacy_policy_version', __('Datenschutzerklärung-Version', self::TEXT_DOMAIN), $options, __('Erhöhe diese Version, wenn du die Datenschutzerklärung inhaltlich änderst, zum Beispiel bei neuen Diensten, Rechtsgrundlagen oder geänderten Anbieterinformationen.', self::TEXT_DOMAIN));
                                    ?>
                                </table>
                            </div>

                            <div class="n24-consent-manager-tab-panel" id="n24cm-panel-statistics" role="tabpanel" aria-labelledby="n24cm-tab-statistics" data-panel="statistics" hidden>
                                <?php $this->render_service_settings_panel('statistics', __('Statistik-Dienst', self::TEXT_DOMAIN), $options); ?>
                            </div>

                            <div class="n24-consent-manager-tab-panel" id="n24cm-panel-marketing" role="tabpanel" aria-labelledby="n24cm-tab-marketing" data-panel="marketing" hidden>
                                <?php $this->render_service_settings_panel('marketing', __('Marketing-Dienst', self::TEXT_DOMAIN), $options); ?>
                            </div>

                            <div class="n24-consent-manager-tab-panel" id="n24cm-panel-external-media" role="tabpanel" aria-labelledby="n24cm-tab-external-media" data-panel="external_media" hidden>
                                <?php $this->render_service_settings_panel('external_media', __('Externe Medien', self::TEXT_DOMAIN), $options); ?>
                            </div>

                            <div class="n24-consent-manager-tab-panel" id="n24cm-panel-templates" role="tabpanel" aria-labelledby="n24cm-tab-templates" data-panel="templates" hidden>
                                <?php $this->render_service_template_library(); ?>
                            </div>

                            <div class="n24-consent-manager-tab-panel" id="n24cm-panel-cookie-box-layout" role="tabpanel" aria-labelledby="n24cm-tab-cookie-box-layout" data-panel="cookie_box_layout" hidden>
                                <?php $this->render_cookie_box_layout_panel($options); ?>
                            </div>
                        </div>
                    </div>

                    <aside class="n24-consent-manager-preview-wrap" aria-label="<?php echo esc_attr__('Cookie-Box Vorschau', self::TEXT_DOMAIN); ?>">
                        <?php $this->render_preview($options); ?>
                    </aside>
                </div>

                <?php submit_button(); ?>
            </form>
            <p><?php echo wp_kses_post(__('Link für Footer oder Seiten: <code>[n24_consent_settings]</code>', self::TEXT_DOMAIN)); ?></p>
        </div>
        <style>
            .n24-consent-manager-layout {
                display: grid;
                grid-template-columns: minmax(0, 980px) minmax(360px, 430px);
                gap: 28px;
                align-items: start;
                max-width: 1438px;
            }

            .n24-consent-manager-main {
                min-width: 0;
                max-width: 980px;
            }

            .n24-consent-manager-tab-nav {
                margin-top: 18px;
            }

            .n24-consent-manager-tab-nav .nav-tab {
                cursor: pointer;
            }

            .n24-consent-manager-tab-panel {
                max-width: 100%;
                padding-top: 12px;
            }

            .n24-consent-manager-tab-panel[hidden] {
                display: none;
            }

            .n24-consent-manager-tab-panel .regular-text {
                max-width: 100%;
            }

            .n24-consent-manager-tab-panel .wp-picker-container .regular-text {
                width: 230px;
            }

            .n24cm-layout-subtabs {
                display: grid;
                gap: 14px;
            }

            .n24cm-layout-subtab-nav {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                border-bottom: 1px solid #c3c4c7;
                padding-bottom: 8px;
            }

            .n24cm-layout-subtab-nav .is-active {
                border-color: #2271b1;
                color: #0a4b78;
                box-shadow: inset 0 -2px 0 #2271b1;
            }

            .n24cm-layout-subtab-panel[hidden] {
                display: none;
            }

            .n24cm-service-editor {
                max-width: 100%;
                min-width: 0;
            }

            .n24cm-content-blocker-intro,
            .n24cm-service-content-blocker {
                border: 1px solid #c3c4c7;
                background: #fff;
                border-radius: 6px;
                margin: 14px 0;
                padding: 14px;
            }

            .n24cm-service-content-blocker {
                background: #f6f7f7;
            }

            .n24cm-service-content-blocker h4,
            .n24cm-service-content-blocker h5 {
                margin: 14px 0 8px;
            }

            .n24cm-service-content-blocker h4:first-child {
                margin-top: 0;
            }

            .n24cm-service-editor-header,
            .n24cm-service-card-header,
            .n24cm-cookie-editor-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }

            .n24cm-service-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 14px;
                border-bottom: 1px solid #c3c4c7;
            }

            .n24cm-service-tab {
                appearance: none;
                border: 1px solid #c3c4c7;
                border-bottom: 0;
                background: #f6f7f7;
                color: #1d2327;
                cursor: pointer;
                padding: 9px 13px;
                border-radius: 6px 6px 0 0;
                font-weight: 600;
                max-width: 220px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .n24cm-service-tab.is-active {
                background: #fff;
                color: #000;
                margin-bottom: -1px;
                padding-bottom: 10px;
            }

            .n24cm-service-list {
                display: grid;
                margin-top: 0;
            }

            .n24cm-service-card,
            .n24cm-cookie-card {
                box-sizing: border-box;
                border: 1px solid #c3c4c7;
                background: #fff;
                border-radius: 6px;
                padding: 16px;
                max-width: 100%;
                min-width: 0;
            }

            .n24cm-service-card {
                border-top: 0;
                border-radius: 0 0 6px 6px;
            }

            .n24cm-service-card[hidden] {
                display: none;
            }

            .n24cm-service-card-header {
                border-bottom: 1px solid #dcdcde;
                margin: -4px 0 14px;
                padding-bottom: 12px;
            }

            .n24cm-service-grid,
            .n24cm-cookie-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px 18px;
            }

            .n24cm-service-grid p,
            .n24cm-cookie-grid p {
                margin: 0;
            }

            .n24cm-service-grid label,
            .n24cm-cookie-grid label {
                display: block;
            }

            .n24cm-field-label {
                display: flex;
                align-items: center;
                gap: 6px;
                margin-bottom: 5px;
            }

            .n24cm-info-icon {
                position: relative;
                display: inline-flex;
                align-items: center;
                color: #2271b1;
                cursor: help;
            }

            .n24cm-info-icon svg {
                width: 16px;
                height: 16px;
                display: block;
            }

            .n24cm-info-icon::after {
                content: attr(data-tooltip);
                position: absolute;
                z-index: 20;
                left: 0;
                bottom: calc(100% + 8px);
                transform: translateX(0);
                width: max-content;
                max-width: min(320px, calc(100vw - 48px));
                padding: 8px 10px;
                border-radius: 4px;
                background: #1d2327;
                color: #fff;
                font-size: 12px;
                font-weight: 400;
                line-height: 1.35;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.15s ease;
                white-space: normal;
            }

            .n24cm-info-icon::before {
                content: "";
                position: absolute;
                z-index: 21;
                left: 4px;
                bottom: calc(100% + 3px);
                border: 5px solid transparent;
                border-top-color: #1d2327;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.15s ease;
            }

            .n24cm-info-icon:hover::after,
            .n24cm-info-icon:focus::after,
            .n24cm-info-icon:hover::before,
            .n24cm-info-icon:focus::before {
                opacity: 1;
            }

            .n24cm-service-grid input,
            .n24cm-service-grid textarea,
            .n24cm-cookie-grid input,
            .n24cm-cookie-grid textarea {
                box-sizing: border-box;
                width: 100%;
                max-width: none;
            }

            .n24cm-service-code {
                margin-top: 14px;
            }

            .n24cm-service-code p {
                margin: 0;
            }

            .n24cm-service-code textarea {
                box-sizing: border-box;
                width: 100%;
                max-width: none;
                font-family: Consolas, Monaco, monospace;
            }

            .n24cm-embed-image-table {
                table-layout: fixed;
                width: 100%;
            }

            .n24cm-embed-image-table th:nth-child(1) {
                width: 96px;
            }

            .n24cm-embed-image-table th:nth-child(2) {
                width: 180px;
            }

            .n24cm-embed-image-table th:nth-child(3) {
                width: 220px;
            }

            .n24cm-embed-image-table code {
                display: block;
                max-width: 100%;
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            .n24cm-embed-image-table input.regular-text {
                box-sizing: border-box;
                width: 100%;
                max-width: 100%;
            }

            .n24cm-cookie-editor {
                margin-top: 18px;
                padding-top: 14px;
                border-top: 1px solid #dcdcde;
            }

            .n24cm-cookie-list {
                display: grid;
                gap: 12px;
                margin-top: 12px;
            }

            .n24cm-cookie-card {
                background: #f6f7f7;
            }

            .n24cm-remove-cookie {
                float: right;
                margin-bottom: 8px;
            }

            .n24cm-cookie-card::after {
                content: "";
                display: block;
                clear: both;
            }

            .n24cm-template-library {
                display: grid;
                gap: 18px;
                max-width: 1100px;
            }

            .n24cm-template-group {
                border: 1px solid #c3c4c7;
                background: #fff;
                border-radius: 6px;
                padding: 16px;
            }

            .n24cm-template-group h3 {
                margin: 0 0 12px;
            }

            .n24cm-template-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 12px;
            }

            .n24cm-template-card {
                border: 1px solid #dcdcde;
                border-radius: 6px;
                padding: 14px;
                background: #f6f7f7;
                display: grid;
                gap: 10px;
            }

            .n24cm-template-card strong {
                display: block;
                font-size: 14px;
            }

            .n24cm-template-card p {
                margin: 0;
                color: #50575e;
            }

            .n24cm-template-target {
                color: #646970;
                font-size: 12px;
            }

            .n24cm-embed-image-field {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            .n24cm-embed-image-field input {
                flex: 1 1 220px;
                min-width: 0;
            }

            .n24cm-embed-image-preview {
                width: 180px;
                aspect-ratio: 16 / 9;
                margin-top: 8px;
                border: 1px solid #dcdcde;
                border-radius: 4px;
                background: #111;
                background-position: center;
                background-size: cover;
            }

            .n24-consent-manager-preview-wrap {
                padding-top: 63px;
                min-width: 0;
            }

            .n24cm-preview-sticky {
                position: sticky;
                top: 44px;
            }

            .n24cm-preview-sticky h2 {
                margin: 0 0 12px;
                font-size: 16px;
            }

            .n24cm-preview-stage {
                min-height: 520px;
                padding: 28px;
                border: 1px solid #c3c4c7;
                background: var(--ccm-color-overlay);
                box-shadow: inset 0 0 0 1px rgba(255,255,255,.35);
                position: relative;
            }

            .n24cm-preview-box {
                background: var(--ccm-color-bg);
                color: var(--ccm-color-text-primary);
                border: 2px solid var(--ccm-color-accent);
                border-radius: 18px;
                box-shadow: 0 16px 34px rgba(0,0,0,.15);
                padding: 22px;
                max-width: 360px;
                margin: 42px auto 0;
            }

            .n24cm-preview-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 14px;
                font-size: 18px;
                line-height: 1.25;
            }

            .n24cm-preview-header-icon,
            .n24cm-preview-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .n24cm-preview-header-icon,
            .n24cm-preview-header-icon svg {
                width: 32px;
                height: 32px;
            }

            .n24cm-preview-header-icon {
                color: var(--ccm-color-box-icon);
            }

            .n24cm-preview-tabs {
                display: flex;
                gap: 12px;
                border-bottom: 1px solid var(--ccm-color-border);
                margin-bottom: 14px;
                overflow: hidden;
                white-space: nowrap;
            }

            .n24cm-preview-tabs span {
                color: var(--ccm-color-text-secondary);
                padding: 8px 0;
                font-size: 12px;
                position: relative;
            }

            .n24cm-preview-tabs .is-active {
                color: var(--ccm-color-accent-dark);
                font-weight: 600;
            }

            .n24cm-preview-tabs .is-active::after {
                content: "";
                position: absolute;
                left: 0;
                right: 0;
                bottom: -1px;
                height: 2px;
                background: var(--ccm-color-accent);
            }

            .n24cm-preview-box p {
                color: var(--ccm-color-text-secondary);
                font-size: 13px;
                line-height: 1.5;
                margin: 0 0 14px;
            }

            .n24cm-preview-categories {
                display: flex;
                flex-direction: column;
                gap: 8px;
                background: var(--ccm-color-bg-alt);
                border: 1px solid var(--ccm-color-border);
                border-radius: 10px;
                padding: 12px;
                margin-bottom: 12px;
            }

            .n24cm-preview-categories > span {
                display: flex;
                align-items: center;
                gap: 8px;
                color: var(--ccm-color-text-primary);
                font-size: 13px;
            }

            .n24cm-preview-categories i {
                width: 16px;
                height: 16px;
                border-radius: 5px;
                border: 1px solid var(--ccm-color-border);
                background: var(--ccm-color-accent);
                box-shadow: inset 0 0 0 3px var(--ccm-color-bg);
            }

            .n24cm-preview-info {
                color: var(--ccm-color-text-primary);
                background: var(--ccm-color-bg-alt);
                border: 1px solid var(--ccm-color-accent);
                border-radius: 10px;
                padding: 10px;
                font-size: 12px;
                line-height: 1.4;
                margin-bottom: 12px;
            }

            .n24cm-preview-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin-top: 8px;
            }

            .n24cm-preview-actions button {
                border-radius: 999px;
                border: 1px solid transparent;
                padding: 8px 10px;
                font-weight: 600;
                font-size: 12px;
                cursor: default;
            }

            .n24cm-preview-primary {
                background: var(--ccm-gradient-primary);
                color: var(--ccm-color-button-text);
            }

            .n24cm-preview-secondary {
                background: transparent;
                color: var(--ccm-color-text-secondary);
                border-color: var(--ccm-color-border) !important;
            }

            .n24cm-preview-floating {
                position: absolute;
                left: 22px;
                bottom: 22px;
                width: 50px;
                height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 0;
                background: transparent;
                color: var(--ccm-color-floating-icon);
                box-shadow: none;
            }

            .n24cm-preview-floating svg {
                width: 100%;
                height: 100%;
            }

            .n24cm-active-services-summary {
                margin-top: 16px;
                border: 1px solid #c3c4c7;
                background: #fff;
                border-radius: 6px;
                padding: 14px;
            }

            .n24cm-active-services-summary h3 {
                margin: 0 0 10px;
                font-size: 14px;
            }

            .n24cm-active-services-grid {
                display: grid;
                gap: 10px;
            }

            .n24cm-active-services-group {
                border-top: 1px solid #dcdcde;
                padding-top: 10px;
            }

            .n24cm-active-services-group:first-child {
                border-top: 0;
                padding-top: 0;
            }

            .n24cm-active-services-label {
                color: #50575e;
                display: flex;
                justify-content: space-between;
                gap: 10px;
                font-size: 12px;
                font-weight: 600;
                margin-bottom: 7px;
                text-transform: uppercase;
            }

            .n24cm-active-services-list {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin: 0;
            }

            .n24cm-active-services-list span {
                background: #f0f6fc;
                border: 1px solid #b6d4fe;
                border-radius: 999px;
                color: #1d2327;
                display: inline-flex;
                max-width: 100%;
                padding: 4px 9px;
                font-size: 12px;
                line-height: 1.35;
            }

            .n24cm-active-services-empty {
                color: #646970;
                font-size: 12px;
                margin: 0;
            }

            @media (max-width: 1500px) {
                .n24-consent-manager-layout {
                    grid-template-columns: 1fr;
                    max-width: 980px;
                }

                .n24-consent-manager-preview-wrap {
                    padding-top: 0;
                }

                .n24cm-preview-sticky {
                    position: static;
                }

                .n24cm-service-grid,
                .n24cm-cookie-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 782px) {
                .n24cm-embed-image-table,
                .n24cm-embed-image-table thead,
                .n24cm-embed-image-table tbody,
                .n24cm-embed-image-table tr,
                .n24cm-embed-image-table th,
                .n24cm-embed-image-table td {
                    display: block;
                    width: 100%;
                }

                .n24cm-embed-image-table thead {
                    display: none;
                }

                .n24cm-embed-image-table td {
                    box-sizing: border-box;
                    border-bottom: 0;
                }
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const tabsRoot = document.querySelector('.n24-consent-manager-tabs');
                if (!tabsRoot) return;

                const storageKey = 'n24ConsentManagerActiveSettingsTab';
                const buttons = Array.from(tabsRoot.querySelectorAll('[data-tab]'));
                const panels = Array.from(tabsRoot.querySelectorAll('[data-panel]'));

                function activateTab(tabName) {
                    const target = panels.find((panel) => panel.dataset.panel === tabName) ? tabName : 'general';

                    buttons.forEach((button) => {
                        const isActive = button.dataset.tab === target;
                        button.classList.toggle('nav-tab-active', isActive);
                        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });

                    panels.forEach((panel) => {
                        const isActive = panel.dataset.panel === target;
                        panel.hidden = !isActive;
                        panel.classList.toggle('is-active', isActive);
                    });

                    try {
                        window.localStorage.setItem(storageKey, target);
                    } catch (error) {}
                }

                buttons.forEach((button) => {
                    button.addEventListener('click', () => activateTab(button.dataset.tab));
                });

                function activateLayoutTab(tabName) {
                    const layoutButtons = Array.from(document.querySelectorAll('[data-layout-tab]'));
                    const layoutPanels = Array.from(document.querySelectorAll('[data-layout-panel]'));
                    const target = layoutPanels.find((panel) => panel.dataset.layoutPanel === tabName) ? tabName : 'colors';

                    layoutButtons.forEach((button) => {
                        button.classList.toggle('is-active', button.dataset.layoutTab === target);
                    });

                    layoutPanels.forEach((panel) => {
                        const isActive = panel.dataset.layoutPanel === target;
                        panel.hidden = !isActive;
                        panel.classList.toggle('is-active', isActive);
                    });
                }

                document.querySelectorAll('[data-layout-tab]').forEach((button) => {
                    button.addEventListener('click', () => activateLayoutTab(button.dataset.layoutTab));
                });

                const initialTab = new URLSearchParams(window.location.search).get('n24cm_tab') || window.localStorage.getItem(storageKey);
                const legacyLayoutTab = ['colors', 'icon', 'texts'].includes(initialTab) ? initialTab : '';
                activateTab(legacyLayoutTab ? 'cookie_box_layout' : (initialTab || 'general'));
                activateLayoutTab(legacyLayoutTab || 'colors');

                const previewStage = document.querySelector('.n24cm-preview-stage');
                const boxIconTargets = Array.from(document.querySelectorAll('.n24cm-preview-header-icon'));
                const floatingIconTargets = Array.from(document.querySelectorAll('.n24cm-preview-icon'));
                const colorFields = Array.from(document.querySelectorAll('.n24cm-color-field'));
                const previewFields = Array.from(document.querySelectorAll('.n24cm-preview-field'));

                function updateGradient() {
                    if (!previewStage) return;

                    const accent = document.querySelector('[data-css-var="--ccm-color-accent"]')?.value || '#a67c00';
                    const accentLight = document.querySelector('[data-css-var="--ccm-color-accent-light"]')?.value || '#c2950e';
                    previewStage.style.setProperty('--ccm-gradient-primary', `linear-gradient(135deg, ${accent} 0%, ${accentLight} 100%)`);
                }

                function updatePreviewText(key, value) {
                    document.querySelectorAll(`[data-preview-text="${key}"]`).forEach((target) => {
                        target.textContent = value;
                    });
                }

                function updatePreviewIcon(targets, value) {
                    targets.forEach((target) => {
                        target.innerHTML = value;
                    });
                }

                function updateActiveServicesSummary() {
                    const summary = document.querySelector('.n24cm-active-services-summary');
                    if (!summary) return;

                    ['statistics', 'marketing', 'external_media'].forEach((category) => {
                        const list = summary.querySelector(`[data-active-services-list="${category}"]`);
                        const countTarget = summary.querySelector(`[data-active-services-count="${category}"]`);
                        const editor = document.querySelector(`.n24cm-service-editor[data-category="${category}"]`);

                        if (!list || !editor) {
                            return;
                        }

                        const knownNames = new Set();
                        const activeNames = [
                            ...Array.from(editor.querySelectorAll('.n24cm-service-card'))
                            .filter((card) => card.querySelector('input[type="checkbox"]')?.checked)
                            .map((card) => card.querySelector('.n24cm-service-name-field')?.value.trim())
                            .filter(Boolean)
                        ].filter((name) => {
                            const key = name.toLowerCase();

                            if (knownNames.has(key)) {
                                return false;
                            }

                            knownNames.add(key);
                            return true;
                        });

                        list.innerHTML = '';

                        if (countTarget) {
                            countTarget.textContent = String(activeNames.length);
                        }

                        if (!activeNames.length) {
                            const empty = document.createElement('p');
                            empty.className = 'n24cm-active-services-empty';
                            empty.textContent = '<?php echo esc_js(__('Keine aktiven Dienste', self::TEXT_DOMAIN)); ?>';
                            list.append(empty);
                            return;
                        }

                        activeNames.forEach((name) => {
                            const item = document.createElement('span');
                            item.textContent = name;
                            list.append(item);
                        });
                    });
                }

                colorFields.forEach((field) => {
                    const cssVar = field.dataset.cssVar;
                    const onColorChange = function (value) {
                        if (previewStage && cssVar) {
                            previewStage.style.setProperty(cssVar, value);
                        }
                        updateGradient();
                    };

                    field.addEventListener('input', () => onColorChange(field.value));
                    field.addEventListener('change', () => onColorChange(field.value));

                    if (window.jQuery && jQuery.fn.wpColorPicker) {
                        jQuery(field).wpColorPicker({
                            change: function (event, ui) {
                                const value = ui.color.toString();
                                field.value = value;
                                onColorChange(value);
                            },
                            clear: function () {
                                onColorChange(field.value);
                            }
                        });
                    }
                });

                previewFields.forEach((field) => {
                    const target = field.dataset.previewTarget;
                    const onInput = function () {
                        if (target === 'box_icon_svg') {
                            updatePreviewIcon(boxIconTargets, field.value);
                            return;
                        }

                        if (target === 'floating_icon_svg') {
                            updatePreviewIcon(floatingIconTargets, field.value);
                            return;
                        }

                        updatePreviewText(target, field.value);
                    };

                    field.addEventListener('input', onInput);
                    field.addEventListener('change', onInput);
                });

                document.querySelectorAll('.n24cm-service-editor').forEach((editor) => {
                    const serviceTabs = editor.querySelector('.n24cm-service-tabs');
                    const serviceList = editor.querySelector('.n24cm-service-list');
                    const serviceTabTemplate = editor.querySelector('.n24cm-service-tab-template');
                    const serviceTemplate = editor.querySelector('.n24cm-service-template');
                    const addServiceButton = editor.querySelector('.n24cm-add-service');

                    if (!serviceTabs || !serviceList || !serviceTabTemplate || !serviceTemplate || !addServiceButton) {
                        return;
                    }

                    function activateService(index) {
                        serviceTabs.querySelectorAll('.n24cm-service-tab').forEach((button) => {
                            const isActive = button.dataset.serviceTab === index;
                            button.classList.toggle('is-active', isActive);
                            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        });

                        serviceList.querySelectorAll('.n24cm-service-card').forEach((card) => {
                            const isActive = card.dataset.serviceIndex === index;
                            card.classList.toggle('is-active', isActive);
                            card.hidden = !isActive;
                        });
                    }

                    function ensureActiveService() {
                        if (serviceTabs.querySelector('.n24cm-service-tab.is-active')) {
                            return;
                        }

                        const firstTab = serviceTabs.querySelector('.n24cm-service-tab');
                        if (firstTab) {
                            activateService(firstTab.dataset.serviceTab);
                        }
                    }

                    serviceTabs.addEventListener('click', (event) => {
                        const button = event.target.closest('.n24cm-service-tab');

                        if (!button) {
                            return;
                        }

                        activateService(button.dataset.serviceTab);
                    });

                    serviceList.addEventListener('input', (event) => {
                        const field = event.target.closest('.n24cm-service-name-field');

                        if (!field) {
                            return;
                        }

                        const card = field.closest('.n24cm-service-card');
                        const tabButton = serviceTabs.querySelector(`[data-service-tab="${card.dataset.serviceIndex}"]`);

                        if (tabButton) {
                            tabButton.textContent = field.value.trim() || '<?php echo esc_js(__('Neuer Dienst', self::TEXT_DOMAIN)); ?>';
                        }

                        updateActiveServicesSummary();
                    });

                    serviceList.addEventListener('change', (event) => {
                        if (event.target.matches('input[type="checkbox"]')) {
                            updateActiveServicesSummary();
                        }
                    });

                    addServiceButton.addEventListener('click', () => {
                        const nextIndex = String(Date.now());
                        const tabWrapper = document.createElement('div');
                        const cardWrapper = document.createElement('div');

                        tabWrapper.innerHTML = serviceTabTemplate.innerHTML.replaceAll('__SERVICE_INDEX__', nextIndex);
                        cardWrapper.innerHTML = serviceTemplate.innerHTML.replaceAll('__SERVICE_INDEX__', nextIndex);
                        serviceTabs.append(...Array.from(tabWrapper.children));
                        serviceList.append(...Array.from(cardWrapper.children));
                        activateService(nextIndex);
                    });

                    editor.n24cmActivateService = activateService;
                    ensureActiveService();
                });

                function updateTemplateAvailability() {
                    document.querySelectorAll('.n24cm-add-template-service').forEach((button) => {
                        const templateKey = button.dataset.templateKey;
                        const existingPreset = document.querySelector(`.n24cm-service-card input[name$="[preset_key]"][value="${templateKey}"]`);
                        button.disabled = Boolean(existingPreset);
                        button.textContent = existingPreset
                            ? '<?php echo esc_js(__('Bereits hinzugefügt', self::TEXT_DOMAIN)); ?>'
                            : '<?php echo esc_js(__('Hinzufügen', self::TEXT_DOMAIN)); ?>';
                    });
                }

                document.querySelectorAll('.n24cm-add-template-service').forEach((button) => {
                    button.addEventListener('click', () => {
                        const category = button.dataset.templateCategory;
                        const templateKey = button.dataset.templateKey;
                        const editor = document.querySelector(`.n24cm-service-editor[data-category="${category}"]`);
                        const tabTemplate = document.querySelector(`.n24cm-template-service-tab[data-template-key="${templateKey}"]`);
                        const cardTemplate = document.querySelector(`.n24cm-template-service-card[data-template-key="${templateKey}"]`);

                        if (!editor || !tabTemplate || !cardTemplate) {
                            return;
                        }

                        const existingPreset = Array.from(editor.querySelectorAll('input[name$="[preset_key]"]')).find((input) => input.value === templateKey);

                        if (existingPreset) {
                            const existingCard = existingPreset.closest('.n24cm-service-card');
                            if (typeof editor.n24cmActivateService === 'function' && existingCard) {
                                editor.n24cmActivateService(existingCard.dataset.serviceIndex);
                            }
                            activateTab(category);
                            return;
                        }

                        const nextIndex = String(Date.now());
                        const tabWrapper = document.createElement('div');
                        const cardWrapper = document.createElement('div');

                        tabWrapper.innerHTML = tabTemplate.innerHTML.replaceAll('__SERVICE_INDEX__', nextIndex);
                        cardWrapper.innerHTML = cardTemplate.innerHTML.replaceAll('__SERVICE_INDEX__', nextIndex);

                        editor.querySelector('.n24cm-service-tabs')?.append(...Array.from(tabWrapper.children));
                        editor.querySelector('.n24cm-service-list')?.append(...Array.from(cardWrapper.children));
                        document.querySelector(`.n24cm-deleted-preset-key[value="${templateKey}"]`)?.remove();

                        if (typeof editor.n24cmActivateService === 'function') {
                            editor.n24cmActivateService(nextIndex);
                        }

                        activateTab(category);
                        updateActiveServicesSummary();
                        updateTemplateAvailability();
                    });
                });

                document.addEventListener('click', (event) => {
                    const selectEmbedImageButton = event.target.closest('.n24cm-select-embed-image');
                    if (selectEmbedImageButton) {
                        event.preventDefault();

                        if (!window.wp || !wp.media) {
                            return;
                        }

                        const fieldWrap = selectEmbedImageButton.closest('.n24cm-embed-image-field');
                        const input = fieldWrap?.querySelector('.n24cm-embed-image-url');
                        const preview = fieldWrap?.parentElement?.querySelector('.n24cm-embed-image-preview');
                        const frame = wp.media({
                            title: '<?php echo esc_js(__('Platzhalterbild auswählen', self::TEXT_DOMAIN)); ?>',
                            button: { text: '<?php echo esc_js(__('Bild verwenden', self::TEXT_DOMAIN)); ?>' },
                            multiple: false
                        });

                        frame.on('select', () => {
                            const attachment = frame.state().get('selection').first()?.toJSON();
                            const url = attachment?.url || '';

                            if (input) {
                                input.value = url;
                            }

                            if (preview) {
                                preview.style.backgroundImage = url ? `url(${url})` : '';
                            }
                        });

                        frame.open();
                        return;
                    }

                    const clearEmbedImageButton = event.target.closest('.n24cm-clear-embed-image');
                    if (clearEmbedImageButton) {
                        event.preventDefault();
                        const fieldWrap = clearEmbedImageButton.closest('.n24cm-embed-image-field');
                        const input = fieldWrap?.querySelector('.n24cm-embed-image-url');
                        const preview = fieldWrap?.parentElement?.querySelector('.n24cm-embed-image-preview');

                        if (input) {
                            input.value = '';
                        }

                        if (preview) {
                            preview.style.backgroundImage = '';
                        }

                        return;
                    }

                    const removeServiceButton = event.target.closest('.n24cm-remove-service');
                    if (removeServiceButton) {
                        event.preventDefault();
                        const editor = removeServiceButton.closest('.n24cm-service-editor');
                        const card = removeServiceButton.closest('.n24cm-service-card');
                        const index = card?.dataset.serviceIndex;
                        const presetKey = card?.querySelector('input[name$="[preset_key]"]')?.value;

                        if (editor && index) {
                            editor.querySelector(`.n24cm-service-tab[data-service-tab="${index}"]`)?.remove();
                        }

                        if (presetKey) {
                            const deletedPresets = document.querySelector('.n24cm-deleted-preset-keys');
                            const alreadyDeleted = document.querySelector(`.n24cm-deleted-preset-key[value="${presetKey}"]`);

                            if (deletedPresets && !alreadyDeleted) {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.className = 'n24cm-deleted-preset-key';
                                input.name = '<?php echo esc_js(self::OPTION_NAME); ?>[deleted_preset_keys][]';
                                input.value = presetKey;
                                deletedPresets.append(input);
                            }
                        }

                        card?.remove();
                        updateActiveServicesSummary();
                        updateTemplateAvailability();

                        if (editor) {
                            const firstTab = editor.querySelector('.n24cm-service-tab');

                            if (firstTab) {
                                firstTab.click();
                            }
                        }
                        return;
                    }

                    const addCookieButton = event.target.closest('.n24cm-add-cookie');
                    if (addCookieButton) {
                        event.preventDefault();
                        const cookieEditor = addCookieButton.closest('.n24cm-cookie-editor');
                        const cookieList = cookieEditor?.querySelector('.n24cm-cookie-list');
                        const cookieTemplate = cookieEditor?.querySelector('.n24cm-cookie-template');

                        if (!cookieEditor || !cookieList || !cookieTemplate) {
                            return;
                        }

                        const nextIndex = String(Date.now());
                        const wrapper = document.createElement('div');
                        wrapper.innerHTML = cookieTemplate.innerHTML.replaceAll('__COOKIE_INDEX__', nextIndex);
                        cookieList.append(...Array.from(wrapper.children));
                        updateActiveServicesSummary();
                        return;
                    }

                    const removeCookieButton = event.target.closest('.n24cm-remove-cookie');
                    if (removeCookieButton) {
                        event.preventDefault();
                        removeCookieButton.closest('.n24cm-cookie-card')?.remove();
                        updateActiveServicesSummary();
                    }
                });

                updateActiveServicesSummary();
                updateTemplateAvailability();
            });
        </script>
        <?php
    }

    public function render_cookie_settings_shortcode(): string
    {
        $options = $this->get_options();

        if (($options['plugin_enabled'] ?? '1') !== '1') {
            return '';
        }

        return sprintf(
            '<a href="#" class="cookie-settings-link">%s</a>',
            esc_html($options['settings_link'])
        );
    }

    public function filter_blocked_content(string $content): string
    {
        if (is_admin() || trim($content) === '') {
            return $content;
        }

        $options = $this->get_options();

        if (($options['plugin_enabled'] ?? '1') !== '1') {
            return $content;
        }

        if (!$this->is_content_blocker_enabled($options)) {
            return $content;
        }

        $content = preg_replace_callback(
            '/<blockquote\b(?=[^>]*\bclass=(["\'])(?:(?!\1).)*instagram-media(?:(?!\1).)*\1)[\s\S]*?<\/blockquote>\s*(?:<script\b[^>]*\bsrc=(["\'])(?:https?:)?\/\/www\.instagram\.com\/embed\.js\2[^>]*>\s*<\/script>)?/i',
            function (array $matches) use ($options): string {
                return $this->replace_blocked_external_content($matches[0], 'instagram', $options);
            },
            $content
        );

        $content = preg_replace_callback(
            '/<div\b(?=[^>]*\bclass=(["\'])(?:(?!\1).)*\bfb-(?:post|video|page|comment|comments)(?:(?!\1).)*\1)[\s\S]*?<\/div>\s*(?:<script\b[^>]*\bsrc=(["\'])(?:https?:)?\/\/connect\.facebook\.net\/[^"\']+\2[^>]*>\s*<\/script>)?/i',
            function (array $matches) use ($options): string {
                return $this->replace_blocked_external_content($matches[0], 'facebook', $options);
            },
            $content
        );

        $content = preg_replace_callback(
            '/<blockquote\b(?=[^>]*\bclass=(["\'])(?:(?!\1).)*\btwitter-tweet\b(?:(?!\1).)*\1)[\s\S]*?<\/blockquote>\s*(?:<script\b[^>]*\bsrc=(["\'])(?:https?:)?\/\/platform\.twitter\.com\/widgets\.js\2[^>]*>\s*<\/script>)?/i',
            function (array $matches) use ($options): string {
                return $this->replace_blocked_external_content($matches[0], 'x', $options);
            },
            $content
        );

        $content = preg_replace_callback(
            '/<iframe\b[^>]*\bsrc=(["\'])(.*?)\1[^>]*>\s*<\/iframe>/is',
            function (array $matches) use ($options): string {
                $blocker_key = $this->get_content_blocker_key_for_url(html_entity_decode($matches[2]));

                if ($blocker_key === '') {
                    return $matches[0];
                }

                return $this->replace_blocked_external_content($matches[0], $blocker_key, $options);
            },
            $content
        );

        return $content;
    }

    public function render_instagram_shortcode($atts = []): string
    {
        $atts = shortcode_atts(
            [
                'url' => '',
            ],
            $atts,
            'n24_instagram'
        );
        $embed_html = $this->build_instagram_embed_html((string) ($atts['url'] ?? ''));

        if ($embed_html === '') {
            return '';
        }

        $options = $this->get_options();
        $definition = $this->get_content_blocker_definition('instagram');

        if (
            $definition
            && $this->is_content_blocker_enabled($options)
            && ($options[$definition['option_key']] ?? '0') === '1'
        ) {
            return $this->render_blocked_content_placeholder($embed_html, $definition, $options);
        }

        return $embed_html;
    }

    private function build_instagram_embed_html(string $url): string
    {
        $url = $this->normalize_instagram_permalink($url);

        if ($url === '') {
            return '';
        }

        return sprintf(
            '<div class="n24-instagram-embed"><blockquote class="instagram-media" data-instgrm-permalink="%1$s" data-instgrm-version="14"><a href="%1$s">%1$s</a></blockquote></div><script async src="//www.instagram.com/embed.js"></script>',
            esc_url($url)
        );
    }

    private function normalize_instagram_permalink(string $url): string
    {
        $url = trim($url);
        $parts = wp_parse_url($url);

        if (!is_array($parts)) {
            return '';
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');

        if ($host === '' || strpos($host, 'instagram.com') === false || $path === '') {
            return '';
        }

        if (!preg_match('~^(?:p|reel|tv)/[^/]+~i', $path)) {
            return '';
        }

        return 'https://www.instagram.com/' . $path . '/';
    }

    private function replace_blocked_external_content(string $html, string $blocker_key, array $options): string
    {
        $definition = $this->get_content_blocker_definition($blocker_key);

        if (!$definition || !$this->is_content_blocker_definition_enabled($definition, $options)) {
            return $html;
        }

        return $this->render_blocked_content_placeholder($html, $definition, $options);
    }

    private function render_blocked_content_placeholder(string $html, array $definition, array $options): string
    {
        $encoded_html = base64_encode($html);
        $source_url = $this->get_blocked_content_source_url($html);
        $embed_key = $this->get_content_blocker_embed_key($definition['key'], $source_url);
        $placeholder_images = $this->sanitize_url_map($options['content_blocker_embed_images'] ?? []);
        $placeholder_titles = $this->sanitize_text_map($options['content_blocker_embed_titles'] ?? []);
        $placeholder_image = $placeholder_images[$embed_key] ?? '';
        $style = $this->get_content_blocker_placeholder_style($options, (string) $definition['service_id'], $placeholder_image);
        $service_available = $this->frontend_service_exists($definition['category'], $definition['service_id'], $options);
        $service = $this->get_admin_service_for_content_blocker($definition, $options);
        $description = $this->get_content_blocker_description_template($options, $definition, $service_available);
        $description = $this->format_content_blocker_description($description, $service, $definition);
        $title = trim((string) ($placeholder_titles[$embed_key] ?? ''));
        $button_text = $this->get_content_blocker_button_text($options, $definition);
        $always_button_text = $this->get_content_blocker_service_setting($options, (string) $definition['service_id'], 'always_button') ?: (string) ($options['content_blocker_always_button'] ?? __('Immer laden', self::TEXT_DOMAIN));
        $blocker_class = $service_available ? 'n24-content-blocker' : 'n24-content-blocker is-service-missing';

        $title_html = $title !== ''
            ? sprintf('<strong>%s</strong>', esc_html($title))
            : '';
        $actions_html = $service_available
            ? sprintf(
                '<span class="n24-content-blocker-actions"><button type="button" class="n24-content-blocker-load-once">%s</button><button type="button" class="n24-content-blocker-accept">%s</button></span>',
                esc_html($button_text),
                esc_html($always_button_text)
            )
            : '';

        return sprintf(
            '<span class="%1$s" data-n24-content-blocker="%2$s" data-n24-service-id="%3$s" data-n24-service-category="%4$s" data-n24-original-html="%5$s" data-n24-embed-key="%6$s" data-n24-service-available="%7$s"%8$s><span class="n24-content-blocker-overlay"></span><span class="n24-content-blocker-inner">%9$s<span class="n24-content-blocker-text">%10$s</span>%11$s</span></span>',
            esc_attr($blocker_class),
            esc_attr($definition['key']),
            esc_attr($definition['service_id']),
            esc_attr($definition['category']),
            esc_attr($encoded_html),
            esc_attr($embed_key),
            $service_available ? '1' : '0',
            $style,
            $title_html,
            wp_kses_post($description),
            $actions_html
        );
    }

    private function get_content_blocker_placeholder_style(array $options, string $service_id, string $placeholder_image): string
    {
        $styles = [];

        if ($placeholder_image !== '') {
            $styles[] = sprintf('background-image:url(%s)', esc_url($placeholder_image));
        }

        foreach ($this->get_content_blocker_service_color_fields() as $field => $config) {
            $service_settings = $options['content_blocker_service_settings'][$service_id] ?? [];
            $value = is_array($service_settings) ? trim((string) ($service_settings[$field] ?? '')) : '';

            if ($value !== '') {
                $styles[] = sprintf('%s:%s', $config['css_var'], esc_attr($value));
            }
        }

        return $styles ? sprintf(' style="%s"', esc_attr(implode(';', $styles))) : '';
    }

    private function get_content_blocker_description_template(array $options, array $definition, bool $service_available): string
    {
        if (!$service_available) {
            return $this->get_content_blocker_service_setting($options, (string) $definition['service_id'], 'missing_service_text') ?: (string) ($options['content_blocker_missing_service_text'] ?? '');
        }

        $description = $this->get_content_blocker_service_setting($options, (string) $definition['service_id'], 'text') ?: (string) ($options['content_blocker_text'] ?? '');

        if (!$this->uses_default_content_placeholder_text($description)) {
            return $description;
        }

        return $this->get_contextual_content_blocker_text($definition);
    }

    private function get_content_blocker_button_text(array $options, array $definition): string
    {
        $button_text = $this->get_content_blocker_service_setting($options, (string) $definition['service_id'], 'button') ?: (string) ($options['content_blocker_button'] ?? '');

        if (!$this->uses_default_content_load_button($button_text)) {
            return $button_text;
        }

        return $this->get_contextual_content_blocker_button($definition);
    }

    private function get_contextual_content_blocker_text(array $definition): string
    {
        if (in_array(($definition['key'] ?? ''), ['facebook', 'instagram', 'x'], true)) {
            return __('Dieser Beitrag wird von %s geladen. Durch das Anzeigen akzeptierst du die Nutzungsbedingungen von %s.', self::TEXT_DOMAIN);
        }

        if (in_array(($definition['key'] ?? ''), ['google_maps', 'openstreetmap'], true)) {
            return __('Diese Karte wird von %s geladen. Durch das Anzeigen akzeptierst du die Nutzungsbedingungen von %s.', self::TEXT_DOMAIN);
        }

        if (in_array(($definition['key'] ?? ''), ['soundcloud', 'spotify'], true)) {
            return __('Diese Audio-Datei wird von %s geladen. Durch das Anzeigen akzeptierst du die Nutzungsbedingungen von %s.', self::TEXT_DOMAIN);
        }

        return __('Dieses Video wird von %s geladen. Durch das Anzeigen akzeptierst du die Nutzungsbedingungen von %s.', self::TEXT_DOMAIN);
    }

    private function get_contextual_content_blocker_button(array $definition): string
    {
        if (in_array(($definition['key'] ?? ''), ['facebook', 'instagram', 'x'], true)) {
            return __('Beitrag laden', self::TEXT_DOMAIN);
        }

        if (in_array(($definition['key'] ?? ''), ['google_maps', 'openstreetmap'], true)) {
            return __('Karte laden', self::TEXT_DOMAIN);
        }

        if (in_array(($definition['key'] ?? ''), ['soundcloud', 'spotify'], true)) {
            return __('Audio laden', self::TEXT_DOMAIN);
        }

        return __('Video laden', self::TEXT_DOMAIN);
    }

    private function uses_default_video_placeholder_text(string $text): bool
    {
        $text = trim($text);

        return in_array(
            $text,
            [
                'Dieses Video wird von %s geladen. Durch das Anzeigen akzeptierst du die Nutzungsbedingungen von %s.',
                'This video is loaded from %s. By displaying it, you accept the terms of use of %s.',
            ],
            true
        );
    }

    private function uses_default_content_placeholder_text(string $text): bool
    {
        $text = trim($text);

        return $this->uses_default_video_placeholder_text($text)
            || in_array(
                $text,
                [
                    'Dieser externe Inhalt wird von %s geladen. Durch das Anzeigen akzeptierst du die Nutzungsbedingungen von %s.',
                    'This external content is loaded from %s. By displaying it, you accept the terms of use of %s.',
                ],
                true
            );
    }

    private function uses_default_video_load_button(string $text): bool
    {
        return in_array(trim($text), ['Video laden', 'Load video'], true);
    }

    private function uses_default_content_load_button(string $text): bool
    {
        return $this->uses_default_video_load_button($text)
            || in_array(trim($text), ['Inhalt laden', 'Load content'], true);
    }

    private function format_content_blocker_description(string $description, array $service, array $definition): string
    {
        $service_name = trim((string) ($service['name'] ?? ''));

        if ($service_name === '') {
            $service_name = (string) ($definition['service_name'] ?? $definition['label'] ?? '');
        }

        if (strpos($description, '%s') !== false) {
            $description = sprintf($description, $service_name, $service_name);
        }

        $description = esc_html($description);
        $privacy_url = trim((string) ($service['privacy_url'] ?? ''));

        if ($privacy_url !== '') {
            $terms_label = esc_html__('Nutzungsbedingungen', self::TEXT_DOMAIN);
            $terms_link = sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                esc_url($privacy_url),
                $terms_label
            );
            $description = str_replace($terms_label, $terms_link, $description);
        }

        return $description;
    }

    private function get_blocked_content_source_url(string $html): string
    {
        if (preg_match('/\bdata-instgrm-permalink=(["\'])(.*?)\1/i', $html, $matches)) {
            return html_entity_decode($matches[2]);
        }

        if (preg_match('/\bdata-href=(["\'])(.*?)\1/i', $html, $matches)) {
            return html_entity_decode($matches[2]);
        }

        if (preg_match('/\bsrc=(["\'])(.*?)\1/i', $html, $matches)) {
            return html_entity_decode($matches[2]);
        }

        if (preg_match('/\bcite=(["\'])(.*?)\1/i', $html, $matches)) {
            return html_entity_decode($matches[2]);
        }

        if (preg_match('/https?:\/\/[^\s<>"\']+/i', $html, $matches)) {
            return html_entity_decode($matches[0]);
        }

        return '';
    }

    private function get_content_blocker_embed_key(string $blocker_key, string $url): string
    {
        $url = trim($url);
        $provider_id = $this->get_provider_content_id($blocker_key, $url);

        if ($provider_id !== '') {
            return sanitize_key($blocker_key . '_' . $provider_id);
        }

        return sanitize_key($blocker_key . '_' . substr(md5($url), 0, 12));
    }

    private function get_provider_content_id(string $blocker_key, string $url): string
    {
        $parts = wp_parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        parse_str((string) ($parts['query'] ?? ''), $query);

        if ($blocker_key === 'youtube') {
            if (!empty($query['v'])) {
                return sanitize_key((string) $query['v']);
            }

            if (preg_match('~(?:embed/|shorts/|youtu\.be/)?([A-Za-z0-9_-]{6,})~', $path, $matches)) {
                return sanitize_key($matches[1]);
            }
        }

        if ($blocker_key === 'vimeo' && preg_match('~(?:video/)?(\d+)~', $path, $matches)) {
            return sanitize_key($matches[1]);
        }

        if ($blocker_key === 'instagram' && preg_match('~(?:p|reel|tv)/([^/]+)~', $path, $matches)) {
            return sanitize_key($matches[1]);
        }

        if ($blocker_key === 'x' && preg_match('~status(?:es)?/(\d+)~', $path, $matches)) {
            return sanitize_key($matches[1]);
        }

        if (in_array($blocker_key, ['facebook', 'google_maps', 'openstreetmap', 'soundcloud', 'spotify'], true)) {
            return sanitize_key(substr(md5($host . '/' . $path . '?' . ($parts['query'] ?? '')), 0, 12));
        }

        return '';
    }

    private function get_detected_content_blocker_embeds(): array
    {
        $post_types = get_post_types(['public' => true], 'names');
        $posts = get_posts([
            'post_type' => array_values($post_types),
            'post_status' => ['publish', 'private', 'draft'],
            'numberposts' => 200,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
        $embeds = [];
        $known = [];

        foreach ($posts as $post) {
            $content = (string) $post->post_content;
            $urls = $this->extract_content_blocker_candidate_urls($content);

            foreach ($urls as $url) {
                $blocker_key = $this->get_content_blocker_key_for_url($url);

                if ($blocker_key === '') {
                    continue;
                }

                $definition = $this->get_content_blocker_definition($blocker_key);

                if (!$definition) {
                    continue;
                }

                $embed_key = $this->get_content_blocker_embed_key($blocker_key, $url);

                if (isset($known[$embed_key])) {
                    continue;
                }

                $known[$embed_key] = true;
                $embeds[] = [
                    'key' => $embed_key,
                    'blocker_key' => $blocker_key,
                    'url' => $url,
                    'service_label' => $definition['service_name'],
                    'post_title' => get_the_title($post) ?: __('Ohne Titel', self::TEXT_DOMAIN),
                ];
            }
        }

        return $embeds;
    }

    private function extract_content_blocker_candidate_urls(string $content): array
    {
        $urls = [];

        if (preg_match_all('/<iframe\b[^>]*\bsrc=(["\'])(.*?)\1[^>]*>/is', $content, $matches)) {
            foreach ($matches[2] as $url) {
                $urls[] = html_entity_decode($url);
            }
        }

        if (preg_match_all('/\bcite=(["\'])(https?:\/\/(?:www\.)?instagram\.com\/[^"\']+)\1/i', $content, $matches)) {
            foreach ($matches[2] as $url) {
                $urls[] = html_entity_decode($url);
            }
        }

        if (preg_match_all('/\bdata-instgrm-permalink=(["\'])(https?:\/\/(?:www\.)?instagram\.com\/[^"\']+)\1/i', $content, $matches)) {
            foreach ($matches[2] as $url) {
                $urls[] = html_entity_decode($url);
            }
        }

        if (preg_match_all('/\bdata-href=(["\'])(https?:\/\/(?:www\.)?(?:facebook\.com|fb\.watch)\/[^"\']+)\1/i', $content, $matches)) {
            foreach ($matches[2] as $url) {
                $urls[] = html_entity_decode($url);
            }
        }

        if (preg_match_all('/\bcite=(["\'])(https?:\/\/(?:www\.)?(?:x\.com|twitter\.com)\/[^"\']+)\1/i', $content, $matches)) {
            foreach ($matches[2] as $url) {
                $urls[] = html_entity_decode($url);
            }
        }

        if (preg_match_all('~https?://(?:www\.|player\.|w\.|open\.)?(?:youtube\.com|youtube-nocookie\.com|youtu\.be|vimeo\.com|instagram\.com|facebook\.com|fb\.watch|google\.com/maps|maps\.google\.|openstreetmap\.org|osm\.org|umap\.openstreetmap\.fr|soundcloud\.com|spotify\.com|x\.com|twitter\.com)[^\s<>"\']*~i', $content, $matches)) {
            foreach ($matches[0] as $url) {
                $urls[] = html_entity_decode($url);
            }
        }

        return array_values(array_unique($urls));
    }

    private function get_content_blocker_key_for_url(string $url): string
    {
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        $path = strtolower((string) wp_parse_url($url, PHP_URL_PATH));

        if ($host === '') {
            return '';
        }

        if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtube-nocookie.com') !== false || strpos($host, 'youtu.be') !== false) {
            return 'youtube';
        }

        if (strpos($host, 'vimeo.com') !== false) {
            return 'vimeo';
        }

        if (strpos($host, 'google.com') !== false && strpos($path, '/maps') !== false) {
            return 'google_maps';
        }

        if (strpos($host, 'maps.google.') !== false || strpos($host, 'googleusercontent.com') !== false) {
            return 'google_maps';
        }

        if (strpos($host, 'instagram.com') !== false) {
            return 'instagram';
        }

        if (strpos($host, 'facebook.com') !== false || strpos($host, 'fb.watch') !== false || strpos($host, 'connect.facebook.net') !== false) {
            return 'facebook';
        }

        if (strpos($host, 'openstreetmap.org') !== false || strpos($host, 'osm.org') !== false || strpos($host, 'umap.openstreetmap.fr') !== false) {
            return 'openstreetmap';
        }

        if (strpos($host, 'soundcloud.com') !== false) {
            return 'soundcloud';
        }

        if (strpos($host, 'spotify.com') !== false) {
            return 'spotify';
        }

        if (strpos($host, 'x.com') !== false || strpos($host, 'twitter.com') !== false || strpos($host, 'platform.twitter.com') !== false) {
            return 'x';
        }

        return '';
    }

    private function get_content_blocker_definition(string $key): ?array
    {
        foreach ($this->get_content_blocker_definitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return null;
    }

    private function get_content_blocker_definitions(): array
    {
        return [
            [
                'key' => 'facebook',
                'label' => 'Facebook',
                'service_id' => 'facebook',
                'service_name' => 'Facebook',
                'category' => 'external_media',
                'option_key' => 'content_blocker_facebook_enabled',
                'hosts' => ['facebook.com', 'fb.watch', 'connect.facebook.net'],
            ],
            [
                'key' => 'youtube',
                'label' => 'YouTube',
                'service_id' => 'youtube',
                'service_name' => 'YouTube',
                'category' => 'external_media',
                'option_key' => 'content_blocker_youtube_enabled',
                'hosts' => ['youtube.com', 'youtube-nocookie.com', 'youtu.be'],
            ],
            [
                'key' => 'vimeo',
                'label' => 'Vimeo',
                'service_id' => 'vimeo',
                'service_name' => 'Vimeo',
                'category' => 'external_media',
                'option_key' => 'content_blocker_vimeo_enabled',
                'hosts' => ['player.vimeo.com', 'vimeo.com'],
            ],
            [
                'key' => 'google_maps',
                'label' => 'Google Maps',
                'service_id' => 'google_maps',
                'service_name' => 'Google Maps',
                'category' => 'external_media',
                'option_key' => 'content_blocker_google_maps_enabled',
                'hosts' => ['google.com/maps', 'maps.google.*'],
            ],
            [
                'key' => 'instagram',
                'label' => 'Instagram',
                'service_id' => 'instagram',
                'service_name' => 'Instagram',
                'category' => 'external_media',
                'option_key' => 'content_blocker_instagram_enabled',
                'hosts' => ['instagram.com', 'www.instagram.com/embed.js'],
            ],
            [
                'key' => 'openstreetmap',
                'label' => 'OpenStreetMap',
                'service_id' => 'openstreetmap',
                'service_name' => 'OpenStreetMap',
                'category' => 'external_media',
                'option_key' => 'content_blocker_openstreetmap_enabled',
                'hosts' => ['openstreetmap.org', 'osm.org', 'umap.openstreetmap.fr'],
            ],
            [
                'key' => 'soundcloud',
                'label' => 'SoundCloud',
                'service_id' => 'soundcloud',
                'service_name' => 'SoundCloud',
                'category' => 'external_media',
                'option_key' => 'content_blocker_soundcloud_enabled',
                'hosts' => ['soundcloud.com', 'w.soundcloud.com'],
            ],
            [
                'key' => 'spotify',
                'label' => 'Spotify',
                'service_id' => 'spotify',
                'service_name' => 'Spotify',
                'category' => 'external_media',
                'option_key' => 'content_blocker_spotify_enabled',
                'hosts' => ['spotify.com', 'open.spotify.com'],
            ],
            [
                'key' => 'x',
                'label' => 'X',
                'service_id' => 'x',
                'service_name' => 'X',
                'category' => 'external_media',
                'option_key' => 'content_blocker_x_enabled',
                'hosts' => ['x.com', 'twitter.com', 'platform.twitter.com'],
            ],
        ];
    }

    private function frontend_service_exists(string $category, string $service_id, array $options): bool
    {
        $services = $this->append_content_blocker_services(
            [
                $category => $this->get_optional_services_for_category($category, $options),
            ],
            $options
        );

        foreach (($services[$category] ?? []) as $service) {
            if (($service['id'] ?? '') === $service_id) {
                return true;
            }
        }

        return false;
    }

    private function render_preview(array $options): void
    {
        ?>
        <div class="n24cm-preview-sticky">
            <h2><?php echo esc_html__('Live Vorschau', self::TEXT_DOMAIN); ?></h2>
            <div class="n24cm-preview-stage" style="<?php echo esc_attr($this->build_preview_style($options)); ?>">
                <div class="n24cm-preview-floating" aria-hidden="true">
                    <span class="n24cm-preview-icon"><?php echo $this->sanitize_icon_svg($options['floating_icon_svg']); ?></span>
                </div>
                <div class="n24cm-preview-box">
                    <div class="n24cm-preview-header">
                        <span class="n24cm-preview-header-icon"><?php echo $this->sanitize_icon_svg($options['box_icon_svg']); ?></span>
                        <strong data-preview-text="dialog_title"><?php echo esc_html($options['dialog_title']); ?></strong>
                    </div>
                    <div class="n24cm-preview-tabs">
                        <span class="is-active" data-preview-text="tab_overview"><?php echo esc_html($options['tab_overview']); ?></span>
                        <span data-preview-text="tab_details"><?php echo esc_html($options['tab_details']); ?></span>
                        <span data-preview-text="tab_history"><?php echo esc_html($options['tab_history']); ?></span>
                    </div>
                    <p data-preview-text="intro_text"><?php echo esc_html($options['intro_text']); ?></p>
                    <div class="n24cm-preview-categories">
                        <span><i></i><span data-preview-text="necessary_label"><?php echo esc_html($options['necessary_label']); ?></span></span>
                        <span><i></i><span data-preview-text="statistics_inactive_label"><?php echo esc_html($options['statistics_inactive_label']); ?></span></span>
                        <span><i></i><span data-preview-text="marketing_inactive_label"><?php echo esc_html($options['marketing_inactive_label']); ?></span></span>
                        <span><i></i><span data-preview-text="external_media_inactive_label"><?php echo esc_html($options['external_media_inactive_label']); ?></span></span>
                    </div>
                    <div class="n24cm-preview-info">
                        <span data-preview-text="info_default"><?php echo esc_html($options['info_default']); ?></span>
                    </div>
                    <div class="n24cm-preview-actions">
                        <button type="button" class="n24cm-preview-primary" data-preview-text="reject_button"><?php echo esc_html($options['reject_button']); ?></button>
                        <button type="button" class="n24cm-preview-primary" data-preview-text="accept_all_button"><?php echo esc_html($options['accept_all_button']); ?></button>
                    </div>
                    <div class="n24cm-preview-actions">
                        <button type="button" class="n24cm-preview-secondary" data-preview-text="save_button"><?php echo esc_html($options['save_button']); ?></button>
                        <button type="button" class="n24cm-preview-secondary" data-preview-text="customize_button"><?php echo esc_html($options['customize_button']); ?></button>
                    </div>
                </div>
            </div>
            <?php $this->render_active_services_summary($options); ?>
        </div>
        <?php
    }

    private function render_active_services_summary(array $options): void
    {
        $groups = [
            'statistics' => __('Statistik', self::TEXT_DOMAIN),
            'marketing' => __('Marketing', self::TEXT_DOMAIN),
            'external_media' => __('Externe Medien', self::TEXT_DOMAIN),
        ];
        ?>
        <div class="n24cm-active-services-summary">
            <h3><?php echo esc_html__('Aktive Dienste', self::TEXT_DOMAIN); ?></h3>
            <div class="n24cm-active-services-grid">
                <?php foreach ($groups as $category => $label) : ?>
                    <?php $services = $this->get_active_summary_services_for_category($category, $options); ?>
                    <div class="n24cm-active-services-group" data-active-services-group="<?php echo esc_attr($category); ?>">
                        <div class="n24cm-active-services-label">
                            <span><?php echo esc_html($label); ?></span>
                            <span><span data-active-services-count="<?php echo esc_attr($category); ?>"><?php echo esc_html((string) count($services)); ?></span> <?php echo esc_html__('aktiv', self::TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="n24cm-active-services-list" data-active-services-list="<?php echo esc_attr($category); ?>">
                            <?php if ($services) : ?>
                                <?php foreach ($services as $service) : ?>
                                    <span><?php echo esc_html($service['name']); ?></span>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p class="n24cm-active-services-empty"><?php echo esc_html__('Keine aktiven Dienste', self::TEXT_DOMAIN); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function render_cookie_box_layout_panel(array $options): void
    {
        ?>
        <div class="n24cm-layout-subtabs">
            <div class="n24cm-layout-subtab-nav" role="tablist" aria-label="<?php echo esc_attr__('Cookie Box Layout', self::TEXT_DOMAIN); ?>">
                <button type="button" class="button button-secondary is-active" data-layout-tab="colors"><?php echo esc_html__('Farben', self::TEXT_DOMAIN); ?></button>
                <button type="button" class="button button-secondary" data-layout-tab="icon"><?php echo esc_html__('Icon', self::TEXT_DOMAIN); ?></button>
                <button type="button" class="button button-secondary" data-layout-tab="texts"><?php echo esc_html__('Texte', self::TEXT_DOMAIN); ?></button>
            </div>

            <div class="n24cm-layout-subtab-panel is-active" data-layout-panel="colors">
                <p><?php echo esc_html__('Die Schriftart wird bewusst nicht gesetzt. Das Plugin erbt die Schrift des aktiven Themes.', self::TEXT_DOMAIN); ?></p>
                <table class="form-table" role="presentation">
                    <?php foreach ($this->get_cookie_box_color_labels() as $key => $label) : ?>
                        <?php $this->render_color_row($key, $label, $options); ?>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="n24cm-layout-subtab-panel" data-layout-panel="icon" hidden>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="n24-consent-manager-box-icon-svg"><?php echo esc_html__('Box-Icon SVG', self::TEXT_DOMAIN); ?></label></th>
                        <td>
                            <textarea id="n24-consent-manager-box-icon-svg" class="n24cm-preview-field large-text code" data-preview-target="box_icon_svg" name="<?php echo esc_attr(self::OPTION_NAME); ?>[box_icon_svg]" rows="8"><?php echo esc_textarea($options['box_icon_svg']); ?></textarea>
                            <p class="description"><?php echo wp_kses_post(__('Erlaubt sind einfache SVG-Elemente wie svg, path, rect, circle, line und polyline. Für frei einstellbare Farben sollte das SVG <code>currentColor</code> für fill oder stroke verwenden.', self::TEXT_DOMAIN)); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="n24-consent-manager-floating-icon-svg"><?php echo esc_html__('Frontend-Icon SVG', self::TEXT_DOMAIN); ?></label></th>
                        <td>
                            <textarea id="n24-consent-manager-floating-icon-svg" class="n24cm-preview-field large-text code" data-preview-target="floating_icon_svg" name="<?php echo esc_attr(self::OPTION_NAME); ?>[floating_icon_svg]" rows="8"><?php echo esc_textarea($options['floating_icon_svg']); ?></textarea>
                            <p class="description"><?php echo wp_kses_post(__('Dieses Icon wird als Floating-Button im Frontend angezeigt. Für frei einstellbare Farben sollte das SVG <code>currentColor</code> für fill oder stroke verwenden.', self::TEXT_DOMAIN)); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="n24cm-layout-subtab-panel" data-layout-panel="texts" hidden>
                <table class="form-table" role="presentation">
                    <?php foreach ($this->get_cookie_box_text_labels() as $key => $label) : ?>
                        <?php $this->render_textarea_row($key, $label, $options); ?>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php
    }

    private function get_cookie_box_color_labels(): array
    {
        return [
            'color_overlay' => __('Overlay', self::TEXT_DOMAIN),
            'color_modal_background' => __('Modal-Hintergrund', self::TEXT_DOMAIN),
            'color_panel_background' => __('Panel-/Listen-Hintergrund', self::TEXT_DOMAIN),
            'color_text_primary' => __('Text primär', self::TEXT_DOMAIN),
            'color_text_secondary' => __('Text sekundär', self::TEXT_DOMAIN),
            'color_text_muted' => __('Text gedimmt', self::TEXT_DOMAIN),
            'color_accent' => __('Akzent', self::TEXT_DOMAIN),
            'color_accent_light' => __('Akzent hell', self::TEXT_DOMAIN),
            'color_accent_dark' => __('Akzent dunkel', self::TEXT_DOMAIN),
            'color_border' => __('Rahmen', self::TEXT_DOMAIN),
            'color_border_hover' => __('Rahmen Hover', self::TEXT_DOMAIN),
            'color_button_text' => __('Primärbutton Text', self::TEXT_DOMAIN),
            'color_floating_background' => __('Floating-Button Hintergrund', self::TEXT_DOMAIN),
            'color_floating_hover_background' => __('Floating-Button Hover', self::TEXT_DOMAIN),
            'color_box_icon' => __('Box-Icon Farbe', self::TEXT_DOMAIN),
            'color_floating_icon' => __('Frontend-Icon Farbe', self::TEXT_DOMAIN),
        ];
    }

    private function get_cookie_box_text_labels(): array
    {
        return [
            'dialog_title' => __('Dialog-Titel', self::TEXT_DOMAIN),
            'tab_overview' => __('Tab Übersicht', self::TEXT_DOMAIN),
            'tab_details' => __('Tab Details', self::TEXT_DOMAIN),
            'tab_history' => __('Tab Historie', self::TEXT_DOMAIN),
            'intro_text' => __('Einleitung', self::TEXT_DOMAIN),
            'necessary_label' => __('Kategorie Notwendig', self::TEXT_DOMAIN),
            'necessary_info' => __('Info Notwendig', self::TEXT_DOMAIN),
            'statistics_label' => __('Kategorie Statistik', self::TEXT_DOMAIN),
            'statistics_inactive_label' => __('Statistik inaktiv', self::TEXT_DOMAIN),
            'statistics_info' => __('Info Statistik', self::TEXT_DOMAIN),
            'statistics_inactive_info' => __('Info Statistik inaktiv', self::TEXT_DOMAIN),
            'marketing_label' => __('Kategorie Marketing', self::TEXT_DOMAIN),
            'marketing_inactive_label' => __('Marketing inaktiv', self::TEXT_DOMAIN),
            'marketing_info' => __('Info Marketing', self::TEXT_DOMAIN),
            'marketing_inactive_info' => __('Info Marketing inaktiv', self::TEXT_DOMAIN),
            'external_media_label' => __('Kategorie Externe Medien', self::TEXT_DOMAIN),
            'external_media_inactive_label' => __('Externe Medien inaktiv', self::TEXT_DOMAIN),
            'external_media_info' => __('Info Externe Medien', self::TEXT_DOMAIN),
            'external_media_inactive_info' => __('Info Externe Medien inaktiv', self::TEXT_DOMAIN),
            'info_default' => __('Info Standard', self::TEXT_DOMAIN),
            'details_intro' => __('Details-Einleitung', self::TEXT_DOMAIN),
            'history_intro' => __('Historie-Einleitung', self::TEXT_DOMAIN),
            'consent_id_label' => __('Consent-ID Label', self::TEXT_DOMAIN),
            'history_empty' => __('Historie leer', self::TEXT_DOMAIN),
            'reject_button' => __('Button ablehnen', self::TEXT_DOMAIN),
            'accept_all_button' => __('Button akzeptieren', self::TEXT_DOMAIN),
            'save_button' => __('Button speichern', self::TEXT_DOMAIN),
            'customize_button' => __('Button Auswahl anpassen', self::TEXT_DOMAIN),
            'settings_link' => __('Link Cookie-Einstellungen', self::TEXT_DOMAIN),
            'floating_aria_label' => __('Floating-Button ARIA-Label', self::TEXT_DOMAIN),
            'service_always_on' => __('Service immer aktiv', self::TEXT_DOMAIN),
            'service_description_label' => __('Service Beschreibung Label', self::TEXT_DOMAIN),
            'service_provider_label' => __('Service Provider Label', self::TEXT_DOMAIN),
            'service_cookies_label' => __('Service Cookies Label', self::TEXT_DOMAIN),
            'service_privacy_label' => __('Service Datenschutz Label', self::TEXT_DOMAIN),
            'service_cookie_policy_label' => __('Service Cookierichtlinie Label', self::TEXT_DOMAIN),
            'service_legal_basis_label' => __('Service Rechtsgrundlage Label', self::TEXT_DOMAIN),
            'service_third_country_label' => __('Service Drittlandübermittlung Label', self::TEXT_DOMAIN),
            'service_recipient_country_label' => __('Service Empfängerland Label', self::TEXT_DOMAIN),
            'service_safeguards_label' => __('Service Garantien Label', self::TEXT_DOMAIN),
            'service_count_single' => __('Service Anzahl Singular', self::TEXT_DOMAIN),
            'service_count_plural' => __('Service Anzahl Plural', self::TEXT_DOMAIN),
            'cookie_name_label' => __('Cookie Name Label', self::TEXT_DOMAIN),
            'cookie_expiry_label' => __('Cookie Laufzeit Label', self::TEXT_DOMAIN),
            'cookie_purpose_label' => __('Cookie Zweck Label', self::TEXT_DOMAIN),
            'history_date_label' => __('Historie Datum Label', self::TEXT_DOMAIN),
            'history_status_label' => __('Historie Status Label', self::TEXT_DOMAIN),
            'necessary_service_name' => __('Notwendiger Service Name', self::TEXT_DOMAIN),
            'necessary_service_purpose' => __('Notwendiger Service Zweck', self::TEXT_DOMAIN),
            'necessary_cookie_expiry' => __('Notwendiger Cookie Laufzeit', self::TEXT_DOMAIN),
            'necessary_cookie_type' => __('Notwendiger Cookie Typ', self::TEXT_DOMAIN),
            'necessary_cookie_purpose' => __('Notwendiger Cookie Zweck', self::TEXT_DOMAIN),
        ];
    }

    private function render_input_row(string $key, string $label, array $options, string $help = ''): void
    {
        printf(
            '<tr><th scope="row"><span class="n24cm-field-label"><label for="n24-consent-manager-%1$s">%2$s</label>%5$s</span></th><td><input id="n24-consent-manager-%1$s" type="text" class="regular-text" name="%3$s[%1$s]" value="%4$s"></td></tr>',
            esc_attr($key),
            esc_html($label),
            esc_attr(self::OPTION_NAME),
            esc_attr($options[$key] ?? ''),
            $this->render_info_icon($help)
        );
    }

    private function render_checkbox_row(string $key, string $label, array $options, string $help = ''): void
    {
        printf(
            '<tr><th scope="row"><span class="n24cm-field-label">%2$s%6$s</span></th><td><label for="n24-consent-manager-%1$s"><input id="n24-consent-manager-%1$s" type="checkbox" name="%3$s[%1$s]" value="1" %4$s> %5$s</label></td></tr>',
            esc_attr($key),
            esc_html($label),
            esc_attr(self::OPTION_NAME),
            checked($options[$key] ?? '0', '1', false),
            esc_html__('Aktivieren', self::TEXT_DOMAIN),
            $this->render_info_icon($help)
        );
    }

    private function render_plain_textarea_row(string $key, string $label, array $options): void
    {
        printf(
            '<tr><th scope="row"><label for="n24-consent-manager-%1$s">%2$s</label></th><td><textarea id="n24-consent-manager-%1$s" class="large-text" rows="3" name="%3$s[%1$s]">%4$s</textarea></td></tr>',
            esc_attr($key),
            esc_html($label),
            esc_attr(self::OPTION_NAME),
            esc_textarea($options[$key] ?? '')
        );
    }

    private function render_service_settings_panel(string $category, string $title, array $options): void
    {
        $services = $this->get_admin_services_for_category($category, $options);

        if (!$services) {
            $services = [$this->get_empty_service_settings()];
        }
        ?>
        <p><?php echo esc_html__('Hier kannst du optionale Dienste für diese Consent-Kategorie pflegen. Aktivierte Dienste erscheinen im Banner und in den Cookie-Details.', self::TEXT_DOMAIN); ?></p>
        <?php if ($category === 'external_media') : ?>
            <p class="description"><?php echo esc_html__('Die Content-Blocker-Einstellungen findest du direkt am Ende des jeweiligen externen Mediendienstes.', self::TEXT_DOMAIN); ?></p>
        <?php endif; ?>
        <div class="n24cm-service-editor" data-category="<?php echo esc_attr($category); ?>" data-next-index="<?php echo esc_attr((string) count($services)); ?>">
            <div class="n24cm-service-editor-header">
                <h3><?php echo esc_html($title); ?></h3>
                <button type="button" class="button button-secondary n24cm-add-service"><?php echo esc_html__('Dienst hinzufügen', self::TEXT_DOMAIN); ?></button>
            </div>
            <div class="n24cm-service-tabs" role="tablist" aria-label="<?php echo esc_attr__('Dienste', self::TEXT_DOMAIN); ?>">
                <?php
                foreach (array_values($services) as $service_index => $service) {
                    $this->render_service_tab_button((string) $service_index, $service, $service_index === 0);
                }
                ?>
            </div>
            <div class="n24cm-service-list">
                <?php
                foreach (array_values($services) as $service_index => $service) {
                    $this->render_service_card($category, (string) $service_index, $service, $service_index === 0, $options);
                }
                ?>
            </div>
            <template class="n24cm-service-tab-template">
                <?php $this->render_service_tab_button('__SERVICE_INDEX__', $this->get_empty_service_settings(), false); ?>
            </template>
            <template class="n24cm-service-template">
                <?php $this->render_service_card($category, '__SERVICE_INDEX__', $this->get_empty_service_settings(), false, $options); ?>
            </template>
        </div>
        <?php
    }

    private function render_service_template_library(): void
    {
        $options = $this->get_options();
        $groups = $this->get_available_service_template_library();
        ?>
        <div class="n24cm-template-library">
            <p><?php echo esc_html__('Diese Vorlagen werden nicht automatisch gespeichert. Mit Hinzufügen wird ein inaktiver Dienst in der passenden Kategorie angelegt, den du danach prüfen und aktivieren kannst.', self::TEXT_DOMAIN); ?></p>
            <?php foreach ($groups as $group_label => $templates) : ?>
                <section class="n24cm-template-group">
                    <h3><?php echo esc_html($group_label); ?></h3>
                    <div class="n24cm-template-grid">
                        <?php foreach ($templates as $template) : ?>
                            <div class="n24cm-template-card">
                                <div>
                                    <strong><?php echo esc_html($template['name']); ?></strong>
                                    <span class="n24cm-template-target">
                                        <?php
                                        printf(
                                            esc_html__('Wird unter %s hinzugefügt', self::TEXT_DOMAIN),
                                            esc_html($this->get_service_category_label($template['category']))
                                        );
                                        ?>
                                    </span>
                                </div>
                                <p><?php echo esc_html($template['purpose']); ?></p>
                                <button type="button" class="button button-secondary n24cm-add-template-service" data-template-key="<?php echo esc_attr($template['preset_key']); ?>" data-template-category="<?php echo esc_attr($template['category']); ?>">
                                    <?php echo esc_html__('Hinzufügen', self::TEXT_DOMAIN); ?>
                                </button>
                            </div>
                            <template class="n24cm-template-service-tab" data-template-key="<?php echo esc_attr($template['preset_key']); ?>">
                                <?php $this->render_service_tab_button('__SERVICE_INDEX__', $template, false); ?>
                            </template>
                            <template class="n24cm-template-service-card" data-template-key="<?php echo esc_attr($template['preset_key']); ?>">
                                <?php $this->render_service_card($template['category'], '__SERVICE_INDEX__', $template, false, $options); ?>
                            </template>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_content_blocker_settings_panel(array $options): void
    {
        $blockers = $this->get_content_blocker_definitions();
        ?>
        <div class="n24cm-content-blocker-settings">
            <p><?php echo esc_html__('Content-Blocker ersetzen externe Inhalte wie Videos, Karten oder Social-Media-Embeds durch einen Platzhalter. Der Originalinhalt wird erst geladen, wenn der passende Dienst erlaubt wurde.', self::TEXT_DOMAIN); ?></p>
            <div class="notice notice-info inline">
                <p>
                    <strong><?php echo esc_html__('Instagram manuell einfügen', self::TEXT_DOMAIN); ?></strong><br>
                    <?php echo esc_html__('Wenn WordPress einen Instagram-Link nicht einbetten kann, nutze einen Shortcode-Block mit:', self::TEXT_DOMAIN); ?>
                    <code>[n24_instagram url="https://www.instagram.com/p/BEITRAG/"]</code>
                </p>
            </div>
            <table class="form-table" role="presentation">
                <?php
                $this->render_checkbox_row('content_blocker_enabled', __('Content-Blocker aktivieren', self::TEXT_DOMAIN), $options);
                $this->render_textarea_row('content_blocker_text', __('Platzhalter-Text', self::TEXT_DOMAIN), $options);
                $this->render_textarea_row('content_blocker_button', __('Button-Text einmalig laden', self::TEXT_DOMAIN), $options);
                $this->render_textarea_row('content_blocker_always_button', __('Button-Text immer laden', self::TEXT_DOMAIN), $options);
                $this->render_textarea_row('content_blocker_missing_service_text', __('Hinweis, wenn kein passender Dienst aktiv ist', self::TEXT_DOMAIN), $options);
                ?>
            </table>

            <h3><?php echo esc_html__('Farben im Inhalts-Platzhalter', self::TEXT_DOMAIN); ?></h3>
            <table class="form-table" role="presentation">
                <?php
                $this->render_color_row('content_blocker_link_color', __('Link Nutzungsbedingungen', self::TEXT_DOMAIN), $options);
                $this->render_color_row('content_blocker_link_hover_color', __('Link Nutzungsbedingungen Hover', self::TEXT_DOMAIN), $options);
                $this->render_color_row('content_blocker_primary_button_background', __('Button Inhalt laden Hintergrund', self::TEXT_DOMAIN), $options);
                $this->render_color_row('content_blocker_primary_button_text', __('Button Inhalt laden Text', self::TEXT_DOMAIN), $options);
                $this->render_color_row('content_blocker_primary_button_hover_background', __('Button Inhalt laden Hover Hintergrund', self::TEXT_DOMAIN), $options);
                $this->render_color_row('content_blocker_primary_button_hover_text', __('Button Inhalt laden Hover Text', self::TEXT_DOMAIN), $options);
                $this->render_color_row('content_blocker_secondary_button_background', __('Button Immer laden Hintergrund', self::TEXT_DOMAIN), $options);
                $this->render_color_row('content_blocker_secondary_button_text', __('Button Immer laden Text', self::TEXT_DOMAIN), $options);
                $this->render_color_row('content_blocker_secondary_button_hover_background', __('Button Immer laden Hover Hintergrund', self::TEXT_DOMAIN), $options);
                $this->render_color_row('content_blocker_secondary_button_hover_text', __('Button Immer laden Hover Text', self::TEXT_DOMAIN), $options);
                ?>
            </table>

            <h3><?php echo esc_html__('Automatisch erkannte Inhalte', self::TEXT_DOMAIN); ?></h3>
            <table class="widefat striped n24cm-content-blocker-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Blocker', self::TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Verknüpfter Dienst', self::TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Erkannte Quellen', self::TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Aktiv', self::TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blockers as $blocker) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($blocker['label']); ?></strong></td>
                            <td><?php echo esc_html($blocker['service_name']); ?></td>
                            <td><code><?php echo esc_html(implode(', ', $blocker['hosts'])); ?></code></td>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[<?php echo esc_attr($blocker['option_key']); ?>]" value="1" <?php checked($options[$blocker['option_key']] ?? '0', '1'); ?>>
                                    <?php echo esc_html__('Blockieren', self::TEXT_DOMAIN); ?>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="description"><?php echo esc_html__('Wichtig: Der verknüpfte Dienst sollte unter Marketing angelegt und aktiviert sein, damit Besucher den Inhalt gezielt freischalten können.', self::TEXT_DOMAIN); ?></p>
            <?php $this->render_content_blocker_embed_image_settings($options); ?>
        </div>
        <?php
    }

    private function render_content_blocker_embed_image_settings(array $options, string $service_id = '', array $embeds = []): void
    {
        if (!$embeds) {
            $embeds = $this->get_detected_content_blocker_embeds();
        }

        if ($service_id !== '') {
            $embeds = array_values(array_filter(
                $embeds,
                static function (array $embed) use ($service_id): bool {
                    return ($embed['blocker_key'] ?? '') === $service_id;
                }
            ));
        }

        $images = $this->sanitize_url_map($options['content_blocker_embed_images'] ?? []);
        $titles = $this->sanitize_text_map($options['content_blocker_embed_titles'] ?? []);
        ?>
        <?php if ($service_id === '') : ?>
            <h3><?php echo esc_html__('Platzhalterbilder pro Embed', self::TEXT_DOMAIN); ?></h3>
            <p><?php echo esc_html__('Hier erscheinen erkannte externe Embeds aus Seiten und Beiträgen. Hinterlege pro Embed einen Titel und optional eine Bild-URL oder wähle ein Bild aus der Mediathek.', self::TEXT_DOMAIN); ?></p>
        <?php else : ?>
            <p class="description"><?php echo esc_html__('Hier erscheinen erkannte Einbettungen dieses Dienstes aus Seiten und Beiträgen.', self::TEXT_DOMAIN); ?></p>
        <?php endif; ?>
        <?php if (!$embeds) : ?>
            <p class="description"><?php echo esc_html__('Aktuell wurden keine unterstützten Embeds gefunden.', self::TEXT_DOMAIN); ?></p>
            <?php return; ?>
        <?php endif; ?>
        <table class="widefat striped n24cm-embed-image-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Embed', self::TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Quelle', self::TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Platzhalter-Titel', self::TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Platzhalterbild', self::TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($embeds as $embed) : ?>
                    <?php $image_url = $images[$embed['key']] ?? ''; ?>
                    <?php $title = $titles[$embed['key']] ?? ''; ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($embed['service_label']); ?></strong><br>
                            <span class="description"><?php echo esc_html($embed['post_title']); ?></span>
                        </td>
                        <td><code><?php echo esc_html($embed['url']); ?></code></td>
                        <td>
                            <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[content_blocker_embed_titles][<?php echo esc_attr($embed['key']); ?>]" value="<?php echo esc_attr($title); ?>" placeholder="<?php echo esc_attr__('z. B. Video 1', self::TEXT_DOMAIN); ?>">
                        </td>
                        <td>
                            <div class="n24cm-embed-image-field">
                                <input type="url" class="regular-text n24cm-embed-image-url" name="<?php echo esc_attr(self::OPTION_NAME); ?>[content_blocker_embed_images][<?php echo esc_attr($embed['key']); ?>]" value="<?php echo esc_attr($image_url); ?>" placeholder="https://...">
                                <button type="button" class="button n24cm-select-embed-image"><?php echo esc_html__('Bild wählen', self::TEXT_DOMAIN); ?></button>
                                <button type="button" class="button-link-delete n24cm-clear-embed-image"><?php echo esc_html__('Entfernen', self::TEXT_DOMAIN); ?></button>
                            </div>
                            <div class="n24cm-embed-image-preview" <?php echo $image_url ? sprintf('style="background-image:url(%s)"', esc_url($image_url)) : ''; ?>></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function get_available_service_template_library(): array
    {
        $options = $this->get_options();
        $present_keys = $this->get_present_preset_keys($options);

        foreach (['statistics', 'marketing', 'external_media'] as $category) {
            foreach ($this->get_admin_services_for_category($category, $options) as $service) {
                $preset_key = sanitize_key($service['preset_key'] ?? '');

                if ($preset_key !== '') {
                    $present_keys[] = $preset_key;
                }
            }
        }

        $present_keys = array_values(array_unique($present_keys));
        $groups = self::get_service_template_library();

        foreach ($groups as $group_label => $templates) {
            $groups[$group_label] = array_values(array_filter(array_map(
                function ($template): array {
                    return is_array($template) ? $this->prepare_service_template($template) : [];
                },
                $templates
            ), function ($template) use ($present_keys) {
                return !empty($template) && (empty($template['preset_key']) || !in_array($template['preset_key'], $present_keys, true));
            }));

            if (!$groups[$group_label]) {
                unset($groups[$group_label]);
            }
        }

        return $groups;
    }

    private function prepare_service_template(array $template): array
    {
        $category = (string) ($template['category'] ?? '');
        $preset_key = sanitize_key($template['preset_key'] ?? '');

        if ($category === '' && $preset_key !== '') {
            foreach (['statistics', 'marketing', 'external_media'] as $candidate_category) {
                if (self::is_external_media_preset_key($preset_key)) {
                    $category = 'external_media';
                    break;
                }

                foreach (self::get_preset_services_for_category($candidate_category) as $preset_service) {
                    if (sanitize_key($preset_service['preset_key'] ?? '') === $preset_key) {
                        $category = $candidate_category;
                        break 2;
                    }
                }
            }
        }

        $template = array_merge(
            $this->get_default_service_compliance_fields($preset_key, $category),
            $template
        );

        if (!isset($template['enabled'])) {
            $template['enabled'] = '0';
        }

        return $template;
    }

    private function get_service_category_label(string $category): string
    {
        if ($category === 'statistics') {
            return __('Statistik', self::TEXT_DOMAIN);
        }

        if ($category === 'external_media') {
            return __('Externe Medien', self::TEXT_DOMAIN);
        }

        return __('Marketing', self::TEXT_DOMAIN);
    }

    private function render_service_tab_button(string $service_index, array $service, bool $is_active): void
    {
        $label = trim((string) ($service['name'] ?? ''));

        if ($label === '') {
            $label = __('Neuer Dienst', self::TEXT_DOMAIN);
        }
        ?>
        <button type="button" class="n24cm-service-tab<?php echo $is_active ? ' is-active' : ''; ?>" role="tab" data-service-tab="<?php echo esc_attr($service_index); ?>" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
            <?php echo esc_html($label); ?>
        </button>
        <?php
    }

    private function get_service_field_help(array $service, string $category): array
    {
        $preset_key = sanitize_key($service['preset_key'] ?? '');
        $name = trim((string) ($service['name'] ?? ''));
        $service_label = $name !== '' ? $name : __('diesen Dienst', self::TEXT_DOMAIN);
        $help = [];

        foreach (['provider', 'address', 'privacy_url', 'cookie_policy_url', 'legal_basis', 'third_country_transfer', 'recipient_country', 'safeguards'] as $field) {
            if (trim((string) ($service[$field] ?? '')) === '') {
                $help[$field] = __('Bitte manuell eintragen: Verwende die Angaben aus deinem Vertrag, deinem Anbieterkonto oder der offiziellen Datenschutzerklärung des Dienstes.', self::TEXT_DOMAIN);
            }
        }

        $help['legal_basis'] = __('Für optionale Dienste in der Regel: Einwilligung, Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TDDDG. Für notwendige Dienste bitte separat prüfen.', self::TEXT_DOMAIN);
        $help['third_country_transfer'] = __('Gib an, ob Daten außerhalb der EU/des EWR verarbeitet werden können, z. B. „möglich“ oder „nein“.', self::TEXT_DOMAIN);
        $help['recipient_country'] = __('Trage Empfängerländer ein, z. B. „Irland/EU“ oder „USA“. Bei globalen Anbietern mögliche Drittländer nennen.', self::TEXT_DOMAIN);
        $help['safeguards'] = __('Beschreibe geeignete Garantien, z. B. EU-Standardvertragsklauseln, Angemessenheitsbeschluss oder vertragliche Schutzmaßnahmen.', self::TEXT_DOMAIN);

        if ($preset_key === 'matomo') {
            $help['provider'] = __('Bei selbst gehostetem Matomo bist in der Regel du bzw. dein Unternehmen der Anbieter. Bei Matomo Cloud trägst du die Angaben von Matomo/InnoCraft ein.', self::TEXT_DOMAIN);
            $help['address'] = __('Bei selbst gehostetem Matomo deine Anbieteradresse eintragen. Bei Matomo Cloud die Anbieteradresse aus deinem Matomo-Vertrag prüfen.', self::TEXT_DOMAIN);
            $help['privacy_url'] = __('Trage die URL deiner eigenen Datenschutzerklärung ein, in der du Matomo erklärst. Bei Matomo Cloud zusätzlich die Matomo-Hinweise prüfen.', self::TEXT_DOMAIN);
            $help['cookie_policy_url'] = __('Trage deine Cookie-Richtlinie oder den Abschnitt deiner Datenschutzerklärung ein, in dem die Matomo-Cookies beschrieben sind.', self::TEXT_DOMAIN);
        }

        $help['service_id'] = $this->get_service_id_help($preset_key, $category, $service_label);
        $help['embed_code'] = $this->get_service_embed_code_help($service, $category);
        $help['opt_out_code'] = __('Optional: Code, der beim Widerruf dieses Dienstes ausgeführt wird, z. B. zum Löschen eigener Cookies oder zum Zurücksetzen eines Anbieters. Nur verwenden, wenn der Anbieter einen solchen Code dokumentiert.', self::TEXT_DOMAIN);

        return array_filter($help);
    }

    private function get_service_id_help(string $preset_key, string $category, string $service_label): string
    {
        $help_by_key = [
            'google_analytics_4' => __('Trage hier die GA4 Measurement-ID ein, z. B. G-XXXXXXXXXX. Alternativ kannst du unten den vollständigen gtag.js-Code einfügen.', self::TEXT_DOMAIN),
            'matomo' => __('Matomo benötigt normalerweise die Matomo-URL und Site-ID im vollständigen Tracking-Code. Eine einzelne ID reicht hier meist nicht aus.', self::TEXT_DOMAIN),
            'google_ads' => __('Trage hier nur dann eine ID ein, wenn du einen einfachen Google-Ads-Basis-Tag laden willst, z. B. AW-XXXXXXXXXX. Für Conversion-Labels nutze den vollständigen Code unten.', self::TEXT_DOMAIN),
            'meta_pixel' => __('Trage hier deine Meta-Pixel-ID ein oder füge unten den vollständigen Meta-Pixel-Basiscode aus dem Events Manager ein.', self::TEXT_DOMAIN),
            'linkedin_insight_tag' => __('Trage hier deine LinkedIn Partner-ID ein oder füge unten den vollständigen Insight-Tag-Code ein.', self::TEXT_DOMAIN),
            'google_tag_manager' => __('Trage hier die Container-ID ein, z. B. GTM-XXXXXXX. Alternativ kannst du unten den vollständigen Container-Code einfügen.', self::TEXT_DOMAIN),
            'tiktok_pixel' => __('Trage hier nur eine ID ein, wenn dein Dienst-Code das unterstützt. Sicherer ist der vollständige Pixel-Code aus dem TikTok Events Manager unten.', self::TEXT_DOMAIN),
            'pinterest_tag' => __('Trage hier nur eine ID ein, wenn dein Dienst-Code das unterstützt. Sicherer ist der vollständige Pinterest-Tag-Code aus Pinterest Ads unten.', self::TEXT_DOMAIN),
            'x_ads_pixel' => __('Trage hier nur eine ID ein, wenn dein Dienst-Code das unterstützt. Sicherer ist der vollständige X Website Tag unten.', self::TEXT_DOMAIN),
        ];

        if (isset($help_by_key[$preset_key])) {
            return $help_by_key[$preset_key];
        }

        if ($category === 'external_media') {
            return sprintf(
                /* translators: %s: service name */
                __('Für %s ist dieses Feld meistens nicht nötig. Normale eingebettete Inhalte werden über den Content-Blocker im Beitrag erkannt. Nutze das Feld nur für eine anbieterspezifische ID.', self::TEXT_DOMAIN),
                $service_label
            );
        }

        return sprintf(
            /* translators: %s: service name */
            __('Falls %s eine Projekt-, Tracking- oder Container-ID bereitstellt, trägst du sie hier ein. Wenn der Anbieter nur einen kompletten Code ausgibt, nutze das Script-Feld darunter.', self::TEXT_DOMAIN),
            $service_label
        );
    }

    private function get_service_embed_code_help(array $service, string $category): string
    {
        $preset_key = sanitize_key($service['preset_key'] ?? '');
        $name = trim((string) ($service['name'] ?? ''));
        $service_label = $name !== '' ? $name : __('diesen Dienst', self::TEXT_DOMAIN);

        $help_by_key = [
            'google_analytics_4' => __('Dieser Code wird erst nach Statistik-Zustimmung geladen. Für Google Analytics 4 reicht alternativ die Measurement-ID im Feld darüber, z. B. G-XXXXXXXXXX.', self::TEXT_DOMAIN),
            'matomo' => __('Füge hier den vollständigen Matomo-Tracking-Code aus Matomo > Verwaltung > Tracking-Code ein. Das Plugin erzeugt Matomo-Code nicht automatisch, weil Matomo-URL und Site-ID projektspezifisch sind.', self::TEXT_DOMAIN),
            'google_ads' => __('Füge hier den vollständigen Google-Ads-Conversion- oder Remarketing-Code ein, wenn du mehr als die einfache AW-ID benötigst.', self::TEXT_DOMAIN),
            'meta_pixel' => __('Füge hier den vollständigen Meta-Pixel-Basiscode aus dem Events Manager ein, falls du nicht nur mit der Pixel-ID arbeiten möchtest.', self::TEXT_DOMAIN),
            'linkedin_insight_tag' => __('Füge hier den vollständigen LinkedIn Insight Tag ein, falls du nicht nur mit der Partner-ID arbeiten möchtest.', self::TEXT_DOMAIN),
            'google_tag_manager' => __('Für Google Tag Manager reicht alternativ die Container-ID, z. B. GTM-XXXXXXX. Der Code wird erst nach Zustimmung dieser Kategorie geladen.', self::TEXT_DOMAIN),
        ];

        if (isset($help_by_key[$preset_key])) {
            return $help_by_key[$preset_key];
        }

        if ($category === 'external_media') {
            return sprintf(
                /* translators: %s: service name */
                __('Für sichtbare %s-Einbettungen nutze normalerweise den WordPress-Embed oder Shortcode im Beitrag. Dieses Feld ist nur für zusätzliche globale Scripte gedacht und wird erst nach Zustimmung geladen.', self::TEXT_DOMAIN),
                $service_label
            );
        }

        return __('Dieser Code wird im Frontend erst geladen, wenn der Dienst bzw. die passende Kategorie erlaubt wurde. Verwende hier den aktuellen Code aus dem Anbieter-Backend.', self::TEXT_DOMAIN);
    }

    private function render_service_card(string $category, string $service_index, array $service, bool $is_active, array $options = []): void
    {
        $base_name = sprintf('%s[%s_services][%s]', self::OPTION_NAME, $category, $service_index);
        $base_id = sprintf('n24-consent-manager-%s-services-%s', $category, $service_index);
        $cookies = $service['cookies'] ?? [];
        $field_help = $this->get_service_field_help($service, $category);

        if (!$cookies) {
            $cookies = [$this->get_empty_cookie_settings()];
        }
        ?>
        <div class="n24cm-service-card<?php echo $is_active ? ' is-active' : ''; ?>" data-service-index="<?php echo esc_attr($service_index); ?>" <?php echo $is_active ? '' : 'hidden'; ?>>
            <input type="hidden" name="<?php echo esc_attr($base_name); ?>[preset_key]" value="<?php echo esc_attr($service['preset_key'] ?? ''); ?>">
            <div class="n24cm-service-card-header">
                <label>
                    <input type="checkbox" name="<?php echo esc_attr($base_name); ?>[enabled]" value="1" <?php checked($service['enabled'] ?? '0', '1'); ?>>
                    <strong><?php echo esc_html__('Dienst aktiv', self::TEXT_DOMAIN); ?></strong>
                </label>
                <button type="button" class="button-link-delete n24cm-remove-service"><?php echo esc_html__('Dienst entfernen', self::TEXT_DOMAIN); ?></button>
            </div>
            <div class="n24cm-service-grid">
                <?php
                $this->render_direct_input_row($base_id . '-name', $base_name . '[name]', __('Dienstname', self::TEXT_DOMAIN), $service['name'] ?? '', 'n24cm-service-name-field');
                $this->render_direct_input_row($base_id . '-provider', $base_name . '[provider]', __('Provider', self::TEXT_DOMAIN), $service['provider'] ?? '', '', $field_help['provider'] ?? '');
                $this->render_direct_input_row($base_id . '-address', $base_name . '[address]', __('Anbieteradresse', self::TEXT_DOMAIN), $service['address'] ?? '', '', $field_help['address'] ?? '');
                $this->render_direct_input_row($base_id . '-privacy-url', $base_name . '[privacy_url]', __('URL Datenschutzerklärung', self::TEXT_DOMAIN), $service['privacy_url'] ?? '', '', $field_help['privacy_url'] ?? '');
                $this->render_direct_input_row($base_id . '-cookie-policy-url', $base_name . '[cookie_policy_url]', __('URL Cookierichtlinie / Nutzungsbedingungen', self::TEXT_DOMAIN), $service['cookie_policy_url'] ?? '', '', $field_help['cookie_policy_url'] ?? '');
                $this->render_direct_input_row($base_id . '-service-id', $base_name . '[service_id]', __('Dienst-/Tracking-ID', self::TEXT_DOMAIN), $service['service_id'] ?? '', '', $field_help['service_id'] ?? '');
                $this->render_direct_textarea_row($base_id . '-purpose', $base_name . '[purpose]', __('Zweck/Beschreibung', self::TEXT_DOMAIN), $service['purpose'] ?? '');
                $this->render_direct_input_row($base_id . '-legal-basis', $base_name . '[legal_basis]', __('Rechtsgrundlage', self::TEXT_DOMAIN), $service['legal_basis'] ?? '', '', $field_help['legal_basis'] ?? '');
                $this->render_direct_input_row($base_id . '-third-country-transfer', $base_name . '[third_country_transfer]', __('Drittlandübermittlung', self::TEXT_DOMAIN), $service['third_country_transfer'] ?? '', '', $field_help['third_country_transfer'] ?? '');
                $this->render_direct_input_row($base_id . '-recipient-country', $base_name . '[recipient_country]', __('Empfängerland', self::TEXT_DOMAIN), $service['recipient_country'] ?? '', '', $field_help['recipient_country'] ?? '');
                $this->render_direct_textarea_row($base_id . '-safeguards', $base_name . '[safeguards]', __('Garantien / Schutzmaßnahmen', self::TEXT_DOMAIN), $service['safeguards'] ?? '', 3, '', $field_help['safeguards'] ?? '');
                ?>
            </div>
            <div class="n24cm-service-code">
                <?php $this->render_direct_textarea_row($base_id . '-embed-code', $base_name . '[embed_code]', __('Einbettungs-Code / Script', self::TEXT_DOMAIN), $service['embed_code'] ?? '', 6, 'code', $field_help['embed_code'] ?? ''); ?>
                <p class="description"><?php echo esc_html($this->get_service_embed_code_help($service, $category)); ?></p>
                <?php $this->render_direct_textarea_row($base_id . '-opt-out-code', $base_name . '[opt_out_code]', __('Opt-out-Code / Cleanup Script', self::TEXT_DOMAIN), $service['opt_out_code'] ?? '', 4, 'code', $field_help['opt_out_code'] ?? ''); ?>
            </div>
            <div class="n24cm-cookie-editor" data-next-cookie-index="<?php echo esc_attr((string) count($cookies)); ?>">
                <div class="n24cm-cookie-editor-header">
                    <h4><?php echo esc_html__('Cookies', self::TEXT_DOMAIN); ?></h4>
                    <button type="button" class="button n24cm-add-cookie"><?php echo esc_html__('Cookie hinzufügen', self::TEXT_DOMAIN); ?></button>
                </div>
                <div class="n24cm-cookie-list">
                    <?php
                    foreach (array_values($cookies) as $cookie_index => $cookie) {
                        $this->render_cookie_card($base_name, $base_id, (string) $cookie_index, $cookie);
                    }
                    ?>
                </div>
                <template class="n24cm-cookie-template">
                    <?php $this->render_cookie_card($base_name, $base_id, '__COOKIE_INDEX__', $this->get_empty_cookie_settings()); ?>
                </template>
            </div>
            <?php if ($category === 'external_media') : ?>
                <?php $this->render_external_media_content_blocker_section($service, $options); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_cookie_card(string $base_name, string $base_id, string $cookie_index, array $cookie): void
    {
        $cookie_name = $base_name . '[cookies][' . $cookie_index . ']';
        $cookie_id = $base_id . '-cookie-' . $cookie_index;
        ?>
        <div class="n24cm-cookie-card" data-cookie-index="<?php echo esc_attr($cookie_index); ?>">
            <button type="button" class="button-link-delete n24cm-remove-cookie"><?php echo esc_html__('Cookie entfernen', self::TEXT_DOMAIN); ?></button>
            <div class="n24cm-cookie-grid">
                <?php
                $this->render_direct_input_row($cookie_id . '-name', $cookie_name . '[name]', __('Cookie Name', self::TEXT_DOMAIN), $cookie['name'] ?? '');
                $this->render_direct_input_row($cookie_id . '-expiry', $cookie_name . '[expiry]', __('Cookie Laufzeit', self::TEXT_DOMAIN), $cookie['expiry'] ?? '');
                $this->render_direct_input_row($cookie_id . '-type', $cookie_name . '[type]', __('Cookie Typ', self::TEXT_DOMAIN), $cookie['type'] ?? '');
                $this->render_direct_textarea_row($cookie_id . '-purpose', $cookie_name . '[purpose]', __('Cookie Zweck', self::TEXT_DOMAIN), $cookie['purpose'] ?? '');
                ?>
            </div>
        </div>
        <?php
    }

    private function render_external_media_content_blocker_section(array $service, array $options): void
    {
        $service_id = sanitize_key($service['preset_key'] ?? '');
        $definition = $this->get_content_blocker_definition($service_id);

        if (!$definition) {
            return;
        }

        $base_name = sprintf('%s[content_blocker_service_settings][%s]', self::OPTION_NAME, $service_id);
        $base_id = sprintf('n24-consent-manager-content-blocker-%s', $service_id);
        $embeds = array_values(array_filter(
            $this->get_detected_content_blocker_embeds(),
            static function (array $embed) use ($service_id): bool {
                return ($embed['blocker_key'] ?? '') === $service_id;
            }
        ));
        ?>
        <div class="n24cm-service-content-blocker">
            <h4><?php echo esc_html__('Content-Blocker für diesen Dienst', self::TEXT_DOMAIN); ?></h4>
            <p class="description"><?php echo esc_html__('Diese Einstellungen gelten nur für diesen externen Mediendienst. Leere Felder nutzen die bisherigen Standardwerte als Fallback.', self::TEXT_DOMAIN); ?></p>
            <?php if ($service_id === 'instagram') : ?>
                <p class="description">
                    <strong><?php echo esc_html__('Instagram manuell einfügen:', self::TEXT_DOMAIN); ?></strong>
                    <code>[n24_instagram url="https://www.instagram.com/p/BEITRAG/"]</code>
                </p>
            <?php endif; ?>
            <p>
                <label>
                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[<?php echo esc_attr($definition['option_key']); ?>]" value="1" <?php checked($options[$definition['option_key']] ?? '0', '1'); ?>>
                    <strong><?php echo esc_html__('Einbettungen dieses Dienstes blockieren', self::TEXT_DOMAIN); ?></strong>
                </label>
            </p>
            <div class="n24cm-service-grid">
                <?php
                $this->render_direct_textarea_row($base_id . '-text', $base_name . '[text]', __('Platzhalter-Text', self::TEXT_DOMAIN), $this->get_content_blocker_admin_text_value($options, $definition), 3);
                $this->render_direct_textarea_row($base_id . '-button', $base_name . '[button]', __('Button-Text einmalig laden', self::TEXT_DOMAIN), $this->get_content_blocker_admin_button_value($options, $definition), 2);
                $this->render_direct_textarea_row($base_id . '-always-button', $base_name . '[always_button]', __('Button-Text immer laden', self::TEXT_DOMAIN), $this->get_content_blocker_service_setting($options, $service_id, 'always_button'), 2);
                $this->render_direct_textarea_row($base_id . '-missing-text', $base_name . '[missing_service_text]', __('Hinweis, wenn kein passender Dienst aktiv ist', self::TEXT_DOMAIN), $this->get_content_blocker_service_setting($options, $service_id, 'missing_service_text'), 3);
                ?>
            </div>

            <h5><?php echo esc_html__('Farben im Platzhalter', self::TEXT_DOMAIN); ?></h5>
            <div class="n24cm-service-grid">
                <?php foreach ($this->get_content_blocker_service_color_fields() as $field => $config) : ?>
                    <?php $this->render_content_blocker_service_color_field($base_id . '-' . $field, $base_name . '[' . $field . ']', $config['label'], $this->get_content_blocker_service_setting($options, $service_id, $field)); ?>
                <?php endforeach; ?>
            </div>

            <h5><?php echo esc_html__('Platzhalterbilder dieses Dienstes', self::TEXT_DOMAIN); ?></h5>
            <?php $this->render_content_blocker_embed_image_settings($options, $service_id, $embeds); ?>
        </div>
        <?php
    }

    private function get_content_blocker_admin_text_value(array $options, array $definition): string
    {
        $service_id = (string) ($definition['service_id'] ?? '');
        $description = $this->get_content_blocker_service_setting($options, $service_id, 'text');

        if ($this->uses_default_content_placeholder_text($description)) {
            return $this->get_contextual_content_blocker_text($definition);
        }

        return $description;
    }

    private function get_content_blocker_admin_button_value(array $options, array $definition): string
    {
        $service_id = (string) ($definition['service_id'] ?? '');
        $button_text = $this->get_content_blocker_service_setting($options, $service_id, 'button');

        if ($this->uses_default_content_load_button($button_text)) {
            return $this->get_contextual_content_blocker_button($definition);
        }

        return $button_text;
    }

    private function render_content_blocker_service_color_field(string $id, string $name, string $label, string $value): void
    {
        printf(
            '<p><label for="%1$s"><strong>%2$s</strong></label><input id="%1$s" type="text" class="regular-text n24cm-color-field" name="%3$s" value="%4$s" placeholder="#a67c00 oder rgba(...)"></p>',
            esc_attr($id),
            esc_html($label),
            esc_attr($name),
            esc_attr($value)
        );
    }

    private function get_content_blocker_service_setting(array $options, string $service_id, string $field): string
    {
        $service_id = sanitize_key($service_id);
        $service_settings = $options['content_blocker_service_settings'][$service_id] ?? [];

        if (is_array($service_settings) && isset($service_settings[$field]) && trim((string) $service_settings[$field]) !== '') {
            return (string) $service_settings[$field];
        }

        $global_key = $this->get_global_content_blocker_setting_key($field);

        return $global_key !== '' ? (string) ($options[$global_key] ?? '') : '';
    }

    private function get_global_content_blocker_setting_key(string $field): string
    {
        $map = [
            'text' => 'content_blocker_text',
            'button' => 'content_blocker_button',
            'always_button' => 'content_blocker_always_button',
            'missing_service_text' => 'content_blocker_missing_service_text',
            'link_color' => 'content_blocker_link_color',
            'link_hover_color' => 'content_blocker_link_hover_color',
            'primary_button_background' => 'content_blocker_primary_button_background',
            'primary_button_text' => 'content_blocker_primary_button_text',
            'primary_button_hover_background' => 'content_blocker_primary_button_hover_background',
            'primary_button_hover_text' => 'content_blocker_primary_button_hover_text',
            'secondary_button_background' => 'content_blocker_secondary_button_background',
            'secondary_button_text' => 'content_blocker_secondary_button_text',
            'secondary_button_hover_background' => 'content_blocker_secondary_button_hover_background',
            'secondary_button_hover_text' => 'content_blocker_secondary_button_hover_text',
        ];

        return $map[$field] ?? '';
    }

    private function get_global_content_blocker_color_key(string $field): string
    {
        $global_key = $this->get_global_content_blocker_setting_key($field);

        return in_array($global_key, self::COLOR_FIELDS, true) ? $global_key : '';
    }

    private function get_content_blocker_service_color_fields(): array
    {
        return [
            'link_color' => ['label' => __('Link Nutzungsbedingungen', self::TEXT_DOMAIN), 'css_var' => '--ccm-content-blocker-link'],
            'link_hover_color' => ['label' => __('Link Nutzungsbedingungen Hover', self::TEXT_DOMAIN), 'css_var' => '--ccm-content-blocker-link-hover'],
            'primary_button_background' => ['label' => __('Button Inhalt laden Hintergrund', self::TEXT_DOMAIN), 'css_var' => '--ccm-content-blocker-primary-bg'],
            'primary_button_text' => ['label' => __('Button Inhalt laden Text', self::TEXT_DOMAIN), 'css_var' => '--ccm-content-blocker-primary-text'],
            'primary_button_hover_background' => ['label' => __('Button Inhalt laden Hover Hintergrund', self::TEXT_DOMAIN), 'css_var' => '--ccm-content-blocker-primary-hover-bg'],
            'primary_button_hover_text' => ['label' => __('Button Inhalt laden Hover Text', self::TEXT_DOMAIN), 'css_var' => '--ccm-content-blocker-primary-hover-text'],
            'secondary_button_background' => ['label' => __('Button Immer laden Hintergrund', self::TEXT_DOMAIN), 'css_var' => '--ccm-content-blocker-secondary-bg'],
            'secondary_button_text' => ['label' => __('Button Immer laden Text', self::TEXT_DOMAIN), 'css_var' => '--ccm-content-blocker-secondary-text'],
            'secondary_button_hover_background' => ['label' => __('Button Immer laden Hover Hintergrund', self::TEXT_DOMAIN), 'css_var' => '--ccm-content-blocker-secondary-hover-bg'],
            'secondary_button_hover_text' => ['label' => __('Button Immer laden Hover Text', self::TEXT_DOMAIN), 'css_var' => '--ccm-content-blocker-secondary-hover-text'],
        ];
    }

    private function render_direct_input_row(string $id, string $name, string $label, string $value, string $class = '', string $help = ''): void
    {
        printf(
            '<p><span class="n24cm-field-label"><label for="%1$s"><strong>%2$s</strong></label>%6$s</span><input id="%1$s" type="text" class="regular-text %5$s" name="%3$s" value="%4$s"></p>',
            esc_attr($id),
            esc_html($label),
            esc_attr($name),
            esc_attr($value),
            esc_attr($class),
            $this->render_info_icon($help)
        );
    }

    private function render_direct_textarea_row(string $id, string $name, string $label, string $value, int $rows = 3, string $class = '', string $help = ''): void
    {
        printf(
            '<p><span class="n24cm-field-label"><label for="%1$s"><strong>%2$s</strong></label>%7$s</span><textarea id="%1$s" class="large-text %6$s" rows="%5$d" name="%3$s">%4$s</textarea></p>',
            esc_attr($id),
            esc_html($label),
            esc_attr($name),
            esc_textarea($value),
            $rows,
            esc_attr($class),
            $this->render_info_icon($help)
        );
    }

    private function render_info_icon(string $help): string
    {
        if (trim($help) === '') {
            return '';
        }

        return sprintf(
            '<span class="n24cm-info-icon" tabindex="0" role="img" aria-label="%1$s" data-tooltip="%1$s"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm8.93 4.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM8 5.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/></svg></span>',
            esc_attr($help)
        );
    }

    private function render_color_row(string $key, string $label, array $options): void
    {
        $css_var = $this->get_css_var_for_color($key);

        printf(
            '<tr><th scope="row"><label for="n24-consent-manager-%1$s">%2$s</label></th><td><input id="n24-consent-manager-%1$s" type="text" class="regular-text n24cm-color-field" data-css-var="%5$s" name="%3$s[%1$s]" value="%4$s" placeholder="#a67c00 oder rgba(...)"></td></tr>',
            esc_attr($key),
            esc_html($label),
            esc_attr(self::OPTION_NAME),
            esc_attr($options[$key] ?? ''),
            esc_attr($css_var)
        );
    }

    private function render_textarea_row(string $key, string $label, array $options): void
    {
        printf(
            '<tr><th scope="row"><label for="n24-consent-manager-%1$s">%2$s</label></th><td><textarea id="n24-consent-manager-%1$s" class="large-text n24cm-preview-field" data-preview-target="%1$s" rows="2" name="%3$s[%1$s]">%4$s</textarea></td></tr>',
            esc_attr($key),
            esc_html($label),
            esc_attr(self::OPTION_NAME),
            esc_textarea($options[$key] ?? '')
        );
    }

    private function get_frontend_settings(): array
    {
        $options = $this->get_options();
        $storage_key = $options['storage_key'];

        $services = [
            'necessary' => [
                [
                    'id' => 'consent_manager',
                    'name' => $options['necessary_service_name'],
                    'provider' => $options['provider_name'],
                    'address' => $options['provider_address'],
                    'privacyUrl' => $options['privacy_url'],
                    'cookiePolicyUrl' => $options['privacy_url'],
                    'description' => $options['necessary_service_purpose'],
                    'purpose' => $options['necessary_service_purpose'],
                    'legalBasis' => __('Art. 6 Abs. 1 lit. f DSGVO und § 25 Abs. 2 TDDDG', self::TEXT_DOMAIN),
                    'thirdCountryTransfer' => __('Nein', self::TEXT_DOMAIN),
                    'recipientCountry' => __('Deutschland / EU', self::TEXT_DOMAIN),
                    'safeguards' => __('Technisch notwendige Speicherung der Consent-Entscheidung im Browser des Nutzers.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => $storage_key,
                            'expiry' => $options['necessary_cookie_expiry'],
                            'type' => $options['necessary_cookie_type'],
                            'purpose' => $options['necessary_cookie_purpose'],
                        ],
                    ],
                ],
            ],
            'statistics' => $this->get_optional_services_for_category('statistics', $options),
            'marketing' => $this->get_optional_services_for_category('marketing', $options),
            'external_media' => $this->get_optional_services_for_category('external_media', $options),
        ];

        $services = $this->append_content_blocker_services($services, $options);
        $services = apply_filters('n24_consent_manager_services', $services);

        return [
            'storageKey' => $storage_key,
            'cookieName' => $storage_key,
            'privacyUrl' => $options['privacy_url'],
            'imprintUrl' => $options['imprint_url'],
            'legalPathSlugs' => $this->get_legal_path_slugs($options),
            'providerName' => $options['provider_name'],
            'providerAddress' => $options['provider_address'],
            'bannerVersion' => $options['banner_version'] ?? self::VERSION,
            'privacyPolicyVersion' => $options['privacy_policy_version'] ?? '',
            'consentLogEnabled' => true,
            'consentLogEndpoint' => esc_url_raw(rest_url('n24-consent-manager/v1/consent-log')),
            'iconSvg' => $options['icon_svg'],
            'boxIconSvg' => $options['box_icon_svg'],
            'floatingIconSvg' => $options['floating_icon_svg'],
            'texts' => $this->get_text_options($options),
            'services' => $services,
            'contentBlockers' => $this->get_frontend_content_blocker_settings($options),
        ];
    }

    private function get_frontend_content_blocker_settings(array $options): array
    {
        $blockers = [];

        foreach ($this->get_content_blocker_definitions() as $blocker) {
            $blockers[$blocker['key']] = [
                'enabled' => $this->is_content_blocker_definition_enabled($blocker, $options),
                'serviceId' => $blocker['service_id'],
                'category' => $blocker['category'],
                'label' => $blocker['label'],
            ];
        }

        return [
            'enabled' => $this->is_content_blocker_enabled($options),
            'blockers' => $blockers,
        ];
    }

    private function is_content_blocker_enabled(array $options): bool
    {
        if (($options['content_blocker_enabled'] ?? '0') === '1') {
            return true;
        }

        foreach ($this->get_content_blocker_definitions() as $blocker) {
            if ($this->is_content_blocker_definition_enabled($blocker, $options)) {
                return true;
            }
        }

        return false;
    }

    private function is_content_blocker_definition_enabled(array $definition, array $options): bool
    {
        if (($options[$definition['option_key']] ?? '0') === '1') {
            return true;
        }

        $admin_service = $this->get_admin_service_for_content_blocker($definition, $options);

        return ($admin_service['enabled'] ?? '0') === '1';
    }

    private function append_content_blocker_services(array $services, array $options): array
    {
        if (!$this->is_content_blocker_enabled($options)) {
            return $services;
        }

        foreach ($this->get_content_blocker_definitions() as $definition) {
            if (!$this->is_content_blocker_definition_enabled($definition, $options)) {
                continue;
            }

            $category = $definition['category'];
            $service_id = $definition['service_id'];

            if (!isset($services[$category]) || !is_array($services[$category])) {
                $services[$category] = [];
            }

            $already_available = false;
            foreach ($services[$category] as $service) {
                if (($service['id'] ?? '') === $service_id) {
                    $already_available = true;
                    break;
                }
            }

            if ($already_available) {
                continue;
            }

            $admin_service = $this->get_admin_service_for_content_blocker($definition, $options);

            if (($admin_service['enabled'] ?? '0') !== '1') {
                continue;
            }

            $frontend_service = $this->format_service_for_frontend($admin_service, $category, $service_id);

            if ($frontend_service) {
                $services[$category][] = $frontend_service;
            }
        }

        return $services;
    }

    private function get_admin_service_for_content_blocker(array $definition, array $options): array
    {
        $categories = array_values(array_unique([$definition['category'], 'external_media', 'marketing']));

        foreach ($categories as $category) {
            foreach ($this->get_admin_services_for_category($category, $options) as $service) {
                if (!is_array($service)) {
                    continue;
                }

                if (sanitize_key($service['preset_key'] ?? '') === $definition['service_id']) {
                    return $service;
                }
            }
        }

        foreach ($this->get_external_media_template_services() as $service) {
            if (!is_array($service)) {
                continue;
            }

            if (sanitize_key($service['preset_key'] ?? '') === $definition['service_id']) {
                return $service;
            }
        }

        foreach (self::get_service_template_library() as $templates) {
            foreach ($templates as $template) {
                if (is_array($template) && sanitize_key($template['preset_key'] ?? '') === $definition['service_id']) {
                    return $template;
                }
            }
        }

        return [
            'preset_key' => $definition['service_id'],
            'name' => $definition['service_name'],
            'provider' => '',
            'address' => '',
            'privacy_url' => '',
            'cookie_policy_url' => '',
            'purpose' => '',
            'legal_basis' => '',
            'third_country_transfer' => '',
            'recipient_country' => '',
            'safeguards' => '',
            'service_id' => '',
            'embed_code' => '',
            'cookies' => [],
        ];
    }

    private function format_service_for_frontend(array $service, string $category, $fallback_index): ?array
    {
        if (self::is_deprecated_service_preset($service)) {
            return null;
        }

        $service = array_merge(
            $this->get_default_service_compliance_fields(sanitize_key($service['preset_key'] ?? ''), $category),
            $service
        );

        $name = trim((string) ($service['name'] ?? ''));

        if ($name === '') {
            return null;
        }

        $cookies = [];

        if (!empty($service['cookies']) && is_array($service['cookies'])) {
            foreach ($service['cookies'] as $cookie) {
                if (!is_array($cookie)) {
                    continue;
                }

                $cookie_name = trim((string) ($cookie['name'] ?? ''));
                $cookie_expiry = trim((string) ($cookie['expiry'] ?? ''));
                $cookie_type = trim((string) ($cookie['type'] ?? ''));
                $cookie_purpose = trim((string) ($cookie['purpose'] ?? ''));

                if ($cookie_name === '' && $cookie_expiry === '' && $cookie_type === '' && $cookie_purpose === '') {
                    continue;
                }

                $cookies[] = [
                    'name' => $cookie_name,
                    'expiry' => $cookie_expiry,
                    'type' => $cookie_type,
                    'purpose' => $cookie_purpose,
                ];
            }
        }

        return [
            'id' => sanitize_key($service['preset_key'] ?? ($category . '_service_' . $fallback_index . '_' . $name)),
            'name' => $name,
            'provider' => $service['provider'] ?? '',
            'address' => $service['address'] ?? '',
            'privacyUrl' => $service['privacy_url'] ?? '',
            'cookiePolicyUrl' => $service['cookie_policy_url'] ?? '',
            'description' => $service['purpose'] ?? '',
            'purpose' => $service['purpose'] ?? '',
            'legalBasis' => $service['legal_basis'] ?? '',
            'thirdCountryTransfer' => $service['third_country_transfer'] ?? '',
            'recipientCountry' => $service['recipient_country'] ?? '',
            'safeguards' => $service['safeguards'] ?? '',
            'serviceId' => $service['service_id'] ?? '',
            'embedCode' => $service['embed_code'] ?? '',
            'optOutCode' => $service['opt_out_code'] ?? '',
            'cookies' => $cookies,
        ];
    }

    private function get_optional_services_for_category(string $category, array $options): array
    {
        $collection_key = $category . '_services';
        $services = [];
        $source_services = !empty($options[$collection_key]) && is_array($options[$collection_key])
            ? $options[$collection_key]
            : [];

        if ($category === 'external_media' && !empty($options['marketing_services']) && is_array($options['marketing_services'])) {
            foreach ($options['marketing_services'] as $service) {
                if (is_array($service) && self::is_external_media_preset_key($service['preset_key'] ?? '')) {
                    $source_services[] = $service;
                }
            }
        }

        $source_services = $this->unique_services_by_identity($source_services);

        if ($category === 'external_media') {
            $source_services = $this->order_services_by_preset_keys($source_services, self::get_external_media_preset_keys());
        }

        if ($source_services) {
            foreach ($source_services as $index => $service) {
                if (!is_array($service) || ($service['enabled'] ?? '0') !== '1') {
                    continue;
                }

                if ($category === 'marketing' && self::is_external_media_preset_key($service['preset_key'] ?? '')) {
                    continue;
                }

                if (self::is_deprecated_service_preset($service)) {
                    continue;
                }

                $name = trim((string) ($service['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $frontend_service = $this->format_service_for_frontend($service, $category, $index);

                if ($frontend_service) {
                    $services[] = $frontend_service;
                }
            }

            if ($services) {
                return $services;
            }
        }

        $prefix = $category . '_service_';

        if (($options[$prefix . 'enabled'] ?? '0') !== '1') {
            return [];
        }

        $name = trim((string) ($options[$prefix . 'name'] ?? ''));

        if ($name === '') {
            return [];
        }

        return [
            [
                'id' => $category . '_service',
                'name' => $name,
                'provider' => $options[$prefix . 'provider'] ?? '',
                'address' => $options[$prefix . 'address'] ?? '',
                'privacyUrl' => $options[$prefix . 'privacy_url'] ?? '',
                'purpose' => $options[$prefix . 'purpose'] ?? '',
                'cookies' => [
                    [
                        'name' => $options[$prefix . 'cookie_name'] ?? '',
                        'expiry' => $options[$prefix . 'cookie_expiry'] ?? '',
                        'type' => $options[$prefix . 'cookie_type'] ?? '',
                        'purpose' => $options[$prefix . 'cookie_purpose'] ?? '',
                    ],
                ],
            ],
        ];
    }

    private function get_admin_services_for_category(string $category, array $options): array
    {
        $collection_key = $category . '_services';
        $deleted_preset_keys = $this->sanitize_key_list($options['deleted_preset_keys'] ?? []);
        $preset_services = array_values(array_filter(
            self::get_preset_services_for_category($category),
            static function ($service) use ($deleted_preset_keys) {
                return empty($service['preset_key']) || !in_array($service['preset_key'], $deleted_preset_keys, true);
            }
        ));
        $preset_services = array_map(function (array $service) use ($category): array {
            return array_merge(
                $this->get_default_service_compliance_fields(sanitize_key($service['preset_key'] ?? ''), $category),
                $service
            );
        }, $preset_services);

        $source_services = !empty($options[$collection_key]) && is_array($options[$collection_key])
            ? $options[$collection_key]
            : [];

        if ($category === 'external_media' && !empty($options['marketing_services']) && is_array($options['marketing_services'])) {
            foreach ($options['marketing_services'] as $service) {
                if (is_array($service) && self::is_external_media_preset_key($service['preset_key'] ?? '')) {
                    $source_services[] = $service;
                }
            }
        }

        $source_services = $this->unique_services_by_identity($source_services);

        if ($source_services) {
            $services = array_values(array_filter($source_services, function ($service) use ($category) {
                if (!$this->service_has_meaningful_data($service) || self::is_deprecated_service_preset($service)) {
                    return false;
                }

                if ($category === 'marketing' && self::is_external_media_preset_key($service['preset_key'] ?? '')) {
                    return false;
                }

                return true;
            }));

            $services = $this->merge_preset_services($services, $preset_services);

            if ($category === 'external_media') {
                $services = $this->order_services_by_preset_keys($services, self::get_external_media_preset_keys());
            }

            return $services;
        }

        $prefix = $category . '_service_';
        $has_legacy_service = !empty($options[$prefix . 'enabled']) || trim((string) ($options[$prefix . 'name'] ?? '')) !== '';

        if (!$has_legacy_service) {
            return $preset_services;
        }

        $services = $this->merge_preset_services([
            [
                'enabled' => $options[$prefix . 'enabled'] ?? '0',
                'name' => $options[$prefix . 'name'] ?? '',
                'provider' => $options[$prefix . 'provider'] ?? '',
                'address' => $options[$prefix . 'address'] ?? '',
                'privacy_url' => $options[$prefix . 'privacy_url'] ?? '',
                'purpose' => $options[$prefix . 'purpose'] ?? '',
                'service_id' => $options[$prefix . 'service_id'] ?? '',
                'embed_code' => $options[$prefix . 'embed_code'] ?? '',
                'cookies' => [
                    [
                        'name' => $options[$prefix . 'cookie_name'] ?? '',
                        'expiry' => $options[$prefix . 'cookie_expiry'] ?? '',
                        'type' => $options[$prefix . 'cookie_type'] ?? '',
                        'purpose' => $options[$prefix . 'cookie_purpose'] ?? '',
                    ],
                ],
            ],
        ], $preset_services);

        if ($category === 'external_media') {
            $services = $this->order_services_by_preset_keys($services, self::get_external_media_preset_keys());
        }

        return $services;
    }

    private function get_active_admin_services_for_category(string $category, array $options): array
    {
        $active_services = [];

        foreach ($this->get_admin_services_for_category($category, $options) as $service) {
            if (!is_array($service) || ($service['enabled'] ?? '0') !== '1') {
                continue;
            }

            $name = trim((string) ($service['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $active_services[] = [
                'name' => $name,
            ];
        }

        return $active_services;
    }

    private function get_active_summary_services_for_category(string $category, array $options): array
    {
        return $this->get_active_admin_services_for_category($category, $options);
    }

    private function get_detected_active_content_blocker_services(array $options): array
    {
        if (!$this->is_content_blocker_enabled($options)) {
            return [];
        }

        $detected_keys = [];

        foreach ($this->get_detected_content_blocker_embeds() as $embed) {
            $url = (string) ($embed['url'] ?? '');
            $blocker_key = $this->get_content_blocker_key_for_url($url);

            if ($blocker_key !== '') {
                $detected_keys[$blocker_key] = true;
            }
        }

        if (!$detected_keys) {
            return [];
        }

        $services = [];

        foreach ($this->get_content_blocker_definitions() as $definition) {
            if (
                empty($detected_keys[$definition['key']])
                || ($options[$definition['option_key']] ?? '0') !== '1'
            ) {
                continue;
            }

            $admin_service = $this->get_admin_service_for_content_blocker($definition, $options);
            $name = trim((string) ($admin_service['name'] ?? ''));

            $services[] = [
                'category' => $definition['category'],
                'name' => $name !== '' ? $name : $definition['service_name'],
            ];
        }

        return $services;
    }

    private function get_present_preset_keys(array $options): array
    {
        $preset_keys = [];

        foreach (self::SERVICE_COLLECTION_FIELDS as $collection_key) {
            if (empty($options[$collection_key]) || !is_array($options[$collection_key])) {
                continue;
            }

            foreach ($options[$collection_key] as $service) {
                if (!is_array($service) || empty($service['preset_key'])) {
                    continue;
                }

                $preset_keys[] = sanitize_key($service['preset_key']);
            }
        }

        return array_values(array_unique(array_filter($preset_keys)));
    }

    private function unique_services_by_identity(array $services): array
    {
        $unique = [];
        $seen = [];

        foreach ($services as $service) {
            if (!is_array($service)) {
                continue;
            }

            $preset_key = sanitize_key($service['preset_key'] ?? '');
            $name_key = sanitize_title((string) ($service['name'] ?? ''));
            $identity = $preset_key !== '' ? 'preset:' . $preset_key : 'name:' . $name_key;

            if ($identity === 'name:') {
                continue;
            }

            if (isset($seen[$identity])) {
                $existing_index = $seen[$identity];
                $existing_enabled = ($unique[$existing_index]['enabled'] ?? '0') === '1';
                $current_enabled = ($service['enabled'] ?? '0') === '1';

                if (!$existing_enabled && $current_enabled) {
                    $unique[$existing_index] = $service;
                }

                continue;
            }

            $seen[$identity] = count($unique);
            $unique[] = $service;
        }

        return $unique;
    }

    private function merge_preset_services(array $services, array $preset_services): array
    {
        $known = [];
        $preset_by_key = [];
        $preset_by_name = [];

        foreach ($preset_services as $preset_service) {
            if (!is_array($preset_service)) {
                continue;
            }

            $preset_key = sanitize_key($preset_service['preset_key'] ?? '');
            $preset_name = sanitize_title((string) ($preset_service['name'] ?? ''));

            if ($preset_key !== '') {
                $preset_by_key[$preset_key] = $preset_service;
            }

            if ($preset_name !== '') {
                $preset_by_name[$preset_name] = $preset_service;
            }
        }

        foreach ($services as $index => $service) {
            if (!is_array($service)) {
                continue;
            }

            if (self::is_deprecated_service_preset($service)) {
                continue;
            }

            $preset_key = sanitize_key($service['preset_key'] ?? '');
            $preset_name = sanitize_title((string) ($service['name'] ?? ''));
            $preset_service = $preset_by_key[$preset_key] ?? $preset_by_name[$preset_name] ?? null;

            if (is_array($preset_service)) {
                $services[$index] = $this->merge_service_with_preset_defaults($service, $preset_service);
                $service = $services[$index];
            }

            if (!empty($service['preset_key'])) {
                $known[sanitize_key($service['preset_key'])] = true;
            }

            if (!empty($service['name'])) {
                $known[sanitize_title((string) $service['name'])] = true;
            }
        }

        foreach ($preset_services as $preset_service) {
            $preset_key = sanitize_key($preset_service['preset_key'] ?? '');
            $preset_name = sanitize_title((string) ($preset_service['name'] ?? ''));

            if (($preset_key && isset($known[$preset_key])) || ($preset_name && isset($known[$preset_name]))) {
                continue;
            }

            $services[] = $preset_service;
        }

        return array_values($services);
    }

    private function merge_service_with_preset_defaults(array $service, array $preset_service): array
    {
        $preset_key = sanitize_key($service['preset_key'] ?? $preset_service['preset_key'] ?? '');
        $category = (string) ($preset_service['category'] ?? $service['category'] ?? '');
        $preset_service = array_merge($this->get_default_service_compliance_fields($preset_key, $category), $preset_service);

        foreach (['provider', 'address', 'privacy_url', 'cookie_policy_url', 'purpose', 'legal_basis', 'third_country_transfer', 'recipient_country', 'safeguards'] as $field) {
            if (trim((string) ($service[$field] ?? '')) === '' && trim((string) ($preset_service[$field] ?? '')) !== '') {
                $service[$field] = $preset_service[$field];
            }
        }

        if (empty($service['cookies']) && !empty($preset_service['cookies'])) {
            $service['cookies'] = $preset_service['cookies'];
        }

        return $service;
    }

    private function get_default_service_compliance_fields(string $preset_key, string $category): array
    {
        $legal_basis_optional = __('Einwilligung, Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TDDDG', self::TEXT_DOMAIN);
        $eu = __('EU / EWR', self::TEXT_DOMAIN);
        $possible_usa = __('Möglich, insbesondere USA', self::TEXT_DOMAIN);
        $scc_or_framework = __('EU-Standardvertragsklauseln und/oder EU-US Data Privacy Framework, soweit anwendbar. Bitte Anbieterangaben prüfen.', self::TEXT_DOMAIN);

        $defaults = [
            'legal_basis' => $legal_basis_optional,
            'third_country_transfer' => __('Bitte prüfen', self::TEXT_DOMAIN),
            'recipient_country' => __('Bitte prüfen', self::TEXT_DOMAIN),
            'safeguards' => __('Bitte anhand der Anbieterangaben und deiner Datenschutzerklärung ergänzen.', self::TEXT_DOMAIN),
        ];

        $eu_services = ['matomo', 'linkedin_insight_tag', 'klarna'];
        $us_possible_services = [
            'google_analytics_4',
            'google_ads',
            'google_tag_manager',
            'youtube',
            'google_maps',
            'meta_pixel',
            'facebook',
            'instagram',
            'vimeo',
            'soundcloud',
            'spotify',
            'x',
            'x_ads_pixel',
            'tiktok_pixel',
            'pinterest_tag',
            'hubspot',
            'intercom',
            'zendesk_chat',
            'cloudflare_turnstile',
            'cloudflare_cdn',
            'google_fonts',
            'adobe_fonts',
            'stripe',
            'paypal',
            'google_recaptcha',
            'hcaptcha',
        ];

        if (in_array($preset_key, $eu_services, true)) {
            $defaults['third_country_transfer'] = __('In der Regel nein bzw. abhängig von Konfiguration/Anbieter', self::TEXT_DOMAIN);
            $defaults['recipient_country'] = $eu;
            $defaults['safeguards'] = __('Bitte Anbieterangaben und eigene Konfiguration prüfen.', self::TEXT_DOMAIN);
        }

        if (in_array($preset_key, $us_possible_services, true)) {
            $defaults['third_country_transfer'] = $possible_usa;
            $defaults['recipient_country'] = __('EU / EWR, ggf. USA', self::TEXT_DOMAIN);
            $defaults['safeguards'] = $scc_or_framework;
        }

        if ($preset_key === 'openstreetmap') {
            $defaults['third_country_transfer'] = __('Möglich, abhängig vom Kartenanbieter und eingebundenen Tiles', self::TEXT_DOMAIN);
            $defaults['recipient_country'] = __('Vereinigtes Königreich / EU, abhängig vom Kartenanbieter', self::TEXT_DOMAIN);
            $defaults['safeguards'] = __('Bitte den konkret verwendeten Karten- bzw. Tile-Anbieter prüfen und in der Datenschutzerklärung aufführen.', self::TEXT_DOMAIN);
        }

        if ($preset_key === '' && $category !== '') {
            $defaults['third_country_transfer'] = __('Bitte für diesen Dienst prüfen', self::TEXT_DOMAIN);
            $defaults['recipient_country'] = __('Bitte für diesen Dienst prüfen', self::TEXT_DOMAIN);
        }

        return $defaults;
    }

    private function order_services_by_preset_keys(array $services, array $preset_keys): array
    {
        $preset_order = array_flip($preset_keys);
        $ordered = [];
        $custom = [];

        foreach ($services as $service) {
            if (!is_array($service)) {
                continue;
            }

            $preset_key = sanitize_key($service['preset_key'] ?? '');

            if ($preset_key !== '' && isset($preset_order[$preset_key]) && !isset($ordered[$preset_key])) {
                $ordered[$preset_key] = $service;
                continue;
            }

            $custom[] = $service;
        }

        $result = [];
        foreach ($preset_keys as $preset_key) {
            if (isset($ordered[$preset_key])) {
                $result[] = $ordered[$preset_key];
            }
        }

        return array_merge($result, $custom);
    }

    private function sanitize_service_collection($services): array
    {
        if (!is_array($services)) {
            return [];
        }

        $sanitized = [];

        foreach ($services as $service) {
            if (!is_array($service)) {
                continue;
            }

            $cookies = [];

            if (!empty($service['cookies']) && is_array($service['cookies'])) {
                foreach ($service['cookies'] as $cookie) {
                    if (!is_array($cookie)) {
                        continue;
                    }

                    $cookie_data = [
                        'name' => sanitize_text_field($cookie['name'] ?? ''),
                        'expiry' => sanitize_text_field($cookie['expiry'] ?? ''),
                        'type' => sanitize_text_field($cookie['type'] ?? ''),
                        'purpose' => sanitize_textarea_field($cookie['purpose'] ?? ''),
                    ];

                    if (
                        $cookie_data['name'] === ''
                        && $cookie_data['expiry'] === ''
                        && $cookie_data['purpose'] === ''
                        && ($cookie_data['type'] === '' || $cookie_data['type'] === __('HTTP Cookie', self::TEXT_DOMAIN))
                    ) {
                        continue;
                    }

                    $cookies[] = $cookie_data;
                }
            }

            $service_data = [
                'preset_key' => sanitize_key($service['preset_key'] ?? ''),
                'enabled' => !empty($service['enabled']) ? '1' : '0',
                'name' => sanitize_text_field($service['name'] ?? ''),
                'provider' => sanitize_text_field($service['provider'] ?? ''),
                'address' => sanitize_text_field($service['address'] ?? ''),
                'privacy_url' => esc_url_raw($service['privacy_url'] ?? ''),
                'cookie_policy_url' => esc_url_raw($service['cookie_policy_url'] ?? ''),
                'purpose' => sanitize_textarea_field($service['purpose'] ?? ''),
                'legal_basis' => sanitize_text_field($service['legal_basis'] ?? ''),
                'third_country_transfer' => sanitize_text_field($service['third_country_transfer'] ?? ''),
                'recipient_country' => sanitize_text_field($service['recipient_country'] ?? ''),
                'safeguards' => sanitize_textarea_field($service['safeguards'] ?? ''),
                'service_id' => sanitize_text_field($service['service_id'] ?? ''),
                'embed_code' => $this->sanitize_embed_code($service['embed_code'] ?? ''),
                'opt_out_code' => $this->sanitize_embed_code($service['opt_out_code'] ?? ''),
                'cookies' => $cookies,
            ];

            if (
                $service_data['enabled'] !== '1'
                && $service_data['name'] === ''
                && $service_data['provider'] === ''
                && $service_data['address'] === ''
                && $service_data['privacy_url'] === ''
                && $service_data['cookie_policy_url'] === ''
                && $service_data['purpose'] === ''
                && $service_data['legal_basis'] === ''
                && $service_data['third_country_transfer'] === ''
                && $service_data['recipient_country'] === ''
                && $service_data['safeguards'] === ''
                && $service_data['service_id'] === ''
                && $service_data['embed_code'] === ''
                && $service_data['opt_out_code'] === ''
                && $service_data['preset_key'] === ''
                && !$cookies
            ) {
                continue;
            }

            $sanitized[] = $service_data;
        }

        return $sanitized;
    }

    private function sanitize_key_list($keys): array
    {
        if (!is_array($keys)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('sanitize_key', $keys))));
    }

    private function sanitize_url_map($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $sanitized = [];

        foreach ($items as $key => $value) {
            $clean_key = sanitize_key((string) $key);
            $clean_url = esc_url_raw($value);

            if ($clean_key === '' || $clean_url === '') {
                continue;
            }

            $sanitized[$clean_key] = $clean_url;
        }

        return $sanitized;
    }

    private function sanitize_text_map($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $sanitized = [];

        foreach ($items as $key => $value) {
            $clean_key = sanitize_key((string) $key);
            $clean_text = sanitize_text_field($value);

            if ($clean_key === '' || $clean_text === '') {
                continue;
            }

            $sanitized[$clean_key] = $clean_text;
        }

        return $sanitized;
    }

    private function sanitize_content_blocker_service_settings($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $sanitized = [];
        $text_fields = ['text', 'button', 'always_button', 'missing_service_text'];
        $color_fields = array_keys($this->get_content_blocker_service_color_fields());

        foreach ($items as $service_id => $settings) {
            $clean_service_id = sanitize_key((string) $service_id);

            if ($clean_service_id === '' || !is_array($settings)) {
                continue;
            }

            foreach ($text_fields as $field) {
                $value = sanitize_text_field($settings[$field] ?? '');

                if ($value !== '') {
                    $sanitized[$clean_service_id][$field] = $value;
                }
            }

            foreach ($color_fields as $field) {
                $fallback = (string) (self::default_options()[$this->get_global_content_blocker_color_key($field)] ?? '');
                $value = $this->sanitize_color($settings[$field] ?? '', $fallback);

                if ($value !== '') {
                    $sanitized[$clean_service_id][$field] = $value;
                }
            }
        }

        return $sanitized;
    }

    private function sanitize_embed_code($code): string
    {
        $code = is_string($code) ? trim($code) : '';

        if ($code === '') {
            return '';
        }

        if (current_user_can('unfiltered_html')) {
            return $code;
        }

        return wp_kses_post($code);
    }

    private function service_has_meaningful_data($service): bool
    {
        if (!is_array($service)) {
            return false;
        }

        if (!empty($service['preset_key']) || !empty($service['enabled'])) {
            return true;
        }

        foreach (['name', 'provider', 'address', 'privacy_url', 'cookie_policy_url', 'purpose', 'service_id', 'embed_code'] as $key) {
            if (trim((string) ($service[$key] ?? '')) !== '') {
                return true;
            }
        }

        if (empty($service['cookies']) || !is_array($service['cookies'])) {
            return false;
        }

        foreach ($service['cookies'] as $cookie) {
            if (!is_array($cookie)) {
                continue;
            }

            $name = trim((string) ($cookie['name'] ?? ''));
            $expiry = trim((string) ($cookie['expiry'] ?? ''));
            $type = trim((string) ($cookie['type'] ?? ''));
            $purpose = trim((string) ($cookie['purpose'] ?? ''));

            if ($name !== '' || $expiry !== '' || $purpose !== '') {
                return true;
            }

            if ($type !== '' && $type !== __('HTTP Cookie', self::TEXT_DOMAIN)) {
                return true;
            }
        }

        return false;
    }

    private static function get_service_template_library(): array
    {
        $preset_groups = [
            __('Vorbelegte Dienste', self::TEXT_DOMAIN) => array_merge(
                self::get_preset_services_as_templates('statistics'),
                self::get_preset_services_as_templates('marketing'),
                self::get_preset_services_as_templates('external_media')
            ),
        ];

        $template_groups = [
            __('Analytics', self::TEXT_DOMAIN) => [
                [
                    'category' => 'marketing',
                    'preset_key' => 'google_tag_manager',
                    'enabled' => '0',
                    'name' => 'Google Tag Manager',
                    'provider' => 'Google Ireland Limited',
                    'address' => 'Gordon House, Barrow Street, Dublin 4, Irland',
                    'privacy_url' => 'https://policies.google.com/privacy',
                    'cookie_policy_url' => 'https://policies.google.com/technologies/cookies',
                    'purpose' => __('Verwaltung und Ausspielung von Tracking- und Marketing-Tags.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => __('Abhängig von eingebundenen Tags', self::TEXT_DOMAIN),
                            'expiry' => __('Abhängig vom Dienst', self::TEXT_DOMAIN),
                            'type' => __('Script/Tag Manager', self::TEXT_DOMAIN),
                            'purpose' => __('Der Tag Manager selbst setzt in der Regel keine Cookies, kann aber andere Dienste auslösen.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'statistics',
                    'preset_key' => 'hotjar',
                    'enabled' => '0',
                    'name' => 'Hotjar',
                    'provider' => 'Hotjar Ltd.',
                    'address' => 'Dragonara Business Centre, 5th Floor, Dragonara Road, Paceville St Julian\'s STJ 3141, Malta',
                    'privacy_url' => 'https://www.hotjar.com/legal/policies/privacy',
                    'cookie_policy_url' => 'https://help.hotjar.com/hc/de/articles/36819973371409-Cookies-die-vom-Hotjar-Tracking-Code-gesetzt-werden',
                    'purpose' => __('Analyse von Nutzerverhalten, Heatmaps und Feedback zur Verbesserung der Website.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '_hjSessionUser_*',
                            'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert eine Hotjar-Benutzerkennung für wiederkehrende Besuche.', self::TEXT_DOMAIN),
                        ],
                        [
                            'name' => '_hjSession_*',
                            'expiry' => __('30 Minuten', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert Daten zur aktuellen Hotjar-Sitzung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'statistics',
                    'preset_key' => 'plausible',
                    'enabled' => '0',
                    'name' => 'Plausible Analytics',
                    'provider' => 'Plausible Insights OÜ',
                    'address' => 'Västriku tn 2, 50403 Tartu, Estland',
                    'privacy_url' => 'https://plausible.io/data-policy',
                    'cookie_policy_url' => 'https://plausible.io/data-policy',
                    'purpose' => __('Cookielose, aggregierte Reichweitenmessung und Website-Statistik.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => __('Keine Cookies', self::TEXT_DOMAIN),
                            'expiry' => __('Keine', self::TEXT_DOMAIN),
                            'type' => __('Cookieless Analytics', self::TEXT_DOMAIN),
                            'purpose' => __('Plausible arbeitet ohne Cookies und ohne personenbezogene Besucherprofile.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'statistics',
                    'preset_key' => 'fathom',
                    'enabled' => '0',
                    'name' => 'Fathom Analytics',
                    'provider' => 'Conva Ventures Inc.',
                    'address' => 'Kanada',
                    'privacy_url' => 'https://usefathom.com/privacy',
                    'cookie_policy_url' => 'https://usefathom.com/data',
                    'purpose' => __('Datenschutzfreundliche und aggregierte Website-Statistik ohne klassisches Tracking.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => __('Keine Cookies', self::TEXT_DOMAIN),
                            'expiry' => __('Keine', self::TEXT_DOMAIN),
                            'type' => __('Cookieless Analytics', self::TEXT_DOMAIN),
                            'purpose' => __('Fathom nutzt cookielose Messung für aggregierte Website-Statistiken.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
            ],
            __('Marketing', self::TEXT_DOMAIN) => [
                [
                    'category' => 'marketing',
                    'preset_key' => 'tiktok_pixel',
                    'enabled' => '0',
                    'name' => 'TikTok Pixel',
                    'provider' => 'TikTok Technology Limited',
                    'address' => '10 Earlsfort Terrace, Dublin, D02 T380, Irland',
                    'privacy_url' => 'https://www.tiktok.com/legal/page/eea/privacy-policy/en',
                    'cookie_policy_url' => 'https://www.tiktok.com/legal/page/global/cookie-policy/en',
                    'purpose' => __('Conversion-Messung, Zielgruppenbildung und Optimierung von TikTok-Werbung.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '_ttp',
                            'expiry' => __('13 Monate', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Misst und verbessert die Performance von TikTok-Werbekampagnen.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'pinterest_tag',
                    'enabled' => '0',
                    'name' => 'Pinterest Tag',
                    'provider' => 'Pinterest Europe Ltd.',
                    'address' => 'Palmerston House, 2nd Floor, Fenian Street, Dublin 2, Irland',
                    'privacy_url' => 'https://policy.pinterest.com/en/privacy-policy',
                    'cookie_policy_url' => 'https://policy.pinterest.com/en/cookies',
                    'purpose' => __('Conversion-Messung und Zielgruppenbildung für Pinterest-Werbung.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '_pin_unauth',
                            'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert eine Nutzerkennung für Pinterest-Kampagnenmessung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'x_ads_pixel',
                    'enabled' => '0',
                    'name' => 'X Ads Pixel',
                    'provider' => 'Twitter International Unlimited Company',
                    'address' => 'One Cumberland Place, Fenian Street, Dublin 2, D02 AX07, Irland',
                    'privacy_url' => 'https://x.com/en/privacy',
                    'cookie_policy_url' => 'https://help.x.com/en/rules-and-policies/x-cookies',
                    'purpose' => __('Conversion-Messung und Remarketing für Anzeigen auf X.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'guest_id',
                            'expiry' => __('13 Monate', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Unterstützt Werbemessung und Personalisierung auf X.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
            ],
            __('Externe Medien', self::TEXT_DOMAIN) => [
                [
                    'category' => 'external_media',
                    'preset_key' => 'spotify',
                    'enabled' => '0',
                    'name' => 'Spotify',
                    'provider' => 'Spotify AB',
                    'address' => 'Regeringsgatan 19, 111 53 Stockholm, Schweden',
                    'privacy_url' => 'https://www.spotify.com/legal/privacy-policy/',
                    'cookie_policy_url' => 'https://www.spotify.com/legal/cookies-policy/',
                    'purpose' => __('Einbindung und Wiedergabe von Spotify-Audioinhalten auf der Website.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'sp_t',
                            'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Unterstützt die Wiedergabe und Personalisierung eingebetteter Spotify-Inhalte.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'external_media',
                    'preset_key' => 'soundcloud',
                    'enabled' => '0',
                    'name' => 'SoundCloud',
                    'provider' => 'SoundCloud Global Limited & Co. KG',
                    'address' => 'Rheinsberger Str. 76/77, 10115 Berlin, Deutschland',
                    'privacy_url' => 'https://soundcloud.com/pages/privacy',
                    'cookie_policy_url' => 'https://soundcloud.com/pages/cookies',
                    'purpose' => __('Einbindung und Wiedergabe von SoundCloud-Audioinhalten auf der Website.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'sc_anonymous_id',
                            'expiry' => __('10 Jahre', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert eine anonyme Kennung für SoundCloud-Player und Nutzungsmessung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'external_media',
                    'preset_key' => 'instagram',
                    'enabled' => '0',
                    'name' => 'Instagram',
                    'provider' => 'Meta Platforms Ireland Limited',
                    'address' => 'Merrion Road, Dublin 4, D04 X2K5, Irland',
                    'privacy_url' => 'https://www.facebook.com/privacy/explanation',
                    'cookie_policy_url' => 'https://www.facebook.com/privacy/policies/cookies/',
                    'purpose' => __('Einbindung und Darstellung von Instagram-Beiträgen auf der Website.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'csrftoken',
                            'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Unterstützt Sicherheitsfunktionen bei eingebetteten Instagram-Inhalten.', self::TEXT_DOMAIN),
                        ],
                        [
                            'name' => 'mid',
                            'expiry' => __('2 Jahre', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert eine Gerätekennung für Instagram-Dienste.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
            ],
            __('Sicherheit', self::TEXT_DOMAIN) => [
                [
                    'category' => 'marketing',
                    'preset_key' => 'google_recaptcha',
                    'enabled' => '0',
                    'name' => 'Google reCAPTCHA',
                    'provider' => 'Google Ireland Limited',
                    'address' => 'Gordon House, Barrow Street, Dublin 4, Irland',
                    'privacy_url' => 'https://policies.google.com/privacy',
                    'cookie_policy_url' => 'https://policies.google.com/technologies/cookies',
                    'purpose' => __('Schutz von Formularen vor Spam und automatisierten Zugriffen.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '_GRECAPTCHA',
                            'expiry' => __('6 Monate', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Stellt den Bot-Schutz von Google reCAPTCHA bereit.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'hcaptcha',
                    'enabled' => '0',
                    'name' => 'hCaptcha',
                    'provider' => 'Intuition Machines, Inc.',
                    'address' => '350 Alabama St, San Francisco, CA 94110, USA',
                    'privacy_url' => 'https://www.hcaptcha.com/privacy',
                    'cookie_policy_url' => 'https://www.hcaptcha.com/terms',
                    'purpose' => __('Schutz von Formularen vor Spam und automatisierten Zugriffen.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'hmt_id',
                            'expiry' => __('30 Tage', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Unterstützt die Erkennung legitimer Formularnutzung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'cloudflare_turnstile',
                    'enabled' => '0',
                    'name' => 'Cloudflare Turnstile',
                    'provider' => 'Cloudflare, Inc.',
                    'address' => '101 Townsend St, San Francisco, CA 94107, USA',
                    'privacy_url' => 'https://www.cloudflare.com/privacypolicy/',
                    'cookie_policy_url' => 'https://www.cloudflare.com/cookie-policy/',
                    'purpose' => __('Datenschutzfreundliche Bot-Prüfung und Formularschutz.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'cf_clearance',
                            'expiry' => __('Bis zu 1 Jahr', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert das Ergebnis einer Sicherheitsprüfung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'cloudflare_cdn',
                    'enabled' => '0',
                    'name' => 'Cloudflare CDN',
                    'provider' => 'Cloudflare, Inc.',
                    'address' => '101 Townsend St, San Francisco, CA 94107, USA',
                    'privacy_url' => 'https://www.cloudflare.com/privacypolicy/',
                    'cookie_policy_url' => 'https://www.cloudflare.com/cookie-policy/',
                    'purpose' => __('Bereitstellung, Absicherung und Beschleunigung der Website über Cloudflare.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '__cf_bm',
                            'expiry' => __('30 Minuten', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Unterstützt Bot-Erkennung und Schutzfunktionen von Cloudflare.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
            ],
            __('Chat & CRM', self::TEXT_DOMAIN) => [
                [
                    'category' => 'marketing',
                    'preset_key' => 'hubspot',
                    'enabled' => '0',
                    'name' => 'HubSpot',
                    'provider' => 'HubSpot Ireland Limited',
                    'address' => 'Ground Floor, Two Dockland Central, Guild Street, Dublin 1, Irland',
                    'privacy_url' => 'https://legal.hubspot.com/privacy-policy',
                    'cookie_policy_url' => 'https://legal.hubspot.com/cookie-policy',
                    'purpose' => __('CRM, Kontaktformulare, Marketing-Automation und Website-Analyse.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'hubspotutk',
                            'expiry' => __('6 Monate', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert eine Besucherkennung für HubSpot-Formulare und Analyse.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'intercom',
                    'enabled' => '0',
                    'name' => 'Intercom',
                    'provider' => 'Intercom R&D Unlimited Company',
                    'address' => '2nd Floor, Stephen Court, 18-21 St. Stephen\'s Green, Dublin 2, Irland',
                    'privacy_url' => 'https://www.intercom.com/legal/privacy',
                    'cookie_policy_url' => 'https://www.intercom.com/legal/cookie-policy',
                    'purpose' => __('Live-Chat, Kundenkommunikation und Support-Funktionen.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'intercom-session-*',
                            'expiry' => __('1 Woche', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert die aktuelle Intercom-Sitzung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'zendesk_chat',
                    'enabled' => '0',
                    'name' => 'Zendesk Chat',
                    'provider' => 'Zendesk, Inc.',
                    'address' => '1019 Market Street, San Francisco, CA 94103, USA',
                    'privacy_url' => 'https://www.zendesk.com/company/privacy-and-data-protection/',
                    'cookie_policy_url' => 'https://www.zendesk.com/company/agreements-and-terms/cookie-policy/',
                    'purpose' => __('Live-Chat und Support-Kommunikation auf der Website.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '__zlcmid',
                            'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert eine Besucherkennung für Zendesk-Chatfunktionen.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'tawk_to',
                    'enabled' => '0',
                    'name' => 'tawk.to',
                    'provider' => 'tawk.to, Inc.',
                    'address' => '187 E Warm Springs Rd, SB298, Las Vegas, NV 89119, USA',
                    'privacy_url' => 'https://www.tawk.to/privacy-policy/',
                    'cookie_policy_url' => 'https://www.tawk.to/terms-of-service/',
                    'purpose' => __('Live-Chat und Support-Kommunikation auf der Website.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'TawkConnectionTime',
                            'expiry' => __('Session', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert Informationen zur aktuellen Chat-Verbindung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
            ],
            __('Zahlung', self::TEXT_DOMAIN) => [
                [
                    'category' => 'marketing',
                    'preset_key' => 'paypal',
                    'enabled' => '0',
                    'name' => 'PayPal',
                    'provider' => 'PayPal Europe S.à r.l. et Cie, S.C.A.',
                    'address' => '22-24 Boulevard Royal, L-2449 Luxemburg',
                    'privacy_url' => 'https://www.paypal.com/de/legalhub/paypal/privacy-full',
                    'cookie_policy_url' => 'https://www.paypal.com/de/legalhub/cookie-full',
                    'purpose' => __('Abwicklung von Zahlungen über PayPal.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'ts',
                            'expiry' => __('3 Jahre', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Unterstützt Sicherheit, Betrugsprävention und Zahlungsabwicklung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'stripe',
                    'enabled' => '0',
                    'name' => 'Stripe',
                    'provider' => 'Stripe Payments Europe, Limited',
                    'address' => '1 Grand Canal Street Lower, Grand Canal Dock, Dublin, Irland',
                    'privacy_url' => 'https://stripe.com/privacy',
                    'cookie_policy_url' => 'https://stripe.com/cookie-settings',
                    'purpose' => __('Abwicklung von Kartenzahlungen und Zahlungsformularen über Stripe.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '__stripe_mid',
                            'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Unterstützt Betrugsprävention und sichere Zahlungsabwicklung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'klarna',
                    'enabled' => '0',
                    'name' => 'Klarna',
                    'provider' => 'Klarna Bank AB (publ)',
                    'address' => 'Sveavägen 46, 111 34 Stockholm, Schweden',
                    'privacy_url' => 'https://www.klarna.com/international/privacy-policy/',
                    'cookie_policy_url' => 'https://www.klarna.com/international/cookie-statement/',
                    'purpose' => __('Abwicklung von Zahlungen, Rechnungskauf und Ratenkauf über Klarna.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'klarna_mdid',
                            'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Unterstützt Geräteerkennung und Zahlungsabwicklung über Klarna.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
            ],
            __('Fonts/Assets', self::TEXT_DOMAIN) => [
                [
                    'category' => 'marketing',
                    'preset_key' => 'google_fonts',
                    'enabled' => '0',
                    'name' => 'Google Fonts',
                    'provider' => 'Google Ireland Limited',
                    'address' => 'Gordon House, Barrow Street, Dublin 4, Irland',
                    'privacy_url' => 'https://policies.google.com/privacy',
                    'cookie_policy_url' => 'https://developers.google.com/fonts/faq/privacy',
                    'purpose' => __('Externe Bereitstellung von Schriftarten über Google Fonts.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => __('Keine Cookies', self::TEXT_DOMAIN),
                            'expiry' => __('Keine', self::TEXT_DOMAIN),
                            'type' => __('Externer Request', self::TEXT_DOMAIN),
                            'purpose' => __('Beim Abruf externer Schriftdateien können technische Zugriffsdaten übertragen werden.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'category' => 'marketing',
                    'preset_key' => 'adobe_fonts',
                    'enabled' => '0',
                    'name' => 'Adobe Fonts',
                    'provider' => 'Adobe Systems Software Ireland Limited',
                    'address' => '4-6 Riverwalk, Citywest Business Park, Dublin 24, Irland',
                    'privacy_url' => 'https://www.adobe.com/privacy/policy.html',
                    'cookie_policy_url' => 'https://www.adobe.com/privacy/cookies.html',
                    'purpose' => __('Externe Bereitstellung von Schriftarten über Adobe Fonts.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => __('Keine Cookies', self::TEXT_DOMAIN),
                            'expiry' => __('Keine', self::TEXT_DOMAIN),
                            'type' => __('Externer Request', self::TEXT_DOMAIN),
                            'purpose' => __('Beim Abruf externer Schriftdateien können technische Zugriffsdaten übertragen werden.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
            ],
        ];

        return array_merge($preset_groups, $template_groups);
    }

    private static function get_preset_services_as_templates(string $category): array
    {
        return array_map(
            static function (array $service) use ($category): array {
                $service['category'] = $category;
                return $service;
            },
            self::get_preset_services_for_category($category)
        );
    }

    private function get_external_media_template_services(): array
    {
        return self::get_preset_services_for_category('external_media');
    }

    private static function get_external_media_preset_keys(): array
    {
        return ['facebook', 'google_maps', 'instagram', 'openstreetmap', 'soundcloud', 'spotify', 'vimeo', 'x', 'youtube'];
    }

    private static function get_preset_services_for_category(string $category): array
    {
        $presets = [
            'statistics' => [
                [
                    'preset_key' => 'google_analytics_4',
                    'enabled' => '0',
                    'name' => 'Google Analytics 4',
                    'provider' => 'Google Ireland Limited',
                    'address' => 'Gordon House, Barrow Street, Dublin 4, Irland',
                    'privacy_url' => 'https://policies.google.com/privacy',
                    'cookie_policy_url' => 'https://policies.google.com/technologies/cookies',
                    'purpose' => __('Analyse der Website-Nutzung, Reichweitenmessung und Verbesserung des Online-Angebots.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '_ga',
                            'expiry' => __('2 Jahre', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Unterscheidet Besucher für statistische Auswertungen.', self::TEXT_DOMAIN),
                        ],
                        [
                            'name' => '_ga_*',
                            'expiry' => __('2 Jahre', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert Sitzungs- und Kampagneninformationen für Google Analytics 4.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'preset_key' => 'matomo',
                    'enabled' => '0',
                    'name' => 'Matomo',
                    'provider' => __('Websitebetreiber / Matomo', self::TEXT_DOMAIN),
                    'address' => __('Adresse des Websitebetreibers eintragen', self::TEXT_DOMAIN),
                    'privacy_url' => home_url('/datenschutz/'),
                    'cookie_policy_url' => home_url('/datenschutz/'),
                    'purpose' => __('Datenschutzfreundliche Analyse der Website-Nutzung und Besucherstatistiken.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '_pk_id*',
                            'expiry' => __('13 Monate', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Erkennt wiederkehrende Besucher für statistische Auswertungen.', self::TEXT_DOMAIN),
                        ],
                        [
                            'name' => '_pk_ses*',
                            'expiry' => __('30 Minuten', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert Daten zur aktuellen Besuchssitzung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
            ],
            'marketing' => [
                [
                    'preset_key' => 'google_tag_manager',
                    'enabled' => '0',
                    'name' => 'Google Tag Manager',
                    'provider' => 'Google Ireland Limited',
                    'address' => 'Gordon House, Barrow Street, Dublin 4, Irland',
                    'privacy_url' => 'https://policies.google.com/privacy',
                    'cookie_policy_url' => 'https://policies.google.com/technologies/cookies',
                    'purpose' => __('Verwaltung und Ausspielung von Tracking- und Marketing-Tags.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => __('Abhängig von eingebundenen Tags', self::TEXT_DOMAIN),
                            'expiry' => __('Abhängig vom Dienst', self::TEXT_DOMAIN),
                            'type' => __('Script/Tag Manager', self::TEXT_DOMAIN),
                            'purpose' => __('Der Google Tag Manager selbst setzt in der Regel keine Cookies, kann aber andere Dienste auslösen.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'preset_key' => 'google_ads',
                    'enabled' => '0',
                    'name' => 'Google Ads Conversion Tracking',
                    'provider' => 'Google Ireland Limited',
                    'address' => 'Gordon House, Barrow Street, Dublin 4, Irland',
                    'privacy_url' => 'https://policies.google.com/privacy',
                    'cookie_policy_url' => 'https://policies.google.com/technologies/cookies',
                    'purpose' => __('Messung von Werbekampagnen, Conversions und Remarketing-Zielgruppen.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '_gcl_au',
                            'expiry' => __('90 Tage', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert Conversion-Informationen für Google Ads.', self::TEXT_DOMAIN),
                        ],
                        [
                            'name' => 'IDE',
                            'expiry' => __('13 Monate', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Wird für personalisierte Werbung und Kampagnenmessung genutzt.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'preset_key' => 'meta_pixel',
                    'enabled' => '0',
                    'name' => 'Meta Pixel',
                    'provider' => 'Meta Platforms Ireland Limited',
                    'address' => 'Merrion Road, Dublin 4, D04 X2K5, Irland',
                    'privacy_url' => 'https://www.facebook.com/privacy/policy/',
                    'cookie_policy_url' => 'https://www.facebook.com/privacy/policies/cookies/',
                    'purpose' => __('Messung und Optimierung von Werbeanzeigen auf Meta-Plattformen.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => '_fbp',
                            'expiry' => __('3 Monate', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Identifiziert Browser für Werbe- und Conversion-Messung.', self::TEXT_DOMAIN),
                        ],
                        [
                            'name' => 'fr',
                            'expiry' => __('3 Monate', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Unterstützt die Auslieferung und Messung relevanter Werbung.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'preset_key' => 'linkedin_insight_tag',
                    'enabled' => '0',
                    'name' => 'LinkedIn Insight Tag',
                    'provider' => 'LinkedIn Ireland Unlimited Company',
                    'address' => 'Wilton Place, Dublin 2, Irland',
                    'privacy_url' => 'https://www.linkedin.com/legal/privacy-policy',
                    'cookie_policy_url' => 'https://www.linkedin.com/legal/cookie-policy',
                    'purpose' => __('Conversion-Messung, Kampagnenanalyse und Zielgruppenbildung für LinkedIn Ads.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'li_fat_id',
                            'expiry' => __('30 Tage', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert eine Klickkennung für Conversion-Zuordnung.', self::TEXT_DOMAIN),
                        ],
                        [
                            'name' => 'bcookie',
                            'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Browserkennung für LinkedIn-Dienste und Sicherheitsfunktionen.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'preset_key' => 'youtube',
                    'enabled' => '0',
                    'name' => 'YouTube',
                    'provider' => 'Google Ireland Limited',
                    'address' => 'Gordon House, Barrow Street, Dublin 4, Irland',
                    'privacy_url' => 'https://policies.google.com/privacy',
                    'cookie_policy_url' => 'https://policies.google.com/technologies/cookies',
                    'purpose' => __('Einbindung und Wiedergabe von YouTube-Videos auf der Website.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'YSC',
                            'expiry' => __('Session', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert eine eindeutige Kennung zur Videowiedergabe und Missbrauchsprävention.', self::TEXT_DOMAIN),
                        ],
                        [
                            'name' => 'VISITOR_INFO1_LIVE',
                            'expiry' => __('6 Monate', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Schätzt die Bandbreite und unterstützt die Darstellung eingebetteter Videos.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'preset_key' => 'vimeo',
                    'enabled' => '0',
                    'name' => 'Vimeo',
                    'provider' => 'Vimeo.com, Inc.',
                    'address' => '330 West 34th Street, 10th Floor, New York, NY 10001, USA',
                    'privacy_url' => 'https://vimeo.com/legal/privacy',
                    'cookie_policy_url' => 'https://vimeo.com/legal/privacy/cookies',
                    'purpose' => __('Einbindung und Wiedergabe von Vimeo-Videos auf der Website.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'vuid',
                            'expiry' => __('2 Jahre', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert eine eindeutige Besucherkennung für eingebettete Vimeo-Videos.', self::TEXT_DOMAIN),
                        ],
                        [
                            'name' => 'player',
                            'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert Einstellungen des Vimeo-Players.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
                [
                    'preset_key' => 'google_maps',
                    'enabled' => '0',
                    'name' => 'Google Maps',
                    'provider' => 'Google Ireland Limited',
                    'address' => 'Gordon House, Barrow Street, Dublin 4, Irland',
                    'privacy_url' => 'https://policies.google.com/privacy',
                    'cookie_policy_url' => 'https://policies.google.com/technologies/cookies',
                    'purpose' => __('Einbindung interaktiver Karten und Standortfunktionen von Google Maps.', self::TEXT_DOMAIN),
                    'cookies' => [
                        [
                            'name' => 'NID',
                            'expiry' => __('6 Monate', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert Google-Einstellungen und unterstützt die Kartendarstellung.', self::TEXT_DOMAIN),
                        ],
                        [
                            'name' => 'CONSENT',
                            'expiry' => __('2 Jahre', self::TEXT_DOMAIN),
                            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                            'purpose' => __('Speichert Informationen zum Einwilligungsstatus für Google-Dienste.', self::TEXT_DOMAIN),
                        ],
                    ],
                ],
            ],
        ];

        $external_media_keys = self::get_external_media_preset_keys();

        if ($category === 'external_media') {
            $external_media = [];
            foreach ($presets['marketing'] as $service) {
                $preset_key = (string) ($service['preset_key'] ?? '');
                if (in_array($preset_key, $external_media_keys, true)) {
                    $external_media[$preset_key] = $service;
                }
            }

            $external_media['facebook'] = [
                'preset_key' => 'facebook',
                'enabled' => '0',
                'name' => 'Facebook',
                'provider' => 'Meta Platforms Ireland Limited',
                'address' => 'Merrion Road, Dublin 4, D04 X2K5, Irland',
                'privacy_url' => 'https://www.facebook.com/privacy/explanation',
                'cookie_policy_url' => 'https://www.facebook.com/privacy/policies/cookies/',
                'purpose' => __('Einbindung und Darstellung von Facebook-Inhalten auf der Website.', self::TEXT_DOMAIN),
                'cookies' => [
                    [
                        'name' => __('Abhängig vom eingebetteten Facebook-Inhalt', self::TEXT_DOMAIN),
                        'expiry' => __('Abhängig vom Dienst', self::TEXT_DOMAIN),
                        'type' => __('HTTP Cookie / Local Storage', self::TEXT_DOMAIN),
                        'purpose' => __('Facebook kann Cookies und ähnliche Technologien für eingebettete Inhalte, Sicherheit und Personalisierung nutzen.', self::TEXT_DOMAIN),
                    ],
                ],
            ];
            $external_media['instagram'] = [
                'preset_key' => 'instagram',
                'enabled' => '0',
                'name' => 'Instagram',
                'provider' => 'Meta Platforms Ireland Limited',
                'address' => 'Merrion Road, Dublin 4, D04 X2K5, Irland',
                'privacy_url' => 'https://www.facebook.com/privacy/explanation',
                'cookie_policy_url' => 'https://www.facebook.com/privacy/policies/cookies/',
                'purpose' => __('Einbindung und Darstellung von Instagram-Beiträgen auf der Website.', self::TEXT_DOMAIN),
                'cookies' => [
                    [
                        'name' => 'csrftoken',
                        'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                        'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                        'purpose' => __('Unterstützt Sicherheitsfunktionen bei eingebetteten Instagram-Inhalten.', self::TEXT_DOMAIN),
                    ],
                    [
                        'name' => 'mid',
                        'expiry' => __('2 Jahre', self::TEXT_DOMAIN),
                        'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                        'purpose' => __('Speichert eine Gerätekennung für Instagram-Dienste.', self::TEXT_DOMAIN),
                    ],
                ],
            ];
            $external_media['openstreetmap'] = [
                'preset_key' => 'openstreetmap',
                'enabled' => '0',
                'name' => 'OpenStreetMap',
                'provider' => 'OpenStreetMap Foundation',
                'address' => 'St John’s Innovation Centre, Cowley Road, Cambridge, CB4 0WS, Vereinigtes Königreich',
                'privacy_url' => 'https://osmfoundation.org/wiki/Privacy_Policy',
                'cookie_policy_url' => 'https://osmfoundation.org/wiki/Terms_of_Use',
                'purpose' => __('Einbindung interaktiver Karten von OpenStreetMap.', self::TEXT_DOMAIN),
                'cookies' => [
                    [
                        'name' => __('Abhängig vom Kartenanbieter', self::TEXT_DOMAIN),
                        'expiry' => __('Abhängig vom Dienst', self::TEXT_DOMAIN),
                        'type' => __('HTTP Cookie / Anfrage-Daten', self::TEXT_DOMAIN),
                        'purpose' => __('Wird für die Bereitstellung und Auslieferung interaktiver Karteninhalte genutzt.', self::TEXT_DOMAIN),
                    ],
                ],
            ];
            $external_media['soundcloud'] = [
                'preset_key' => 'soundcloud',
                'enabled' => '0',
                'name' => 'SoundCloud',
                'provider' => 'SoundCloud Global Limited & Co. KG',
                'address' => 'Rheinsberger Str. 76/77, 10115 Berlin, Deutschland',
                'privacy_url' => 'https://soundcloud.com/pages/privacy',
                'cookie_policy_url' => 'https://soundcloud.com/pages/cookies',
                'purpose' => __('Einbindung und Wiedergabe von SoundCloud-Audioinhalten auf der Website.', self::TEXT_DOMAIN),
                'cookies' => [
                    [
                        'name' => 'sc_anonymous_id',
                        'expiry' => __('10 Jahre', self::TEXT_DOMAIN),
                        'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                        'purpose' => __('Speichert eine anonyme Kennung für SoundCloud-Player und Nutzungsmessung.', self::TEXT_DOMAIN),
                    ],
                ],
            ];
            $external_media['spotify'] = [
                'preset_key' => 'spotify',
                'enabled' => '0',
                'name' => 'Spotify',
                'provider' => 'Spotify AB',
                'address' => 'Regeringsgatan 19, 111 53 Stockholm, Schweden',
                'privacy_url' => 'https://www.spotify.com/legal/privacy-policy/',
                'cookie_policy_url' => 'https://www.spotify.com/legal/cookies-policy/',
                'purpose' => __('Einbindung und Wiedergabe von Spotify-Audioinhalten auf der Website.', self::TEXT_DOMAIN),
                'cookies' => [
                    [
                        'name' => 'sp_t',
                        'expiry' => __('1 Jahr', self::TEXT_DOMAIN),
                        'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                        'purpose' => __('Unterstützt die Wiedergabe und Personalisierung eingebetteter Spotify-Inhalte.', self::TEXT_DOMAIN),
                    ],
                    [
                        'name' => 'sp_landing',
                        'expiry' => __('1 Tag', self::TEXT_DOMAIN),
                        'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
                        'purpose' => __('Speichert Informationen zum Aufruf eingebetteter Spotify-Inhalte.', self::TEXT_DOMAIN),
                    ],
                ],
            ];
            $external_media['x'] = [
                'preset_key' => 'x',
                'enabled' => '0',
                'name' => 'X',
                'provider' => 'Twitter International Unlimited Company',
                'address' => 'One Cumberland Place, Fenian Street, Dublin 2, D02 AX07, Irland',
                'privacy_url' => 'https://x.com/en/privacy',
                'cookie_policy_url' => 'https://help.x.com/en/rules-and-policies/x-cookies',
                'purpose' => __('Einbindung und Darstellung von X-Beiträgen auf der Website.', self::TEXT_DOMAIN),
                'cookies' => [
                    [
                        'name' => __('Abhängig vom eingebetteten X-Inhalt', self::TEXT_DOMAIN),
                        'expiry' => __('Abhängig vom Dienst', self::TEXT_DOMAIN),
                        'type' => __('HTTP Cookie / Local Storage', self::TEXT_DOMAIN),
                        'purpose' => __('X kann Cookies und ähnliche Technologien für eingebettete Beiträge, Sicherheit und Personalisierung nutzen.', self::TEXT_DOMAIN),
                    ],
                ],
            ];

            return array_values(array_filter(
                array_map(
                    static function (string $preset_key) use ($external_media): ?array {
                        return $external_media[$preset_key] ?? null;
                    },
                    $external_media_keys
                )
            ));
        }

        if ($category === 'marketing') {
            return array_values(array_filter(
                $presets['marketing'],
                static function (array $service) use ($external_media_keys): bool {
                    return !in_array((string) ($service['preset_key'] ?? ''), $external_media_keys, true);
                }
            ));
        }

        return $presets[$category] ?? [];
    }

    private static function is_deprecated_service_preset($service): bool
    {
        if (!is_array($service)) {
            return false;
        }

        return in_array(sanitize_key($service['preset_key'] ?? ''), self::DEPRECATED_SERVICE_PRESET_KEYS, true);
    }

    private static function is_external_media_preset_key($preset_key): bool
    {
        return in_array(sanitize_key((string) $preset_key), self::get_external_media_preset_keys(), true);
    }

    private function get_empty_service_settings(): array
    {
        return [
            'preset_key' => '',
            'enabled' => '0',
            'name' => '',
            'provider' => '',
            'address' => '',
            'privacy_url' => '',
            'cookie_policy_url' => '',
            'purpose' => '',
            'service_id' => '',
            'embed_code' => '',
            'opt_out_code' => '',
            'cookies' => [
                $this->get_empty_cookie_settings(),
            ],
        ];
    }

    private function get_empty_cookie_settings(): array
    {
        return [
            'name' => '',
            'expiry' => '',
            'type' => __('HTTP Cookie', self::TEXT_DOMAIN),
            'purpose' => '',
        ];
    }

    private function get_text_options(array $options): array
    {
        $texts = [];

        foreach (self::TEXT_FIELDS as $key) {
            $texts[$key] = $options[$key] ?? '';
        }

        return $texts;
    }

    private function build_inline_css(): string
    {
        $options = $this->get_options();
        $map = $this->get_color_css_var_map();

        $declarations = [];
        foreach ($map as $option_key => $css_var) {
            $declarations[] = sprintf('%s:%s', $css_var, esc_html($options[$option_key]));
        }

        $declarations[] = sprintf(
            '--ccm-gradient-primary:linear-gradient(135deg, %1$s 0%%, %2$s 100%%)',
            esc_html($options['color_accent']),
            esc_html($options['color_accent_light'])
        );

        return '.consent-floating-btn,.consent-banner,.n24-content-blocker{' . implode(';', $declarations) . '}';
    }

    private function build_preview_style(array $options): string
    {
        $declarations = [];

        foreach ($this->get_color_css_var_map() as $option_key => $css_var) {
            $declarations[] = sprintf('%s:%s', $css_var, esc_attr($options[$option_key] ?? ''));
        }

        $declarations[] = sprintf(
            '--ccm-gradient-primary:linear-gradient(135deg, %1$s 0%%, %2$s 100%%)',
            esc_attr($options['color_accent'] ?? ''),
            esc_attr($options['color_accent_light'] ?? '')
        );

        return implode(';', $declarations);
    }

    private function get_css_var_for_color(string $key): string
    {
        return $this->get_color_css_var_map()[$key] ?? '';
    }

    private function get_color_css_var_map(): array
    {
        return [
            'color_overlay' => '--ccm-color-overlay',
            'color_modal_background' => '--ccm-color-bg',
            'color_panel_background' => '--ccm-color-bg-alt',
            'color_text_primary' => '--ccm-color-text-primary',
            'color_text_secondary' => '--ccm-color-text-secondary',
            'color_text_muted' => '--ccm-color-text-muted',
            'color_accent' => '--ccm-color-accent',
            'color_accent_light' => '--ccm-color-accent-light',
            'color_accent_dark' => '--ccm-color-accent-dark',
            'color_border' => '--ccm-color-border',
            'color_border_hover' => '--ccm-color-border-hover',
            'color_button_text' => '--ccm-color-button-text',
            'color_floating_background' => '--ccm-color-floating-bg',
            'color_floating_hover_background' => '--ccm-color-floating-hover-bg',
            'color_box_icon' => '--ccm-color-box-icon',
            'color_floating_icon' => '--ccm-color-floating-icon',
            'content_blocker_link_color' => '--ccm-content-blocker-link',
            'content_blocker_link_hover_color' => '--ccm-content-blocker-link-hover',
            'content_blocker_primary_button_background' => '--ccm-content-blocker-primary-bg',
            'content_blocker_primary_button_text' => '--ccm-content-blocker-primary-text',
            'content_blocker_primary_button_hover_background' => '--ccm-content-blocker-primary-hover-bg',
            'content_blocker_primary_button_hover_text' => '--ccm-content-blocker-primary-hover-text',
            'content_blocker_secondary_button_background' => '--ccm-content-blocker-secondary-bg',
            'content_blocker_secondary_button_text' => '--ccm-content-blocker-secondary-text',
            'content_blocker_secondary_button_hover_background' => '--ccm-content-blocker-secondary-hover-bg',
            'content_blocker_secondary_button_hover_text' => '--ccm-content-blocker-secondary-hover-text',
        ];
    }

    private function get_options(): array
    {
        $options = get_option(self::OPTION_NAME, []);

        if (!$options) {
            foreach (self::LEGACY_OPTION_NAMES as $legacy_option_name) {
                $legacy = get_option($legacy_option_name, []);

                if (is_array($legacy) && $legacy) {
                    $options = $legacy;
                    break;
                }
            }
        }

        if (is_array($options) && !empty($options['icon_svg'])) {
            if (empty($options['box_icon_svg'])) {
                $options['box_icon_svg'] = $options['icon_svg'];
            }

            if (empty($options['floating_icon_svg'])) {
                $options['floating_icon_svg'] = $options['icon_svg'];
            }
        }

        return wp_parse_args($options, self::default_options());
    }

    private static function get_default_icon_svg(): string
    {
        return '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><circle cx="16" cy="16" r="12" stroke="currentColor" stroke-width="2.5"/><circle cx="11" cy="13" r="2.2" fill="currentColor"/><circle cx="18" cy="10" r="2" fill="currentColor"/><circle cx="21" cy="18" r="2.4" fill="currentColor"/><circle cx="13" cy="21" r="1.9" fill="currentColor"/></svg>';
    }

    private function get_legal_path_slugs(array $options): array
    {
        $slugs = ['datenschutz', 'impressum'];

        foreach (['privacy_url', 'imprint_url'] as $key) {
            $path = (string) wp_parse_url($options[$key], PHP_URL_PATH);
            $slug = trim($path, '/');

            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        return array_values(array_unique($slugs));
    }

    private function sanitize_color(string $value, string $fallback): string
    {
        $value = trim($value);

        if (preg_match('/^#([0-9a-fA-F]{3})$/', $value, $matches)) {
            return sprintf(
                '#%1$s%1$s%2$s%2$s%3$s%3$s',
                strtolower($matches[1][0]),
                strtolower($matches[1][1]),
                strtolower($matches[1][2])
            );
        }

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return strtolower($value);
        }

        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $value)) {
            return $value;
        }

        return $fallback;
    }

    private function sanitize_icon_svg(string $svg): string
    {
        $allowed = [
            'svg' => [
                'xmlns' => true,
                'width' => true,
                'height' => true,
                'viewbox' => true,
                'viewBox' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
                'aria-hidden' => true,
                'role' => true,
                'focusable' => true,
            ],
            'path' => [
                'd' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
            ],
            'rect' => [
                'x' => true,
                'y' => true,
                'width' => true,
                'height' => true,
                'rx' => true,
                'fill' => true,
                'stroke' => true,
            ],
            'circle' => [
                'cx' => true,
                'cy' => true,
                'r' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
            ],
            'line' => [
                'x1' => true,
                'y1' => true,
                'x2' => true,
                'y2' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
            ],
            'polyline' => [
                'points' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
            ],
        ];

        return wp_kses($svg, $allowed);
    }

    private static function default_options(): array
    {
        $default_icon_svg = self::get_default_icon_svg();

        return [
            'plugin_enabled' => '1',
            'login_enabled' => '0',
            'privacy_url' => home_url('/datenschutz/'),
            'imprint_url' => home_url('/impressum/'),
            'provider_name' => get_bloginfo('name'),
            'provider_address' => '',
            'storage_key' => 'n24_consent_manager_consent',
            'banner_version' => self::VERSION,
            'privacy_policy_version' => '1.0',
            'deleted_preset_keys' => [],
            'color_overlay' => 'rgba(255, 255, 255, 0.85)',
            'color_modal_background' => '#ffffff',
            'color_panel_background' => '#f8fafc',
            'color_text_primary' => '#111827',
            'color_text_secondary' => '#334155',
            'color_text_muted' => '#64748b',
            'color_accent' => '#a67c00',
            'color_accent_light' => '#c2950e',
            'color_accent_dark' => '#826100',
            'color_border' => '#cbd5e1',
            'color_border_hover' => '#94a3b8',
            'color_button_text' => '#ffffff',
            'color_floating_background' => '#ffffff',
            'color_floating_hover_background' => '#f1f5f9',
            'color_box_icon' => '#111827',
            'color_floating_icon' => '#a67c00',
            'content_blocker_link_color' => '#7cc7ff',
            'content_blocker_link_hover_color' => '#a3d7ff',
            'content_blocker_primary_button_background' => '#be920c',
            'content_blocker_primary_button_text' => '#ffffff',
            'content_blocker_primary_button_hover_background' => '#bfa34e',
            'content_blocker_primary_button_hover_text' => '#ffffff',
            'content_blocker_secondary_button_background' => '#be920c',
            'content_blocker_secondary_button_text' => '#ffffff',
            'content_blocker_secondary_button_hover_background' => '#bfa34e',
            'content_blocker_secondary_button_hover_text' => '#ffffff',
            'icon_svg' => $default_icon_svg,
            'box_icon_svg' => $default_icon_svg,
            'floating_icon_svg' => $default_icon_svg,
            'statistics_service_enabled' => '0',
            'statistics_service_name' => '',
            'statistics_service_provider' => '',
            'statistics_service_address' => '',
            'statistics_service_privacy_url' => '',
            'statistics_service_purpose' => __('Statistische Auswertung der Website-Nutzung.', self::TEXT_DOMAIN),
            'statistics_service_cookie_name' => '',
            'statistics_service_cookie_expiry' => '',
            'statistics_service_cookie_type' => __('HTTP Cookie', self::TEXT_DOMAIN),
            'statistics_service_cookie_purpose' => '',
            'statistics_services' => self::get_preset_services_for_category('statistics'),
            'marketing_service_enabled' => '0',
            'marketing_service_name' => '',
            'marketing_service_provider' => '',
            'marketing_service_address' => '',
            'marketing_service_privacy_url' => '',
            'marketing_service_purpose' => __('Personalisierte Inhalte und Werbung.', self::TEXT_DOMAIN),
            'marketing_service_cookie_name' => '',
            'marketing_service_cookie_expiry' => '',
            'marketing_service_cookie_type' => __('HTTP Cookie', self::TEXT_DOMAIN),
            'marketing_service_cookie_purpose' => '',
            'marketing_services' => self::get_preset_services_for_category('marketing'),
            'external_media_service_enabled' => '0',
            'external_media_service_name' => '',
            'external_media_service_provider' => '',
            'external_media_service_address' => '',
            'external_media_service_privacy_url' => '',
            'external_media_service_purpose' => __('Einbindung externer Medien und Social-Media-Inhalte.', self::TEXT_DOMAIN),
            'external_media_service_cookie_name' => '',
            'external_media_service_cookie_expiry' => '',
            'external_media_service_cookie_type' => __('HTTP Cookie', self::TEXT_DOMAIN),
            'external_media_service_cookie_purpose' => '',
            'external_media_services' => self::get_preset_services_for_category('external_media'),
            'content_blocker_enabled' => '1',
            'content_blocker_facebook_enabled' => '1',
            'content_blocker_youtube_enabled' => '1',
            'content_blocker_vimeo_enabled' => '1',
            'content_blocker_google_maps_enabled' => '1',
            'content_blocker_instagram_enabled' => '1',
            'content_blocker_openstreetmap_enabled' => '1',
            'content_blocker_soundcloud_enabled' => '1',
            'content_blocker_spotify_enabled' => '1',
            'content_blocker_x_enabled' => '1',
            'content_blocker_embed_images' => [],
            'content_blocker_embed_titles' => [],
            'content_blocker_service_settings' => [],
            'dialog_title' => __('Datenschutzeinstellungen', self::TEXT_DOMAIN),
            'tab_overview' => __('Übersicht', self::TEXT_DOMAIN),
            'tab_details' => __('Details & Cookies', self::TEXT_DOMAIN),
            'tab_history' => __('Historie', self::TEXT_DOMAIN),
            'intro_text' => __('Es werden Cookies genutzt. Notwendige Cookies sind für die Website-Funktion erforderlich. Andere Cookies sind optional und werden nur mit Ihrer Einwilligung gesetzt.', self::TEXT_DOMAIN),
            'necessary_label' => __('Notwendig', self::TEXT_DOMAIN),
            'necessary_info' => __('Essenziell für die Grundfunktionen der Website.', self::TEXT_DOMAIN),
            'statistics_label' => __('Statistik', self::TEXT_DOMAIN),
            'statistics_inactive_label' => __('Statistik (derzeit nicht aktiv)', self::TEXT_DOMAIN),
            'statistics_info' => __('Statistische Auswertung der Website-Nutzung.', self::TEXT_DOMAIN),
            'statistics_inactive_info' => __('Derzeit sind keine Statistik-Dienste aktiv.', self::TEXT_DOMAIN),
            'marketing_label' => __('Marketing', self::TEXT_DOMAIN),
            'marketing_inactive_label' => __('Marketing (derzeit nicht aktiv)', self::TEXT_DOMAIN),
            'marketing_info' => __('Personalisierte Inhalte und Werbung.', self::TEXT_DOMAIN),
            'marketing_inactive_info' => __('Derzeit sind keine Marketing-Dienste aktiv.', self::TEXT_DOMAIN),
            'external_media_label' => __('Externe Medien', self::TEXT_DOMAIN),
            'external_media_inactive_label' => __('Externe Medien (derzeit nicht aktiv)', self::TEXT_DOMAIN),
            'external_media_info' => __('Inhalte von Videoplattformen, Karten und Social-Media-Plattformen.', self::TEXT_DOMAIN),
            'external_media_inactive_info' => __('Derzeit sind keine externen Medien aktiv.', self::TEXT_DOMAIN),
            'info_default' => __('Essenziell für die Grundfunktionen der Website.', self::TEXT_DOMAIN),
            'details_intro' => __('Sie können auswählen, welche Kategorien Sie erlauben. Ihre Entscheidung können Sie jederzeit über Cookie-Einstellungen ändern.', self::TEXT_DOMAIN),
            'history_intro' => __('Hier finden Sie den Verlauf Ihrer Einwilligungen.', self::TEXT_DOMAIN),
            'consent_id_label' => __('Ihre Consent-ID:', self::TEXT_DOMAIN),
            'history_empty' => __('Noch keine Einträge vorhanden.', self::TEXT_DOMAIN),
            'reject_button' => __('Alle ablehnen', self::TEXT_DOMAIN),
            'accept_all_button' => __('Alle akzeptieren', self::TEXT_DOMAIN),
            'save_button' => __('Auswahl speichern', self::TEXT_DOMAIN),
            'customize_button' => __('Auswahl anpassen', self::TEXT_DOMAIN),
            'settings_link' => __('Cookie-Einstellungen', self::TEXT_DOMAIN),
            'floating_aria_label' => __('Datenschutz-Einstellungen öffnen', self::TEXT_DOMAIN),
            'service_always_on' => __('Immer an', self::TEXT_DOMAIN),
            'service_description_label' => __('Beschreibung', self::TEXT_DOMAIN),
            'service_provider_label' => __('Provider', self::TEXT_DOMAIN),
            'service_cookies_label' => __('Cookie(s)', self::TEXT_DOMAIN),
            'service_privacy_label' => __('Datenschutzerklärung', self::TEXT_DOMAIN),
            'service_cookie_policy_label' => __('Cookierichtlinie', self::TEXT_DOMAIN),
            'service_legal_basis_label' => __('Rechtsgrundlage', self::TEXT_DOMAIN),
            'service_third_country_label' => __('Drittlandübermittlung', self::TEXT_DOMAIN),
            'service_recipient_country_label' => __('Empfängerland', self::TEXT_DOMAIN),
            'service_safeguards_label' => __('Garantien / Schutzmaßnahmen', self::TEXT_DOMAIN),
            'service_count_single' => __('Service', self::TEXT_DOMAIN),
            'service_count_plural' => __('Services', self::TEXT_DOMAIN),
            'cookie_name_label' => __('Name', self::TEXT_DOMAIN),
            'cookie_expiry_label' => __('Laufzeit', self::TEXT_DOMAIN),
            'cookie_purpose_label' => __('Zweck', self::TEXT_DOMAIN),
            'history_date_label' => __('Datum', self::TEXT_DOMAIN),
            'history_status_label' => __('Status', self::TEXT_DOMAIN),
            'necessary_service_name' => __('Consent Manager', self::TEXT_DOMAIN),
            'necessary_service_purpose' => __('Speichert den Zustimmungsstatus des Benutzers für Cookie-Einstellungen.', self::TEXT_DOMAIN),
            'necessary_cookie_expiry' => __('1 Jahr', self::TEXT_DOMAIN),
            'necessary_cookie_type' => __('Local Storage', self::TEXT_DOMAIN),
            'necessary_cookie_purpose' => __('Technisch notwendig', self::TEXT_DOMAIN),
            'content_blocker_title' => '',
            'content_blocker_text' => __('Dieser externe Inhalt wird von %s geladen. Durch das Anzeigen akzeptierst du die Nutzungsbedingungen von %s.', self::TEXT_DOMAIN),
            'content_blocker_button' => __('Inhalt laden', self::TEXT_DOMAIN),
            'content_blocker_always_button' => __('Immer laden', self::TEXT_DOMAIN),
            'content_blocker_missing_service_text' => __('Der passende Dienst ist im Consent Manager noch nicht aktiv. Bitte aktivieren Sie den Dienst im Backend.', self::TEXT_DOMAIN),
        ];
    }
}

register_activation_hook(__FILE__, ['N24_Consent_Manager', 'activate']);
new N24_Consent_Manager();
