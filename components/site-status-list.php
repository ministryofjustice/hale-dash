<?php
$sites = get_sites();

$environments = [
    'prod',
    'staging',
    'dev',
    'demo'
];

function get_live_urls() {
    $url["Criminal Cases Review Commission"] = "ccrc.gov.uk";
    $url["Magistrates Recruitment"] = "magistrates.judiciary.uk";
    $url["Hawlfraint, Recriwtio Ynadon"] = "magistrates.judiciary.uk";
    $url["Recriwtio Ynadon"] = "magistrates.judiciary.uk";
    $url["Victims Commissioner"] = "victimscommissioner.org.uk";
    $url["Independent Monitoring Boards"] = "imb.org.uk";
    $url["National Preventive Mechanism"] = "nationalpreventivemechanism.org.uk";
    $url["Independent Commission for Reconciliation &amp; Information Recovery"] = "icrir.independent-inquiry.uk";
    $url["Public Defender Service"] = "publicdefenderservice.org.uk";
    $url["Prison and Probation Jobs"] = "prisonandprobationjobs.gov.uk";
    $url["Brook House Inquiry"] = "brookhouseinquiry.org.uk";

    return $url;
}

$live_urls = get_live_urls();
$this_url = get_bloginfo('url');

foreach ($sites as $site) {
    switch_to_blog($site->blog_id);
    ?>
    <div class="website">
        <div class="website__heading">
            <?php
            if (isset($live_urls[get_bloginfo('name')])) {
                //echo fav_icon("http://www.google.com/s2/favicons?domain=" . $live_urls[get_bloginfo('name')]);
            }
            ?>
            <h2 class="website__heading__text govuk-heading-s"><?php echo get_bloginfo('name'); ?></h2>
        </div>
        <?php
        foreach ($environments as $env) {

            $site_path_slug = "";

            if (get_option('site_path_slug')) {
                $site_path_slug = get_option('site_path_slug');
            }

            $env_url = "https://hale-platform-$env.apps.live.cloud-platform.service.justice.gov.uk/$site_path_slug";
            ?>
            <div class="website__environment">
                <?php
                if ($env == "prod") {
                    if (isset($live_urls[trim(get_bloginfo('name'))])) {
                        $env_url = $live_urls[trim(get_bloginfo('name'))];
                    }
                    
                    if (is_plugin_active_on_site('wp-force-login/wp-force-login.php', $site->blog_id)) {
                        // Plugin is active on the specified site.
                        $status = '<span class="website__up-down"><strong class="govuk-tag govuk-tag--grey">Private</strong></span>';
                    } else {
                        // Plugin is inactive on the specified site.
                        $status = '<span class="website__up-down"><strong class="govuk-tag">Public</strong></span>';
                    }

                    if (strpos($env_url, "http") === false) {
                        $env_url = "https://" . $env_url;
                    }
                }
                echo "<a href='$env_url' class='website__environment__link website__environment__link--$env govuk-link'>" . ucfirst($env) . "</a>";
                ?>
            </div>
            <?php
        }
        echo $status;
        ?>
    </div>
    <?php
    restore_current_blog();
}
