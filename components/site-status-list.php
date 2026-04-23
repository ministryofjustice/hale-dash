<?php
$environments = [
    'staging',
    'dev',
    'demo'
];

$dashboard_ID = "59";

if ($this_env == "Local") {
    // set in feature-metrics.php
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
    $theme = get_option( 'stylesheet' );
    $deprecated = get_theme_mod( 'deprecated_paragraph_widths' );
    if ($lang != $main_lang) $site_lang_attribute = "lang='$lang'";
    if ($lang == "") $site_lang_attribute = "lang=en-US"; //WP uses "" to denote en-US

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

    // Production status tag (was previously set inside the env loop).
    if ($site_name == $next_site_name && ($this_env == "Prod" || $this_env == "Local")) {
        $status = '<span class="website__up-down"><strong class="govuk-tag hale-dash-better-tag govuk-tag--turquoise">Next</strong></span>';
    } elseif (is_plugin_active_on_site('wp-force-login/wp-force-login.php', $site_id)) {
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
                echo $icon;
                if ($site_id == $dashboard_ID) {
                    echo "<h2 class='website__heading__text govuk-heading-s'>Hale Platform Dashboard</h2>";
                    echo "<p class='govuk-body govuk-hint govuk-!-margin-bottom-0 website__explanation'>This dashboard</p>";
                } else {
                    echo "<h2 $site_lang_attribute class='website__heading__text govuk-heading-s'>$site_name</h2>";
                    $warning .= language_warning($lang);
                    $warning .= timezone_warning($timezone);
                    $warning .= theme_warning($theme);
                    $warning .= deprecated_warning($deprecated);
                }
            ?>
        </div>
        <?php if ($site_id != $dashboard_ID): ?>
            <a class="website__domain govuk-link govuk-body-s" href="<?php echo $prod_url; ?>" title="<?php echo $prod_url; ?>"><?php echo $prod_domain; ?></a>
        <?php endif; ?>
        <div class="website__users govuk-body-s govuk-!-margin-bottom-0">
            <?php
                echo $status;
                $user_count = count_users()['total_users'];
                if ($user_count && $user_count <= 1000) echo "<br />$user_count users";
                if ($user_count && $user_count > 1000) {
                    $user_count_text = number_format((float)($user_count/1000), 1, '.', '').'k';
                    echo "<br />$user_count_text users";
                }
            ?>
        </div>
        <div class="website__footer govuk-body-s">
            <div class='website__technical govuk-!-margin-bottom-0'>
                <?php
                    if ($site_path_slug != "") echo "<h2 class='website__slug-title'>Slug</h2> <code class='website__slug'>$site_path_slug</code>";
                    echo "<h2 class='website__id-title'>ID</h2> <span class='website_id'>$site_id</span>";
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
                        echo "<span class='website__environment website__environment--disabled website__environment--$env'>" . ucfirst($env) . "</span>";
                    } else {
                        echo "<a href='$env_url' class='website__environment website__environment--$env govuk-link'>" . ucfirst($env) . "</a>";
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
    restore_current_blog();
}
