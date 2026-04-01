<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams;

class SearchStreams extends \Traffic\Service\AbstractService
{
    private $_allowedCampaignIds = NULL;
    public function __construct($allowedCampaignIds = NULL)
    {
        $this->_allowedCampaignIds = $allowedCampaignIds;
    }
    public function search($query)
    {
        if (empty($query)) {
            return [];
        }
        $result = $this->_searchStreams($query);
        return \Core\Json\SerializerFactory::serialize($result, new Serializer\StreamSearchResultSerializer());
    }
    public function searchAndPaginate($query, $offset, $limit)
    {
        $result = $this->search($query);
        $chunk = array_slice($result, $offset, $limit);
        return ["total" => count($result), "items" => $chunk];
    }
    private function _searchStreams($query)
    {
        $rules = [];
        $rules[] = " action_payload LIKE " . \Core\Db\Db::quote("%" . $query . "%");
        if (isset($query) && $query[0] == "#") {
            $rules[] = " id = " . \Core\Db\Db::quote(substr($query, 1));
        } else {
            $rules[] = " name LIKE " . \Core\Db\Db::quote("%" . $query . "%");
        }
        $where = "(" . implode(" OR ", $rules) . ")";
        $where .= " AND state != " . \Core\Db\Db::quote("deleted") . " ";
        if (!empty($this->_allowedCampaignIds)) {
            $where .= " AND campaign_id IN (" . implode(", ", $this->_allowedCampaignIds) . ")";
        }
        return Repository\StreamRepository::instance()->getStreams($where);
    }
}

?>