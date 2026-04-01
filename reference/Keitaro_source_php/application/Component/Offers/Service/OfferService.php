<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Offers\Service;

class OfferService extends \Core\Entity\Service\EntityService
{
    use \Component\Landings\Mixin\ActionableResourceTrait;
    public function definition()
    {
        return \Traffic\Model\Offer::definition();
    }
    public function archive(\Core\Entity\Model\EntityModelInterface $entity)
    {
        self::archive($entity);
        StreamOfferAssociationService::instance()->deleteByOffer($entity);
    }
    public function delete(\Core\Entity\Model\EntityModelInterface $entity)
    {
        $this->removeFolder($entity);
        return self::delete($entity);
    }
    public function create($data)
    {
        unset($data["url_desc"]);
        unset($data["contains_tid_param"]);
        $data = $this->_checkCountry($data);
        $this->_checkAlternativeOffer($data);
        return $this->_createResource($data);
    }
    public function update(\Core\Entity\Model\EntityModelInterface $entity, $data)
    {
        unset($data["url_desc"]);
        unset($data["contains_tid_param"]);
        $data = $this->_checkCountry($data);
        $this->_checkAlternativeOffer($data);
        return $this->_updateResource($entity, $data);
    }
    public function addParameterToUrl($url, $offerParam)
    {
        if (!empty($offerParam)) {
            if (strpbrk($offerParam, "/:") === false) {
                $url = \Traffic\Service\UrlService::instance()->addParameterToUrl($url, $offerParam);
            } else {
                if (substr($url, -1) != "/") {
                    $url .= "/";
                }
                $url .= $offerParam;
                $url = \Traffic\Service\UrlService::instance()->filterDoubleSlashes($url);
            }
        }
        return $url;
    }
    private function _checkCountry($data)
    {
        if (!empty($data["country"]) && !is_array($data["country"]) && strlen($data["country"]) == 2) {
            $data["country"] = [$data["country"]];
        }
        return $data;
    }
    private function _checkAlternativeOffer($data)
    {
        if (!empty($data["conversion_cap_enabled"]) && empty($data["alternative_offer_id"])) {
            throw new \Core\Application\Exception\Error(\Core\Locale\LocaleService::t("offers.no_alternative_offer"));
        }
    }
}

?>