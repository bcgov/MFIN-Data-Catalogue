<?php

namespace Drupal\Tests\bc_dc\Functional;

use Drupal\Core\Config\FileStorage;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests.
 *
 * @group BcDc
 */
class BcDcFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bc_dc',
  ];

  /**
   * Tests.
   */
  public function test(): void {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Login as admin.
    $this->drupalLogin($this->rootUser);

    // Import config, like `drush config:import`.
    $config_path = DRUPAL_ROOT . '/../config/sync';
    $config_source = new FileStorage($config_path);
    \Drupal::service('config.installer')->installOptionalConfig($config_source);

    // Create a data_set node.
    $this->drupalGet('node/add/data_set', ['query' => ['display' => 'data_set_description']]);
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'edit-title-0-value' => 'Data set ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Data set ' . $edit['edit-title-0-value'] . ' has been created');

    // Admin has access to build page.
    $this->drupalGet('node/1/build');
    $this->assertSession()->statusCodeEquals(200);

    // Anonymous has no access to build page.
    $this->drupalLogout();
    $this->drupalGet('node/1/build');
    $this->assertSession()->statusCodeEquals(403);
  }

}
