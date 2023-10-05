<?php

/**
 * Hale Dash theme functions and definitions
 *
 * @package   Hale Dash
 * @copyright Ministry Of Justice
 * @version   2.0
 */

/**
 * Sets up theme defaults and registers support for various WordPress features.
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function hale_dash_setup() {}

add_action('after_setup_theme', 'hale_dash_setup');

function hale_dash_enqueue_styles() {
	wp_enqueue_style( 'hale-dash-style',
        hale_dash_mix_asset('/css/hale-dash-style.min.css'),
		array( 'hale-style' ),
		wp_get_theme()->get( 'Version' )
	);
}

add_action( 'wp_enqueue_scripts', 'hale_dash_enqueue_styles' );

/**
 * Gett asset path function
 * 
 * @param $filename
 * @return string
 */
function hale_dash_mix_asset($filename)
{
    $manifest = file_get_contents(get_theme_file_path() . '/dist/mix-manifest.json');   
    $manifest = json_decode($manifest, true);

    if (!isset($manifest[$filename])) {
        error_log("Mix asset '$filename' does not exist in manifest.");
    }

    return get_theme_file_uri() . '/dist' . $manifest[$filename];
}

function ping($host, $port, $timeout) {
    // Copied from https://stackoverflow.com/questions/1239068/ping-site-and-return-result-in-php
    $tB = microtime(true);
    $fP = fSockOpen($host, $port, $errno, $errstr, $timeout);
    if (!$fP) {
        return '<span class="website__up-down"><strong class="govuk-tag govuk-tag--red">Down</strong></span>';
    }

    $tA = microtime(true);
    $wait = round((($tA - $tB) * 1000), 0);
    if ($wait < 50) {
        return '<span class="website__up-down"><strong class="govuk-tag govuk-tag--green">Good</strong></span>';
    }
    return '<span class="website__up-down"><strong class="govuk-tag govuk-tag--yellow">Okay</strong></span>';
    // below will shew time taken in MS
    return '<span class="website__up-down"><strong class="govuk-tag govuk-tag--blue">Up '.$wait.' ms</strong></span>';
}

function get_live_urls() {
    $url["Criminal Cases Review Commission"] = "ccrc.gov.uk";
    $url["Magistrates Recruitment"] = "magistrates.judiciary.uk";
    $url["Hawlfraint, Recriwtio Ynadon"] = "magistrates.judiciary.uk";
    $url["Recriwtio Ynadon"] = "magistrates.judiciary.uk";
    $url["Victims Commissioner"] = "victimscommissioner.org.uk";
    $url["Independent Monitoring Boards"] = "imb.org.uk";
    $url["Independent Commission for Reconciliation &amp; Information Recovery"] = "icrir.independent-inquiry.uk";
    $url["Public Defender Service"] = "publicdefenderservice.org.uk";
    $url["Prison and Probation Jobs"] = "prisonandprobationjobs.gov.uk";
    $url["Brook House Inquiry"] = "brookhouseinquiry.org.uk";

    return $url;
}

function fav_icon($image) {
    // From https://stackoverflow.com/questions/5701593/how-to-get-a-websites-favicon-with-php
    // Read image path, convert to base64 encoding
    $imageData = base64_encode(file_get_contents($image));

    if (empty($imageData)) return;

    // Format the image SRC:  data:{mime};base64,{data};
    $src = 'data: '.mime_content_type($image).';base64,'.$imageData;

    // Echo out a sample image
    return '<img class="website__heading__favicon" src="' . $src . '">';
}

function festiveGreeting($now) {
    $date = date('nd', $now);
    $greeting = "";
    switch ($date) {
        case 1231:
        case 101:
            $greeting = "🥂 Happy New Year 🥂";
            break;
        case 123:
            $greeting = "🇵🇳 Happy Bounty Day 🇵🇳";
            break;
        case 126:
            $greeting = "🇦🇺 Happy Australia Day 🇦🇺";
            break;
        case 206:
            $greeting = "🇳🇿 Happy Waitangi Day 🇳🇿";
            break;
        case 214:
            $greeting = "❤️ Happy St Valentine’s Day ❤️";
            break;
        case 229:
            $greeting = "🗓️ Happy Leap Year 🗓️";
            break;
        case 301:
            $greeting = "🏴󠁧󠁢󠁷󠁬󠁳󠁿 Happy St David’s Day 🏴󠁧󠁢󠁷󠁬󠁳󠁿";
            break;
        case 308:
            $greeting = "♀️ Happy International Women’s Day ♀️";
            break;
        case 317:
            $greeting = "☘️ Happy St Patrick’s Day ☘️";
            break;
        case 422:
            $greeting = "🌍 Happy Earth Day 🌏";
            break;
        case 423:
            $greeting = "🏴󠁧󠁢󠁥󠁮󠁧󠁿 Happy St George’s Day 🏴󠁧󠁢󠁥󠁮󠁧󠁿";
            break;
        case 425:
            $greeting = "🇬🇸 Happy Liberation Day 🇬🇸";
            break;
        case 509:
            $greeting = "🇬🇬 Happy Liberation Day 🇯🇪";
            break;
        case date('nd', strtotime('third monday of may')):
            $greeting = "🇰🇾 Happy Discovery Day 🇰🇾";
            if ($date == 521) $greeting .= " <br />🇸🇭 Happy St Helena’s Day 🇸🇭"; //in case of clash
            break;
        case date('nd', strtotime('last monday of may')) - 3: //Friday before last Monday in May
            $greeting = "🇧🇲 Happy Bermuda Day 🇧🇲";
            if ($date == 521) $greeting .= " <br />🇸🇭 Happy St Helena’s Day 🇸🇭"; //in case of clash
            break;
        case 521:
            $greeting = "🇸🇭 Happy St Helena’s Day 🇸🇭";
            break;
        case 530:
            $greeting = "🇦🇮 Happy Anguilla Day 🇦🇮";
            break;
        case 614:
            $greeting = "🇫🇰 Happy Liberation Day 🇫🇰";
            break;
        case 701:
            $greeting = "🇨🇦 Happy Canada Day 🇨🇦";
            break;
        case 705:
            $greeting = "🇮🇲 Happy Tynwald Day 🇮🇲";
            break;
        case 711:
            $greeting = "⚖️ Happy International Criminal Justice Day ⚖️";
            break;
        case 910:
            $greeting = "🇬🇮 Happy Gibraltar National Day 🇬🇮";
            break;
        case 1021:
            $greeting = "🇻🇬 Happy St Ursula’s Day 🇻🇬";
            break;
        case 1031:
            $greeting = "🎃 Happy Halloween 🎃";
            break;
        case 1105:
            $greeting = "🔥 Happy Bonfire Night 🔥";
            break;
        case 1130:
            $greeting = "🏴󠁧󠁢󠁳󠁣󠁴󠁿 Happy St Andrew’s Day 🏴󠁧󠁢󠁳󠁣󠁴󠁿";
            break;
        case 1224:
        case 1225:
            $greeting = "🎄 Happy Christmas 🎄";
            break;
        case 1226:
            $greeting = "📦 Happy Boxing Day 📦";
            break;
    }
    if ($greeting !="") {
        $greeting = '
    <div class="govuk-grid-column-two-thirds govuk-!-margin-top-6">
        <div class="govuk-notification-banner govuk-notification-banner--success"
            aria-labelledby="govuk-notification-banner-title"
            data-module="govuk-notification-banner">
            <div class="govuk-notification-banner__header">
                <h2 class="govuk-notification-banner__title" id="govuk-notification-banner-title">
                    Greetings
                </h2>
            </div>
            <div class="govuk-notification-banner__content">
                <h3 class="govuk-notification-banner__heading">
                    '.$greeting.'
                </h3>
            </div>
        </div>
    </div>
        ';
    }
    return $greeting;
}

function getShortName($url,$root_url) {
    /**
     * This function returns the short name by extracting it from the url
     * Inputs:
     *  $url = the url of the page which short name is needed
     *  $this_url = the url of the root
     */
    // This function returns the short name by extracting it from the url

    if ($url == $root_url) return;
    $short_name = end(explode("/",$url));
    return $short_name;
}
