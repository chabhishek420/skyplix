<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("landings", new Controller\LandingsController());
    }
    public function loadDelayedCommands(\Component\DelayedCommands\Repository\DelayedCommandRepository $repo)
    {
        $repo->register(DelayedCommand\CreatePreviewImageCommand::NAME, new DelayedCommand\CreatePreviewImageCommand());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/landing_pages", "desc" => "Retrieve all landing pages", "onMatch" => function () {
            return ["controller" => "landings", "action" => "index"];
        }]);
        $repo->register(["method" => "GET", "route" => "/landing_pages/[i:id]", "desc" => "Retrieve a specific landing page", "onMatch" => function ($id) {
            return ["controller" => "landings", "action" => "show", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "PUT", "route" => "/landing_pages/[i:id]/clone", "desc" => "Clone a landing page", "onMatch" => function ($id) {
            return ["controller" => "landings", "action" => "clone", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/landing_pages", "desc" => "Create a landing page", "onMatch" => function () {
            return ["controller" => "landings", "action" => "create"];
        }]);
        $repo->register(["method" => "PUT", "route" => "/landing_pages/[i:id]", "desc" => "Edit a landing page", "onMatch" => function ($id) {
            return ["controller" => "landings", "action" => "update", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/landing_pages/[i:id]", "desc" => "Archive landing page", "onMatch" => function ($id) {
            return ["controller" => "landings", "action" => "archive", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "GET", "route" => "/landing_pages/[i:id]/get_structure", "desc" => "Load files local landing", "onMatch" => function ($id) {
            return ["controller" => "editor", "action" => "loadFiles", "params" => ["id" => $id, "type" => "landing"]];
        }]);
        $repo->register(["method" => "GET", "route" => "/landing_pages/[i:id]/get_file", "desc" => "Load file data local landing", "onMatch" => function ($id) {
            return ["controller" => "editor", "action" => "loadFileData", "params" => ["id" => $id, "type" => "landing"]];
        }]);
        $repo->register(["method" => "PUT", "route" => "/landing_pages/[i:id]/update_file", "desc" => "Save file data local landing", "onMatch" => function ($id) {
            return ["controller" => "editor", "action" => "saveFileData", "params" => ["id" => $id, "type" => "landing"]];
        }]);
        $repo->register(["method" => "POST", "route" => "/landing_pages/[i:id]/add_file", "desc" => "Create file local landing", "onMatch" => function ($id) {
            return ["controller" => "editor", "action" => "createFile", "params" => ["id" => $id, "type" => "landing"]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/landing_pages/[i:id]/remove_file", "desc" => "Remove file local landing", "onMatch" => function ($id) {
            return ["controller" => "editor", "action" => "removeFile", "params" => ["id" => $id, "type" => "landing"]];
        }]);
        $repo->register(["method" => "GET", "route" => "/landing_pages/[i:id]/download", "desc" => "Download local landing", "onMatch" => function ($id) {
            return ["controller" => "landings", "action" => "download", "params" => ["id" => $id]];
        }]);
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneLandings());
    }
}

?>