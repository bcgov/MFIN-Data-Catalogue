<?php

namespace Drupal\cs_notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure CS Notify settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'cs_notify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cs_notify_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('cs_notify.settings');

    $form['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('API URL'),
      '#description' => $this->t('Base URL for the CS Notify API.'),
      '#default_value' => $config->get('api_url'),
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('For production environments, this should normally be overridden via settings.php.'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Request timeout'),
      '#description' => $this->t('HTTP timeout in seconds.'),
      '#default_value' => $config->get('timeout') ?? 30,
      '#min' => 1,
      '#required' => TRUE,
    ];

    $form['process_notify_queues_during_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process Notify queues using Drupal cron'),
      '#description' => $this->t("
        Normally, you should leave this checked, 
        so that Notify queues are processed by Drupal's cron.<br/>
        
        Uncheck it if the queues are processed by something else, such as a separate <code>drush queue:run</code> job 
        (such as we do on Openshift in BC Gov's 'Basebuild').
      "),
      '#default_value' => $config->get('process_notify_queues_during_cron') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $this->configFactory
      ->getEditable('cs_notify.settings')
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('timeout', (int) $form_state->getValue('timeout'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}