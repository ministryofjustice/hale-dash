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

    return $url;
}

$live_urls = get_live_urls();
$this_url = get_bloginfo('url');

global $wpdb;

// Build a site_id => logged-in count map from the active user IDs already resolved
// in feature-metrics.php — one batch query across all sites, no per-site loop needed.
$site_active_counts = [];
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
        $site_active_counts[$bid] = ($site_active_counts[$bid] ?? 0) + 1;
    }
}

$transient_key = 'hale_dash_sites_' . sanitize_key($this_env);

// Allow cache busting via URL param (admins only)
if (isset($_GET['refresh_dash']) && current_user_can('manage_network')) {
    delete_transient($transient_key);
}

$cached = get_transient($transient_key);
if ($cached !== false) {
    echo wp_kses_post($cached);
    	return;
}

ob_start();

foreach ($sites as $site) {
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

    // Resolve the production URL / domain shown under the site title.
    $site_path_slug = get_option('site_path_slug') ?: "";
    if ($this_env == "Prod") {
        $prod_url = $site_url;
    } elseif (isset($live_urls[trim($site_name)])) {
        $prod_url = $live_urls[trim($site_name)];
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
    <div class="hale-dash-site-item" data-site-name="<?php echo esc_attr(strtolower($site_name)); ?>" data-site-id="<?php echo esc_attr($site_id); ?>" data-site-slug="<?php echo esc_attr(strtolower($site_path_slug)); ?>">
    <article class="website">
        <div class="website__heading">
            <?php
                if ($site_id == $dashboard_ID) {
                    echo "<h2 class='website__heading__text govuk-heading-s'>Hale Platform Dashboard</h2>";
                    echo "<p class='govuk-body govuk-hint govuk-!-margin-bottom-0 website__explanation'>This dashboard</p>";
                } else {
                    echo "<h2 " . $site_lang_attribute . " class='website__heading__text govuk-heading-s'>" . esc_html($site_name) . "</h2>";
                    $warning .= language_warning($lang);
                    $warning .= timezone_warning($timezone);
                    $warning .= theme_warning($theme);
                    $warning .= deprecated_warning($deprecated);
                }
            ?>
        </div>
        <?php if ($site_id != $dashboard_ID): ?>
            <a class="website__domain govuk-link govuk-body-s" href="<?php echo esc_url($prod_url); ?>" title="<?php echo esc_attr($prod_url); ?>"><?php echo esc_html($prod_domain); ?></a>
        <?php endif; ?>
        <div class="website__users govuk-body-s govuk-!-margin-bottom-0">
            <?php
                echo $status;
                if ($user_count && $user_count <= 1000) echo "<br />" . intval($user_count) . " users";
                				if ($user_count && $user_count > 1000) {
                					$user_count_text = number_format((float)($user_count/1000), 1, '.', '').'k';
                					echo "<br />" . esc_html($user_count_text) . " users";
                				}
                				$site_logged_in = (int) ($site_active_counts[$site_id] ?? 0);
                				if ($site_logged_in > 0) echo "<span class='website__online-count'>" . intval($site_logged_in) . " online</span>";
            ?>
        </div>
        <div class="website__footer govuk-body-s">
            <div class='website__technical govuk-!-margin-bottom-0'>
                <?php
                    if ($site_path_slug != "") echo "<h2 class='website__slug-title'>Slug</h2> <code class='website__slug'>" . esc_html($site_path_slug) . "</code>";
                    echo "<h2 class='website__id-title'>ID</h2> <span class='website_id'>" . intval($site_id) . "</span>";
                ?>
            </div>
            <div class="website__links govuk-!-margin-bottom-0">
                <?php
                foreach ($environments as $env) {
                    switch ($env) {
                        case "local":
                            $env_url = "https://hale.docker/$site_path_slug";
                            break;
                        default:
                            $env_url = "https://$env.websitebuilder.service.justice.gov.uk/$site_path_slug";
                    }

                    if ($site_path_slug == "" && $site_id != 1) {
                        echo "<span class='website__environment website__environment--disabled website__environment--" . esc_attr($env) . "'>" . esc_html(ucfirst($env)) . "</span>";
                    } else {
                        echo "<a href='" . esc_url($env_url) . "' class='website__environment website__environment--" . esc_attr($env) . " govuk-link'>" . esc_html(ucfirst($env)) . "</a>";
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
