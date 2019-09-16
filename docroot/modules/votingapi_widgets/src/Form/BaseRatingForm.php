<?php

namespace Drupal\votingapi_widgets\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\votingapi\VoteResultFunctionManager;

/**
 * Form controller for Campaign edit forms.
 *
 * @ingroup adspree_link_manager
 */
class BaseRatingForm extends ContentEntityForm {

  /**
   * @var VoteResultFunctionManager $votingapiResult
   */
  protected $votingapiResult;

  /**
   * Class constructor.
   */
  public function __construct(VoteResultFunctionManager $votingapi_result, EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->votingapiResult = $votingapi_result;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.votingapi.resultfunction'),
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  public function getFormId() {
    $form_id = parent::getFormId();
    $entity = $this->getEntity();
    $voted_entity_type = $entity->getVotedEntityType();
    $voted_entity_id = $entity->getVotedEntityId();
    $voted_entity = $this->entityManager->getStorage($voted_entity_type)->load($voted_entity_id);

    $additional_form_id_parts = [];
    $additional_form_id_parts[] = $voted_entity->getEntityTypeId();
    $additional_form_id_parts[] = $voted_entity->bundle();
    $additional_form_id_parts[] = $voted_entity->id();
    $additional_form_id_parts[] = $entity->bundle();
    $additional_form_id_parts[] = $entity->field_name->value;
    $form_id = implode('_', $additional_form_id_parts) . '__' . $form_id;
    return $form_id;
  }

  public $plugin;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->getEntity();
    $result_function = $this->getResultFunction($form_state);
    $options = $form_state->get('options');
    $form_id = Html::getUniqueId('vote-form');
    $plugin = $form_state->get('plugin');
    $settings = $form_state->get('settings');

    $form['#cache']['contexts'][] = 'user.permissions';
    $form['#cache']['contexts'][] = 'user.roles:authenticated';

    $form['#attributes']['id'] = $form_id;

    $form['value'] = [
      '#type' => 'select',
      '#options' => $options,
      '#attributes' => [
        'autocomplete' => 'off',
        'data-result-value' => ($this->getResults($result_function)) ? $this->getResults($result_function) : -1,
        'data-vote-value' => ($entity->getValue()) ? $entity->getValue() : (($this->getResults($result_function)) ? $this->getResults($result_function) : -1),
        'data-style' => ($settings['style']) ? $settings['style'] : 'default',
      ],
    ];

    $form['value']['#attributes']['data-show-own-vote'] = 'true';
    $form['value']['#default_value'] = (int) $entity->getValue();

    if (!$settings['show_own_vote']) {
      $form['value']['#attributes']['data-show-own-vote'] = 'false';
      $form['value']['#default_value'] = $this->getResults($result_function);
    }

    if ($settings['readonly'] || !$plugin->canVote($entity)) {
      $form['value']['#attributes']['disabled'] = 'disabled';
    }

    if ($settings['show_results']) {
      $form['result'] = [
        '#theme' => 'container',
        '#attributes' => [
          'class' => ['vote-result'],
        ],
        '#children' => [],
        '#weight' => 100,
      ];

      $form['result']['#children']['result'] = $plugin->getVoteSummary($entity);
    }

    $form['submit'] = $form['actions']['submit'];
    $form['actions']['#access'] = FALSE;

    $form['submit'] += [
      '#type' => 'button',
      '#ajax' => [
        'callback' => array($this, 'ajaxSubmit'),
        'event' => 'click',
        'wrapper' => $form_id,
        'progress' => [
          'type' => NULL,
        ],
      ],
    ];
    return $form;
  }

  /**
   * Get result function.
   */
  protected function getResultFunction(FormStateInterface $form_state) {
    $entity = $this->getEntity();
    return ($form_state->get('resultfunction')) ? $form_state->get('resultfunction') : 'vote_field_average:' . $entity->getVotedEntityType() . '.' . $entity->field_name->value;
  }

  /**
   * Get results.
   */
  public function getResults($result_function = FALSE, $reset = FALSE) {
    $entity = $this->entity;
    if ($reset) {
      drupal_static_reset(__FUNCTION__);
    }
    $resultCache = &drupal_static(__FUNCTION__);

    if (!$result_function && isset($resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()])) {
      return $resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()];
    }

    if (!$result_function) {
      $results = $this->votingapiResult->getResults($entity->getVotedEntityType(), $entity->getVotedEntityId());
      if (!array_key_exists($entity->getEntityTypeId(), $results)) {
        return [];
      }
      $resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()] = $results[$entity->getEntityTypeId()];
      return $resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()];
    }

    if (isset($resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()])
        && isset($resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()][$result_function])) {
      return $resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()][$result_function];
    }

    $results = $this->votingapiResult->getResults($entity->getVotedEntityType(), $entity->getVotedEntityId());
    if (isset($results[$entity->getEntityTypeId()][$result_function])) {
      $resultCache[$entity->getEntityTypeId()] = [
        $entity->getVotedEntityId() => $results[$entity->getEntityTypeId()],
      ];
      return $resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()][$result_function];
    }
    return [];
  }

  /**
   * Ajax submit handler.
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    $this->save($form, $form_state);
    $settings = $form_state->get('settings');
    $result_function = $this->getResultFunction($form_state);
    $plugin = $form_state->get('plugin');
    $entity = $this->getEntity();
    $result_value = $this->getResults($result_function, TRUE);

    $form['value']['#attributes']['data-show-own-vote'] = 'true';
    $form['value']['#default_value'] = (int) $entity->getValue();

    if ($settings['show_own_vote'] === '0') {
      $form['value']['#attributes']['data-show-own-vote'] = 'false';
      $form['value']['#default_value'] = $result_value;
    }

    $form['value']['#attributes']['data-vote-value'] = $entity->getValue();
    $form['value']['#attributes']['data-result-value'] = $result_value;
    if ($settings['show_results'] === '1') {
      $form['result']['#children']['result'] = $plugin->getVoteSummary($entity);
    }

    if (!$plugin->canVote($entity)) {
      $form['value']['#attributes']['disabled'] = 'disabled';
    }

    $form_state->setRebuild(TRUE);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $plugin = $form_state->get('plugin');

    if ($plugin->canVote($entity)) {
      return parent::save($form, $form_state);
    }

    return FALSE;
  }

}
