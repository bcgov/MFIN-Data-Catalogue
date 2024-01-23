<?php

namespace Drupal\bc_dc\Form;

use Drupal\bc_dc\Service\ReviewReminder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the module configuration form.
 */
class BcDcSettingsForm extends ConfigFormBase {

  /**
   * Constructor.
   *
   * @param \Drupal\bc_dc\Service\ReviewReminder $bcDcReviewReminder
   *   The bc_dc.review_reminder service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   */
  public function __construct(
    protected ReviewReminder $bcDcReviewReminder,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('bc_dc.review_reminder'),
      $container->get('config.factory'),
    );
  }

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

    $form['notifications'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Metadata record review reminders'),
      '#description' => $this->t('These are usually sent every week on Sunday. Use this button to send them now.'),
    ];
    $form['notifications']['send'] = [
      '#type' => 'submit',
      '#submit' => ['::sendDataSetReviewReminders'],
      '#value' => $this->t('Send metadata record review reminders'),
    ];

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

  /**
   * Submit handler to send data_set review reminders.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function sendDataSetReviewReminders(array &$form, FormStateInterface $form_state): void {
    $this->bcDcReviewReminder->sendRemindersToAllUsers();

    $this->messenger()->addMessage($this->t('Metadata record review reminders have been sent.'));
  }

}
