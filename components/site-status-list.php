<?php
$environments = [
    'staging',
    'dev',
    'demo'
];

$dashboard_ID = "59";

if ($this_env == "Local") {
    $environments[] = "local";
}

// Backup urls for non-production incarnations of the dashboard
function get_live_urls() {
    $url["Criminal Cases Review Commission"] = "ccrc.gov.uk";
    $url["Magistrates Recruitment"] = "magistrates.judiciary.uk";
    $url["Recriwtio Ynadon"] = "https://magistrates.judiciary.uk/cymraeg/";
    $url["Victims Commissioner"] = "victimscommissioner.org.uk";
    $url["Independent Monitoring Boards"] = "imb.org.uk";
    $url["National Preventive Mechanism"] = "nationalpreventivemechanism.org.uk";
    $url["Independent Commission for Reconciliation &amp; Information Recovery"] = "icrir.independent-inquiry.uk";
    $url["Public Defender Service"] = "publicdefenderservice.org.uk";
    $url["Prison and Probation Jobs"] = "prisonandprobationjobs.gov.uk";
    $url["Brook House Inquiry"] = "brookhouseinquiry.org.uk";
    $url["Law Commission"] = "lawcom.gov.uk";
    $url["Justice Jobs"] = "jobs.justice.gov.uk";
    $url["Prisons &amp; Probation Ombudsman"] = "ppo.gov.uk";
    $url["Lay Observers"] = "layobservers.org";
    $url["SIFoCC"] = "sifocc.org";
    $url["Victim and Witness Information"] = "victimandwitnessinformation.org.uk";
    $url["New Futures Network"] = "newfuturesnetwork.gov.uk";
    $url["Omagh Bombing Inquiry"] = "https://omagh.independent-inquiry.uk/";
    $url["Gwybodaeth i Ddioddefwyr a Thystion"] = "https://cym.victimandwitnessinformation.org.uk/";
    $url["CJSM"] = "https://cjsm.justice.gov.uk/";
    $url["Independent Public Advocate"] = "https://independentpublicadvocate.org.uk/";
    $url["Criminal Justice Inspectorates"] = "https://justiceinspectorates.gov.uk/";
    $url["Patrick Finucane Inquiry"] = "https://finucane.independent-inquiry.uk/";
    $url["The Nottingham Inquiry"] = "https://nottingham.independent-inquiry.uk/";
    $url["Judicial Appointments Commission"] = "https://judicialappointments.gov.uk/";

    return $url;
}

$live_urls = get_live_urls();
$this_url = get_bloginfo('url');

global $wpdb;

// Build a site_id => logged-in count map from the active user IDs already resolved
// in feature-metrics.php — one batch query across all sites, no per-site loop needed.
$site_active_counts = [];
$site_active_users = [];
if (!empty($active_user_ids)) {
    $placeholders = implode(',', array_fill(0, count($active_user_ids), '%d'));
    $base         = $wpdb->base_prefix;
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, meta_key FROM {$wpdb->usermeta}
            WHERE meta_key LIKE %s
            AND user_id IN ($placeholders)",
            array_merge([$wpdb->esc_like($base) . '%capabilities'], $active_user_ids)
        )
    );
    foreach ($rows as $row) {
        $key = $row->meta_key;
        if ($key === $base . 'capabilities') {
            $bid = 1;
        } elseif (preg_match('/^' . preg_quote($base, '/') . '(\d+)_capabilities$/', $key, $m)) {
            $bid = (int) $m[1];
        } else {
            continue;
        }
        if (!array_key_exists($bid,$site_active_users)) $site_active_users[$bid] = "";
        $site_active_counts[$bid] = ($site_active_counts[$bid] ?? 0) + 1;
        $site_active_users[$bid] .= $row->user_id . "; ";
    }
}

$transient_key             = 'hale_dash_sites_' . sanitize_key($this_env);
$refresh_dash_nonce_action = 'refresh_dash_' . $transient_key;

// Allow cache busting for network admins only, but require POST + nonce to prevent CSRF.
if (
    isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['refresh_dash'], $_POST['refresh_dash_nonce']) &&
    current_user_can('manage_network') &&
    wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['refresh_dash_nonce'])), $refresh_dash_nonce_action)
) {
    delete_transient($transient_key);
}

$cached = get_transient($transient_key);
if ($cached !== false) {
    echo $cached;
    return;
}

ob_start();

foreach ($sites as $site) {
    $current_user_logged_in = false;
    $marc_logged_in = false;
    $marc_user_id = 34;
    $dev_user_ids = [2,26,49];
    $site_id = $site->blog_id;
    $site_url = get_site_url($site_id);
    switch_to_blog($site_id);
    $site_name = get_bloginfo('name');
    $icon = get_fav_icon(get_site_icon_url());
    $lang = get_option('WPLANG');
    $timezone = wp_timezone_string();
    $main_lang = get_locale();
    $site_lang_attribute = "";
    $warning = "";
    $theme = get_option('stylesheet');
    $deprecated = get_theme_mod('deprecated_paragraph_widths');
    if ($lang != $main_lang) $site_lang_attribute = "lang='" . esc_attr($lang) . "'";
    if ($lang == "") $site_lang_attribute = "lang='en-US'";

    if (str_contains($site_active_users[$site_id],$current_user_id.";")) {
        $current_user_logged_in = true;
    }
    if ($current_user_id != $marc_user_id && in_array($current_user_id,$dev_user_ids) && str_contains($site_active_users[$site_id],$marc_user_id.";")) {
        $marc_logged_in = true;
    }

    // Resolve the production URL / domain shown under the site title.
    $site_path_slug = get_option('site_path_slug') ?: "";
    if ($this_env == "Prod") {
        $prod_url = $site_url;
    } elseif (isset($live_urls[trim($site_name)])) {
        $prod_url = $live_urls[trim($site_name)];
    } elseif ($site_id == "1") {
        $prod_url = "https://websitebuilder.service.justice.gov.uk/";
    } else {
        $prod_url = "https://websitebuilder.service.justice.gov.uk/$site_path_slug";
    }
    if (strpos($prod_url, "http") === false) {
        $prod_url = "https://" . $prod_url;
    }
    $prod_domain = parse_url($prod_url, PHP_URL_HOST);
    if ($path = parse_url($prod_url, PHP_URL_PATH)) {
        $prod_domain .= rtrim($path, '/');
    }

    $url_data_attr = "";
    if (!str_contains($prod_domain, "websitebuilder.service.justice.gov.uk")) {
        $url_data_attr = "data-site-url='$prod_domain' ";
    }

    $new_tab_svg = '<svg class="website__domain-icon" aria-hidden="true" focusable="false" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';

    // COUNT query is far cheaper than count_users() which fetches all rows
    $blog_prefix = $wpdb->get_blog_prefix($site_id);
    $user_count  = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s",
            $blog_prefix . 'capabilities'
        )
    );

    // Active plugins already loaded into WP option cache by the switch above
    $active_plugins = (array) get_option('active_plugins');
    $is_private = in_array('wp-force-login/wp-force-login.php', $active_plugins);

    restore_current_blog();

    // Production status tag
    if ($site_name == $next_site_name && ($this_env == "Prod" || $this_env == "Local")) {
        $status = '<span class="website__up-down"><strong class="govuk-tag hale-dash-better-tag govuk-tag--turquoise">Next</strong></span>';
    } elseif ($is_private) {
        $status = '<span class="website__up-down"><strong class="govuk-tag hale-dash-better-tag govuk-tag--grey">Private</strong></span>';
    } elseif ($this_env == "Prod" || $this_env == "Local") {
        $status = '<span class="website__up-down"><strong class="govuk-tag hale-dash-better-tag govuk-tag--blue hale-dash-better-tag--blue">Public</strong></span>';
    } else {
        $status = '<span class="website__up-down"><strong class="govuk-tag hale-dash-better-tag govuk-tag--red hale-dash-better-tag--red">Public</strong></span>';
    }
    ?>
    <div class="hale-dash-site-item" data-site-name="<?php echo esc_attr(strtolower($site_name)); ?>" data-site-id="<?php echo esc_attr($site_id); ?>" data-site-slug="<?php echo esc_attr(strtolower($site_path_slug)); ?>" <?php echo $url_data_attr;?>>
    <article class="website">
        <div class="website__heading">
            <?php
                if ($site_id == $dashboard_ID) {
                    echo "<h3 class='website__heading__text govuk-heading-s'>Hale Platform Dashboard</h3>";
                    echo "<p class='govuk-body govuk-hint govuk-!-margin-bottom-0 website__explanation'>This dashboard</p>";
                } else {
                    echo "<h3 " . $site_lang_attribute . " class='website__heading__text govuk-heading-s'>" . esc_html($site_name) . "</h3>";
                    $warning .= language_warning($lang);
                    $warning .= timezone_warning($timezone);
                    $warning .= theme_warning($theme);
                    $warning .= deprecated_warning($deprecated);
                }
                // if ($marc_logged_in) echo '<div class="birthday-logo" style="background-image:url(' . esc_url(get_theme_file_uri('/assets/images/marc.png')) . ');"></div>';
            ?>
        </div>
        <?php if ($site_id != $dashboard_ID): ?>
            <p class="website__domain govuk-body-s govuk-!-margin-0"><a class="govuk-link" href="<?php echo esc_url($prod_url); ?>" title="<?php echo esc_attr($prod_url); ?>" target="_blank" rel="noopener noreferrer"><span class="website__domain-text"><?php echo esc_html($prod_domain); ?></span><?php echo $new_tab_svg;?></a></p>
            <?php if (!$is_private && !$current_user_logged_in): ?>
                <p class="website__login govuk-body-s govuk-!-margin-0">Or, <a class="govuk-link" href="<?php echo esc_url($prod_url); ?>/hale-wpms-2020" target="_blank" rel="noopener noreferrer"><span class="website__domain-text">login here</span><?php echo $new_tab_svg;?></a></p>
            <?php endif; ?>
        <?php endif; ?>
        <div class="website__technical">
            <?php
                if ($site_path_slug != "") echo "<span class='website__slug-title'>Slug</span> <code class='website__slug'>" . esc_html($site_path_slug) . "</code>";
                echo "<span class='website__id-title'>ID</span> <span class='website_id'>" . intval($site_id) . "</span>";
                if ($user_count) {
                    $display_count = $user_count > 1000
                        ? number_format((float)($user_count / 1000), 1, '.', '') . 'k'
                        : intval($user_count);
                    echo "<span class='website__users-title'>Users</span> <span class='website__user-count'>" . esc_html($display_count) . "</span>";
                }
            ?>
        </div>
        <div class="website__users govuk-body-s govuk-!-margin-bottom-0">
            <?php
                echo $status;
                $site_logged_in = (int) ($site_active_counts[$site_id] ?? 0);
                $online_class = "";
                if ($current_user_logged_in) $online_class .= "logged-in ";
                if ($marc_logged_in) $online_class .= "marc-logged-in ";
                if ($site_logged_in > 0) echo "<span class='website__online-count $online_class' data-users='{$site_active_users[$site_id]}'>" . intval($site_logged_in) . " online</span>";
            ?>
        </div>
        <div class="website__footer govuk-body-s">
            <div class="website__links">
                <?php
                foreach ($environments as $env) {
                    switch ($env) {
                        case "local":
                            $env_url = "https://hale.docker/";
                            break;
                        default:
                            $env_url = "https://$env.websitebuilder.service.justice.gov.uk/";
                    }

                    if ($site_path_slug == "" && $site_id != 1) {
                        echo "<span class='website__environment website__environment--disabled website__environment--" . esc_attr($env) . "'>" . esc_html(ucfirst($env)) . "</span>";
                    } elseif ($site_id == "1") {
                        echo "<a href='" . esc_url($env_url) . "' class='website__environment website__environment--" . esc_attr($env) . " govuk-link'>" . esc_html(ucfirst($env)) . "</a>";
                    } else {
                        echo "<a href='" . esc_url($env_url.$site_path_slug) . "' class='website__environment website__environment--" . esc_attr($env) . " govuk-link'>" . esc_html(ucfirst($env)) . "</a>";
                    }
                }
                ?>
            </div>
        </div>
    </article>
    <?php if ($warning): ?>
        <div class="website__warning"><?php echo $warning; ?></div>
    <?php endif; ?>
    </div>

    <?php
}

$output = ob_get_clean();
set_transient($transient_key, $output, 5 * MINUTE_IN_SECONDS);
echo $output;
