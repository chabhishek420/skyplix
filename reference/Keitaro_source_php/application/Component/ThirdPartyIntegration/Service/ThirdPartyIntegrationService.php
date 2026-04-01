<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Service;

class ThirdPartyIntegrationService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\ThirdPartyIntegration\Model\ThirdPartyIntegration::definition();
    }
    public function updateValues($id, $data)
    {
        $integration = \Component\ThirdPartyIntegration\Repository\ThirdPartyIntegrationRepository::instance()->find($id);
        if ($integration) {
            $settings = $integration->getIntegrationParams();
            foreach ($data as $key => $datum) {
                $settings[$key] = $datum;
            }
            $integration->setData(["settings" => $settings]);
            $integration->save();
        }
        return $integration;
    }
    public function deleteById($id)
    {
        $integration = \Component\ThirdPartyIntegration\Repository\ThirdPartyIntegrationRepository::instance()->find($id);
        if ($integration) {
            $this->delete($integration);
        }
    }
}

?>