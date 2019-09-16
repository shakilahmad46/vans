<?php

/**
 * @file
 * Definition of field_collection_views_handler_field_edit_link.
 */

/**
 * Field handler to present a link node edit.
 *
 * @ingroup views_field_handlers
 */
class field_collection_views_handler_field_edit_link extends field_collection_views_handler_field_field_path {
  /**
   * Renders the link.
   */
  function render_link($field_collection_item, $values) {
    // Ensure user has access to edit this field collection.
   if (!field_collection_item_access('update', $field_collection_item)) {
     return;
   }

    $field_path = field_collection_field_get_path($field_collection_item->fieldInfo());
       //?destination=[host_entity_path]
    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "$field_path/$field_collection_item->item_id/edit";
    $this->options['alter']['query'] = drupal_get_destination();
    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
    return $text;
  }
}
