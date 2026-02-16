<?php

/**
 * Currency Formatter Service
 *
 * Provides locale-aware currency formatting using Laravel's Number utility
 * and PHP's NumberFormatter. Serves as the single source of truth for
 * currency metadata used by both backend and frontend.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Support;

use Illuminate\Support\Number;
use NumberFormatter;

/**
 * Service for formatting currency values using Laravel's built-in Number utility.
 * Provides locale-aware and currency-specific formatting.
 */
class CurrencyFormatter
{
    /**
     * Format a numeric value as a currency string.
     *
     * @param  float|int|string  $amount  The numeric value to format
     * @param  string  $currencyCode  The ISO 4217 currency code (e.g., 'IDR', 'USD')
     * @param  string|null  $locale  The locale to use. If null, automatically determined by currency mapping.
     * @return string The formatted currency string
     */
    public function format($amount, string $currencyCode, ?string $locale = null): string
    {
        $locale = $locale ?? $this->getLocaleForCurrency($currencyCode);

        return Number::currency((float) $amount, in: $currencyCode, locale: $locale);
    }

    /**
     * Get the currency symbol for a specific code.
     *
     * @param  string  $currencyCode  The ISO 4217 currency code
     * @param  string|null  $locale  The locale to use
     * @return string
     */
    public function getSymbol(string $currencyCode, ?string $locale = null): string
    {
        $locale = $locale ?? $this->getLocaleForCurrency($currencyCode);
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currencyCode);

        return $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Get the most appropriate locale for a currency code.
     *
     * @param  string  $currency
     * @return string
     */
    public function getLocaleForCurrency(string $currency): string
    {
        $definitions = self::getCurrencyDefinitions();

        return $definitions[$currency]['locale'] ?? app()->getLocale();
    }

    /**
     * Get currency options formatted for Settings Registrar.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function getSettingsOptions(): array
    {
        $options = [];
        foreach (self::getCurrencyDefinitions() as $code => $data) {
            $options[] = [
                'value' => $code,
                'label' => "{$code} - {$data['label']}",
            ];
        }

        return $options;
    }

    /**
     * The Single Source of Truth for currency metadata.
     * Used by Backend (Formatting/Settings) and Frontend (via Inertia).
     *
     * @return array<string, array{label: string, locale: string}>
     */
    public static function getCurrencyDefinitions(): array
    {
        return [
            // Major World Currencies
            'USD' => ['label' => 'United States Dollar', 'locale' => 'en_US'],
            'EUR' => ['label' => 'Euro', 'locale' => 'de_DE'],
            'GBP' => ['label' => 'British Pound Sterling', 'locale' => 'en_GB'],
            'JPY' => ['label' => 'Japanese Yen', 'locale' => 'ja_JP'],
            'CHF' => ['label' => 'Swiss Franc', 'locale' => 'de_CH'],
            'CNY' => ['label' => 'Chinese Yuan', 'locale' => 'zh_CN'],
            'CNH' => ['label' => 'Chinese Yuan (Offshore)', 'locale' => 'zh_CN'],

            // Americas
            'ARS' => ['label' => 'Argentine Peso', 'locale' => 'es_AR'],
            'BBD' => ['label' => 'Barbadian Dollar', 'locale' => 'en_BB'],
            'BMD' => ['label' => 'Bermudian Dollar', 'locale' => 'en_BM'],
            'BOB' => ['label' => 'Bolivian Boliviano', 'locale' => 'es_BO'],
            'BRL' => ['label' => 'Brazilian Real', 'locale' => 'pt_BR'],
            'BSD' => ['label' => 'Bahamian Dollar', 'locale' => 'en_BS'],
            'BZD' => ['label' => 'Belize Dollar', 'locale' => 'en_BZ'],
            'CAD' => ['label' => 'Canadian Dollar', 'locale' => 'en_CA'],
            'CLP' => ['label' => 'Chilean Peso', 'locale' => 'es_CL'],
            'CLF' => ['label' => 'Chilean Unit of Account (UF)', 'locale' => 'es_CL'],
            'COP' => ['label' => 'Colombian Peso', 'locale' => 'es_CO'],
            'CRC' => ['label' => 'Costa Rican Colón', 'locale' => 'es_CR'],
            'CUP' => ['label' => 'Cuban Peso', 'locale' => 'es_CU'],
            'DOP' => ['label' => 'Dominican Peso', 'locale' => 'es_DO'],
            'GTQ' => ['label' => 'Guatemalan Quetzal', 'locale' => 'es_GT'],
            'GYD' => ['label' => 'Guyanese Dollar', 'locale' => 'en_GY'],
            'HNL' => ['label' => 'Honduran Lempira', 'locale' => 'es_HN'],
            'HTG' => ['label' => 'Haitian Gourde', 'locale' => 'fr_HT'],
            'JMD' => ['label' => 'Jamaican Dollar', 'locale' => 'en_JM'],
            'KYD' => ['label' => 'Cayman Islands Dollar', 'locale' => 'en_KY'],
            'MXN' => ['label' => 'Mexican Peso', 'locale' => 'es_MX'],
            'NIO' => ['label' => 'Nicaraguan Córdoba', 'locale' => 'es_NI'],
            'PAB' => ['label' => 'Panamanian Balboa', 'locale' => 'es_PA'],
            'PEN' => ['label' => 'Peruvian Sol', 'locale' => 'es_PE'],
            'PYG' => ['label' => 'Paraguayan Guarani', 'locale' => 'es_PY'],
            'SRD' => ['label' => 'Surinamese Dollar', 'locale' => 'nl_SR'],
            'TTD' => ['label' => 'Trinidad and Tobago Dollar', 'locale' => 'en_TT'],
            'UYU' => ['label' => 'Uruguayan Peso', 'locale' => 'es_UY'],
            'VES' => ['label' => 'Venezuelan Bolívar Soberano', 'locale' => 'es_VE'],
            'XCD' => ['label' => 'East Caribbean Dollar', 'locale' => 'en_AG'],

            // Europe
            'ALL' => ['label' => 'Albanian Lek', 'locale' => 'sq_AL'],
            'BAM' => ['label' => 'Bosnia-Herzegovina Convertible Mark', 'locale' => 'bs_BA'],
            'BGN' => ['label' => 'Bulgarian Lev', 'locale' => 'bg_BG'],
            'BYN' => ['label' => 'Belarusian Ruble', 'locale' => 'be_BY'],
            'CZK' => ['label' => 'Czech Koruna', 'locale' => 'cs_CZ'],
            'DKK' => ['label' => 'Danish Krone', 'locale' => 'da_DK'],
            'FOK' => ['label' => 'Faroese Króna', 'locale' => 'fo_FO'],
            'GEL' => ['label' => 'Georgian Lari', 'locale' => 'ka_GE'],
            'GGP' => ['label' => 'Guernsey Pound', 'locale' => 'en_GG'],
            'GIP' => ['label' => 'Gibraltar Pound', 'locale' => 'en_GI'],
            'HRK' => ['label' => 'Croatian Kuna', 'locale' => 'hr_HR'],
            'HUF' => ['label' => 'Hungarian Forint', 'locale' => 'hu_HU'],
            'IMP' => ['label' => 'Isle of Man Pound', 'locale' => 'en_IM'],
            'ISK' => ['label' => 'Icelandic Króna', 'locale' => 'is_IS'],
            'JEP' => ['label' => 'Jersey Pound', 'locale' => 'en_JE'],
            'MDL' => ['label' => 'Moldovan Leu', 'locale' => 'ro_MD'],
            'MKD' => ['label' => 'Macedonian Denar', 'locale' => 'mk_MK'],
            'NOK' => ['label' => 'Norwegian Krone', 'locale' => 'nb_NO'],
            'PLN' => ['label' => 'Polish Zloty', 'locale' => 'pl_PL'],
            'RON' => ['label' => 'Romanian Leu', 'locale' => 'ro_RO'],
            'RSD' => ['label' => 'Serbian Dinar', 'locale' => 'sr_RS'],
            'RUB' => ['label' => 'Russian Ruble', 'locale' => 'ru_RU'],
            'SEK' => ['label' => 'Swedish Krona', 'locale' => 'sv_SE'],
            'TRY' => ['label' => 'Turkish Lira', 'locale' => 'tr_TR'],
            'UAH' => ['label' => 'Ukrainian Hryvnia', 'locale' => 'uk_UA'],

            // Asia
            'AED' => ['label' => 'United Arab Emirates Dirham', 'locale' => 'ar_AE'],
            'AFN' => ['label' => 'Afghan Afghani', 'locale' => 'fa_AF'],
            'AMD' => ['label' => 'Armenian Dram', 'locale' => 'hy_AM'],
            'AZN' => ['label' => 'Azerbaijani Manat', 'locale' => 'az_AZ'],
            'BDT' => ['label' => 'Bangladeshi Taka', 'locale' => 'bn_BD'],
            'BHD' => ['label' => 'Bahraini Dinar', 'locale' => 'ar_BH'],
            'BND' => ['label' => 'Brunei Dollar', 'locale' => 'ms_BN'],
            'BTN' => ['label' => 'Bhutanese Ngultrum', 'locale' => 'dz_BT'],
            'HKD' => ['label' => 'Hong Kong Dollar', 'locale' => 'zh_HK'],
            'IDR' => ['label' => 'Indonesian Rupiah', 'locale' => 'id_ID'],
            'ILS' => ['label' => 'Israeli New Shekel', 'locale' => 'he_IL'],
            'INR' => ['label' => 'Indian Rupee', 'locale' => 'en_IN'],
            'IQD' => ['label' => 'Iraqi Dinar', 'locale' => 'ar_IQ'],
            'IRR' => ['label' => 'Iranian Rial', 'locale' => 'fa_IR'],
            'JOD' => ['label' => 'Jordanian Dinar', 'locale' => 'ar_JO'],
            'KGS' => ['label' => 'Kyrgystani Som', 'locale' => 'ky_KG'],
            'KHR' => ['label' => 'Cambodian Riel', 'locale' => 'km_KH'],
            'KRW' => ['label' => 'South Korean Won', 'locale' => 'ko_KR'],
            'KWD' => ['label' => 'Kuwaiti Dinar', 'locale' => 'ar_KW'],
            'KZT' => ['label' => 'Kazakhstani Tenge', 'locale' => 'kk_KZ'],
            'LAK' => ['label' => 'Lao Kip', 'locale' => 'lo_LA'],
            'LBP' => ['label' => 'Lebanese Pound', 'locale' => 'ar_LB'],
            'LKR' => ['label' => 'Sri Lankan Rupee', 'locale' => 'si_LK'],
            'MMK' => ['label' => 'Myanmar Kyat', 'locale' => 'my_MM'],
            'MNT' => ['label' => 'Mongolian Tugrik', 'locale' => 'mn_MN'],
            'MOP' => ['label' => 'Macanese Pataca', 'locale' => 'zh_MO'],
            'MVR' => ['label' => 'Maldivian Rufiyaa', 'locale' => 'dv_MV'],
            'MYR' => ['label' => 'Malaysian Ringgit', 'locale' => 'ms_MY'],
            'NPR' => ['label' => 'Nepalese Rupee', 'locale' => 'ne_NP'],
            'OMR' => ['label' => 'Omani Rial', 'locale' => 'ar_OM'],
            'PHP' => ['label' => 'Philippine Peso', 'locale' => 'en_PH'],
            'PKR' => ['label' => 'Pakistani Rupee', 'locale' => 'ur_PK'],
            'QAR' => ['label' => 'Qatari Riyal', 'locale' => 'ar_QA'],
            'SAR' => ['label' => 'Saudi Riyal', 'locale' => 'ar_SA'],
            'SGD' => ['label' => 'Singapore Dollar', 'locale' => 'en_SG'],
            'SYP' => ['label' => 'Syrian Pound', 'locale' => 'ar_SY'],
            'THB' => ['label' => 'Thai Baht', 'locale' => 'th_TH'],
            'TJS' => ['label' => 'Tajikistani Somoni', 'locale' => 'tg_TJ'],
            'TMT' => ['label' => 'Turkmenistani Manat', 'locale' => 'tk_TM'],
            'TWD' => ['label' => 'New Taiwan Dollar', 'locale' => 'zh_TW'],
            'UZS' => ['label' => 'Uzbekistani Som', 'locale' => 'uz_UZ'],
            'VND' => ['label' => 'Vietnamese Dong', 'locale' => 'vi_VN'],
            'YER' => ['label' => 'Yemeni Rial', 'locale' => 'ar_YE'],

            // Africa
            'AOA' => ['label' => 'Angolan Kwanza', 'locale' => 'pt_AO'],
            'BIF' => ['label' => 'Burundian Franc', 'locale' => 'rn_BI'],
            'BWP' => ['label' => 'Botswanan Pula', 'locale' => 'en_BW'],
            'CDF' => ['label' => 'Congolese Franc', 'locale' => 'fr_CD'],
            'CVE' => ['label' => 'Cape Verdean Escudo', 'locale' => 'pt_CV'],
            'DJF' => ['label' => 'Djiboutian Franc', 'locale' => 'fr_DJ'],
            'DZD' => ['label' => 'Algerian Dinar', 'locale' => 'ar_DZ'],
            'EGP' => ['label' => 'Egyptian Pound', 'locale' => 'ar_EG'],
            'ERN' => ['label' => 'Eritrean Nakfa', 'locale' => 'ti_ER'],
            'ETB' => ['label' => 'Ethiopian Birr', 'locale' => 'am_ET'],
            'GHS' => ['label' => 'Ghanaian Cedi', 'locale' => 'en_GH'],
            'GMD' => ['label' => 'Gambian Dalasi', 'locale' => 'en_GM'],
            'GNF' => ['label' => 'Guinean Franc', 'locale' => 'fr_GN'],
            'KES' => ['label' => 'Kenyan Shilling', 'locale' => 'en_KE'],
            'KMF' => ['label' => 'Comorian Franc', 'locale' => 'ar_KM'],
            'LRD' => ['label' => 'Liberian Dollar', 'locale' => 'en_LR'],
            'LSL' => ['label' => 'Lesotho Loti', 'locale' => 'en_LS'],
            'LYD' => ['label' => 'Libyan Dinar', 'locale' => 'ar_LY'],
            'MAD' => ['label' => 'Moroccan Dirham', 'locale' => 'ar_MA'],
            'MGA' => ['label' => 'Malagasy Ariary', 'locale' => 'mg_MG'],
            'MRU' => ['label' => 'Mauritanian Ouguiya', 'locale' => 'ar_MR'],
            'MUR' => ['label' => 'Mauritian Rupee', 'locale' => 'en_MU'],
            'MWK' => ['label' => 'Malawian Kwacha', 'locale' => 'en_MW'],
            'MZN' => ['label' => 'Mozambican Metical', 'locale' => 'pt_MZ'],
            'NAD' => ['label' => 'Namibian Dollar', 'locale' => 'en_NA'],
            'NGN' => ['label' => 'Nigerian Naira', 'locale' => 'en_NG'],
            'RWF' => ['label' => 'Rwandan Franc', 'locale' => 'rw_RW'],
            'SCR' => ['label' => 'Seychellois Rupee', 'locale' => 'fr_SC'],
            'SDG' => ['label' => 'Sudanese Pound', 'locale' => 'ar_SD'],
            'SHP' => ['label' => 'Saint Helena Pound', 'locale' => 'en_SH'],
            'SLE' => ['label' => 'Sierra Leonean Leone (new)', 'locale' => 'en_SL'],
            'SLL' => ['label' => 'Sierra Leonean Leone (old)', 'locale' => 'en_SL'],
            'SOS' => ['label' => 'Somali Shilling', 'locale' => 'so_SO'],
            'SSP' => ['label' => 'South Sudanese Pound', 'locale' => 'en_SS'],
            'STN' => ['label' => 'São Tomé and Príncipe Dobra', 'locale' => 'pt_ST'],
            'SZL' => ['label' => 'Swazi Lilangeni', 'locale' => 'en_SZ'],
            'TND' => ['label' => 'Tunisian Dinar', 'locale' => 'ar_TN'],
            'TZS' => ['label' => 'Tanzanian Shilling', 'locale' => 'sw_TZ'],
            'UGX' => ['label' => 'Ugandan Shilling', 'locale' => 'en_UG'],
            'XAF' => ['label' => 'Central African CFA Franc', 'locale' => 'fr_CM'],
            'XOF' => ['label' => 'West African CFA Franc', 'locale' => 'fr_SN'],
            'ZAR' => ['label' => 'South African Rand', 'locale' => 'en_ZA'],
            'ZMW' => ['label' => 'Zambian Kwacha', 'locale' => 'en_ZM'],
            'ZWG' => ['label' => 'Zimbabwean Gold', 'locale' => 'en_ZW'],
            'ZWL' => ['label' => 'Zimbabwean Dollar', 'locale' => 'en_ZW'],

            // Oceania & Pacific
            'AUD' => ['label' => 'Australian Dollar', 'locale' => 'en_AU'],
            'FJD' => ['label' => 'Fijian Dollar', 'locale' => 'en_FJ'],
            'KID' => ['label' => 'Kiribati Dollar', 'locale' => 'en_KI'],
            'NZD' => ['label' => 'New Zealand Dollar', 'locale' => 'en_NZ'],
            'PGK' => ['label' => 'Papua New Guinean Kina', 'locale' => 'en_PG'],
            'SBD' => ['label' => 'Solomon Islands Dollar', 'locale' => 'en_SB'],
            'TOP' => ['label' => 'Tongan Paʻanga', 'locale' => 'to_TO'],
            'TVD' => ['label' => 'Tuvaluan Dollar', 'locale' => 'en_TV'],
            'VUV' => ['label' => 'Vanuatu Vatu', 'locale' => 'en_VU'],
            'WST' => ['label' => 'Samoan Tala', 'locale' => 'en_WS'],
            'XPF' => ['label' => 'CFP Franc', 'locale' => 'fr_PF'],

            // Caribbean
            'ANG' => ['label' => 'Netherlands Antillean Guilder', 'locale' => 'nl_CW'],
            'AWG' => ['label' => 'Aruban Florin', 'locale' => 'nl_AW'],
            'FKP' => ['label' => 'Falkland Islands Pound', 'locale' => 'en_FK'],
            'XCG' => ['label' => 'Caribbean Guilder', 'locale' => 'nl_CW'],

            // Special Drawing Rights
            'XDR' => ['label' => 'Special Drawing Rights (IMF)', 'locale' => 'en_US'],
        ];
    }
}
