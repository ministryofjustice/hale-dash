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
    $fP = fSockOpen($host, $port, $errno, $errstr, $timeout);
    if (!$fP) {
        return '<span class="website__up-down"><strong class="govuk-tag govuk-tag--grey">Private</strong></span>';
    }
    return '<span class="website__up-down"><strong class="govuk-tag">Public</strong></span>';
}

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

function fav_icon($src, $google = false) {

    if ($google) {
        // From https://stackoverflow.com/questions/5701593/how-to-get-a-websites-favicon-with-php
        // Read image path, convert to base64 encoding
        $imageData = base64_encode(file_get_contents($src));

        if (empty($imageData)) {
            return
            '<svg class="website__favicon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
                <style>
                </style>
                <path class="a" fill="#0b0c0c" fill-rule="evenodd" d="m19.94,10.78c-.36-.88.06-1.89.94-2.26.87-.35,1.89.07,2.25.95.36.88-.06,1.89-.93,2.24-.89.36-1.89-.06-2.26-.93Zm-4.55,3.97c-.89.36-1.31,1.38-.94,2.26.36.88,1.37,1.3,2.26.93.87-.35,1.29-1.37.93-2.24-.36-.88-1.37-1.31-2.25-.95Zm11.44-.28c.87-.35,1.29-1.37.93-2.24-.36-.88-1.38-1.31-2.25-.95-.89.36-1.31,1.38-.94,2.26.36.88,1.37,1.3,2.26.93Zm.82,3.64c.36.88,1.37,1.3,2.26.93.88-.35,1.29-1.37.93-2.24-.36-.88-1.38-1.31-2.25-.95-.89.36-1.31,1.38-.94,2.26Zm-17.78-6.39c.89.36,1.89-.06,2.26-.93s-.06-1.89-.94-2.26c-.87-.35-1.89.07-2.25.95-.36.88.06,1.89.93,2.24Zm-4.63,2.75c.89.36,1.89-.06,2.26-.93.36-.88-.06-1.89-.94-2.26-.87-.35-1.89.07-2.25.95-.36.88.06,1.89.93,2.24Zm-3.08,4.57c.89.36,1.89-.06,2.26-.93s-.06-1.89-.94-2.26c-.87-.35-1.89.07-2.25.95-.36.88.06,1.89.93,2.24ZM15.12,6.37c.07.08.14.16.22.22l-1.25,3.77v.02c-.06.19-.09.39-.09.6,0,1.02.76,1.87,1.74,2.01h.05c.08,0,.17.02.25.02s.17,0,.25-.02h.05c.98-.15,1.74-.99,1.74-2.01,0-.21-.03-.41-.09-.6v-.02l-1.25-3.77c.08-.07.16-.14.22-.22s2.17,1.14,2.17,1.14v-3.21l-2.17.69c-.06-.08-.13-.16-.21-.22s.88-2.76.88-2.76h-3.14l.88,2.75c-.07.07-.15.14-.21.22s-2.17-.68-2.17-.68v3.21l2.17-1.15h-.02Zm15.85,15.41c-1.24,1.69-2.45,4.07-3.43,8.22-3.48-.49-7.38-.77-11.51-.77s-8.03.28-11.5.77c-.99-4.15-2.19-6.53-3.43-8.22l2.33-.83c-.23,1.19-.27,1.74,0,2.5.38-.37.75-1.07,1.04-2.13l1.13,3.77c-.69-.48-1.22-.78-1.83-.79,1.07,2.32,2.43,2.92,3.29,2.75,1.06-.2,1.56-1.15,1.39-1.95-.24-1.14-1.42-1.44-1.96-.11-1.05-2.14.73-2.8,1.87-2.17-1.75-1.75-1.95-3.3-.54-5.19,1.97,1.51,2,3.01,1.11,5.11,1.15-1.32,2.95-.61,2.3,1.52-.83-1.29-1.93-.48-1.75.75.15,1.06,1.55,1.92,3.3,1.77,2.51-.22,2.66-1.96,2.73-3.39-.62-.11-1.73.46-2.68,1.79l-.35-3.99c1.03,1.07,1.97,1.28,3.01,1.31-.35-1.08-1.94-2.85-1.94-2.85h4.99s-1.59,1.77-1.94,2.85c1.04-.04,1.98-.23,3.01-1.31l-.35,3.99c-.94-1.33-2.05-1.9-2.68-1.79.07,1.44.21,3.16,2.73,3.39,1.75.15,3.15-.71,3.3-1.77.18-1.22-.92-2.03-1.75-.75-.64-2.13,1.15-2.84,2.3-1.52-.89-2.1-.87-3.59,1.11-5.11,1.42,1.89,1.21,3.43-.54,5.19,1.14-.63,2.92.04,1.87,2.17-.55-1.33-1.72-1.03-1.96.11-.17.8.33,1.75,1.39,1.95.87.16,2.22-.44,3.29-2.75-.61.02-1.14.32-1.83.79l1.13-3.77c.29,1.05.65,1.75,1.04,2.13.26-.77.22-1.32,0-2.5l2.33.83h0Z"/>
            </svg>';
        };

        // Format the image SRC:  data:{mime};base64,{data};
        $src = 'data: '.mime_content_type($src).';base64,'.$imageData;
    }

    return '<img class="website__favicon" src="' . $src . '" alt=""/>';
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

function getBrithday() {
    if (in_array(get_current_user_id(),[2,26,34,49]) && date("nd",time()) == 1011) {
        $greeting = "Happy Birthday Marc";
    return '
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
                <div class="birthday-logo" style="background-image:url(https://hale.docker/wp-content/themes/hale-dash/assets/images/marc.png);"></div>
                    <h3 class="govuk-notification-banner__heading" style="display:inline-block;">
                        '.$greeting.'
                    </h3>
                    <div class="birthday-logo" style="background-image:url(https://hale.docker/wp-content/themes/hale-dash/assets/images/marc.png);"></div>
                </div>
            </div>
        </div>
    ';
    }
}
