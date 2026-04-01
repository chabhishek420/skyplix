<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("favouriteStreams", new Controller\FavouriteStreamsController());
        $repo->register("streams", new Controller\StreamsController());
        $repo->register("streamEvents", new Controller\StreamEventsController());
        $repo->register("collections", new Controller\CollectionsController());
        $repo->register("streamTypes", new Controller\StreamTypesController());
        $repo->register("streamSchemas", new Controller\StreamSchemasController());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/campaigns/[i:campaign_id]/streams", "desc" => "Retrieve streams of a campaign", "onMatch" => function ($campaign_id) {
            return ["controller" => "streams", "action" => "index", "params" => ["campaign_id" => $campaign_id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/streams", "desc" => "Create a stream", "onMatch" => function () {
            return ["controller" => "streams", "action" => "create"];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/streams/[i:id]", "desc" => "Move steam to archive", "onMatch" => function ($id) {
            return ["controller" => "streams", "action" => "delete", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "PUT", "route" => "/streams/[i:id]", "desc" => "Update a stream", "onMatch" => function ($id) {
            return ["controller" => "streams", "action" => "update", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "GET", "route" => "/streams/deleted", "desc" => "Retrieve deleted streams", "onMatch" => function () {
            return ["controller" => "streams", "action" => "deleted"];
        }]);
        $repo->register(["method" => "GET", "route" => "/streams/[i:id]", "desc" => "Retrieve a specific stream", "onMatch" => function ($id) {
            return ["controller" => "streams", "action" => "show", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/streams/[i:id]/restore", "desc" => "Restore an archived stream", "onMatch" => function ($id) {
            return ["controller" => "streams", "action" => "restore", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/streams/[i:id]/enable", "desc" => "Enable a stream", "onMatch" => function ($id) {
            return ["controller" => "streams", "action" => "enable", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/streams/[i:id]/disable", "desc" => "Disable a stream", "onMatch" => function ($id) {
            return ["controller" => "streams", "action" => "disable", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "GET", "route" => "/streams/search", "desc" => "Search streams", "onMatch" => function () {
            return ["controller" => "streams", "action" => "search"];
        }]);
        $repo->register(["method" => "GET", "route" => "/stream/:id/events", "desc" => "Retrieve stream events", "onMatch" => function ($id) {
            return ["controller" => "streamEvents", "action" => "index", "params" => ["stream_id" => $id]];
        }]);
        $repo->register(["method" => "GET", "route" => "/stream_types", "desc" => "Retrieve available stream types", "onMatch" => function () {
            return ["controller" => "streamTypes", "action" => "listAsOptions"];
        }]);
        $repo->register(["method" => "GET", "route" => "/stream_actions", "desc" => "Retrieve available stream actions", "onMatch" => function () {
            return ["controller" => "streamActions", "action" => "index"];
        }]);
        $repo->register(["method" => "GET", "route" => "/stream_schemas", "desc" => "Retrieve available stream schemas", "onMatch" => function () {
            return ["controller" => "streamSchemas", "action" => "listAsOptions"];
        }]);
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneStreams());
        $repo->register(new PruneTask\PruneStreamEvents());
        $repo->register(new PruneTask\PruneLandingOfferCache());
    }
}

?>