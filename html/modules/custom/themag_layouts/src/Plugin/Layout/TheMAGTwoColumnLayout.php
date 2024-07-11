<?php

namespace Drupal\themag_layouts\Plugin\Layout;

/**
 * Configurable two column layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class TheMAGTwoColumnLayout extends TheMAGLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionBackgroundOptions() {
    return [
      'white' => 'White',
      'gray' => 'Gray',
      'gray-light' => 'Gray Light',
      'black' => 'Black',
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
      '6-6' => '6/6',
      '4-8' => '4/8',
      '8-4' => '8/4',
      '3-9' => '3/9',
      '9-3' => '9/3',
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
