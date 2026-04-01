<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class Domain extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_className = "Domain";
    protected static $_tableName = "domains";
    protected static $_aclKey = "domains";
    protected static $_entityName = "domain";
    const NETWORK_STATUS_ACTIVE = "active";
    const NETWORK_STATUS_ERROR = "error";
    const NETWORK_STATUS_VALIDATING = "validating";
    const SSL_STATUS_AWAITING_DNS = "awaiting_dns";
    const SSL_STATUS_AWAITING_SSL = "awaiting_ssl";
    const SSL_STATUS_BLOCK_SSL = "blocked";
    const SSL_STATUS_LIMIT_SSL = "request_limit";
    const SSL_STATUS_REQUESTING_SSL = "requesting_ssl";
    const SSL_STATUS_ISSUED = "issued";
    const SSL_STATUS_ERROR = "error";
    const SSL_SCHEMA = "https";
    public static function serializer()
    {
        return new \Component\Domains\Serializer\DomainSerializer();
    }
    public static function service()
    {
        return \Component\Domains\Service\DomainService::instance();
    }
    public static function validator()
    {
        return new \Component\Domains\Validator\DomainValidator();
    }
    public static function repository()
    {
        return \Component\Domains\Repository\DomainsRepository::instance();
    }
    public function getName()
    {
        return $this->get("name");
    }
    public function getSSLData()
    {
        $data = is_array($this->get("ssl_data")) ? $this->get("ssl_data") : [];
        return new \Component\Domains\DTO\DomainSSLDataPayload($data);
    }
    public function isActive()
    {
        if ($this->get("network_status") === NETWORK_STATUS_ACTIVE) {
            return true;
        }
        return false;
    }
    public function isSSL()
    {
        return $this->get("is_ssl");
    }
    public function isWildcard()
    {
        return $this->get("wildcard");
    }
    public function getDefaultCampaignId()
    {
        return $this->get("default_campaign_id");
    }
    public function isAssociated()
    {
        return $this->get("network_status") === NETWORK_STATUS_ACTIVE;
    }
    public function getSSLRedirect()
    {
        if ($this->get("ssl_redirect")) {
            return SSL_SCHEMA;
        }
        return false;
    }
    public function getAllowIndexing()
    {
        return $this->get("allow_indexing");
    }
    public function getSSLStatus()
    {
        return $this->get("ssl_status");
    }
}

?>