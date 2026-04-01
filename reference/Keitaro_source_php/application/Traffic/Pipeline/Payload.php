<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline;

class Payload
{
    private $_serverRequest = NULL;
    private $_stream = NULL;
    private $_campaign = NULL;
    private $_offer = NULL;
    private $_landing = NULL;
    private $_response = NULL;
    private $_rawClick = NULL;
    private $_rawClicksToStore = [];
    private $_actionType = NULL;
    private $_actionPayload = NULL;
    private $_actionOptions = NULL;
    private $_forcedOfferId = NULL;
    private $_forcedCampaignId = NULL;
    private $_forcedStreamId = NULL;
    private $_tokenNeeded = NULL;
    private $_addTokenToUrl = NULL;
    private $_forceRedirectOffer = NULL;
    private $_forceChooseOffer = NULL;
    private $_cookieBindStream = false;
    private $_cookieBindLanding = false;
    private $_cookieBindOffer = false;
    private $_saveToken = false;
    private $_saveUniquenessId = false;
    private $_aborted = false;
    private $_allowedOptions = ["server_request", "campaign", "stream", "landing", "offer", "token", "response", "raw_click", "action_type", "action_payload", "action_options", "raw_clicks_to_store", "forced_offer_id", "token_needed", "add_token_to_url", "forced_campaign_id", "forced_stream_id", "force_choose_offer", "force_redirect_offer"];
    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            $name = "_" . \Traffic\Tools\Tools::toCamelCase($key, true);
            if (!in_array($key, $this->_allowedOptions)) {
                throw new Stage\StageException("Incorrect option " . $key);
            }
            $this->{$name} = $value;
        }
    }
    public function getServerRequest()
    {
        return $this->_serverRequest;
    }
    public function setServerRequest(\Traffic\Request\ServerRequest $request)
    {
        if (empty($request)) {
            throw new \Exception("Trying to set empty ServerRequest");
        }
        return $this->_serverRequest = $request;
    }
    public function getRawClick()
    {
        return $this->_rawClick;
    }
    public function setRawClick(\Traffic\RawClick $rawClick)
    {
        return $this->_rawClick = $rawClick;
    }
    public function addRawClickToStore(\Traffic\RawClick $rawClick)
    {
        $this->_rawClicksToStore[] = $rawClick;
    }
    public function getRawClicksToStore()
    {
        return $this->_rawClicksToStore;
    }
    public function getCampaign()
    {
        return $this->_campaign;
    }
    public function setCampaign(\Traffic\Model\Campaign $campaign = NULL)
    {
        $this->_campaign = $campaign;
    }
    public function getStream()
    {
        return $this->_stream;
    }
    public function setStream(\Traffic\Model\BaseStream $stream = NULL)
    {
        $this->_stream = $stream;
    }
    public function getOffer()
    {
        return $this->_offer;
    }
    public function setOffer(\Traffic\Model\Offer $offer = NULL)
    {
        $this->_offer = $offer;
    }
    public function getLanding()
    {
        return $this->_landing;
    }
    public function setLanding(\Traffic\Model\Landing $landing = NULL)
    {
        $this->_landing = $landing;
    }
    public function getResponse()
    {
        return $this->_response;
    }
    public function setResponse(\Traffic\Response\Response $response)
    {
        if (empty($response)) {
            throw new \Exception("Trying to set empty Response");
        }
        $this->_response = $response;
    }
    public function getActionPayload()
    {
        return $this->_actionPayload;
    }
    public function setActionPayload($actionPayload = NULL)
    {
        $this->_actionPayload = $actionPayload;
    }
    public function getActionOptions()
    {
        return $this->_actionOptions;
    }
    public function getActionOption($key)
    {
        return isset($this->_actionOptions[$key]) ? $this->_actionOptions[$key] : NULL;
    }
    public function setActionOptions($actionOptions = NULL)
    {
        $this->_actionOptions = $actionOptions;
    }
    public function getActionType()
    {
        return $this->_actionType;
    }
    public function setActionType($actionType = NULL)
    {
        $this->_actionType = $actionType;
    }
    public function getForcedOfferId()
    {
        return $this->_forcedOfferId;
    }
    public function setOfferId($id)
    {
        $this->_forcedOfferId = $id;
    }
    public function isTokenNeeded()
    {
        return $this->_tokenNeeded;
    }
    public function setNeedToken($status = 1)
    {
        $this->_tokenNeeded = $status;
    }
    public function shouldAddTokenToURL()
    {
        return $this->_addTokenToUrl;
    }
    public function setAddTokenToUrl($status = 1)
    {
        $this->_addTokenToUrl = $status;
    }
    public function isAborted()
    {
        return $this->_aborted;
    }
    public function abort($status = true)
    {
        $this->_aborted = $status;
    }
    public function setForcedCampaignId($id)
    {
        $this->_forcedCampaignId = $id;
    }
    public function getForcedCampaignId()
    {
        return $this->_forcedCampaignId;
    }
    public function setForcedStreamId($id)
    {
        $this->_forcedStreamId = $id;
    }
    public function getForcedStreamId()
    {
        return $this->_forcedStreamId;
    }
    public function setForceRedirectOffer($state = true)
    {
        $this->_forceRedirectOffer = $state;
    }
    public function isForceRedirectOffer()
    {
        return $this->_forceRedirectOffer;
    }
    public function setForceChooseOffer($state = true)
    {
        $this->_forceChooseOffer = $state;
    }
    public function isForceChooseOffer()
    {
        return $this->_forceChooseOffer;
    }
    public function enableCookieBindStream()
    {
        $this->_cookieBindStream = true;
    }
    public function isCookieStreamBinded()
    {
        return $this->_cookieBindStream;
    }
    public function enableCookieBindLanding()
    {
        $this->_cookieBindLanding = true;
    }
    public function isCookieLandingBinded()
    {
        return $this->_cookieBindLanding;
    }
    public function enableCookieBindOffer()
    {
        $this->_cookieBindOffer = true;
    }
    public function isCookieOfferBinded()
    {
        return $this->_cookieBindOffer;
    }
    public function enableSaveToken()
    {
        $this->_saveToken = true;
    }
    public function isSaveTokenRequired()
    {
        return $this->_saveToken;
    }
    public function enableSaveUniquenessId()
    {
        $this->_saveUniquenessId = true;
    }
    public function isSaveUniquenessRequired()
    {
        return $this->_saveUniquenessId;
    }
}

?>