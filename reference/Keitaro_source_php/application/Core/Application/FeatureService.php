<?php

namespace Core\Application;

class FeatureService extends \Traffic\Service\AbstractService
{
    private $_payload = NULL;
    const USERS_LIMIT = 6;
    public function __construct(EssentialPayload $payload = NULL)
    {
        if ($payload) {
            $this->init($payload);
        }
    }
    public function init(EssentialPayload $payload)
    {
        $this->_payload = $payload;
    }
    public function isTrial()
    {
        return $this->_payload->edition() == EssentialPayload::TRIAL;
    }
    public function isBasic()
    {
        return $this->_payload->edition() == EssentialPayload::BASIC;
    }
    public function isPro()
    {
        return $this->_payload->edition() == EssentialPayload::PRO;
    }
    public function isBusiness()
    {
		return true;
        return $this->_payload->edition() == EssentialPayload::BUSINESS;
    }
    public function getEdition()
    {
        return $this->_payload->edition();
    }
    public function hasDomainsFeature()
    {
        return $this->isTrial() || $this->isPro() || $this->isBusiness();
    }
    public function hasExtensionsFeature()
    {
        return $this->isTrial() || $this->isPro() || $this->isBusiness();
    }
    public function hasUsersFeature()
    {
        return $this->isTrial() || $this->isPro() || $this->isBusiness();
    }
    public function hasBrandingFeature()
    {
        return $this->isBusiness();
    }
    public function hasUnlimitedUserFeature()
    {
        if ($this->isBusiness()) {
            return true;
        }
        if ($this->isTrial()) {
            return false;
        }
        if ($this->isBasic()) {
            return false;
        }
        return !$this->_payload->mustLimitUsers();
    }
    public function hasClickApiFeature()
    {
		return true;
        if ($this->isBasic() && $this->_payload->clickApiForProOnly()) {
            return false;
        }
        return true;
    }
    public function hasAdminApiFeature()
    {
		return true;
        if ($this->isTrial() || $this->isBasic()) {
            return false;
        }
        if ($this->isBusiness()) {
            return true;
        }
        if ($this->isPro() && !$this->_payload->adminApiBusinessOnly()) {
            return true;
        }
        return false;
    }
    public function getUsersLimit()
    {
		return 9999;
        if ($this->hasUnlimitedUserFeature()) {
            return 9999;
        }
        return USERS_LIMIT;
    }
    public function getLicenseExpireTime()
    {
        $date = new \DateTime();
        return $date->setTimestamp(time() + 60 * 60 * 24 * 256 * 3);
    }
}

?>