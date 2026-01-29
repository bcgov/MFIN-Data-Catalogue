<?php

namespace Drupal\Tests\bcbb\Functional;

require_once __DIR__ . '/../BcbbTestingTrait.php';

use Drupal\Tests\bcbb\BcbbTestingTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests.
 *
 * @group BcBb
 */
abstract class BcbbBrowserTestBase extends BrowserTestBase {

  use BcbbTestingTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bcbb_theme';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

}
