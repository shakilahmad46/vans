<?php

namespace Drupal\acquia_search_solr\EventSubscriber;

use Drupal\acquia_search_solr\AcquiaCryptConnector;
use Drupal\acquia_search_solr\Helper\Runtime;
use Drupal\acquia_search_solr\Helper\Storage;
use Drupal\Component\Utility\Crypt;
use Solarium\Core\Client\Response;
use Solarium\Core\Event\Events;
use Solarium\Core\Event\preExecuteRequest;
use Solarium\Core\Event\postExecuteRequest;
use Solarium\Core\Plugin\AbstractPlugin;
use Solarium\Exception\HttpException;
use Solarium\Core\Client\Adapter\AdapterHelper;

/**
 * Class AcquiaSearchSolrSubscriber.
 *
 * Extends Solarium plugin for the Acquia Search module: authenticate, etc.
 *
 * @package Drupal\acquia_search_solr\EventSubscriber
 */
class AcquiaSearchSolrSubscriber extends AbstractPlugin {

  /**
   * {@inheritdoc}
   *
   * @var \Solarium\Client
   */
  protected $client;

  /**
   * Array of derived keys, keyed by environment id.
   *
   * @var array
   */
  protected $derivedKey = [];

  /**
   * Nonce.
   *
   * @var string
   */
  protected $nonce = '';

  /**
   * URI.
   *
   * @var string
   */
  protected $uri = '';

  /**
   * {@inheritdoc}
   */
  public function initPluginType() {

    $dispatcher = $this->client->getEventDispatcher();
    $dispatcher->addListener(Events::PRE_EXECUTE_REQUEST, [$this, 'preExecuteRequest']);
    $dispatcher->addListener(Events::POST_EXECUTE_REQUEST, [$this, 'postExecuteRequest']);

  }

  /**
   * Build Acquia Search Solr Authenticator.
   *
   * @param \Solarium\Core\Event\preExecuteRequest $event
   *   PreExecuteRequest event.
   */
  public function preExecuteRequest(preExecuteRequest $event) {

    $request = $event->getRequest();
    $request->addParam('request_id', uniqid(), TRUE);
    if ($request->getFileUpload()) {
      $helper = new AdapterHelper();
      $body = $helper->buildUploadBodyFromRequest($request);
      $request->setRawData($body);
    }

    // If we're hosted on Acquia, and have an Acquia request ID,
    // append it to the request so that we map Solr queries to Acquia search
    // requests.
    if (isset($_ENV['HTTP_X_REQUEST_ID'])) {
      $xid = empty($_ENV['HTTP_X_REQUEST_ID']) ? '-' : $_ENV['HTTP_X_REQUEST_ID'];
      $request->addParam('x-request-id', $xid);
    }
    $endpoint = $this->client->getEndpoint();
    $this->uri = $endpoint->getCoreBaseUri() . $request->getUri();

    $this->nonce = Crypt::randomBytesBase64(24);
    $raw_post_data = $request->getRawData();
    // We don't have any raw POST data for pings only.
    if (!$raw_post_data) {
      $parsed_url = parse_url($this->uri);
      $path = $parsed_url['path'] ?? '/';
      $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
      $raw_post_data = $path . $query;
    }

    $cookie = $this->calculateAuthCookie($raw_post_data, $this->nonce);
    $request->addHeader('Cookie: ' . $cookie);
    $request->addHeader('User-Agent: ' . 'acquia_search_solr/' . Storage::getVersion());

  }

  /**
   * Validate response.
   *
   * @param \Solarium\Core\Event\postExecuteRequest $event
   *   postExecuteRequest event.
   *
   * @throws \Solarium\Exception\HttpException
   */
  public function postExecuteRequest(postExecuteRequest $event) {

    $response = $event->getResponse();

    if ($response->getStatusCode() != 200) {
      throw new HttpException($response->getStatusMessage());
    }

    if ($event->getRequest()->getHandler() == 'admin/ping') {
      return;
    }

    $this->authenticateResponse($event->getResponse(), $this->nonce, $this->uri);

  }

  /**
   * Validate the hmac for the response body.
   *
   * @param \Solarium\Core\Client\Response $response
   *   Solarium Response.
   * @param string $nonce
   *   Nonce.
   * @param string $url
   *   Url.
   *
   * @return \Solarium\Core\Client\Response
   *   Solarium Response.
   *
   * @throws HttpException
   */
  protected function authenticateResponse(Response $response, $nonce, $url) {

    $hmac = $this->extractHmac($response->getHeaders());
    if (!$this->validateResponse($hmac, $nonce, $response->getBody())) {
      throw new HttpException('Authentication of search content failed url: ' . $url);
    }

    return $response;

  }

  /**
   * Look in the headers and get the hmac_digest out.
   *
   * @param array $headers
   *   Headers array.
   *
   * @return string
   *   Hmac_digest or empty string.
   */
  public function extractHmac(array $headers): string {

    $reg = [];

    if (is_array($headers)) {
      foreach ($headers as $value) {
        if (stristr($value, 'pragma') && preg_match("/hmac_digest=([^;]+);/i", $value, $reg)) {
          return trim($reg[1]);
        }
      }
    }

    return '';

  }

  /**
   * Validate the authenticity of returned data using a nonce and HMAC-SHA1.
   *
   * @param string $hmac
   *   HMAC.
   * @param string $nonce
   *   Nonce.
   * @param string $string
   *   Data string.
   * @param string $derived_key
   *   Derived key.
   * @param string $env_id
   *   Environment Id.
   *
   * @return bool
   *   TRUE if response is valid.
   */
  public function validateResponse($hmac, $nonce, $string, $derived_key = NULL, $env_id = NULL) {

    if (empty($derived_key)) {
      $derived_key = $this->getDerivedKey($env_id);
    }

    return $hmac == hash_hmac('sha1', $nonce . $string, $derived_key);

  }

  /**
   * Get the derived key.
   *
   * Get the derived key for the solr hmac using the information shared with
   * acquia.com.
   *
   * @param string $env_id
   *   Environment Id.
   *
   * @return string|null
   *   Derived Key.
   */
  public function getDerivedKey($env_id = NULL): ?string {

    if (empty($env_id)) {
      $env_id = $this->client->getEndpoint()->getKey();
    }

    // Get derived key for Acquia Search V3.
    $search_v3_index = $this->getSearchIndexKeys();
    if ($search_v3_index) {
      $this->derivedKey[$env_id] = AcquiaCryptConnector::createDerivedKey($search_v3_index['product_policies']['salt'], $search_v3_index['key'], $search_v3_index['secret_key']);
      return $this->derivedKey[$env_id];
    }

    return NULL;

  }

  /**
   * Creates an authenticator based on a data string and HMAC-SHA1.
   *
   * @param string $string
   *   Data string.
   * @param string $nonce
   *   Nonce.
   * @param string $derived_key
   *   Derived key.
   * @param string $env_id
   *   Environment Id.
   *
   * @return string
   *   Auth cookie string.
   */
  public function calculateAuthCookie($string, $nonce, $derived_key = NULL, $env_id = NULL) {

    if (empty($derived_key)) {
      $derived_key = $this->getDerivedKey($env_id);
    }

    if (empty($derived_key)) {
      // Expired or invalid subscription - don't continue.
      return '';
    }

    $time = REQUEST_TIME;

    return 'acquia_solr_time=' . $time . '; acquia_solr_nonce=' . $nonce . '; acquia_solr_hmac=' . hash_hmac('sha1', $time . $nonce . $string, $derived_key) . ';';

  }

  /**
   * Fetches the Acquia Search v3 index keys.
   *
   * @return array|null
   *   Search v3 index keys.
   */
  public function getSearchIndexKeys(): ?array {

    $core_service = Runtime::getPreferredSearchCoreService();

    // Preferred core isn't available - you have to configure it using settings
    // described in the README.txt.
    if (!$core_service->isPreferredCoreAvailable()) {
      return NULL;
    }

    return $core_service->getPreferredCore()['data'];

  }

}
