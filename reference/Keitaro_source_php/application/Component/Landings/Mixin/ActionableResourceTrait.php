<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\Mixin;

interface ActionableResourceTrait
{
    private $_mappingTypes = ["preloaded" => "curl", "local" => "local_file"];
    protected function _createResource($data);
    protected function _updateResource(\Core\Entity\Model\EntityModelInterface $_updateResource, \Core\Entity\Model\EntityModelInterface $entity, $data);
    public function checkModelCorrectness($data);
    public function cloneResource(\Core\Entity\Model\EntityModelInterface $entity, $addData);
    private function _cloneContentFor(\Core\Entity\Model\EntityModelInterface $model);
    public function addPreviewData(\Core\Entity\Model\EntityModelInterface $entity, $data);
    public function removeFolder(\Core\Entity\Model\EntityModelInterface $entity);
    private function _generateFolderName($name);
    private function _makeUniqueFolder($folderName);
    private function _createNewFolderName($folderName);
    private function _getActionTypeByItemType($type);
}

?>