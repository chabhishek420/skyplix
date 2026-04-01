<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Renderer;

class JsonRenderer implements RendererInterface
{
    private $_builder = NULL;
    const FORMAT = "array";
    public function __construct(\Component\Grid\Builder\BuilderInterface $builder)
    {
        $this->_builder = $builder;
    }
    public function create($summary)
    {
        $startTime = microtime(true);
        $result = $this->getRowsAndCount();
        if ($summary) {
            $summary = $this->_builder->getSummary();
            $result["summary"] = $summary;
        }
        $result["meta"] = ["execution_time" => number_format(microtime(true) - $startTime, 4), "datetime" => (new \DateTime())->format("c")];
        return $result;
    }
    public function getRowsAndCount()
    {
        $rows = $this->_builder->fetchRows();
        $count = \Core\Db\Db::instance()->getFoundRowsCount();
        return ["rows" => $this->_builder->getDecorator()->decorateRows($rows), "total" => $count];
    }
}

?>