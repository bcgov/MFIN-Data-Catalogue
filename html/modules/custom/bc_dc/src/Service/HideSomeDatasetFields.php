<?php

namespace Drupal\bc_dc\Service;

class HideSomeDatasetFields {

  const SHOW_FIELDS_FOR_DATA_TYPES = [
        'field_columns'                   => [20],
        'field_critical_information'      => [20],
        'field_data_quality_issues'       => [20],
        'field_data_set_historical_change'=> [20],
        'field_source_system'             => [20],
        'field_data_sets_used'            => [20,10],
        'field_form_number'               => [5],
      ];

  /**
   * Hides some fields on the data set build forms.
   * 
   * On the 'Section 1', 'Section 2', etc. forms on the Build tab,
   * some fields need to be hidden some of the time.
   * 
   * e.g. 'Data Sets Used' field should be 
   *    shown for Data or Report record types (Term IDs 20 or 10), 
   *    but hidden for Forms (Term ID 5). 
   */
  public function hideOnEditForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state): void {

    $form_object = $form_state->getFormObject();
    $form_mode = $form_object->getFormDisplay($form_state)->getMode();

    // Check if we are in one of the section forms (section_1, section_2, etc.)
    if (preg_match('/^section_\d$/', $form_mode)) {
      $data_set = $form_object->getEntity();
      $this_dataset_record_types = array_column($data_set->get('field_data_set_type')->getValue(), 'target_id');

      foreach (self::SHOW_FIELDS_FOR_DATA_TYPES as $field_name => $record_types_to_show) {
        if (isset($form[$field_name])) {
          $show_this_field = array_intersect($this_dataset_record_types, $record_types_to_show) !== [];
          $form[$field_name]['#access'] = $show_this_field;
        }
      }
    }
  }
}