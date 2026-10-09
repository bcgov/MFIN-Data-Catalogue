<?php

namespace Drupal\bc_dc\Service;

/**
 * The data_set content type (aka Metadata record) has a number of fields that
 *   are not relevant for all record types. 
 * 
 * For example, the 'Form Number' field should be hidden for all record types 
 *   except for the 'Form' record type (Term ID 5). 
 * Similarly, the 'Data Sets Used' field should be hidden for the 'Form' record type (Term ID 5), 
 *   but shown for the 'Data' and 'Report' record types (Term IDs 20 and 10).
 * 
 * This service provides a methods to hide some fields, specific to the record type 
 *   of the data set being edited. 
 */
class HideSomeDatasetFields {

  /**
   * Hides some fields on the data set build forms.
   * 
   * On the 'Section 1', 'Section 2', etc. forms on the Build tab,
   * some fields need to be hidden some of the time.
   */
  public function hideOnEditForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();

    // Check if we are in one of the section forms (section_1, section_2, etc.)
    $form_mode = $form_object->getFormDisplay($form_state)->getMode();
    if (preg_match('/^section_\d$/', $form_mode)) {

      $data_set = $form_object->getEntity();
      foreach ($this->getFieldsToHide($data_set) as $field_to_hide) {
        if (isset($form[$field_to_hide])) {
          $form[$field_to_hide]['#access'] = FALSE;
        }
      }
    }
  }

  private function getFieldsToHide($dataset_entity) {
    $fields_to_hide = [];
    $dataset_record_types = array_column($dataset_entity->get('field_data_set_type')->getValue(), 'target_id');
    $show_fields_for_datatypes = [
        // Field name         =>    [record type term IDs to show for]
        'field_columns'                   => [20],
        'field_critical_information'      => [20],
        'field_data_quality_issues'       => [20],
        'field_data_set_historical_change'=> [20],
        'field_source_system'             => [20],
        'field_data_sets_used'            => [20,10],
        'field_form_number'               => [5],
      ];

    foreach ($show_fields_for_datatypes as $field_name => $record_types_to_show) {
        if (array_intersect($dataset_record_types, $record_types_to_show) === []) {
          // No intersection. This field should be hidden, not shown.
          $fields_to_hide[] = $field_name;
        }
    }
    return $fields_to_hide;
  }

}