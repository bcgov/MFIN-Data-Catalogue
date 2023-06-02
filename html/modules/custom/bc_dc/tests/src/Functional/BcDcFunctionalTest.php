<?php

namespace Drupal\Tests\bc_dc\Functional;

// This should not be needed because of autoloading, but without this, it cannot
// find BcbbTestingTrait.
require_once DRUPAL_ROOT . '/modules/contrib/bcbb/tests/src/BcbbTestingTrait.php';

use Drupal\Core\Config\FileStorage;
use Drupal\node\Entity\Node;
use Drupal\Tests\bcbb\BcbbTestingTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Functional tests.
 *
 * @group BcDc
 */
class BcDcFunctionalTest extends BrowserTestBase {

  use BcbbTestingTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'dc_theme';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bc_dc',
  ];

  /**
   * Array of user objects keyed by user name.
   *
   * @var Drupal\user\Entity\User[]
   */
  protected $users = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    // Import config, like `drush config:import`.
    $config_path = DRUPAL_ROOT . '/../config/sync';
    $config_source = new FileStorage($config_path);
    \Drupal::service('config.installer')->installOptionalConfig($config_source);
  }

  /**
   * Create a user with roles.
   */
  protected function createTestUser(string $name, array $roles = []): User|false {
    // If the user exists, delete it before creation.
    $account = user_load_by_name($name);
    if ($account) {
      $account->delete();
    }

    // Create user with roles.
    $values = [
      'roles' => $roles,
    ];
    $account = $this->createUser([], $name, FALSE, $values);

    $this->users[$name] = $account;

    return $account;
  }

  /**
   * Tests.
   */
  public function test(): void {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Login as admin.
    $this->drupalLogin($this->rootUser);

    // Test that roles exist.
    $roles = [
      'data_administrator',
      'data_custodian',
      'data_catalogue_user',
    ];
    foreach ($roles as $role_id) {
      $role = Role::load($role_id);
      $this->assertSession()->assert($role instanceof Role, 'Role ' . $role_id . ' should exist.');
    }

    // Configure registration_role module.
    // @todo Remove this section and have the config come in from config import.
    $this->drupalGet('admin/people/registration-role');
    $edit = [
      'edit-role-to-select-data-catalogue-user' => TRUE,
      'edit-registration-mode-admin' => 'admin',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Create test users.
    $this->createTestUser('Test Data administrator', ['data_administrator']);
    $this->createTestUser('Test Data custodian', ['data_custodian']);
    $this->createTestUser('Test Data catalogue user', ['data_catalogue_user']);

    // Test that new users are assigned role data_catalogue_user.
    // This only works because of the registration_role module config done
    // above. There is also an ExistingSite test for this.
    foreach (array_keys($this->users) as $username) {
      $account = user_load_by_name($username);
      $this->assertSession()->assert($account->hasRole('data_catalogue_user'), 'Test user ' . $username . ' should have role data_catalogue_user.');
    }

    // Re-save page_manager build page. Without this, the route is not created.
    $this->drupalGet('admin/structure/page_manager/manage/data_set_build/general');
    $this->submitForm([], 'Update and save');

    // Create a basic page node.
    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);
    $randomMachineName = $this->randomMachineName();
    $edit = [
      'edit-title-0-value' => 'Test basic page ' . $randomMachineName . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Basic page ' . $edit['edit-title-0-value'] . ' has been created');
    // Page links to pathauto path for this page.
    $this->linkByHrefStartsWithExists('/test-basic-page-' . strtolower($randomMachineName));

    // Create a data_set node.
    $this->drupalGet('node/add/data_set', ['query' => ['display' => 'data_set_description']]);
    $this->assertSession()->statusCodeEquals(200);
    $randomMachineName = $this->randomMachineName();
    $data_set_title = 'Test data set ' . $randomMachineName . $this->randomString();
    $data_set_path = '/data-set/test-data-set-' . strtolower($randomMachineName);
    $edit = [
      'edit-title-0-value' => $data_set_title,
      'edit-status-value' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Data set ' . $edit['edit-title-0-value'] . ' has been created');
    // Test for breadcrumb link.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "region-breadcrumb")]//li[@class = "breadcrumb-item"]//a[@href = "/data-set"]');

    // Admin has access to data_set build page.
    $this->drupalGet('node/2/build');
    $this->assertSession()->statusCodeEquals(200);
    // Page has ISO dates.
    $this->isoDateTest();
    // Page links to pathauto path for this page.
    $this->linkByHrefStartsWithExists($data_set_path);

    // Data set dashboard.
    $this->drupalGet('user/1');
    $this->assertSession()->statusCodeEquals(200);
    // The create-new link exists.
    $this->assertSession()->elementExists('xpath', '//a[@href = "/node/add/data_set?display=data_set_description"][text() = "Add new data set"]');
    // View link.
    $args = [
      ':data_set_title' => 'View "' . $data_set_title . '".',
      ':data_set_path' => $data_set_path,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//table[contains(@class, "dc-dashboard-table-mydatasets")]//tr/td/a[text() = "View"][@class = "button"][@aria-label = :data_set_title][starts-with(@href, :data_set_path)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Build link.
    $args = [
      ':data_set_title' => 'Build "' . $data_set_title . '".',
      ':data_set_path' => $data_set_path,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//table[contains(@class, "dc-dashboard-table-mydatasets")]//tr/td/a[text() = "Build"][@class = "button"][@aria-label = :data_set_title][@href = "/node/2/build"]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // No empty message.
    $this->assertSession()->elementNotExists('xpath', '//table[contains(@class, "dc-dashboard-table-mydatasets")]//tr/td[text() = "No data sets to show."]');

    // Test bookmarks.
    //
    // No items bookmarked.
    $this->assertSession()->elementExists('xpath', '//table[contains(@class, "dc-dashboard-table-bookmarks")]//tr/td[text() = "No data sets to show."]');
    // Bookmark an item.
    $this->clickLink('Bookmark');
    $this->assertSession()->pageTextContains('Item added to your bookmarks');
    $xpath = $this->assertSession()->buildXPathQuery('//table[contains(@class, "dc-dashboard-table-bookmarks")]//tr/td/a[text() = "Build"][@class = "button"][@aria-label = :data_set_title][@href = "/node/2/build"]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    $this->assertSession()->elementNotExists('xpath', '//table[contains(@class, "dc-dashboard-table-bookmarks")]//tr/td[text() = "No data sets to show."]');

    // Publish the data_set and there are no data rows, just the empty message.
    $data_set = Node::load(2);
    $data_set->setPublished()->save();
    $this->drupalGet('user/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('xpath', '//table[contains(@class, "dc-dashboard-table-mydatasets")]//tr/td[text() = "No data sets to show."]');

    // Data set landing page.
    $this->drupalGet('data-set');
    $this->assertSession()->statusCodeEquals(200);

    // Anonymous has no access to data_set build page.
    $this->drupalLogout();
    $this->drupalGet('node/2/build');
    $this->assertSession()->statusCodeEquals(403);

    // Anonymous has access to view page.
    $this->drupalGet('node/2');
    $this->assertSession()->statusCodeEquals(200);
    // Page has ISO dates.
    $this->isoDateTest();
  }

  /**
   * Test for ISO dates in page content.
   */
  protected function isoDateTest(): void {
    $page_content = $this->getSession()->getPage()->getContent();

    $date_types = [
      'Published date',
      'Modified date',
    ];
    foreach ($date_types as $date_type) {
      $position = strpos($page_content, $date_type);
      $position = strpos($page_content, '<time ', $position);
      $time_element = substr($page_content, $position, 100);
      $match = preg_match(',<time(?: [^>]+)? datetime="(\d\d\d\d-[01]\d-[0-3]\d)[^"]+"(?: [^>]+)?>([^<]+)</time>,', $time_element, $matches);
      $this->assertSession()->assert($match && $matches[1] === $matches[2], $date_type . ' element should contain ISO date.');

      // XPath would be better, but that resulted in out-of-memory errors.
      // @code
      // $time_element = $this->xpath('//div[contains(text(), "' . $date_type .
      // '")]//time');
      // $time_element = reset($time_element);
      // $this->assertSession()->assert((bool) $time_element, $date_type .
      // ' element should exist.');
      // $this->assertSession()->assert(preg_match('/^(\d\d\d\d-[01]\d-[0-3]\d)T/',
      // $time_element->getAttribute('datetime'), $matches), $date_type .
      // ' should have ISO-formatted datetime attribute.');
      // $this->assertSession()->assert($time_element->textContent ===
      // $matches[1], $date_type .
      // ' contens should match date in datetime attribute.');
      // @endcode
    }
  }

}
