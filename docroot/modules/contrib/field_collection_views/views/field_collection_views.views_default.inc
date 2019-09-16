<?php
// $Id$

/**
 * @file
 * Contains the field_collection_view view.
 */

/**
 * Implementation of hook_views_default_views().
 */
function field_collection_views_views_default_views() {
  $view = new view();
  $view->name = 'field_collection_view';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'field_collection_item';
  $view->human_name = 'field collection view';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['distinct'] = TRUE;
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'item_id' => 'item_id',
    'field_resume_job_title' => 'field_resume_job_title',
    'field_resume_time_period' => 'field_resume_time_period',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'item_id' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'field_resume_job_title' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'field_resume_time_period' => array(
      'align' => '',
      'separator' => '',
    ),
  );
  /* Footer: Field collection item: Add Field Collection */
  $handler->display->display_options['footer']['add_link']['id'] = 'add_link';
  $handler->display->display_options['footer']['add_link']['table'] = 'field_collection_item';
  $handler->display->display_options['footer']['add_link']['field'] = 'add_link';
  $handler->display->display_options['footer']['add_link']['label'] = 'Add';
  /* Relationship: Field collection item: Entity with the FC (field_fc) */
  $handler->display->display_options['relationships']['field_fc_node']['id'] = 'field_fc_node';
  $handler->display->display_options['relationships']['field_fc_node']['table'] = 'field_collection_item';
  $handler->display->display_options['relationships']['field_fc_node']['field'] = 'field_fc_node';
  $handler->display->display_options['relationships']['field_fc_node']['required'] = TRUE;
  /* Field: Field collection item: Field collection item ID */
  $handler->display->display_options['fields']['item_id']['id'] = 'item_id';
  $handler->display->display_options['fields']['item_id']['table'] = 'field_collection_item';
  $handler->display->display_options['fields']['item_id']['field'] = 'item_id';
  $handler->display->display_options['fields']['item_id']['label'] = 'Id';
  $handler->display->display_options['fields']['item_id']['element_label_colon'] = FALSE;
  /* Field: Field collection item: Edit Field Collection */
  $handler->display->display_options['fields']['edit_link']['id'] = 'edit_link';
  $handler->display->display_options['fields']['edit_link']['table'] = 'field_collection_item';
  $handler->display->display_options['fields']['edit_link']['field'] = 'edit_link';
  $handler->display->display_options['fields']['edit_link']['label'] = 'edit';
  /* Field: Field collection item: Add Field Collection */
  $handler->display->display_options['fields']['delete_link']['id'] = 'delete_link';
  $handler->display->display_options['fields']['delete_link']['table'] = 'field_collection_item';
  $handler->display->display_options['fields']['delete_link']['field'] = 'delete_link';
  $handler->display->display_options['fields']['delete_link']['label'] = 'delete';
  /* Sort criterion: Field collection item: Field collection item ID */
  $handler->display->display_options['sorts']['item_id']['id'] = 'item_id';
  $handler->display->display_options['sorts']['item_id']['table'] = 'field_collection_item';
  $handler->display->display_options['sorts']['item_id']['field'] = 'item_id';
  /* Contextual filter: Field collection item: Field collection item ID */
  $handler->display->display_options['arguments']['item_id']['id'] = 'item_id';
  $handler->display->display_options['arguments']['item_id']['table'] = 'field_collection_item';
  $handler->display->display_options['arguments']['item_id']['field'] = 'item_id';
  $handler->display->display_options['arguments']['item_id']['default_action'] = 'not found';
  $handler->display->display_options['arguments']['item_id']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['item_id']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['item_id']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['item_id']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['item_id']['break_phrase'] = TRUE;
  $views[$view->name] = $view;
  return $views;
}
