<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Controller;

class StreamEventsController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        $streamId = (int) $this->getParam("stream_id");
        $limit = (int) $this->getParam("limit");
        $page = (int) $this->getParam("page");
        $stream = \Component\Streams\Repository\StreamRepository::instance()->find($streamId);
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        $where = "stream_id = " . $stream->getId();
        $items = \Component\Streams\Repository\StreamEventsRepository::instance()->all($where, "id desc", $limit, ($page - 1) * $limit);
        foreach ($items as $item) {
            if ($item->get("state") == \Component\Streams\Model\StreamEvent::UNREAD) {
                $item->set("state", \Component\Streams\Model\StreamEvent::READ)->save();
            }
        }
        return ["total" => \Component\Streams\Repository\StreamEventsRepository::instance()->count($where), "items" => $this->serialize($items, new \Component\Streams\Serializer\StreamEventSerializer())];
    }
    public function clearAction()
    {
        $streamId = (int) $this->getParam("stream_id");
        $stream = \Component\Streams\Repository\StreamRepository::instance()->find($streamId);
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        $where = "stream_id = " . $stream->getId();
        \Component\StreamEvents\Service\StreamEventService::instance()->directDeleteAll($where);
    }
}

?>