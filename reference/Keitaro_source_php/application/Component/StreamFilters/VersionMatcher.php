<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters;

class VersionMatcher
{
    const EQUALS = "equals";
    const EQUALS_OR_GREATER = "equals_or_greater";
    const EQUALS_OR_LESS = "equals_or_less";
    const IN_LIST = "in_list";
    const BETWEEN = "between";
    public function match($version, $operator, $expression)
    {
        $method = "_match" . \Traffic\Tools\Tools::toCamelCase($operator);
        return call_user_func([$this, $method], $version, $expression);
    }
    private function _matchEqualsOrGreater($version, $expression)
    {
        return 0 <= version_compare($version, $expression[0]);
    }
    private function _matchEqualsOrLess($version, $expression)
    {
        return version_compare($version, $expression[0]) <= 0;
    }
    private function _matchEquals($version, $expression)
    {
        return $version == $expression[0];
    }
    private function _matchBetween($version, $expression)
    {
        if (!is_array($expression)) {
            return false;
        }
        return version_compare($expression[0], $version) <= 0 && version_compare($version, $expression[1]) <= 0;
    }
    private function _matchInList($version, $expression)
    {
        if (!is_array($expression)) {
            return false;
        }
        return in_array($version, $expression);
    }
}

?>