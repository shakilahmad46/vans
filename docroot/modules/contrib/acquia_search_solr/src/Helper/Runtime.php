<?php

namespace Drupal\acquia_search_solr\Helper;

use Drupal\acquia_search_solr\AcquiaSearchApiClient;
use Drupal\acquia_search_solr\PreferredSearchCore;
use Drupal\Core\Database\Database;
use Drupal\search_api\Entity\Server;

/**
 * Class Runtime.
 *
 * Contains various helpers.
 */
class Runtime {

  /**
   * Preferred search core service.
   *
   * @var \Drupal\acquia_search_solr\PreferredSearchCore
   */
  protected static $preferredSearchCoreService;

  /**
   * Instantiates the PreferredSearchCore class.
   *
   * Helps to determines which search core should be used and whether it is
   * available within the subscription.
   *
   * @return \Drupal\acquia_search_solr\PreferredSearchCore
   *   Preferred search core service.
   */
  public static function getPreferredSearchCoreService(): PreferredSearchCore {

    if (self::$preferredSearchCoreService) {
      return self::$preferredSearchCoreService;
    }

    $ah_env = $_ENV['AH_SITE_ENVIRONMENT'] ?? '';
    $ah_site_name = $_ENV['AH_SITE_NAME'] ?? '';
    $ah_site_group = $_ENV['AH_SITE_GROUP'] ?? '';
    $conf_path = \Drupal::service('site.path');
    $sites_folder_name = substr($conf_path, strrpos($conf_path, '/') + 1);
    $ah_db_name = '';

    if ($ah_env && $ah_site_name && $ah_site_group) {
      $options = Database::getConnection()->getConnectionOptions();
      $ah_db_name = $options['database'];
    }

    if (!$available_cores = Runtime::getAcquiaSearchApiClient(Storage::getUuid())->getSearchIndexes(Storage::getIdentifier())) {
      $available_cores = [];
    }

    return new PreferredSearchCore(Storage::getIdentifier(), $ah_env, $sites_folder_name, $ah_db_name, $available_cores);

  }

  /**
   * Determine if we should enforce read-only mode.
   *
   * @return bool
   *   TRUE if we should enforce read-only mode.
   */
  public static function shouldEnforceReadOnlyMode(): bool {

    $read_only = FALSE;

    // Check if the read-only mode is forced in configuration.
    if (Storage::isReadOnly()) {
      $read_only = TRUE;
    }

    \Drupal::moduleHandler()->alter('acquia_search_solr_should_enforce_read_only', $read_only);

    return $read_only;

  }

  /**
   * Initializes and returns an instance of AcquiaSearchApiClient.
   *
   * @param string $application_uuid
   *   Acquia application UUID.
   *
   * @return \Drupal\acquia_search_solr\AcquiaSearchApiClient
   *   Acquia Search API Client.
   */
  public static function getAcquiaSearchApiClient(string $application_uuid = NULL): AcquiaSearchApiClient {

    if (!$application_uuid) {
      $application_uuid = Storage::getUuid();
    }

    $drupal_http_client = \Drupal::service('http_client');
    $cache = \Drupal::cache();

    $auth_info = [
      'host' => Storage::getApiHost(),
      'app_uuid' => $application_uuid,
      'key' => Storage::getApiKey(),
    ];

    return new AcquiaSearchApiClient($auth_info, $drupal_http_client, $cache);

  }

  /**
   * Determine whether given server belongs to an Acquia search server.
   *
   * @param \Drupal\search_api\Entity\Server $server
   *   A search server configuration entity.
   *
   * @return bool
   *   TRUE if given server config belongs to an Acquia search server.
   */
  public static function isAcquiaServer(Server $server): bool {

    $backend_config = $server->getBackendConfig();

    return !empty($backend_config['connector']) && $backend_config['connector'] === 'solr_acquia_search_solr';

  }

}
