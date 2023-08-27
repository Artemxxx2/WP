<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;

class CountriesHelper
{
    public function help(Application $application, $code = null)
    {
        $ret = [
            'AF' => __('Afghanistan', 'directories'),
            'AX' => __('Aland Islands', 'directories'),
            'AL' => __('Albania', 'directories'),
            'DZ' => __('Algeria', 'directories'),
            'AS' => __('American Samoa', 'directories'),
            'AD' => __('Andorra', 'directories'),
            'AO' => __('Angola', 'directories'),
            'AI' => __('Anguilla', 'directories'),
            'AQ' => __('Antarctica', 'directories'),
            'AG' => __('Antigua And Barbuda', 'directories'),
            'AR' => __('Argentina', 'directories'),
            'AM' => __('Armenia', 'directories'),
            'AW' => __('Aruba', 'directories'),
            'AU' => __('Australia', 'directories'),
            'AT' => __('Austria', 'directories'),
            'AZ' => __('Azerbaijan', 'directories'),
            'BS' => __('Bahamas', 'directories'),
            'BH' => __('Bahrain', 'directories'),
            'BD' => __('Bangladesh', 'directories'),
            'BB' => __('Barbados', 'directories'),
            'BY' => __('Belarus', 'directories'),
            'BE' => __('Belgium', 'directories'),
            'BZ' => __('Belize', 'directories'),
            'BJ' => __('Benin', 'directories'),
            'BM' => __('Bermuda', 'directories'),
            'BT' => __('Bhutan', 'directories'),
            'BO' => __('Bolivia', 'directories'),
            'BA' => __('Bosnia And Herzegovina', 'directories'),
            'BW' => __('Botswana', 'directories'),
            'BV' => __('Bouvet Island', 'directories'),
            'BR' => __('Brazil', 'directories'),
            'IO' => __('British Indian Ocean Territory', 'directories'),
            'BN' => __('Brunei Darussalam', 'directories'),
            'BG' => __('Bulgaria', 'directories'),
            'BF' => __('Burkina Faso', 'directories'),
            'BI' => __('Burundi', 'directories'),
            'KH' => __('Cambodia', 'directories'),
            'CM' => __('Cameroon', 'directories'),
            'CA' => __('Canada', 'directories'),
            'CV' => __('Cape Verde', 'directories'),
            'KY' => __('Cayman Islands', 'directories'),
            'CF' => __('Central African Republic', 'directories'),
            'TD' => __('Chad', 'directories'),
            'CL' => __('Chile', 'directories'),
            'CN' => __('China', 'directories'),
            'CX' => __('Christmas Island', 'directories'),
            'CC' => __('Cocos (Keeling) Islands', 'directories'),
            'CO' => __('Colombia', 'directories'),
            'KM' => __('Comoros', 'directories'),
            'CG' => __('Congo', 'directories'),
            'CD' => __('Congo, Democratic Republic', 'directories'),
            'CK' => __('Cook Islands', 'directories'),
            'CR' => __('Costa Rica', 'directories'),
            'CI' => __("Cote D'Ivoire", 'directories'),
            'HR' => __('Croatia', 'directories'),
            'CU' => __('Cuba', 'directories'),
            'CY' => __('Cyprus', 'directories'),
            'CZ' => __('Czech Republic', 'directories'),
            'DK' => __('Denmark', 'directories'),
            'DJ' => __('Djibouti', 'directories'),
            'DM' => __('Dominica', 'directories'),
            'DO' => __('Dominican Republic', 'directories'),
            'EC' => __('Ecuador', 'directories'),
            'EG' => __('Egypt', 'directories'),
            'SV' => __('El Salvador', 'directories'),
            'GQ' => __('Equatorial Guinea', 'directories'),
            'ER' => __('Eritrea', 'directories'),
            'EE' => __('Estonia', 'directories'),
            'ET' => __('Ethiopia', 'directories'),
            'FK' => __('Falkland Islands (Malvinas)', 'directories'),
            'FO' => __('Faroe Islands', 'directories'),
            'FJ' => __('Fiji', 'directories'),
            'FI' => __('Finland', 'directories'),
            'FR' => __('France', 'directories'),
            'GF' => __('French Guiana', 'directories'),
            'PF' => __('French Polynesia', 'directories'),
            'TF' => __('French Southern Territories', 'directories'),
            'GA' => __('Gabon', 'directories'),
            'GM' => __('Gambia', 'directories'),
            'GE' => __('Georgia', 'directories'),
            'DE' => __('Germany', 'directories'),
            'GH' => __('Ghana', 'directories'),
            'GI' => __('Gibraltar', 'directories'),
            'GR' => __('Greece', 'directories'),
            'GL' => __('Greenland', 'directories'),
            'GD' => __('Grenada', 'directories'),
            'GP' => __('Guadeloupe', 'directories'),
            'GU' => __('Guam', 'directories'),
            'GT' => __('Guatemala', 'directories'),
            'GG' => __('Guernsey', 'directories'),
            'GN' => __('Guinea', 'directories'),
            'GW' => __('Guinea-Bissau', 'directories'),
            'GY' => __('Guyana', 'directories'),
            'HT' => __('Haiti', 'directories'),
            'HM' => __('Heard Island & Mcdonald Islands', 'directories'),
            'VA' => __('Holy See (Vatican City State)', 'directories'),
            'HN' => __('Honduras', 'directories'),
            'HK' => __('Hong Kong', 'directories'),
            'HU' => __('Hungary', 'directories'),
            'IS' => __('Iceland', 'directories'),
            'IN' => __('India', 'directories'),
            'ID' => __('Indonesia', 'directories'),
            'IR' => __('Iran', 'directories'),
            'IQ' => __('Iraq', 'directories'),
            'IE' => __('Ireland', 'directories'),
            'IM' => __('Isle Of Man', 'directories'),
            'IL' => __('Israel', 'directories'),
            'IT' => __('Italy', 'directories'),
            'JM' => __('Jamaica', 'directories'),
            'JP' => __('Japan', 'directories'),
            'JE' => __('Jersey', 'directories'),
            'JO' => __('Jordan', 'directories'),
            'KZ' => __('Kazakhstan', 'directories'),
            'KE' => __('Kenya', 'directories'),
            'KI' => __('Kiribati', 'directories'),
            'KR' => __('Korea', 'directories'),
            'KW' => __('Kuwait', 'directories'),
            'KG' => __('Kyrgyzstan', 'directories'),
            'LA' => __("Lao People's Democratic Republic", 'directories'),
            'LV' => __('Latvia', 'directories'),
            'LB' => __('Lebanon', 'directories'),
            'LS' => __('Lesotho', 'directories'),
            'LR' => __('Liberia', 'directories'),
            'LY' => __('Libyan Arab Jamahiriya', 'directories'),
            'LI' => __('Liechtenstein', 'directories'),
            'LT' => __('Lithuania', 'directories'),
            'LU' => __('Luxembourg', 'directories'),
            'MO' => __('Macao', 'directories'),
            'MK' => __('Macedonia', 'directories'),
            'MG' => __('Madagascar', 'directories'),
            'MW' => __('Malawi', 'directories'),
            'MY' => __('Malaysia', 'directories'),
            'MV' => __('Maldives', 'directories'),
            'ML' => __('Mali', 'directories'),
            'MT' => __('Malta', 'directories'),
            'MH' => __('Marshall Islands', 'directories'),
            'MQ' => __('Martinique', 'directories'),
            'MR' => __('Mauritania', 'directories'),
            'MU' => __('Mauritius', 'directories'),
            'YT' => __('Mayotte', 'directories'),
            'MX' => __('Mexico', 'directories'),
            'FM' => __('Federated States of Micronesia', 'directories'),
            'MD' => __('Moldova', 'directories'),
            'MC' => __('Monaco', 'directories'),
            'MN' => __('Mongolia', 'directories'),
            'ME' => __('Montenegro', 'directories'),
            'MS' => __('Montserrat', 'directories'),
            'MA' => __('Morocco', 'directories'),
            'MZ' => __('Mozambique', 'directories'),
            'MM' => __('Myanmar', 'directories'),
            'NA' => __('Namibia', 'directories'),
            'NR' => __('Nauru', 'directories'),
            'NP' => __('Nepal', 'directories'),
            'NL' => __('Netherlands', 'directories'),
            'AN' => __('Netherlands Antilles', 'directories'),
            'NC' => __('New Caledonia', 'directories'),
            'NZ' => __('New Zealand', 'directories'),
            'NI' => __('Nicaragua', 'directories'),
            'NE' => __('Niger', 'directories'),
            'NG' => __('Nigeria', 'directories'),
            'NU' => __('Niue', 'directories'),
            'NF' => __('Norfolk Island', 'directories'),
            'MP' => __('Northern Mariana Islands', 'directories'),
            'NO' => __('Norway', 'directories'),
            'OM' => __('Oman', 'directories'),
            'PK' => __('Pakistan', 'directories'),
            'PW' => __('Palau', 'directories'),
            'PS' => __('Palestinian Territories', 'directories'),
            'PA' => __('Panama', 'directories'),
            'PG' => __('Papua New Guinea', 'directories'),
            'PY' => __('Paraguay', 'directories'),
            'PE' => __('Peru', 'directories'),
            'PH' => __('Philippines', 'directories'),
            'PN' => __('Pitcairn', 'directories'),
            'PL' => __('Poland', 'directories'),
            'PT' => __('Portugal', 'directories'),
            'PR' => __('Puerto Rico', 'directories'),
            'QA' => __('Qatar', 'directories'),
            'RE' => __('Reunion', 'directories'),
            'RO' => __('Romania', 'directories'),
            'RU' => __('Russian Federation', 'directories'),
            'RW' => __('Rwanda', 'directories'),
            'BL' => __('Saint Barthelemy', 'directories'),
            'SH' => __('Saint Helena', 'directories'),
            'KN' => __('Saint Kitts And Nevis', 'directories'),
            'LC' => __('Saint Lucia', 'directories'),
            'MF' => __('Saint Martin', 'directories'),
            'PM' => __('Saint Pierre And Miquelon', 'directories'),
            'VC' => __('Saint Vincent And Grenadines', 'directories'),
            'WS' => __('Samoa', 'directories'),
            'SM' => __('San Marino', 'directories'),
            'ST' => __('Sao Tome And Principe', 'directories'),
            'SA' => __('Saudi Arabia', 'directories'),
            'SN' => __('Senegal', 'directories'),
            'RS' => __('Serbia', 'directories'),
            'SC' => __('Seychelles', 'directories'),
            'SL' => __('Sierra Leone', 'directories'),
            'SG' => __('Singapore', 'directories'),
            'SK' => __('Slovakia', 'directories'),
            'SI' => __('Slovenia', 'directories'),
            'SB' => __('Solomon Islands', 'directories'),
            'SO' => __('Somalia', 'directories'),
            'ZA' => __('South Africa', 'directories'),
            'GS' => __('South Georgia And Sandwich Isl.', 'directories'),
            'ES' => __('Spain', 'directories'),
            'LK' => __('Sri Lanka', 'directories'),
            'SD' => __('Sudan', 'directories'),
            'SR' => __('Suriname', 'directories'),
            'SJ' => __('Svalbard And Jan Mayen', 'directories'),
            'SZ' => __('Swaziland', 'directories'),
            'SE' => __('Sweden', 'directories'),
            'CH' => __('Switzerland', 'directories'),
            'SY' => __('Syrian Arab Republic', 'directories'),
            'TW' => __('Taiwan', 'directories'),
            'TJ' => __('Tajikistan', 'directories'),
            'TZ' => __('Tanzania', 'directories'),
            'TH' => __('Thailand', 'directories'),
            'TL' => __('Timor-Leste', 'directories'),
            'TG' => __('Togo', 'directories'),
            'TK' => __('Tokelau', 'directories'),
            'TO' => __('Tonga', 'directories'),
            'TT' => __('Trinidad And Tobago', 'directories'),
            'TN' => __('Tunisia', 'directories'),
            'TR' => __('Turkey', 'directories'),
            'TM' => __('Turkmenistan', 'directories'),
            'TC' => __('Turks And Caicos Islands', 'directories'),
            'TV' => __('Tuvalu', 'directories'),
            'UG' => __('Uganda', 'directories'),
            'UA' => __('Ukraine', 'directories'),
            'AE' => __('United Arab Emirates', 'directories'),
            'GB' => __('United Kingdom', 'directories'),
            'US' => __('United States', 'directories'),
            'UM' => __('United States Outlying Islands', 'directories'),
            'UY' => __('Uruguay', 'directories'),
            'UZ' => __('Uzbekistan', 'directories'),
            'VU' => __('Vanuatu', 'directories'),
            'VE' => __('Venezuela', 'directories'),
            'VN' => __('Viet Nam', 'directories'),
            'VG' => __('British Virgin Islands', 'directories'),
            'VI' => __('U.S. Virgin Islands', 'directories'),
            'WF' => __('Wallis And Futuna', 'directories'),
            'EH' => __('Western Sahara', 'directories'),
            'YE' => __('Yemen', 'directories'),
            'ZM' => __('Zambia', 'directories'),
            'ZW' => __('Zimbabwe', 'directories'),
        ];
        if (isset($code)) {
            return isset($ret[$code]) ? $ret[$code] : null;
        }

        asort($ret, SORT_LOCALE_STRING);

        return $ret;
    }

    public function phone()
    {
        return [
            'AF' => '+93',
            'AL' => '+355',
            'DZ' => '+213',
            'AS' => '+1',
            'AD' => '+376',
            'AO' => '+244',
            'AI' => '+1',
            'AG' => '+1',
            'AR' => '+54',
            'AM' => '+374',
            'AW' => '+297',
            'AU' => '+61',
            'AT' => '+43',
            'AZ' => '+994',
            'BH' => '+973',
            'BD' => '+880',
            'BB' => '+1',
            'BY' => '+375',
            'BE' => '+32',
            'BZ' => '+501',
            'BJ' => '+229',
            'BM' => '+1',
            'BT' => '+975',
            'BO' => '+591',
            'BA' => '+387',
            'BW' => '+267',
            'BR' => '+55',
            'IO' => '+246',
            'VG' => '+1',
            'BN' => '+673',
            'BG' => '+359',
            'BF' => '+226',
            'MM' => '+95',
            'BI' => '+257',
            'KH' => '+855',
            'CM' => '+237',
            'CA' => '+1',
            'CV' => '+238',
            'KY' => '+1',
            'CF' => '+236',
            'TD' => '+235',
            'CL' => '+56',
            'CN' => '+86',
            'CO' => '+57',
            'KM' => '+269',
            'CK' => '+682',
            'CR' => '+506',
            'CI' => '+225',
            'HR' => '+385',
            'CU' => '+53',
            'CY' => '+357',
            'CZ' => '+420',
            'CD' => '+243',
            'DK' => '+45',
            'DJ' => '+253',
            'DM' => '+1',
            'DO' => '+1',
            'EC' => '+593',
            'EG' => '+20',
            'SV' => '+503',
            'GQ' => '+240',
            'ER' => '+291',
            'EE' => '+372',
            'ET' => '+251',
            'FK' => '+500',
            'FO' => '+298',
            'FM' => '+691',
            'FJ' => '+679',
            'FI' => '+358',
            'FR' => '+33',
            'GF' => '+594',
            'PF' => '+689',
            'GA' => '+241',
            'GE' => '+995',
            'DE' => '+49',
            'GH' => '+233',
            'GI' => '+350',
            'GR' => '+30',
            'GL' => '+299',
            'GD' => '+1',
            'GP' => '+590',
            'GU' => '+1',
            'GT' => '+502',
            'GN' => '+224',
            'GW' => '+245',
            'GY' => '+592',
            'HT' => '+509',
            'HN' => '+504',
            'HK' => '+852',
            'HU' => '+36',
            'IS' => '+354',
            'IN' => '+91',
            'ID' => '+62',
            'IR' => '+98',
            'IQ' => '+964',
            'IE' => '+353',
            'IL' => '+972',
            'IT' => '+39',
            'JM' => '+1',
            'JP' => '+81',
            'JO' => '+962',
            'KZ' => '+7',
            'KE' => '+254',
            'KI' => '+686',
            'XK' => '+381',
            'KW' => '+965',
            'KG' => '+996',
            'LA' => '+856',
            'LV' => '+371',
            'LB' => '+961',
            'LS' => '+266',
            'LR' => '+231',
            'LY' => '+218',
            'LI' => '+423',
            'LT' => '+370',
            'LU' => '+352',
            'MO' => '+853',
            'MK' => '+389',
            'MG' => '+261',
            'MW' => '+265',
            'MY' => '+60',
            'MV' => '+960',
            'ML' => '+223',
            'MT' => '+356',
            'MH' => '+692',
            'MQ' => '+596',
            'MR' => '+222',
            'MU' => '+230',
            'YT' => '+262',
            'MX' => '+52',
            'MD' => '+373',
            'MC' => '+377',
            'MN' => '+976',
            'ME' => '+382',
            'MS' => '+1',
            'MA' => '+212',
            'MZ' => '+258',
            'NA' => '+264',
            'NR' => '+674',
            'NP' => '+977',
            'NL' => '+31',
            'AN' => '+599',
            'NC' => '+687',
            'NZ' => '+64',
            'NI' => '+505',
            'NE' => '+227',
            'NG' => '+234',
            'NU' => '+683',
            'NF' => '+672',
            'KP' => '+850',
            'MP' => '+1',
            'NO' => '+47',
            'OM' => '+968',
            'PK' => '+92',
            'PW' => '+680',
            'PS' => '+970',
            'PA' => '+507',
            'PG' => '+675',
            'PY' => '+595',
            'PE' => '+51',
            'PH' => '+63',
            'PL' => '+48',
            'PT' => '+351',
            'PR' => '+1',
            'QA' => '+974',
            'CG' => '+242',
            'RE' => '+262',
            'RO' => '+40',
            'RU' => '+7',
            'RW' => '+250',
            'BL' => '+590',
            'SH' => '+290',
            'KN' => '+1',
            'MF' => '+590',
            'PM' => '+508',
            'VC' => '+1',
            'WS' => '+685',
            'SM' => '+378',
            'ST' => '+239',
            'SA' => '+966',
            'SN' => '+221',
            'RS' => '+381',
            'SC' => '+248',
            'SL' => '+232',
            'SG' => '+65',
            'SK' => '+421',
            'SI' => '+386',
            'SB' => '+677',
            'SO' => '+252',
            'ZA' => '+27',
            'KR' => '+82',
            'ES' => '+34',
            'LK' => '+94',
            'LC' => '+1',
            'SD' => '+249',
            'SR' => '+597',
            'SZ' => '+268',
            'SE' => '+46',
            'CH' => '+41',
            'SY' => '+963',
            'TW' => '+886',
            'TJ' => '+992',
            'TZ' => '+255',
            'TH' => '+66',
            'BS' => '+1',
            'GM' => '+220',
            'TL' => '+670',
            'TG' => '+228',
            'TK' => '+690',
            'TO' => '+676',
            'TT' => '+1',
            'TN' => '+216',
            'TR' => '+90',
            'TM' => '+993',
            'TC' => '+1',
            'TV' => '+688',
            'UG' => '+256',
            'UA' => '+380',
            'AE' => '+971',
            'GB' => '+44',
            'US' => '+1',
            'UY' => '+598',
            'VI' => '+1',
            'UZ' => '+998',
            'VU' => '+678',
            'VA' => '+39',
            'VE' => '+58',
            'VN' => '+84',
            'WF' => '+681',
            'YE' => '+967',
            'ZM' => '+260',
            'ZW' => '+263',
        ];
    }
}