<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Repository;

class ThirdPartyIntegrationRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\ThirdPartyIntegration\Model\ThirdPartyIntegration::definition();
    }
    public function findByIntegrationName($integration_name)
    {
        $where = "integration = " . \Core\Db\Db::quote($integration_name);
        return $this->all($where);
    }
}

?>