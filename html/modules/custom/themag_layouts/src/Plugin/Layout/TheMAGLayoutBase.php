<?php

namespace Drupal\themag_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Base class of layouts with configurable widths.
 *
 * @internal
 *   Plugin classes are internal.
 */
abstract class TheMAGLayoutBase extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $container_options = array_keys($this->getContainerStyleOptions());
    $width_options = array_keys($this->getColumnWidthOptions());

    $configuration = [
      'section_bg' => '',
      'section_padding' => '',
      'section_margin' => 'my-default',
      'section_custom_classes' => '',
      'container_style' => array_shift($container_options),
      'row_custom_classes' => '',
      'no_gutters' => 0,
      'column_widths' => array_shift($width_options),
      'sticky_columns' => 0,
    ];

    foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
      $configuration[$region_name] = [
        'custom_classes' => '',
      ];
    }

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['section_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Section settings'),
      '#open' => FALSE,
    ];

    $form['container_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Container settings'),
      '#open' => FALSE,
    ];

    $form['row_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Row settings'),
      '#open' => FALSE,
    ];

    $form['column_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Column settings'),
      '#open' => TRUE,
    ];

    $form['section_details']['section_bg'] = [
      '#type' => 'select',
      '#title' => $this->t('Section background color'),
      '#default_value' => $this->configuration['section_bg'],
      "#empty_option" => $this->t('- Default -'),
      '#options' => $this->getSectionBackgroundOptions(),
      '#description' => $this->t('Choose a background color for the section.'),
    ];

    $form['section_details']['section_padding'] = [
      '#type' => 'select',
      '#title' => $this->t('Section padding'),
      "#empty_option" => $this->t('- None -'),
      '#default_value' => $this->configuration['section_padding'],
      '#options' => $this->getSectionPaddingOptions(),
      '#description' => $this->t('The predefined padding styles will add vertical padding to the section element. To set custom padding, use the Custom CSS classes field.'),

    ];

    $form['section_details']['section_margin'] = [
      '#type' => 'select',
      '#title' => $this->t('Section margin'),
      "#empty_option" => $this->t('- None -'),
      '#default_value' => $this->configuration['section_margin'],
      '#options' => $this->getSectionMarginOptions(),
      '#description' => $this->t('By default, the sections margin is inherited from the theme style. To set custom margin, use the Custom CSS classes field.'),
    ];

    $form['section_details']['section_custom_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom CSS classes'),
      '#default_value' => $this->configuration['section_custom_classes'],
      '#placeholder' => 'e.g. section-hero text-center',
      '#description' => $this->t('Use this field to add custom CSS classes to the section wrapper element.'),
    ];

    $form['container_details']['container_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Container style'),
      '#default_value' => $this->configuration['container_style'],
      '#options' => $this->getContainerStyleOptions(),
      '#description' => $this->t('Choose the container style.'),
    ];

    $form['row_details']['row_custom_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Row custom classes'),
      '#default_value' => $this->configuration['row_custom_classes'],
      '#placeholder' => 'e.g. align-items-center',
      '#description' => $this->t('Use this field to add custom CSS classes to the row element. Since the layout relies on Bootstrap 4, you can refer to Bootstrap\'s <a href="https://getbootstrap.com/docs/4.3/layout/grid" target="_blank">grid system documentation</a> for more.'),
    ];

    $form['column_details']['column_widths'] = [
      '#type' => 'select',
      '#title' => $this->t('Column widths'),
      '#default_value' => $this->configuration['column_widths'],
      '#options' => $this->getColumnWidthOptions(),
      '#description' => $this->t('Choose column widths. The widths are expressed in columns using the <a href="https://getbootstrap.com/docs/4.3/layout/grid" target="_blank">Bootstrap\'s twelve column grid system</a>. You can also choose "Custom" if you\'d like to use custom column classes to customize the grid by your needs.'),
    ];

    $form['column_details']['no_gutters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No gutters'),
      '#default_value' => $this->configuration['no_gutters'],
      '#description' => $this->t('Remove gutters between columns.'),
    ];

    foreach ($this->getPluginDefinition()->getRegions() as $region_name => $value) {
      $form['column_details'][$region_name]['custom_classes'] = [
        '#type' => 'textfield',
        '#title' => $this->t($value['label'] . ' column classes'),
        '#default_value' => $this->configuration[$region_name]['custom_classes'],
        '#placeholder' => 'e.g. col-12 col-md-6',
        '#description' => $this->t('Add classes to the ', ['@label' => $value['label']]) . ' column.',
        '#states' => [
          'visible' => [
            ':input[name="layout_settings[column_details][column_widths]"]' => [
              'value' => 'custom',
            ],
          ],
        ],
      ];
    }

    if ($this->hasStickyColumns()) {
      $form['column_details']['sticky_columns'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable sticky columns'),
        '#default_value' => $this->configuration['sticky_columns'],
        '#description' => $this->t('When this option is enabled, the shorter elements in the section scrolls with the content as the viewer moves down the page.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Section.
    $section_details = $form_state->getValue('section_details');
    $this->configuration['section_bg'] = $section_details['section_bg'];
    $this->configuration['section_padding'] = $section_details['section_padding'];
    $this->configuration['section_margin'] = $section_details['section_margin'];
    $this->configuration['section_custom_classes'] = $section_details['section_custom_classes'];

    // Container.
    $container_details = $form_state->getValue('container_details');
    $this->configuration['container_style'] = $container_details['container_style'];

    // Row.
    $row_details = $form_state->getValue('row_details');
    $this->configuration['row_custom_classes'] = $row_details['row_custom_classes'];

    // Columns.
    $column_details = $form_state->getValue('column_details');
    $this->configuration['column_widths'] = $column_details['column_widths'];
    foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
      $this->configuration[$region_name]['custom_classes'] = $column_details[$region_name]['custom_classes'];
    }
    $this->configuration['no_gutters'] = $column_details['no_gutters'];
    $this->configuration['sticky_columns'] = $column_details['sticky_columns'];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);

    // Wrapper attributes.
    $build['#attributes']['class'] = [
      'themag-layout',
      $this->getPluginDefinition()->getTemplate(),
      $this->getPluginDefinition()->getTemplate() . '--' . $this->configuration['column_widths'],
      ($this->configuration['section_bg']) ? 'themag-layout--' . $this->configuration['section_bg'] : '',
      ($this->configuration['section_padding']) ? 'themag-layout--' . $this->configuration['section_padding'] : '',
      ($this->configuration['section_margin']) ? 'themag-layout--' . $this->configuration['section_margin'] : '',
      $this->configuration['section_custom_classes'],
    ];

    // Container attributes.
    $build['#container_attributes']['class'] = [
      $this->configuration['container_style'],
    ];

    // Row attributes.
    $build['#row_attributes']['class'] = [
      'row',
      $this->configuration['row_custom_classes'],
      ($this->configuration['no_gutters']) ? 'no-gutters' : '',
    ];

    // Region/Column attributes.
    $build['#region_attributes'] = [];

    foreach ($regions as $region_name => $value) {
      $build['#region_attributes'][$region_name]['class'] = [
        'themag-layout__region',
        'themag-layout__region--' . str_replace('_', '-', $region_name),
        ($this->configuration['sticky_columns']) ? 'js-sticky-column' : '',
      ];

      if ($this->configuration['column_widths'] == 'custom') {
        $build['#region_attributes'][$region_name]['class'] = [
          $this->configuration[$region_name]['custom_classes'],
        ];
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  abstract protected function getSectionBackgroundOptions();

  /**
   * {@inheritdoc}
   */
  abstract protected function getSectionPaddingOptions();

  /**
   * {@inheritdoc}
   */
  abstract protected function getSectionMarginOptions();

  /**
   * {@inheritdoc}
   */
  abstract protected function getContainerStyleOptions();

  /**
   * {@inheritdoc}
   */
  abstract protected function getColumnWidthOptions();

  /**
   * {@inheritdoc}
   */
  abstract protected function hasStickyColumns();

}
