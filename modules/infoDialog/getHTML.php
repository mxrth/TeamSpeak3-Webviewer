<?php

error_reporting(E_ERROR);
session_name('ms_ts3Viewer');
session_start();

define("s_root", $_SESSION['s_root']);
define("s_http", $_SESSION['s_http']);

require_once(s_root . 'utils.func.php');
require_once(s_root . 'TSQuery.class.php');

define('msBASEDIR', dirname($_SERVER['PHP_SELF']));
$config_name = isset($_GET['config']) ? $_GET['config'] : '';
str_replace('/', '', $config_name);
str_replace('.', '', $config_name);
$paths[] = s_root . "config/" . $config_name . ".xml";
$paths[] = s_root . 'config/' . $config_name . ".conf";
$paths[] = s_root . 'config/config.xml';
$paths[] = s_root . 'config/config.conf';
$paths[] = s_root . 'viewer.conf';

foreach ($paths as $path)
{
    if (file_exists($path))
    {
        if (substr($path, -4) == ".xml")
        {
            $viewer_conf = parseConfigFile($path, true);
        }
        else
        {
            $viewer_conf = parseConfigFile($path, false);
        }
        break;
    }
}

// Sets the language new to be sure that the language is right
if (isset($_SESSION['language']) && $_SESSION['language'] != "")
{
    $viewer_conf['language'] = $_SESSION['language'];
}

setL10n($viewer_conf['language'], "ms-tsv-infoDialog", s_root . "l10n");


foreach ($viewer_conf as $key => $value)
{
    if (preg_match("/^cachetime_/", $key))
    {
        $temp = explode('_', $key, 2);
        $temp[1] = str_replace('_', ' -', $temp[1]);
        $config['cachetime'][$temp[1]] = $value;
    }
    if (preg_match("/^servergrp_images_/", $key))
    {
        $temp = explode('_', $key);
        $temp = array_pop($temp);
        $config['servergrp_images'][$temp] = $value;
    }
    if (preg_match("/^channelgrp_images_/", $key))
    {
        $temp = explode('_', $key);
        $temp = array_pop($temp);
        $config['channelgrp_images'][$temp] = $value;
    }
}
if ($viewer_conf['use_serverimages'] == true)
        $viewer_conf['serverimages'] = s_http . "get_server_icon.php?config=" . $_GET['config'] . "&id=";
else $viewer_conf['serverimages'] = s_http . "images/" . $viewer_conf['imagepack'] . "/";
$viewer_conf['client_name'] = "Maxesstuff TS3 Webviewer";

$config = parseConfigFile(s_root . 'modules/infoDialog/infoDialog.xml', true);

$info = $_SESSION['infoDialog']['info'];


if ($_GET['type'] == 'client' && $_GET['title'] == "true")
{
    $matches = Array();
    preg_match("/^.*?([0-9]*)$/", $_GET['id'], $matches);
    $user = getUserByID($info['clientlist'], $matches[1]);
    echo escape_name($user['client_nickname']);
    die();
}

if ($_GET['type'] == 'client')
{
    $config['show_html_for_client'] = explode(",",
            $config['show_html_for_client']);

    $out = '
    <style type="text/css">
        .infodialog
        {
            font-size: small; !important
        }
        
        .label
        {
            font-weight: bold;
        }
    </style>
    <table style="margin:0" width="100%" height="100%" class="infodialog">';

    $matches = Array();
    preg_match("/^.*?([0-9]*)$/", $_GET['id'], $matches);
    $user = getUserByID($info['clientlist'], $matches[1]);
    if ($user == NULL) die();
    $query = new TSQuery($viewer_conf['host'], $viewer_conf['queryport']);
    $query->set_caching((int) $viewer_conf['enable_caching'],
            (int) $viewer_conf['standard_cachetime'],
            (int) $viewer_conf['cachetime']);
    if ($viewer_conf['login_needed'])
    {
        ts3_check($query->login($viewer_conf['username'],
                        $viewer_conf['password']), 'login');
    }

    $query->use_by_port($viewer_conf['vserverport']);
    $query->send_cmd("clientupdate client_nickname=" . $query->ts3query_escape($viewer_conf['client_name']));
    $clientinfo = $query->clientinfo($user['clid']);
    foreach ($config['show_html_for_client'] as $to_show)
    {
        $to_show = trim($to_show);
        switch ($to_show)
        {
            case 'nickname':
                $out .= '<tr>';
                $out .= '<td class="label">' . _('Nickname') . ':</td>';
                $out .= '<td>' . escape_name($user['client_nickname']) . '</td></tr>';
                break;
            case 'country':
                $out .= '<tr>';
                $out .= '<td class="label">' . _('Country') . ":</td>";
                $out .= '<td><span style="margin-right:10px;">' . getCountryIcon($clientinfo['return']['client_country']) . '</span>' . twolettertocountry($clientinfo['return']['client_country']) . '</td></tr>';
                $out .= '</tr>';
                break;
            case 'version':
                $out .= '<tr>';
                $out .= '<td class="label">' . _('Version') . ':</td>';
                $out .= '<td>' . $user['client_version'] . '</td></tr>';
                break;
            case 'servergroup':
                $out .= '<tr>';
                $out .= '<td class="label">' . _('Servergroup(s)') . ':</td>';
                $out .= '<td>';

                foreach (get_servergroup_images($user, $info['servergroups'],
                        $viewer_conf['use_serverimages'],
                        $viewer_conf['servergrp_images']) as $image)
                {
                    if ($image == 0)
                    {
                        $out .= '<img src="' . s_http . 'images/serverimages/na.png" alt="" style="margin-right:2px;"/>';
                    }
                    else
                    {
                        $out .= '<img src="' . $viewer_conf['serverimages'] . $image . '" alt="" style="margin-right:2px;" />';
                    }
                }
                $out .= '</td></tr>';
                break;
            case 'channelgroup':
                $out .= '<tr>';
                $out .= '<td class="label">' . _('Channelgroup(s)') . ':</td>';
                $out .= '<td>';
                $channelgroup_icon = get_channelgroup_image($user,
                        $info['channelgroups'],
                        $viewer_conf['use_serverimages'],
                        $viewer_conf['channelgrp_images']);

                if ($channelgroup_icon == 0)
                {
                    $out .= '<img src="' . s_http . 'images/serverimages/na.png" alt="" style="margin-right:2px;" />';
                }
                else
                {
                    $out .= '<img  src="' . $viewer_conf['serverimages'] . $channelgroup_icon . '" alt="" style="margin-right:2px;"/>';
                }
                $out .= '</td></tr>';
                break;
            case 'connections':
                $out .= '<tr>';
                $out .= '<td class="label">' . _('Connections') . ':</td>';
                $out .= '<td>' . $clientinfo['return']['client_totalconnections'];
                $out .= '</td></tr>';
                break;
            case 'description':
                if (!empty($clientinfo['return']['client_description']))
                {
                    $out .= '<tr>';
                    $out .= '<td class="label">' . _('Description') . ':</td>';
                    $out .= '<td>' . escape_name($clientinfo['return']['client_description']);
                    $out .= '</td></tr>';
                }
                break;
        }
    }
    $query->quit();

    $out.= '</table>';
    echo $out;
}

// Returns the path of the countryicons
function getCountryIcon($country)
{
    $path = s_http . "modules/infoDialog/flags/";
    $country = strtolower($country);

    return '<img src="' . $path . $country . '.png" alt="" />';
}

// Returns the countryname of a two-letter countrycode
function twolettertocountry($code)
{
    $countries = array(
        'AF' => 'Afghanistan',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua And Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia And Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'BN' => 'Brunei',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Columbia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => 'Cote D\'Ivorie (Ivory Coast)',
        'HR' => 'Croatia (Hrvatska)',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'CD' => 'Democratic Republic Of Congo (Zaire)',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'TP' => 'East Timor',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands (Malvinas)',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'FX' => 'France, Metropolitan',
        'GF' => 'French Guinea',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard And McDonald Islands',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macau',
        'MK' => 'Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar (Burma)',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'AN' => 'Netherlands Antilles',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'KP' => 'North Korea',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'SH' => 'Saint Helena',
        'KN' => 'Saint Kitts And Nevis',
        'LC' => 'Saint Lucia',
        'PM' => 'Saint Pierre And Miquelon',
        'VC' => 'Saint Vincent And The Grenadines',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome And Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovak Republic',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia And South Sandwich Islands',
        'KR' => 'South Korea',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard And Jan Mayen',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syria',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad And Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks And Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'UK' => 'United Kingdom',
        'US' => 'United States',
        'UM' => 'United States Minor Outlying Islands',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican City (Holy See)',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'VG' => 'Virgin Islands (British)',
        'VI' => 'Virgin Islands (US)',
        'WF' => 'Wallis And Futuna Islands',
        'EH' => 'Western Sahara',
        'WS' => 'Western Samoa',
        'YE' => 'Yemen',
        'YU' => 'Yugoslavia',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe'
    );

    foreach ($countries as $key => $value)
    {
        if ($key == strtoupper($code)) return $value;
    }
    return _('none');
}

?>
