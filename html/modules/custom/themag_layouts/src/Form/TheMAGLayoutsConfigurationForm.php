<?php

namespace Drupal\themag_layouts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form definition for the TheMAG layouts.
 */
class TheMAGLayoutsConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['themag_layouts.configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'themag_layouts_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('themag_layouts.configuration');

    $form['hide_themag_not_configurable_layouts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove TheMAG not-configurable layouts from Layout Builder'),
      '#default_value' => $config->get('hide_themag_not_configurable_layouts'),
      '#description' => $this->t('This will remove TheMAG not-configurable layouts from off-canvas navigation under Layout Builder. These layouts will still be available in other modules which can use layouts like Panels and Panelizer.'),
    ];

    $form['hide_default_drupal_layouts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove default Drupal layouts form Layout Builder'),
      '#default_value' => $config->get('hide_default_drupal_layouts'),
      '#description' => $this->t('This will remove default Drupal layouts from off-canvas navigation under Layout Builder. These layouts will still be available in other modules which can use layouts.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('themag_layouts.configuration')
      ->set('hide_themag_not_configurable_layouts', $form_state->getValue('hide_themag_not_configurable_layouts'))
      ->set('hide_default_drupal_layouts', $form_state->getValue('hide_default_drupal_layouts'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
