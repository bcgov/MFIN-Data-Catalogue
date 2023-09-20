<?php

namespace Drupal\bc_dc\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the module configuration form.
 */
class BcDcSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bc_dc_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['bc_dc.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $bc_dc_settings = $this->config('bc_dc.settings');

    $form['data_set_review_period_alert'] = [
      '#type' => 'number',
      '#title' => $this->t('Review period alert'),
      '#description' => $this->t('The number of days before a review date that the editor will be alerted.'),
      '#required' => TRUE,
      '#default_value' => $bc_dc_settings->get('data_set_review_period_alert'),
      '#min' => 1,
      '#step' => 1,
    ];
    $form['review_needed_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Review needed message'),
      '#description' => $this->t('The message to appear when a data set is within the configured review period of its review date.'),
      '#required' => TRUE,
      '#default_value' => $bc_dc_settings->get('review_needed_message'),
    ];
    $form['review_overdue_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Review overdue message'),
      '#description' => $this->t('The message to appear when a data set is after its review date.'),
      '#required' => TRUE,
      '#default_value' => $bc_dc_settings->get('review_overdue_message'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    // Save individual config values.
    $bc_dc_settings = $this->config('bc_dc.settings');
    $fields_to_save = [
      'data_set_review_period_alert',
      'review_needed_message',
      'review_overdue_message',
    ];
    foreach ($fields_to_save as $field) {
      $bc_dc_settings->set($field, $form_state->getValue($field));
    }

    $bc_dc_settings->save();

    $this->getLogger('bc_dc')->notice('BC Data Catalogue Module settings have been updated.');
  }

}
