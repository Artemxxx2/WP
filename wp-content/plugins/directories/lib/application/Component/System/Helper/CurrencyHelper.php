<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;

class CurrencyHelper
{
    public function options(Application $application)
    {
        return [
            'ALL' => 'Albania Lek',
            'AFN' => 'Afghanistan Afghani',
            'ARS' => 'Argentina Peso',
            'AWG' => 'Aruba Guilder',
            'AUD' => 'Australia Dollar',
            'AZN' => 'Azerbaijan New Manat',
            'BSD' => 'Bahamas Dollar',
            'BHD' => 'Bahraini Dinar',
            'BBD' => 'Barbados Dollar',
            'BDT' => 'Bangladeshi taka',
            'BYR' => 'Belarus Ruble',
            'BZD' => 'Belize Dollar',
            'BMD' => 'Bermuda Dollar',
            'BOB' => 'Bolivia Boliviano',
            'BAM' => 'Bosnia and Herzegovina Convertible Marka',
            'BWP' => 'Botswana Pula',
            'BGN' => 'Bulgaria Lev',
            'BRL' => 'Brazil Real',
            'BND' => 'Brunei Darussalam Dollar',
            'KHR' => 'Cambodia Riel',
            'CAD' => 'Canada Dollar',
            'KYD' => 'Cayman Islands Dollar',
            'CLP' => 'Chile Peso',
            'CNY' => 'China Yuan Renminbi',
            'COP' => 'Colombia Peso',
            'CRC' => 'Costa Rica Colon',
            'HRK' => 'Croatia Kuna',
            'CUP' => 'Cuba Peso',
            'CZK' => 'Czech Republic Koruna',
            'DKK' => 'Denmark Krone',
            'DOP' => 'Dominican Republic Peso',
            'XCD' => 'East Caribbean Dollar',
            'EGP' => 'Egypt Pound',
            'SVC' => 'El Salvador Colon',
            'EEK' => 'Estonia Kroon',
            'EUR' => 'Euro Member Countries',
            'FKP' => 'Falkland Islands (Malvinas) Pound',
            'FJD' => 'Fiji Dollar',
            'GHC' => 'Ghana Cedis',
            'GIP' => 'Gibraltar Pound',
            'GTQ' => 'Guatemala Quetzal',
            'GGP' => 'Guernsey Pound',
            'GYD' => 'Guyana Dollar',
            'HNL' => 'Honduras Lempira',
            'HKD' => 'Hong Kong Dollar',
            'HUF' => 'Hungary Forint',
            'ISK' => 'Iceland Krona',
            'INR' => 'India Rupee',
            'IDR' => 'Indonesia Rupiah',
            'IRR' => 'Iran Rial',
            'IMP' => 'Isle of Man Pound',
            'ILS' => 'Israel Shekel',
            'JMD' => 'Jamaica Dollar',
            'JPY' => 'Japan Yen',
            'JEP' => 'Jersey Pound',
            'JOD' => 'Jordanian Dinar',
            'KES' => 'Kenyan Shilling',
            'KZT' => 'Kazakhstan Tenge',
            'KPW' => 'Korea (North) Won',
            'KRW' => 'Korea (South) Won',
            'KWD' => 'Kuwaiti Dinar',
            'KGS' => 'Kyrgyzstan Som',
            'LAK' => 'Laos Kip',
            'LVL' => 'Latvia Lat',
            'LBP' => 'Lebanon Pound',
            'LRD' => 'Liberia Dollar',
            'LTL' => 'Lithuania Litas',
            'MKD' => 'Macedonia Denar',
            'MYR' => 'Malaysia Ringgit',
            'MUR' => 'Mauritius Rupee',
            'MXN' => 'Mexico Peso',
            'MNT' => 'Mongolia Tughrik',
            'MZN' => 'Mozambique Metical',
            'NAD' => 'Namibia Dollar',
            'NPR' => 'Nepal Rupee',
            'ANG' => 'Netherlands Antilles Guilder',
            'NZD' => 'New Zealand Dollar',
            'NIO' => 'Nicaragua Cordoba',
            'NGN' => 'Nigeria Naira',
            'NOK' => 'Norway Krone',
            'OMR' => 'Oman Rial',
            'PKR' => 'Pakistan Rupee',
            'PAB' => 'Panama Balboa',
            'PYG' => 'Paraguay Guarani',
            'PEN' => 'Peru Nuevo Sol',
            'PHP' => 'Philippines Peso',
            'PLN' => 'Poland Zloty',
            'QAR' => 'Qatar Riyal',
            'RON' => 'Romania New Leu',
            'RUB' => 'Russia Ruble',
            'SHP' => 'Saint Helena Pound',
            'SAR' => 'Saudi Arabia Riyal',
            'RSD' => 'Serbia Dinar',
            'SCR' => 'Seychelles Rupee',
            'SGD' => 'Singapore Dollar',
            'SBD' => 'Solomon Islands Dollar',
            'SOS' => 'Somalia Shilling',
            'ZAR' => 'South Africa Rand',
            'LKR' => 'Sri Lanka Rupee',
            'SEK' => 'Sweden Krona',
            'CHF' => 'Switzerland Franc',
            'SRD' => 'Suriname Dollar',
            'SYP' => 'Syria Pound',
            'TWD' => 'Taiwan New Dollar',
            'THB' => 'Thailand Baht',
            'TTD' => 'Trinidad and Tobago Dollar',
            'TRL' => 'Turkey Lira (TRL)',
            'TRY' => 'Turkey Lira',
            'TVD' => 'Tuvalu Dollar',
            'TZS' => 'Tanzanian Shilling',
            'UAH' => 'Ukraine Hryvna',
            'GBP' => 'United Kingdom Pound',
            'USD' => 'United States Dollar',
            'UYU' => 'Uruguay Peso',
            'UZS' => 'Uzbekistan Som',
            'VUV' => 'Vanuatu Vatu',
            'VEF' => 'Venezuela Bolivar',
            'VND' => 'Viet Nam Dong',
            'XAF' => 'CFA Franc',
            'YER' => 'Yemen Rial',
            'ZMK' => 'Zambian Kwacha',
            'ZWD' => 'Zimbabwe Dollar'
        ];
    }

    public function formats(Application $application, $currency = null)
    {
        // Currencies each with an array of decimals, prepend = 0 or append = 1 symbol, symbol.
        $formats = [
            'AUD' => [2, 0, 'AU$'], // Australian Dollar
            'BHD' => [3], // Bahraini Dinar
            'BRL' => [2, 0, 'R$'], // Brazilian Real
            'CAD' => [2, 0, 'CA$'], // Canadian Dollar
            'CLP' => [0], // Chilean Peso
            'CNY' => [2, 0, 'CN&yen;'], // China Yuan Renminbi
            'CZK' => [2, 1, 'Kc'], // Czech Koruna
            'XCD' => [2, 0, 'EC$'], // East Caribbean Dollar
            'EUR' => [2, 0, '&euro;'], // Euro
            'HKD' => [2, 0, 'HK$'], // Hong Kong Dollar
            'HUF' => [0], // Hungary Forint
            'ISK' => [0, 1, 'kr'], // Iceland Krona
            'INR' => [2, 0, '&#2352;'], // Indian Rupee
            'JPY' => [0, 0, '&yen;'], // Japan Yen
            'JOD' => [3], // Jordanian Dinar
            'KWD' => [3], // Kuwaiti Dinar
            'LBP' => [0], // Lebanese Pound
            'LTL' => [2, 1, 'Lt'], // Lithuanian Litas
            'MUR' => [0], // Mauritius Rupee
            'MXN' => [2, 0, 'MX$'], // Mexican Peso
            'ILS' => [2, 0, '&#8362;'], // New Israeli Shekel
            'NGN' => [0, 0, '&#8358;'],
            'NZD' => [2, 0, 'NZ$'], // New Zealand Dollar
            'NOK' => [2, 1, 'kr'], // Norwegian Krone
            'GBP' => [2, 0, '&pound;'], // Pound Sterling
            'OMR' => [3], // Rial Omani
            'ZAR' => [2, 0, 'R'], // South Africa Rand
            'KRW' => [0, 0, '&#8361;'], // Korea (South) Won
            'SEK' => [2, 1, 'kr'], // Swedish Krona
            'CHF' => [2, 0, 'SFr '], // Swiss Franc
            'TWD' => [2, 0, 'NT$'], // Taiwan Dollar
            'THB' => [2, 1, '&#3647;'], // Thailand Baht
            'TRL' => [2, 0, '&#8378;'], // Turkish Lira
            'TRY' => [2, 0, '&#8378;'], // Turkish Lira
            'USD' => [2, 0, '$'], // US Dollar
            'VUV' => [0], // Vanuatu Vatu
            'VND' => [0, 0, '&#x20ab;'], // Viet Nam Dong
            'XAF' => [0], // 'CFA Franc'
        ];
        if (isset($currency)) {
            return isset($formats[$currency = strtoupper($currency)]) ? $formats[$currency] : null;
        }
        return $formats;
    }

    public function format(Application $application, $value, $currency, $attachSymbol = true, array $format = null)
    {
        if (!isset($format)) {
            $currencies = $this->formats($application);
            $format = isset($currencies[$currency]) ? $currencies[$currency] : [2];
        }
        $value = $currency === 'INR' ?
            $this->numberFormatIndian($application, $value, $format[0]) :
            $application->getPlatform()->numberFormat($value, $format[0]);
        if ($attachSymbol !== false) {
            if (!isset($format[2])) {
                // No symbol
                $value .= ' ' . $currency;
            } else {
                // Has symbol
                if (is_numeric($attachSymbol)) {
                    $format[1] = $attachSymbol === 0 ? 0 : 1;
                }
                if (empty($format[1])) {
                    $value = $format[2] . $value; // prepend symbol
                } else {
                    $value .= $format[2]; // append symbol
                }
            }
        }

        return $value;
    }

    public function numberFormatIndian(Application $application, $value, $decLength = 2)
    {
        if (strpos($value, '.')) {
            list($value, $decimals) = explode('.', $value);
            if (strlen($decimals) > $decLength) {
                $decimals = substr($decimals, 0, $decLength);
            } else {
                while ($decLength - strlen($decimals)) {
                    $decimals .= '0';
                }
            }
        }
        if (strlen($value) > 5 ) {
            $last_three = substr($value, -3);
            $value = substr($value, 0, -3); // remove last three
            if (strlen($value) % 2 === 1) {
                $value = '0' . $value; // prepend with extra digit to be able to split by 2 digits
            }
            $formatted = '';
            foreach (str_split($value, 2) as $_value) {
                // Convert first 2 to int to remove prefixed 0 if any
                if ($formatted === '') {
                    $formatted = (int)$_value . ',';
                } else {
                    $formatted .= $_value . ',';
                }
            }
            $formatted .= $last_three;
        } else {
            $formatted = number_format($value, $decLength);
        }
        return isset($decimals) ? $formatted . '.' . $decimals : $formatted;
    }
}