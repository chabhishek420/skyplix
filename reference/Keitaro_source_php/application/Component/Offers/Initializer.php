<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Offers;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("offers", new Controller\OffersController());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/offers", "desc" => "Get list of offers", "onMatch" => function () {
            return ["controller" => "offers", "action" => "index"];
        }]);
        $repo->register(["method" => "GET", "route" => "/offers/[i:id]", "desc" => "Get a specific offer", "onMatch" => function ($id) {
            return ["controller" => "offers", "action" => "show", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/offers", "desc" => "Create an offer", "onMatch" => function () {
            return ["controller" => "offers", "action" => "create"];
        }]);
        $repo->register(["method" => "PUT", "route" => "/offers/[i:id]", "desc" => "Update an offer", "onMatch" => function ($id) {
            return ["controller" => "offers", "action" => "update", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/offers/[i:id]/clone", "desc" => "Clone offer", "onMatch" => function ($id) {
            return ["controller" => "offers", "action" => "clone", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/offers/[i:id]/archive", "desc" => "Move an offer to archive", "onMatch" => function ($id) {
            return ["controller" => "offers", "action" => "archive", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "GET", "route" => "/offers/[i:id]/get_structure", "desc" => "Load files local landing", "onMatch" => function ($id) {
            return ["controller" => "editor", "action" => "loadFiles", "params" => ["id" => $id, "type" => "offer"]];
        }]);
        $repo->register(["method" => "GET", "route" => "/offers/[i:id]/get_file", "desc" => "Load file data local landing", "onMatch" => function ($id) {
            return ["controller" => "editor", "action" => "loadFileData", "params" => ["id" => $id, "type" => "offer"]];
        }]);
        $repo->register(["method" => "PUT", "route" => "/offers/[i:id]/update_file", "desc" => "Save file data local landing", "onMatch" => function ($id) {
            return ["controller" => "editor", "action" => "saveFileData", "params" => ["id" => $id, "type" => "offer"]];
        }]);
        $repo->register(["method" => "POST", "route" => "/offers/[i:id]/add_file", "desc" => "Create file local landing", "onMatch" => function ($id) {
            return ["controller" => "editor", "action" => "createFile", "params" => ["id" => $id, "type" => "offer"]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/offers/[i:id]/remove_file", "desc" => "Remove file local landing", "onMatch" => function ($id) {
            return ["controller" => "editor", "action" => "removeFile", "params" => ["id" => $id, "type" => "offer"]];
        }]);
        $repo->register(["method" => "GET", "route" => "/offers/[i:id]/download", "desc" => "Download local landing", "onMatch" => function ($id) {
            return ["controller" => "offers", "action" => "download", "params" => ["id" => $id]];
        }]);
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneOffers());
    }
}

?>