<?php

/**
 * @file
 * Definition of field_collection_views_handler_area.
 */

/**
 * Field handler to present a field collection add link.
 *
 * @ingroup views_field_handlers
 */
class field_collection_views_handler_area_add_link extends views_handler_area {
  function render($empty = FALSE) {
    if (!field_collection_item_access('add')) {
      return;
    }
    $path  = $this->view->args[1];
    $text = !empty($this->options['label']) ? $this->options['label'] : t('add');
    return l( $text, $path,  array('query' => drupal_get_destination()));
  }
}
