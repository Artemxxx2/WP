<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;

class ValidateHelper
{
    public function text(Application $application, Form\Form $form, $value, array $element, $checkRequired = true, $isHtml = false)
    {
        $value = (string)$value;
        if (!empty($element['#char_validation'])
            && in_array($element['#char_validation'], array('integer', 'numeric', 'alnum', 'alpha', 'lower', 'upper', 'url', 'email'))
        ) {
            $element['#' . $element['#char_validation']] = true;
        }

        if (empty($element['#no_trim'])) {
            $value = trim(function_exists('mb_convert_kana') ? mb_convert_kana($value, 's', 'UTF-8') : $value);
        }

        // Remove value sent from placeholder
        if ($element['#type'] === 'url' && $value === 'https://') {
            $value = '';
        }

        if (strlen($value) === 0) {
            if ($checkRequired) {
                if ($form->isFieldRequired($element)) {
                    $form->setError(isset($element['#required_error_message']) ? $element['#required_error_message'] : __('Please fill out this field.', 'directories'), $element);
                    return false;
                }
            }
            return $value;
        }

        if (!empty($element['#integer'])) {
            if (!preg_match('/^-?\d+$/', $value)) {
                $form->setError(__('The input value must be an integer.', 'directories'), $element);
                return false;
            }
            if (empty($element['#integer_allow_string'])) {
                $value = intval($value);
            }
        } elseif (!empty($element['#numeric'])) {
            if (!is_numeric($value)) {
                $form->setError(__('The input value must be numeric.', 'directories'), $element);
                return false;
            }
        } elseif (!empty($element['#alpha'])) {
            if (!ctype_alpha($value)) {
                $form->setError(__('The input value must consist of alphabets only.', 'directories'), $element);
                return false;
            }
        } elseif (!empty($element['#alnum'])) {
            if (!ctype_alnum($value)) {
                $form->setError(__('The input value must consist of alphanumeric characters only.', 'directories'), $element);
                return false;
            }
        } elseif (!empty($element['#lower'])) {
            if (!ctype_lower($value)) {
                $form->setError(__('The input value must consist of lowercase characters only.', 'directories'), $element);
                return false;
            }
        } elseif (!empty($element['#upper'])) {
            if (!ctype_upper($value)) {
                $form->setError(__('The input value must consist of uppercase characters only.', 'directories'), $element);
                return false;
            }
        } elseif ($element['#type'] === 'url' || !empty($element['#url'])) {
            try {
                $value = $this->url($application, $value, !empty($element['#allow_url_no_protocol']));
            } catch (Exception\IException $e) {
                $form->setError($e->getMessage(), $element);
                return false;
            }
        } elseif ($element['#type'] === 'email' || !empty($element['#email'])) {
            try {
                $value = $this->email($application, $value, !empty($element['#check_mx']), !empty($element['#check_exists']));
            } catch (Exception\IException $e) {
                $form->setError($e->getMessage(), $element);
                return false;
            }
        }

        // Check min/max length
        $min_length = empty($element['#min_length']) ? null : (int)$element['#min_length'];
        $max_length = empty($element['#max_length']) ? null : (int)$element['#max_length'];
        if (isset($min_length) || isset($max_length)) {
            $value_length = $application->System_MB_strlen($isHtml ? strip_tags(html_entity_decode($value)) : $value, 'UTF-8');
            if ($max_length && $min_length) {
                if ($max_length === $min_length) {
                    if ($value_length !== $max_length) {
                        $form->setError(sprintf(__('The input value must be %d characters.', 'directories'), $max_length), $element);
                        return false;
                    }
                } else {
                    if ($value_length < $min_length || $value_length > $max_length) {
                        $form->setError(sprintf(__('The input value must be between %d and %d characters.', 'directories'), $min_length, $max_length), $element);
                        return false;
                    }
                }
            } elseif ($max_length) {
                if ($value_length > $max_length) {
                    $form->setError(sprintf(__('The input value must be shorter than %d characters.', 'directories'), $max_length), $element);
                    return false;
                }
            } elseif ($min_length) {
                if ($value_length < $min_length) {
                    $form->setError(sprintf(__('The input value must be longer than %d characters.', 'directories'), $min_length), $element);
                    return false;
                }
            }
        }

        if (!empty($element['#integer']) || !empty($element['#numeric'])) {
            if (isset($element['#min_value'])) {
                if ($value < $element['#min_value']) {
                    $form->setError(sprintf(__('The value must be equal or greater than %s.', 'directories'), $element['#min_value']), $element);
                    return false;
                }
            }
            if (isset($element['#max_value'])) {
                if ($value > $element['#max_value']) {
                    $form->setError(sprintf(__('The value must not be greater than %s.', 'directories'), $element['#max_value']), $element);
                    return false;
                }
            }
        }

        // Validate against regex?
        if (isset($element['#regex']) && strlen($element['#regex'])) {
            if (!preg_match($element['#regex'], $value, $matches)) {
                $form->setError(isset($element['#regex_error_message']) ? $element['#regex_error_message'] : sprintf(__('The input value did not match the regular expression: %s', 'directories'), $element['#regex']), $element);
                return false;
            }
        }

        return $application->Filter('form_validate_field_value', $value, [$form, $element, $checkRequired, $isHtml]);
    }

    public function email(Application $application, $value, $checkMx = false, $checkExists = false)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new Exception\RuntimeException(__('The input value is not a valid E-mail address.', 'directories'));
        }
        if (!empty($checkMx)
            && Form\Field\TextField::canCheckMx()
        ) {
            list(, $domain) = explode('@', $value);
            if (!$domain
                || !checkdnsrr($domain, 'MX')
            ) {
                throw new Exception\RuntimeException(__('Invalid domain name.', 'directories'));
            }
        }
        if (!empty($checkExists)) {
            $identity = $application->getPlatform()->getUserIdentityFetcher()->fetchByEmail($value);
            if (!$identity->isAnonymous()) {
                // There is already a registered user with that email address
                throw new Exception\RuntimeException(__('The email address is already registered and may not be used.', 'directories'));
            }
        }

        return $value;
    }

    public function url(Application $application, $value, $allowNoProtocol = false)
    {
        $value = preg_replace('/\s+/S', '', $value);
        $value_to_check = $value;
        if (0 === strpos($value, '//')) {
            if (!empty($allowNoProtocol)) {
                $value_to_check = 'http:' . $value;
            }
        } elseif (false === strpos($value, '://')) {
            $value_to_check = $value = 'https://' . $value;
        }
        if (!preg_match('#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#', $value_to_check)) { // supports IDN
        //if (!filter_var($value_to_check, FILTER_VALIDATE_URL)) {
            throw new Exception\RuntimeException(__('The input value is not a valid URL.', 'directories'));
        }

        return $value;
    }

    public function date(Application $application, $value, array $element, Form\Form $form)
    {
        $value = trim((string)$value);

        // Required?
        if (strlen($value) === 0) {
            if ($form->isFieldRequired($element)) {
                $form->setError(isset($element['#required_error_message']) ? $element['#required_error_message'] : __('Please select a date.', 'directories'), $element);
                return false;
            }
            return isset($element['#empty_value']) ? $element['#empty_value'] : null;
        }

        // Check date
        list($year, $month, $day) = $this->_getDate($value = strtotime($value));
        if (!checkdate($month, $day, $year)) {
            $form->setError(__('Invalid date.', 'directories'), $element);

            return false;
        }

        $value = $application->getPlatform()->getSiteToSystemTime($value);

        // Make sure the submitted date falls between allowed date rage
        if (isset($element['#min_date']) && isset($element['#max_date'])) {
            if ($value < $element['#min_date']
                || $value > $element['#max_date']
            ) {
                $min_date_str = $application->System_Date_datetime($element['#min_date']);
                $max_date_str = $application->System_Date_datetime($element['#max_date']);
                $form->setError(sprintf(__('Date must be between %s and %s.', 'directories'), $min_date_str, $max_date_str), $element);
                return false;
            }
        } elseif (isset($element['#min_date'])) {
            if ($value < $element['#min_date']) {
                $min_date_str = $application->System_Date_datetime($element['#min_date']);
                $form->setError(sprintf(__('Date must be later than %s.', 'directories'), $min_date_str), $element);
                return false;
            }
        } elseif (isset($element['#max_date'])) {
            if ($value > $element['#max_date']) {
                $max_date_str = $application->System_Date_datetime($element['#max_date']);
                $form->setError(sprintf(__('Date must be earlier than %s.', 'directories'), $max_date_str), $element);
                return false;
            }
        }

        return $value;
    }

    protected function _getDate($timestamp)
    {
        $date = getdate($timestamp);

        return array($date['year'], $date['mon'], $date['mday'], $date['hours'], $date['minutes']);
    }

    public function time(Application $application, $value, $convert = false)
    {
        $value = trim($value);
        if (strpos($value, ' ')) {
            if (!$parts = explode(' ', $value)) return false;

            if (isset($parts[1])) {
                $ampm = strtoupper($parts[1]);
                if (!in_array($ampm, ['AM', 'PM'])) return false;
            }
            $value = $parts[0];
        }

        $time = array_map('trim', explode(':', $value));
        if (count($time) !== 2 || !is_numeric($time[0]) || !is_numeric($time[1])) return false;

        $time[0] = intval($time[0]);
        $time[1] = intval($time[1]);
        if ($time[0] < 0 || $time[0] > 23 || $time[1] < 0 || $time[1] > 59) return false;

        if (isset($ampm)) {
            if ($time[0] > 12) return false;

            if ($ampm === 'PM') $time[0] += 12;
        }

        if (!$convert) return $time;

        return mktime($time[0], $time[1], 0) % 86400;
    }
}
