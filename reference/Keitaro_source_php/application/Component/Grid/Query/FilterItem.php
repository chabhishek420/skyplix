<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Query;

class FilterItem
{
    protected $_validOperators = NULL;
    private $_name = NULL;
    private $_operator = NULL;
    private $_expression = NULL;
    private $_caseSensitive = NULL;
    const EQUALS = "EQUALS";
    const NOT_EQUAL = "NOT_EQUAL";
    const GREATER_THAN = "GREATER_THAN";
    const EQUALS_OR_GREATER_THAN = "EQUALS_OR_GREATER_THAN";
    const LESS_THAN = "LESS_THAN";
    const EQUALS_OR_LESS_THAN = "EQUALS_OR_LESS_THAN";
    const MATCH_REGEXP = "MATCH_REGEXP";
    const NOT_MATCH_REGEXP = "NOT_MATCH_REGEXP";
    const BEGINS_WITH = "BEGINS_WITH";
    const IP_BEGINS_WITH = "IP_BEGINS_WITH";
    const ENDS_WITH = "ENDS_WITH";
    const CONTAINS = "CONTAINS";
    const NOT_CONTAIN = "NOT_CONTAIN";
    const IN_LIST = "IN_LIST";
    const NOT_IN_LIST = "NOT_IN_LIST";
    const BETWEEN = "BETWEEN";
    const IS_SET = "IS_SET";
    const IS_NOT_SET = "IS_NOT_SET";
    const IS_TRUE = "IS_TRUE";
    const IS_FALSE = "IS_FALSE";
    const HAS_LABEL = "HAS_LABEL";
    const HAS_NOT_LABEL = "HAS_NOT_LABEL";
    const OPERATOR = "operator";
    const NAME = "name";
    const EXPRESSION = "expression";
    const CASE_SENSITIVE = "case_sensitive";
    public function __construct($info)
    {
        if (empty($info[OPERATOR])) {
            throw new Error("Filter " . $info["name"] . " must contain 'operator'");
        }
        if (!empty($info[OPERATOR])) {
            $info[OPERATOR] = strtoupper($info[OPERATOR]);
        }
        if (!in_array($info[OPERATOR], $this->_validOperators)) {
            throw new Error("Filter " . $info["name"] . " contains incorrect operator '" . $info[OPERATOR] . "'");
        }
        if (!empty($info[NAME])) {
            $this->setName($info[NAME]);
        }
        $this->setOperator($info[OPERATOR]);
        $this->setExpression(isset($info[EXPRESSION]) ? $info[EXPRESSION] : "");
        $this->_caseSensitive = isset($info[CASE_SENSITIVE]) ? $info[CASE_SENSITIVE] : false;
    }
    public function getName()
    {
        return $this->_name;
    }
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }
    public function getOperator()
    {
        return $this->_operator;
    }
    public function setOperator($operator)
    {
        $this->_operator = $operator;
        return $this;
    }
    public function getExpression()
    {
        return $this->_expression;
    }
    public function setExpression($expression)
    {
        $this->_expression = $expression;
        return $this;
    }
    public function isCaseSensitive()
    {
        return $this->_caseSensitive;
    }
    public function toSql()
    {
        $action = "_build";
        $action .= \Traffic\Tools\Tools::toCamelCase(strtolower($this->getOperator()));
        $conditions = call_user_func_array([$this, $action], [$this->getName(), $this->getExpression()]);
        if (is_array($conditions) && count($conditions)) {
            return "(" . implode(" OR ", $conditions) . ")";
        }
        return "(" . $conditions . ")";
    }
    private function _buildEquals($name, $expression = "")
    {
        $binary = $this->_binary();
        return $binary . $name . " = " . \Core\Db\Db::quote($expression);
    }
    private function _buildNotEqual($name, $expression = "")
    {
        $binary = $this->_binary();
        return $binary . $name . " <> " . \Core\Db\Db::quote($expression);
    }
    private function _buildGreaterThan($name, $expression)
    {
        return $name . " > " . \Core\Db\Db::quote($expression);
    }
    private function _buildEqualsOrGreaterThan($name, $expression)
    {
        return $name . " >= " . \Core\Db\Db::quote($expression);
    }
    private function _buildLessThan($name, $expression)
    {
        return $name . " < " . \Core\Db\Db::quote($expression);
    }
    private function _buildEqualsOrLessThan($name, $expression)
    {
        return $name . " <= " . \Core\Db\Db::quote($expression);
    }
    private function _buildMatchRegexp($name, $expression)
    {
        if (empty($expression)) {
            $expression = "^\$";
        }
        $binary = $this->_binary();
        return $name . " REGEXP " . $binary . \Core\Db\Db::quote($expression);
    }
    private function _buildNotMatchRegexp($name, $expression)
    {
        if (empty($expression)) {
            $expression = "^\$";
        }
        $binary = $this->_binary();
        return $name . " NOT REGEXP " . $binary . \Core\Db\Db::quote($expression);
    }
    private function _buildBeginsWith($name, $expression = "")
    {
        if (!is_string($expression)) {
            throw new Error("[BEGINS_WITH] Type of expressions must be a String");
        }
        $binary = $this->_binary();
        return $name . " LIKE " . $binary . \Core\Db\Db::quote($expression . "%");
    }
    private function _buildIpBeginsWith($name, $expression = "")
    {
        if (empty($expression)) {
            return "1";
        }
        $ipParts = explode(".", $expression);
        $minIp = implode(".", array_pad($ipParts, 4, "0"));
        $maxIp = implode(".", array_pad($ipParts, 4, "255"));
        $minIp = \Traffic\Tools\Tools::ip2long($minIp);
        $maxIp = \Traffic\Tools\Tools::ip2long($maxIp);
        return $minIp . " <= " . $name . " AND " . $name . " <= " . $maxIp;
    }
    private function _buildEndsWith($name, $expression = "")
    {
        if (!is_string($expression)) {
            throw new Error("[ENDS_EIWHT] Type of expressions must be a String");
        }
        $binary = $this->_binary();
        return $name . " LIKE " . $binary . \Core\Db\Db::quote("%" . $expression);
    }
    private function _buildContains($name, $expression = "")
    {
        if (!is_string($expression)) {
            throw new Error("[CONTAINS] Type of expressions must be a String");
        }
        $binary = $this->_binary();
        return $name . " LIKE " . $binary . \Core\Db\Db::quote("%" . $expression . "%");
    }
    private function _buildNotContain($name, $expression = "")
    {
        if (!is_string($expression)) {
            throw new Error("[NOT_CONTAIN] Type of expressions must be a String");
        }
        $binary = $this->_binary();
        return $name . " NOT LIKE " . $binary . \Core\Db\Db::quote("%" . $expression . "%");
    }
    private function _buildInList($name, $expressions = [])
    {
        if (!is_array($expressions) || count($expressions) == 0) {
            $expressions = [-1];
        }
        $binary = $this->_binary();
        return $name . " " . $binary . "IN (" . implode(",", \Core\Db\Db::quote($expressions)) . ")";
    }
    private function _buildNotInList($name, $expressions = [])
    {
        if (!is_array($expressions) || count($expressions) == 0) {
            $expressions = [-1];
        }
        $binary = $this->_binary();
        return $name . " " . $binary . "NOT IN (" . implode(",", \Core\Db\Db::quote($expressions)) . ")";
    }
    private function _buildBetween($name, $expressions = [])
    {
        if (empty($expressions)) {
            return 1;
        }
        if (count($expressions) == 1) {
            return $this->_buildGreaterThan($name, $expressions[0]);
        }
        $binary = $this->_binary();
        return $name . " " . $binary . "BETWEEN " . implode(" AND ", \Core\Db\Db::quote($expressions)) . "";
    }
    private function _buildIsSet($name)
    {
        return $name . " IS NOT NULL AND " . $name . " <> ''";
    }
    private function _buildIsNotSet($name)
    {
        return $name . " IS NULL OR " . $name . " = ''";
    }
    private function _buildIsTrue($name)
    {
        return $name . " = 1";
    }
    private function _buildIsFalse($name)
    {
        return $name . " = 0";
    }
    private function _binary()
    {
        if ($this->isCaseSensitive()) {
            return "BINARY ";
        }
        return "";
    }
}

?>