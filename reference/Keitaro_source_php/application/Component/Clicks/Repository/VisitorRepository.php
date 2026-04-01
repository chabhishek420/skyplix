<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Repository;

class VisitorRepository extends \Core\Entity\Repository\EntityRepository
{
    const ID = "id";
    const IP = "ip";
    public function definition()
    {
        return \Component\Clicks\Model\Visitor::definition();
    }
    public function getEmptyVisitorIds($lastId = NULL)
    {
        $sql = "SELECT v.id as " . ID . ", ip.value as " . IP . "\n                FROM keitaro_visitors v\n                JOIN keitaro_ref_ips ip on v.ip_id = ip.id\n                JOIN keitaro_ref_countries c on v.country_id = c.id\n                WHERE trim(c.value) = \"\" ";
        if (!empty($lastId)) {
            $sql .= "AND v.id > " . $lastId . " ";
        }
        $sql .= "ORDER BY id ASC\n                LIMIT 1000";
        return \Core\Db\Db::instance()->getAll($sql);
    }
}

?>