<?php
$environments = [
    'prod',
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
    ?>
    <div class="website">
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
        <div class="website__links">
            <?php
            foreach ($environments as $env) {

                $site_path_slug = "";

                if (get_option('site_path_slug')) {
                    $site_path_slug = get_option('site_path_slug');
                }

                switch ($env) {
                    case "prod":
                        $env_url = "https://websitebuilder.service.justice.gov.uk/$site_path_slug";
                        break;
                    case "local":
                        $env_url = "https://hale.docker/$site_path_slug";
                        break;
                    default:
                      $env_url = "https://$env.websitebuilder.service.justice.gov.uk/$site_path_slug";
                }


                ?>
                <div class="website__environment">
                    <?php
                    if ($env == "prod") {

                        if ($this_env == "Prod") {
                            $env_url = $site_url;
                        } elseif (isset($live_urls[trim($site_name)])) {
                            // checks hard-coded list of URLs
                            $env_url = $live_urls[trim($site_name)];
                        }

                        if ($site_name == $next_site_name && ($this_env=="Prod" || $this_env=="Local")) {
                            // Plugin matches next site name.
                            $status = '<span class="website__up-down"><strong class="govuk-tag hale-dash-better-tag govuk-tag--turquoise">Next</strong></span>';
                        } elseif (is_plugin_active_on_site('wp-force-login/wp-force-login.php', $site_id)) {
                            // Plugin is active on the specified site.
                            $status = '<span class="website__up-down"><strong class="govuk-tag hale-dash-better-tag govuk-tag--grey">Private</strong></span>';
                        } elseif ($this_env=="Prod" || $this_env=="Local") {
                            // Plugin is inactive on the specified site & site is prod or local.
                            $status = '<span class="website__up-down"><strong class="govuk-tag hale-dash-better-tag govuk-tag--blue hale-dash-better-tag--blue">Public</strong></span>';
                        } else {
                            // Plugin is inactive on the specified site & site is NOT prod or local.
                            $status = '<span class="website__up-down"><strong class="govuk-tag hale-dash-better-tag govuk-tag--red hale-dash-better-tag--red">Public</strong></span>';
                        }

                        if (strpos($env_url, "http") === false) {
                            $env_url = "https://" . $env_url;
                        }
                    } else {
                        if (!is_plugin_active_on_site('wp-force-login/wp-force-login.php', $site_id)) {
                            // Plugin is inactive on the specified site.
                            // $warning .= ucfirst("$env environment is not password protected! <br />"); // This didn't work!
                        }
                    }

                    // Add in the "login" link to prod
                    $env_link = "<a href='$env_url' class='website__environment__link website__environment__link--$env govuk-link'>" . ucfirst($env) . "</a>";
                    $login_link = "";

                    if ($env == 'prod') {
                    $login_link = " | <a href='$env_url/hale-wpms-2020'>Login</a>";
                    echo $env_link . $login_link;
                    } elseif ($site_path_slug == "" && $site_id != 1) {
                        // No slug, so no useful link
                        // Just write env name to keep alignment
                        echo ucfirst($env) . "<!-- No slug, no link -->";
                    } else {
                        echo $env_link;
                    }

                    ?>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="website__users">
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
        <div class='website__technical'>
            <?php
                if ($site_path_slug != "") echo "<h2 class='website__slug-title'>Slug</h2> <code class='website__slug'>$site_path_slug</code> <br /> ";
                echo "<h2 class='website__id-title'>ID</h2> <span class='website_id'>$site_id</span>";
            ?>
        </div>
    </div>
    <?php
        if ($warning) {
            echo "<div class='website__warning'>$warning</div>";
        }
    ?>

    <?php
    restore_current_blog();
}
