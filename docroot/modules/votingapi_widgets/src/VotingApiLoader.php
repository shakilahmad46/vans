<?php

namespace Drupal\votingapi_widgets;

use Drupal\votingapi_widgets\Plugin\VotingApiWidgetManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Implements lazy loading.
 */
class VotingApiLoader {

  protected $manager;
  protected $entityTypeManager;

  public function __construct(VotingApiWidgetManager $manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->manager = $manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Build rate form.
   */
  public function buildForm($plugin_id, $entity_type, $entity_bundle, $entity_id, $vote_type, $field_name, $settings) {
    $definitions = $this->manager->getDefinitions();
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    $plugin = $this->manager->createInstance($plugin_id, $definitions[$plugin_id]);
    $fieldDefinition = $entity->{$field_name}->getFieldDefinition();
    if (empty($plugin) || empty($entity) || !$entity->hasField($field_name)) {
      return [];
    }
    return $plugin->buildForm($entity_type, $entity_bundle, $entity_id, $vote_type, $field_name, unserialize($settings));
  }

}
