<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\Controller;

class ConversionsController extends \Admin\Controller\BaseController
{
    public function logDefinitionAction()
    {
        $definition = new \Component\Conversions\Grid\ConversionsLogDefinition();
        return $definition->getGridDefinition();
    }
    public function updateCostDefinitionAction()
    {
        $definition = new \Component\Clicks\Grid\ClicksDefinition();
        return $definition->getGridDefinition();
    }
    public function logAction()
    {
        $userParams = \Component\Grid\QueryParams\UserParams::create($this);
        return \Component\Conversions\Repository\ConversionRepository::instance()->log($this->getPostParams(), $userParams);
    }
    public function importAction()
    {
        if ($this->getParam("data") && $this->getParam("currency")) {
            $result = \Component\Conversions\Service\ConversionsService::instance()->import($this->getParam("data"), $this->getParam("currency"));
            $message = ["errors" => $result["errors"], "success" => $result["good"], "total" => $result["total"]];
            return $message;
        }
        throw new \Core\Application\Exception\Error("Import data or currency is empty");
    }
    public function statusesAction()
    {
        return \Component\Conversions\Repository\ConversionRepository::instance()->getStatuses();
    }
}

?>