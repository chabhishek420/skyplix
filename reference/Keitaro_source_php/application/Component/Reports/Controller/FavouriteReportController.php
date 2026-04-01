<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Controller;

class FavouriteReportController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        $reports = \Component\Reports\Repository\FavouriteReportRepository::instance()->allByUser($this->getUser());
        return $this->serialize($reports, new \Component\Reports\Serializer\FavouriteReportSerializer());
    }
    public function createAction()
    {
        $report = \Component\Reports\Service\FavouriteBookmarkService::instance()->createForUser($this->getUser(), $this->getPostParams());
        return $this->serialize($report, new \Component\Reports\Serializer\FavouriteReportSerializer());
    }
    public function updateAction()
    {
        $report = \Component\Reports\Repository\FavouriteReportRepository::instance()->findByUser($this->getUser(), $this->getParam("id"));
        $report = \Component\Reports\Service\FavouriteBookmarkService::instance()->update($report, $this->getPostParams());
        return $this->serialize($report, new \Component\Reports\Serializer\FavouriteReportSerializer());
    }
    public function deleteAction()
    {
        $report = \Component\Reports\Repository\FavouriteReportRepository::instance()->findByUser($this->getUser(), $this->getParam("id"));
        \Component\Reports\Service\FavouriteBookmarkService::instance()->delete($report);
    }
}

?>