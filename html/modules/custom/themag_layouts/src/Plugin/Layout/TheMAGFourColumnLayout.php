<?php

namespace Drupal\themag_layouts\Plugin\Layout;

/**
 * Configurable two column layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class TheMAGFourColumnLayout extends TheMAGLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionBackgroundOptions() {
    return [
      'py-small' => 'Small',
      'py-medium' => 'Medium',
      'py-large' => 'Large',
      'py-xlarge' => 'Extra Large',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionPaddingOptions() {
    return [
      'py-xsmall' => 'Extra Small',
      'py-small' => 'Small',
      'py-medium' => 'Medium',
      'py-large' => 'Large',
      'py-xlarge' => 'Extra Large',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionMarginOptions() {
    return [
      'my-default' => 'Inherit from the theme',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getContainerStyleOptions() {
    return [
      'container' => 'Boxed',
      'container-fluid' => 'Fluid',
      'container-full' => 'Full',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getColumnWidthOptions() {
    return [
      '3-3-3-3' => '3/3/3/3',
      'custom' => 'Custom',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function hasStickyColumns() {
    return TRUE;
  }

}
