<?php

namespace Drupal\Tests\bc_dc\Kernel;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests.
 *
 * @group BcDc
 */
class BcDcKernelTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedData;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->enableModules(['bc_dc', 'paragraphs']);
    $this->typedData = $this->container->get('typed_data_manager');
  }

  /**
   * Test validation constraints.
   */
  public function testValidation() {
    // Create a definition that specifies some AllowedValues.
    $definition = DataDefinition::create('string')
      ->addConstraint('BcDcUrlConstraint');

    // Valid paths.
    $paths = [
      'http://example.com/path',
      'https://example.com/path',
      'ftp://example.com/path',
      'ftps://example.com/path',
      'file:///path/filename.pdf',
      '//test/path',
      '\\fin.gov.bc.ca\PATH\file_name.pdf',
    ];
    foreach ($paths as $path) {
      $typed_data = $this->typedData->create($definition, $path);
      $violations = $typed_data->validate();
      $this->assertEquals(0, $violations->count(), 'Validation should pass for valid path: ' . $path);
    }

    // Invalid paths.
    $paths = [
      'test-path',
      'mailto:example@example.com',
      'tel:+12345678901',
    ];
    foreach ($paths as $path) {
      $typed_data = $this->typedData->create($definition, $path);
      $violations = $typed_data->validate();
      $this->assertEquals(1, $violations->count(), 'Validation should fail for invalid path: ' . $path);
    }
  }

}
