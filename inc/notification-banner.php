<?php

function festiveGreeting($now) {
    if (date('D d', $now) == "Fri 13")
        $friday_13th = "🙂 Happy Friday 13<sup>th</sup> 🙂";
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
	case 1110;
	    $greeting = "🚢 Edmund Fitzgerald day 🚢";
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
    if (isset($friday_13th)) {
        if (!empty($greeting)) {
            $greeting.="<br />".$friday_13th;
        } else {
            $greeting=$friday_13th;
        }
    }
    if ($greeting !="") {
        $greeting = '
    <div class="govuk-grid-column-two-thirds">
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
        <div class="govuk-grid-column-two-thirds">
            <div class="govuk-notification-banner govuk-notification-banner--success"
                aria-labelledby="govuk-notification-banner-title"
                data-module="govuk-notification-banner">
                <div class="govuk-notification-banner__header">
                    <h2 class="govuk-notification-banner__title" id="govuk-notification-banner-title">
                        Greetings
                    </h2>
                </div>
                <div class="govuk-notification-banner__content">
                <div class="birthday-logo" style="background-image:url(' . esc_url(get_theme_file_uri('/assets/images/marc.png')) . ');"></div>
                    <h3 class="govuk-notification-banner__heading" style="display:inline-block;">
                        '.$greeting.'
                    </h3>
                    <div class="birthday-logo" style="background-image:url(' . esc_url(get_theme_file_uri('/assets/images/marc.png')) . ');"></div>
                </div>
            </div>
        </div>
    ';
    }
}
