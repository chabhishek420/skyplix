<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Controller;

class FavouriteStreamsController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        $streams = \Component\Streams\Repository\FavouriteStreamRepository::instance()->getFavouriteStreams($this->getUser());
        return $this->serialize($streams, new \Component\Streams\Serializer\StreamSerializer());
    }
    public function addAction()
    {
        $streamId = (int) $this->getParam("stream_id");
        $stream = \Component\Streams\Repository\StreamRepository::instance()->find($streamId);
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
        if (!$this->isEditAllowed($campaign)) {
            $this->throwDeny();
        }
        \Component\Streams\Service\FavouriteStreamService::instance()->addStream($this->getUser(), $stream);
    }
    public function removeAction()
    {
        $streamId = (int) $this->getParam("stream_id");
        $stream = \Component\Streams\Repository\StreamRepository::instance()->find($streamId);
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
        if (!$this->isEditAllowed($campaign)) {
            $this->throwDeny();
        }
        \Component\Streams\Service\FavouriteStreamService::instance()->removeStream($this->getUser(), $stream);
    }
}

?>