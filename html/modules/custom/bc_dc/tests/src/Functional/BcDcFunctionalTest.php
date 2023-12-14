<?php

namespace Drupal\Tests\bc_dc\Functional;

// This should not be needed because of autoloading, but without this, it cannot
// find BcbbTestingTrait.
require_once DRUPAL_ROOT . '/modules/contrib/bcbb/tests/src/BcbbTestingTrait.php';

use Drupal\Core\Config\FileStorage;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
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

  // phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bc_dc',
    'dblog',
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
   * {@inheritdoc}
   *
   * Same as parent except that it will not return a less-than character, which
   * can be interpreted as the start of an HTML tag.
   */
  public function randomString($length = 8) {
    $string = parent::randomString($length);
    $string = str_replace('<', 'a', $string);
    return $string;
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

    // Test config for message_gcnotify. Ensures tests cannot send messages.
    $this->drupalGet('admin/config/message/message-gcnotify');
    $edit = [
      'enabled' => '0',
      'prod_apikey' => 'test-prod_apikey',
      'prod_template' => 'test-prod_template',
      'test_apikey' => 'test-test_apikey',
      'team_apikey' => 'test-team_apikey',
      'test_template' => 'test-test_template',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Test that roles exist.
    $roles = [
      'data_catalogue_administrator',
      'data_catalogue_editor',
      'data_catalogue_user',
    ];
    foreach ($roles as $role_id) {
      $role = Role::load($role_id);
      $this->assertSession()->assert($role instanceof Role, 'Role ' . $role_id . ' should exist.');
    }

    // Create terms in organization vocabulary.
    $test_org_names = [
      'Public',
      'IDIR users',
      'Test organization one ' . $this->randomString(),
      'Test organization two ' . $this->randomString(),
    ];
    $test_orgs = [];
    foreach ($test_org_names as $key => $name) {
      $test_orgs[$key] = Term::create([
        'vid' => 'organization',
        'name' => $name,
      ]);
      $save = $test_orgs[$key]->save();
      $this->assertSame($save, SAVED_NEW);
    }

    // Module configuration.
    // @todo Remove this section and have the config come in from config import.
    //
    // Configure registration_role module.
    $this->drupalGet('admin/people/registration-role');
    $edit = [
      'edit-role-to-select-data-catalogue-user' => TRUE,
      'edit-registration-mode-admin' => 'admin',
    ];
    $this->submitForm($edit, 'Save configuration');
    // Configure toc_filter.
    $this->drupalGet('admin/config/content/formats/manage/basic_html');
    $edit_book = [
      'edit-filters-toc-filter-status' => 1,
      'edit-filters-toc-filter-settings-type' => 'full',
    ];
    $this->submitForm($edit_book, 'Save configuration');
    // Configure rabbit_hole.
    $this->drupalGet('admin/config/content/rabbit-hole');
    $edit = [
      'edit-entity-types-taxonomy-term' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');
    // Configure workflows module.
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/type/node');
    $edit = [
      'edit-bundles-data-set' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    // Configure tac_lite module.
    //
    // Main tac_lite config.
    $this->drupalGet('admin/config/people/tac_lite');
    $edit = [
      'edit-tac-lite-categories' => ['organization'],
      'edit-tac-lite-schemes' => 3,
    ];
    $this->submitForm($edit, 'Save configuration');
    // Configure tac_lite scheme_1.
    $this->drupalGet('admin/config/people/tac_lite/scheme_1');
    $edit = [
      'edit-tac-lite-config-scheme-1-name' => 'Edit',
      'edit-tac-lite-config-scheme-1-perms' => ['grant_update'],
      'edit-tac-lite-config-scheme-1-unpublished' => 1,
      'edit-allowed-fields-field-primary-responsibility-org' => 1,
      // Grant permission by role.
      'edit-tac-lite-grants-scheme-1-administrator-organization' => [-1, 0],
      'edit-tac-lite-grants-scheme-1-data-catalogue-administrator-organization' => [-1, 0],
    ];
    $this->submitForm($edit, 'Save configuration');
    // Configure tac_lite scheme_2.
    $this->drupalGet('admin/config/people/tac_lite/scheme_2');
    $edit = [
      'edit-tac-lite-config-scheme-2-name' => 'View - unpublished',
      'edit-tac-lite-config-scheme-2-perms' => ['grant_view'],
      'edit-tac-lite-config-scheme-2-unpublished' => 1,
      'edit-allowed-fields-field-primary-responsibility-org' => 1,
      // Grant permission by role.
      'edit-tac-lite-grants-scheme-2-administrator-organization' => [-1, 0],
      'edit-tac-lite-grants-scheme-2-data-catalogue-administrator-organization' => [-1, 0],
    ];
    $this->submitForm($edit, 'Save configuration');
    // Configure tac_lite scheme_3.
    $this->drupalGet('admin/config/people/tac_lite/scheme_3');
    $edit = [
      'edit-tac-lite-config-scheme-3-name' => 'View - published',
      'edit-tac-lite-config-scheme-3-perms' => ['grant_view'],
      'edit-allowed-fields-field-primary-responsibility-org' => 1,
      'edit-allowed-fields-field-visibility' => 1,
      // Grant permission by role.
      'edit-tac-lite-grants-scheme-3-administrator-organization' => [-1, 0],
      'edit-tac-lite-grants-scheme-3-anonymous-organization' => [$test_orgs[0]->id()],
      'edit-tac-lite-grants-scheme-3-authenticated-organization' => [$test_orgs[0]->id(), $test_orgs[1]->id()],
      'edit-tac-lite-grants-scheme-3-data-catalogue-manager-organization' => [$test_orgs[0]->id(), $test_orgs[1]->id()],
      'edit-tac-lite-grants-scheme-3-data-catalogue-editor-organization' => [$test_orgs[0]->id(), $test_orgs[1]->id()],
      'edit-tac-lite-grants-scheme-3-data-catalogue-user-organization' => [$test_orgs[0]->id(), $test_orgs[1]->id()],
      // Rebuild permissions.
      'edit-tac-lite-rebuild' => 1,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Create test users.
    $this->createTestUser('Test Data catalogue administrator', ['data_catalogue_administrator']);
    $this->createTestUser('Test Data catalogue manager', ['data_catalogue_manager']);
    $this->createTestUser('Test Data catalogue editor', ['data_catalogue_editor']);
    $this->createTestUser('Test Data catalogue user', ['data_catalogue_user']);

    // Put 'Test Data catalogue editor' into an organization.
    $user = User::load($this->users['Test Data catalogue editor']->id());
    $user->field_organization[] = ['target_id' => $test_orgs[2]->id()];
    $user->save();

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

    // Create a basic page node. node/1.
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

    // Test that the creation form shows only the empty message.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "form-item-data-set-name")]');
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "form-item-field-primary-responsibility-org")]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "form-item-empty-user-field-organization-message")]');

    // Put admin user in one of these organizations.
    $user = User::load($this->rootUser->id());
    $user->field_organization[] = ['target_id' => $test_orgs[2]->id()];
    $user->save();

    // Test that the creation form shows the name but not the organization.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "form-item-data-set-name")]');
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "form-item-field-primary-responsibility-org")]');
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "form-item-empty-user-field-organization-message")]');

    // Put admin user in the other of these organizations.
    // Now edit-field-primary-responsibility-org will exist to be set.
    $user->field_organization[] = ['target_id' => $test_orgs[3]->id()];
    $user->save();

    // Create a data_set node. node/2.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    // Test that the creation form shows the name and the organization.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "form-item-data-set-name")]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "form-item-field-primary-responsibility-org")]');
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "form-item-empty-user-field-organization-message")]');
    // Create a data_set.
    $randomMachineName = $this->randomMachineName();
    $data_set_title = 'Test data set One ' . $randomMachineName . $this->randomString();
    $data_set_path = '/data-set/test-data-set-one-' . strtolower($randomMachineName);
    $edit = [
      'edit-data-set-name' => $data_set_title,
      'edit-field-primary-responsibility-org' => 3,
    ];
    $this->submitForm($edit, 'Create');
    $this->assertSession()->pageTextContains('Metadata record created.');
    // Link to new data_set appears.
    $args = [
      ':data_set_title' => $data_set_title,
      ':data_set_path' => $data_set_path,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "block-views-blockdashboard-moderation-blocks-dashboard-unpublished")]
      [//td[normalize-space(text()) = :data_set_title]]
      [//a[@href = "/node/2/build"][text() = "Build"]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Admin has access to data_set build page.
    $this->drupalGet('node/2/build');
    $this->assertSession()->statusCodeEquals(200);
    // Page has correct breadcrumbs.
    $breadcrumbs = $this->xpath('//ol[@class = "breadcrumb"]/li/a');
    $this->assertCount(2, $breadcrumbs, 'Page has 2 breadcrumbs.');
    $this->assertEquals('/', $breadcrumbs[0]->getAttribute('href'));
    $this->assertEquals('/data-set', $breadcrumbs[1]->getAttribute('href'));
    // "Edit" tab does appear for data_set content type.
    $this->assertSession()->elementExists('xpath', '//a[@href = "/node/2/edit"]');
    // "Outline" tab does not appear for data_set content type.
    $this->assertSession()->elementNotExists('xpath', '//a[@href = "/node/2/outline"]');
    // Page has ISO dates.
    $this->isoDateTest();
    // Page links to pathauto path for this page.
    $this->linkByHrefStartsWithExists($data_set_path);
    // Section headers and edit links.
    // Check for: A div.block-bc-dc-edit-button that has an 'h2' child with the
    // correct contents and an 'a' descendent with button classes, @aria-label,
    // @href, and text.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-edit-button")][h2[text() = "Section 1: Details"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 1"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_1")]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-edit-button")][h2[text() = "Section 2: Data description"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 2"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_2")]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-edit-button")][h2[text() = "Section 3: Data usage"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 3"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_3")]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-edit-button")][h2[text() = "Section 4: Data value"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 4"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_4")]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-edit-button")][h2[text() = "Section 5: Data dictionary"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 5"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_5")]');
    // Build page does not link to referenced entities.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "field--type-entity-reference")]//a');

    // Check for fields that have inline labels.
    $fields_inline_optional = [
      'field--name-field-series' => ['label' => 'Series', 'text' => 'Optional'],
      'field--name-field-last-review-date' => ['label' => 'Last review date', 'text' => 'Never'],
      'field--name-field-security-classification' => ['label' => 'Security classification', 'text' => 'Required'],
      'field--name-field-source-system' => ['label' => 'Source system', 'text' => 'Optional'],
      'field--name-field-data-set-type' => ['label' => 'Data set type', 'text' => 'Required'],
      'field--name-field-information-schedule' => ['label' => 'Information schedule', 'text' => 'Optional'],
    ];
    foreach ($fields_inline_optional as $class => $field) {
      $args = [
        ':class' => $class,
        ':label' => $field['label'],
        ':text' => $field['text'],
      ];
      $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--label-inline")][contains(@class, :class)][div[@class = "field__label"][text() = :label]]/div/em[text() = :text]', $args);
      $this->assertSession()->elementExists('xpath', $xpath);
    }
    // Check for fields that are dates and have inline labels.
    // The time formats are tested elsewhere.
    $fields_inline_optional = [
      'field--name-field-published-date' => 'Published date',
      'field--name-field-modified-date' => 'Modified date',
    ];
    foreach ($fields_inline_optional as $class => $label) {
      $args = [
        ':class' => $class,
        ':label' => $label,
      ];
      $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--label-inline")][contains(@class, :class)][div[@class = "field__label"][text() = :label]]/div/time', $args);
      $this->assertSession()->elementExists('xpath', $xpath);
    }

    // Create data_set_type term.
    $data_set_type_term = Term::create([
      'vid' => 'data_set_type',
      'name' => 'SQL',
    ]);
    $data_set_type_term->save();
    // Complete required fields in section 1.
    $this->click('a[aria-label = "Edit Section 1"]');
    $edit = [
      'edit-field-data-set-type' => $data_set_type_term->id(),
    ];
    $this->submitForm($edit, 'Save');

    // Create security_classification term.
    $security_classification_term = Term::create([
      'vid' => 'security_classification',
      'name' => 'Confidential - Protected eh?',
    ]);
    $security_classification_term->save();
    // Save Section 4 so that the boolean values are FALSE instead of empty.
    $this->click('a[aria-label = "Edit Section 4"]');
    $edit = [
      'field_security_classification' => $security_classification_term->id(),
    ];
    $this->submitForm($edit, 'Save');
    // Check for fields that are boolean and have inline labels.
    $fields_inline_optional = [
      'field--name-field-critical-information' => 'Critical information',
      'field--name-field-authoritative-info' => 'Authoritative info',
      'field--name-field-high-value-info' => 'High value info',
    ];
    foreach ($fields_inline_optional as $class => $label) {
      $args = [
        ':class' => $class,
        ':label' => $label,
      ];
      $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--label-inline")][contains(@class, :class)][div[@class = "field__label"][text() = :label]]/div[text() = "No"]', $args);
      $this->assertSession()->elementExists('xpath', $xpath);
    }
    // Check for fields that are optional and normally have labels above.
    // Labels are inline when the field is empty.
    $fields_inline_optional = [
      'field--name-body' => ['label' => 'Data set description', 'text' => 'Required'],
      'field--name-field-data-quality-issues' => ['label' => 'Data quality issues', 'text' => 'Optional'],
      'field--name-field-data-set-historical-change' => ['label' => 'Data set historical change', 'text' => 'Optional'],
    ];
    foreach ($fields_inline_optional as $class => $field) {
      $args = [
        ':class' => $class,
        ':label' => $field['label'],
        ':text' => $field['text'],
      ];
      $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--label-inline")][contains(@class, :class)][div[@class = "field__label"][text() = :label]]/div/em[text() = :text]', $args);
      $this->assertSession()->elementExists('xpath', $xpath);
    }

    // Empty column names section.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--name-field-columns")]/div/em[text() = "Optional"]');
    // No columns exist on column edit page.
    $this->click('a[aria-label = "Edit Section 5"]');
    $this->assertSession()->statusCodeEquals(200);
    // There should not be any rows under 'tbody', however after hiding items
    // under header_actions in bc_dc_form_node_data_set_edit_form_alter(), one
    // empty row appears. This tests that the row is empty, that is, not
    // containing any information about a column.
    $this->assertSession()->elementNotExists('xpath', '//table[@id = "field-columns-values"]/tbody/tr/td/*');
    // Add a column.
    $this->click('input#field-columns-data-column-add-more');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'edit-field-columns-0-subform-field-column-name-0-value' => 'Data set column 1 name ' . $this->randomString(),
      'edit-field-columns-0-subform-field-column-description-0-value' => 'Data set column 1 description ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    // The column count appears.
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-columns")]/div/div[text() = "1"]');
    $this->assertSession()->elementExists('xpath', $xpath);
    // The other fields do not appear.
    $this->assertSession()->pageTextNotContains('Data set column 1 description');
    // Check that "Edit all" and "Collapse all" controls do not exist.
    $this->click('a[aria-label = "Edit Section 5"]');
    $this->click('input#field-columns-data-column-add-more');
    $this->assertSession()->elementNotExists('xpath', '//input[@value = "Edit all"]');
    $this->assertSession()->elementNotExists('xpath', '//input[@value = "Collapse all"]');

    // Section 4 edit page.
    $this->clickLink('Build');
    $this->click('a[aria-label = "Edit Section 4"]');
    $this->assertSession()->statusCodeEquals(200);
    // Test that field_security_classification widget is radio buttons.
    $this->assertSession()->elementExists('xpath', '//div[@id = "edit-field-security-classification"]//input[@type = "radio"]');
    // Section 2 edit page.
    $this->clickLink('Build');
    $this->click('a[aria-label = "Edit Section 2"]');
    // Submit with some updates.
    $edit = [
      'edit-body-0-value' => 'Data set description ' . $this->randomString() . ' Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
      'edit-field-visibility-1' => 1,
    ];
    $this->submitForm($edit, 'Save');
    // Test that long text gets trimmed.
    $this->assertSession()->pageTextContains('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna');
    $this->assertSession()->pageTextNotContains('Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.');

    // Data set dashboard.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    // View link.
    $args = [
      ':data_set_path' => $data_set_path,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "block-views-blockdashboard-moderation-blocks-dashboard-unpublished")]//tr/td/a[text() = "View"][starts-with(@href, :data_set_path)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Build link.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-views-blockdashboard-moderation-blocks-dashboard-unpublished")]//tr/td/a[text() = "Build"][@class = "btn btn-primary"][@href = "/node/2/build"]');
    // No empty message.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "block-views-blockdashboard-moderation-blocks-dashboard-unpublished")]//div[normalize-space(text()) = "You currently do not have any draft metadata records."]');

    // Test bookmarks.
    //
    // No items bookmarked.
    $this->assertSession()->linkNotExists('Remove bookmark');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-views-blockbookmarks-dashboard-bookmarks")]//div[normalize-space(text()) = "You currently do not have any metadata records bookmarked."]');
    // Bookmark an item.
    $this->clickLink('View');
    $this->clickLink('Bookmark');
    $this->assertSession()->pageTextContains('Item added to your bookmarks');
    // View page has link to remove bookmark.
    $this->assertSession()->elementExists('xpath', '//a[*[contains(@class, "title")][contains(text(), "Remove bookmark")]][*[contains(@class, "count")][contains(text(), "Bookmarked by 1 person")]]');
    // Bookmark now appears on the dashboard.
    $this->drupalGet('user');
    $args = [
      ':data_set_title' => $data_set_title,
      ':data_set_path' => $data_set_path,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "block-views-blockbookmarks-dashboard-bookmarks")]//tr/td/a[normalize-space(text()) = :data_set_title][starts-with(@href, :data_set_path)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "block-views-blockbookmarks-dashboard-bookmarks")]//div[normalize-space(text()) = "You currently do not have any metadata records bookmarked."]');

    // Revisions and diff are enabled and available.
    $this->drupalGet('node/2');
    $this->assertSession()->elementExists('xpath', '//nav[contains(@class, "tabs")]/ul/li/a[@href = "/node/2/revisions"]');
    $this->assertTrue(\Drupal::service('module_handler')->moduleExists('diff'), 'Module diff should be enabled.');

    // Publish the data_set.
    $this->drupalGet('node/2/build');
    $edit = [
      'edit-full-review' => TRUE,
    ];
    $this->submitForm($edit, 'Publish');
    $this->clickLink('Build');
    // field_last_review should display today.
    $args = [
      ':class' => 'field--name-field-last-review-date',
      ':label' => 'Last review date',
      ':text' => date('Y-m-d'),
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--label-inline")][contains(@class, :class)][div[@class = "field__label"][text() = :label]]/div/time[text() = :text]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // There are no data rows, just the empty message.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-views-blockdashboard-moderation-blocks-dashboard-unpublished")]//div[normalize-space(text()) = "You currently do not have any draft metadata records."]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-views-blockdashboard-blocks-dashboard-needs-review")]//div[normalize-space(text()) = "You currently have no metadata records needing review."]');

    // Test data set update message.
    //
    // Recently-bookmarked data set has no data set updated message.
    $this->assertSession()->pageTextNotContains('Updated:');
    // Set the updated date later than the bookmark date.
    $data_set = Node::load(2);
    $data_set->set('field_modified_date', (new \DateTime('tomorrow'))->format('Y-m-d'))->save();
    // The data set updated message should appear.
    $this->drupalGet('user');
    $args = [
      ':data_set_path' => $data_set_path,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "block-views-blockbookmarks-dashboard-bookmarks")]//tr/td
      [span[@class = "badge text-bg-success"][text() = "Updated"]]
      [a[starts-with(@href, :data_set_path)]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Examine logs to check that update notification emails would have been
    // sent to users who bookmarked the updated data_set.
    $options = [
      'query' => ['type' => ['bc_dc', 'message_gcnotify']],
    ];
    $this->drupalGet('admin/reports/dblog', $options);
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-watchdog")]/div/table/tbody/tr/td/a[text() = "Sent message to 1 users when updating data_set 2."]');
    // Get from @title the request that would have been sent to GC Notify.
    $element = $this->xpath('//div[contains(@class, "view-watchdog")]/div/table/tbody/tr/td/a[contains(@title, "A dataset you have bookmarked has been updated")]');
    $element = reset($element);
    $title = $element->getAttribute('title');
    preg_match('/^GC Notify disabled[^:]+: (.+)/', $title, $matches);
    $gcnotify_request = json_decode($matches[1]);
    // Run tests on the request.
    $this->assertEquals('A dataset you have bookmarked has been updated', $gcnotify_request->rows[1][1]);
    $this->assertMatchesRegularExpression('(The following dataset has been updated:
' . preg_quote($data_set_title) . '
https?://[^/]+/node/2)', htmlspecialchars_decode($gcnotify_request->rows[1][2]));

    // The bookmark field_last_viewed_date gets updated when visiting a page.
    //
    // Get needed services.
    $flagService = \Drupal::service('flag');
    $bookmark_flag = $flagService->getFlagById('bookmark');
    $bookmark_flagging = $flagService->getFlagging($bookmark_flag, $data_set);
    // Set the field_last_viewed_date to yesterday.
    $date_yesterday = (new \DateTime('yesterday'))->format('Y-m-d\TH:i:s');
    $bookmark_flagging->set('field_last_viewed_date', $date_yesterday)->save();
    // Visit the page to update the last-visited time.
    $this->drupalGet('node/2');
    // The field_last_viewed_date should now be later.
    $field_last_viewed_date = $flagService->getFlagging($bookmark_flag, $data_set)->get('field_last_viewed_date')->value;
    // Ensure comparisons are between ISO dates.
    $this->assertMatchesRegularExpression('/^\d\d\d\d-\d\d-\d\dT/', $date_yesterday);
    $this->assertMatchesRegularExpression('/^\d\d\d\d-\d\d-\d\dT/', $field_last_viewed_date);
    // Check that field_last_viewed_date is now greater than what it was set to.
    $this->assertGreaterThan(1, 2);
    $this->assertGreaterThan($date_yesterday, $field_last_viewed_date);

    // Run tests as editor. Some of the above could be tested as editor as well.
    $this->drupalLogin($this->users['Test Data catalogue editor']);

    // Import data columns page.
    $this->drupalGet('node/2/build');
    $this->click('a[aria-label = "Edit Section 5"]');
    $this->assertSession()->elementExists('xpath', '//a[@href = "/node/2/add-columns?destination=/node/2/build"][text() = "Import/export data columns"]');
    $this->clickLink('Import/export data columns');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('xpath', '//h1[text() = "Add columns"]');
    $this->assertSession()->pageTextContains('Upload a file to add columns to the data dictionary.');
    $this->assertSession()->elementExists('xpath', '//a[@href = "/node/2/build"][text() = "Cancel"]');
    // Create a term in the data_type taxonomy for later use.
    Term::create([
      'vid' => 'data_type',
      'name' => 'Integer',
    ])->save();
    // File upload tests.
    $tests = [
      // File with an unsupported extension.
      'test-empty.txt' => 'Only files with the following extensions are allowed:',
      // Empty 'tsv' file.
      'test-empty.tsv' => 'Uploaded file was empty.',
      // File with an empty header.
      'test-empty-header.csv' => 'Uploaded file has at least one column with an empty header.',
      // Duplicate column header.
      'test-duplicate-column.csv' => 'Uploaded file contains duplicate column headers: header 1',
      // Unknown fields.
      'test-unknown-column.csv' => 'File contains unknown fields: header 1, header 2, header 3',
      // No data rows.
      'test-no-data-rows.csv' => 'Uploaded file had no data rows. The first row must be column headers.',
      // No column_name.
      'test-no-column_name.csv' => 'Uploaded file does not have a column_name field.',
      // Empty column_name.
      'test-empty-column_name.csv' => 'Uploaded file has at least one empty column_name field.',
      // Invalid value in entitiy reference column.
      'test-invalid-rows.csv' => 'Uploaded file had invalid values in some columns. The invalid values are shown below.',
    ];
    foreach ($tests as $filename => $error_message) {
      // Upload test file.
      $edit = [
        'edit-import-file-upload' => __DIR__ . '/../../files/' . $filename,
      ];
      $this->submitForm($edit, 'Upload');
      // Check for error message.
      $text = $this->assertSession()->elementExists('xpath', '//div[@role = "alert"][contains(@class, "alert-error")]')->getText();
      $this->assertStringContainsString($error_message, $text, 'Error file: ' . $filename);
    }
    // Case is ignored in entitiy reference column comparison.
    $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/tbody/tr[1]/td[text() = "Name 1"]');
    $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/tbody/tr[1]/td[not(@class)][text() = "Integer"]');
    // Plain label for valid value in entitiy reference column.
    $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/tbody/tr[2]/td[text() = "Name 2"]');
    $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/tbody/tr[2]/td[not(@class)][text() = "Integer"]');
    // Empty for empty value in entitiy reference column.
    $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/tbody/tr[3]/td[text() = "Name 3"]');
    $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/tbody/tr[3]/td[not(@class)][not(text())]');
    // Test for error message for invalid value in entitiy reference column.
    $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/tbody/tr[4]/td[text() = "Name 4"]');
    $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/tbody/tr[4]/td[@class = "error"][text() = "Invalid: invalid"]');

    // Test value import files.
    $file_types_to_test = [
      'csv',
      'tsv',
      'ods',
      'xlsx',
    ];
    foreach ($file_types_to_test as $file_extension) {
      $this->drupalGet('node/2/add-columns', ['query' => ['destination' => '/node/2/build']]);
      // Upload valid import file.
      $edit = [
        'edit-import-file-upload' => __DIR__ . '/../../files/test-valid.' . $file_extension,
      ];
      $this->submitForm($edit, 'Upload');
      // Confirmation page.
      // This would normally be done with ::elementExists() but for an unknown
      // reason, it always fails.
      $text = $this->assertSession()->elementExists('xpath', '//div[@role = "alert"][contains(@class, "alert-warning")]')->getText();
      $this->assertStringContainsString('Warning message Existing columns will be deleted when these new columns are imported.', $text);
      // Import data table.
      $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/thead/tr/th[1][text() = "column_name"]');
      $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/thead/tr/th[2][text() = "column_size"]');
      $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/tbody/tr/td[1][text() = "Name ' . $file_extension . '"]');
      $this->assertSession()->elementExists('xpath', '//table[@id = "edit-import-data-table"]/tbody/tr/td[2][text() = "50"]');
    }

    // Complete import.
    $this->submitForm([], 'Import');
    // Success page.
    $text = $this->assertSession()->elementExists('xpath', '//div[@role = "alert"][contains(@class, "alert-success")]')->getText();
    $this->assertStringContainsString('Added 1 data columns from imported file.', $text);
    // Count of columns.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--name-field-columns")]/div/div[text() = "1"]');

    // Anonymous has no access to data_set build page.
    $this->drupalLogout();
    $this->drupalGet('node/2/build');
    $this->assertSession()->statusCodeEquals(404);

    // Anonymous has no access to data_set add-columns page.
    $this->drupalGet('node/2/add-columns');
    $this->assertSession()->statusCodeEquals(404);

    // Anonymous has access to view page.
    $this->drupalGet('node/2');
    $this->assertSession()->statusCodeEquals(200);
    // Page has ISO dates.
    $this->isoDateTest();

    // Anonymous has access to download csv for Metadata record when file has
    // been uploaded.
    $this->drupalGet('node/2/download/columns/csv');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Name');
    // Anonymous has access to download xlsx for Metadata record when file has
    // been uploaded.
    $this->drupalGet('node/2/download/columns/xlsx');
    $this->assertSession()->statusCodeEquals(200);

    // Anonymous dashboard.
    $this->drupalGet('dashboard');
    // Content block exists.
    $this->assertSession()->elementExists('xpath', '//div[@id = "block-dc-theme-content"]');
    // Search block exists.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "bcbb-search-api-form")]');

    // Test adding bookmarks.
    $this->drupalLogin($this->users['Test Data catalogue user']);
    $this->drupalGet('node/2');
    // "Edit" tab does not appear for data_set content type.
    $this->assertSession()->elementNotExists('xpath', '//a[@href = "/node/2/edit"]');
    // Bookmarked by 1.
    $this->assertSession()->elementExists('xpath', '//a[*[contains(@class, "title")][contains(text(), "Bookmark")]][*[contains(@class, "count")][contains(text(), "Bookmarked by 1 person")]]');
    // Add a bookmark.
    $this->click('div.flag-bookmark.action-flag > a');
    // Bookmarked by 2.
    $this->assertSession()->elementExists('xpath', '//a[*[contains(@class, "title")][contains(text(), "Remove bookmark")]][*[contains(@class, "count")][contains(text(), "Bookmarked by 2 people")]]');

    // Test book module.
    //
    // Login as admin.
    $this->drupalLogin($this->rootUser);
    // Create a Book as admin. node/3.
    $this->drupalGet('node/add/book');
    $this->assertSession()->statusCodeEquals(200);
    $edit_book = [
      'edit-title-0-value' => 'Test Book ' . $this->randomString(),
      'edit-book-bid' => 'new',
      'edit-path-0-pathauto' => FALSE,
      'edit-path-0-alias' => '/documentation',
    ];
    $this->submitForm($edit_book, 'Save');
    $text = $this->assertSession()->elementExists('xpath', '//h1')->getText();
    $this->assertStringContainsString($edit_book['edit-title-0-value'], $text);
    $book_url = $this->getUrl();
    // Create child page as Test Data catalogue administrator. node/4.
    $this->drupalLogin($this->users['Test Data catalogue administrator']);
    $this->drupalGet($book_url);
    // "Edit" tab does appear for book content type.
    $this->assertSession()->elementExists('xpath', '//a[@href = "/node/3/edit"]');
    // "Outline" tab appears for data_set content type.
    $this->assertSession()->elementExists('xpath', '//a[@href = "/node/3/outline"]');
    $this->clickLink('Add child page');
    $this->assertSession()->statusCodeEquals(200);
    $test_header = 'Test Header 3 ' . $this->randomString();
    $edit_child = [
      'edit-title-0-value' => 'Test Book Child Page ' . $this->randomString(),
      'edit-body-0-summary' => 'Test Book Child Summary ' . $this->randomString(),
      'edit-body-0-value' => '<p>[toc]</p><p>Lorem ipsum dolor sit amet.</p><h2>Header 2</h2><p>Lorem ipsum dolor sit amet.</p><h3>' . $test_header . '</h3><p>Lorem ipsum dolor sit amet.</p><h2>Header 2</h2><p>Lorem ipsum dolor sit amet.</p>',
    ];
    $this->submitForm($edit_child, 'Save');
    $text = $this->assertSession()->elementExists('xpath', '//h1')->getText();
    $this->assertStringContainsString($edit_child['edit-title-0-value'], $text);
    $child_url = $this->getUrl();
    // Create grandchild. node/5.
    $this->clickLink('Add child page');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'edit-title-0-value' => 'Test Book Grandchild Page ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    $text = $this->assertSession()->elementExists('xpath', '//h1')->getText();
    $this->assertStringContainsString($edit['edit-title-0-value'], $text);
    // Book title appears in breadcrumbs.
    $args = [
      ':title' => $edit_book['edit-title-0-value'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//nav/ol[@class = "breadcrumb"]/li/a[text() = :title]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Main book page.
    $this->drupalGet($book_url);
    // Main book page does not have book navigation block.
    $this->assertSession()->elementNotExists('xpath', '//div[@id = "block-dc-theme-booknavigation"]');
    // Summary appears in list of child pages.
    $args = [
      ':summary' => $edit_child['edit-body-0-summary'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//nav[@class = "book-navigation"]/ul/li/div[contains(@class, "summary")][text() = :summary]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Book traversal links.
    $args = [
      ':title' => $edit_child['edit-title-0-value'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//nav[@class = "book-navigation"]/ul[@aria-label = "Document navigation"]/li/a[@title = "Go to next page"][contains(text(), :title)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Default "Book traversal links" header is not present.
    $this->assertSession()->pageTextNotContains('Book traversal links');
    // Printer-friendly version.
    $this->assertSession()->elementExists('xpath', '//div[@class = "node__links"]/ul/li/a[contains(text(), "Printer-friendly version")]');

    // Child page.
    $this->drupalGet($child_url);
    // Child page has book navigation block in sidebar.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "region-sidebar-second")]/div[@id = "block-dc-theme-booknavigation"]');
    // Child page does not have list of child pages.
    $this->assertSession()->elementNotExists('xpath', '//nav[@class = "book-navigation"]/ul[not(@aria-label)]');
    // Child page has a table of contents from toc_filter.
    $args = [
      ':header' => $test_header,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "toc-tree")]/ol/li/ol/li/a[text() = :header]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Test information schedule taxonomy terms.
    //
    // Create test record_life_cycle_duration term.
    $record_life_cycle_duration_values = [
      'vid' => 'record_life_cycle_duration',
      'name' => 'record_life_cycle_duration One ' . $this->randomString(),
      'field_abbr_full_name' => 'record_life_cycle_duration One full name',
    ];
    $record_life_cycle_duration_entity = Term::create($record_life_cycle_duration_values);
    $record_life_cycle_duration_entity->save();
    // Create test information_schedule terms.
    $info_schedule_values = [];
    $info_schedule_terms = [];
    // First.
    $info_schedule_values[0] = [
      'vid' => 'information_schedule',
      'name' => 'information schedule One ' . $this->randomString(),
      'field_abbr_full_name' => 'First full name',
    ];
    $info_schedule_terms[0] = Term::create($info_schedule_values[0]);
    $info_schedule_terms[0]->save();
    // Second.
    $info_schedule_values[1] = [
      'vid' => 'information_schedule',
      'name' => 'information schedule Two ' . $this->randomString(),
      'field_schedule_number' => $this->randomMachineName(),
      'parent' => $info_schedule_terms[0]->id(),
    ];
    $info_schedule_terms[1] = Term::create($info_schedule_values[1]);
    $info_schedule_terms[1]->save();
    // Third.
    $info_schedule_values[2] = [
      'vid' => 'information_schedule',
      'name' => 'information schedule Three ' . $this->randomString(),
      'parent' => $info_schedule_terms[1]->id(),
      'field_schedule_number' => $this->randomMachineName(),
      'field_active_period' => $record_life_cycle_duration_entity->id(),
      'field_active_period_extension' => $this->randomMachineName(),
      'field_semi_active_period' => $record_life_cycle_duration_entity->id(),
      'field_semi_active_extension' => $this->randomMachineName(),
    ];
    $info_schedule_terms[2] = Term::create($info_schedule_values[2]);
    $info_schedule_terms[2]->save();
    // Set field_information_schedule to value with child.
    $data_set = Node::load(2);
    $data_set->set('field_information_schedule', $info_schedule_terms[1]->id())->save();
    // Test that the IM classification details appear without a link.
    $this->drupalGet('node/2');
    $args = [
      ':classification_details' => $info_schedule_values[1]['name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-information-schedule")]
      [div[@class = "field__label"][normalize-space(text()) = "IM classification details"]]
      [div[@class = "field__item"][text() = :classification_details]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Set field_information_schedule to value without child.
    $data_set->set('field_information_schedule', $info_schedule_terms[2]->id())->save();

    // Test that the information schedule appears correctly.
    $this->drupalGet('node/2');

    // Test Information management section.
    //
    // Information schedule type.
    $args = [
      ':information_schedule_type' => $info_schedule_values[0]['name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-information-schedule")]
      [div[@class = "field__label"][normalize-space(text()) = "Information schedule type"]]
      [div[@class = "field__item"][text() = :information_schedule_type]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Test that the IM classification details appear with a link.
    $args = [
      ':classification_details' => $info_schedule_values[1]['name'] . ': ' . $info_schedule_values[2]['name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-information-schedule")]
      [div[@class = "field__label"][normalize-space(text()) = "IM classification details"]]
      [div[@class = "field__item"]/a[text() = :classification_details]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Schedule code.
    $args = [
      ':field_schedule_code' => $info_schedule_values[1]['field_schedule_number'] . '-' . $info_schedule_values[2]['field_schedule_number'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-schedule-code")]
      [div[@class = "field__label"][normalize-space(text()) = "IM classification code"]]
      [div[@class = "field__item"][text() = :field_schedule_code]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Update a parent term and see the field_schedule_code updated in children.
    $new_field_schedule_number = $this->randomMachineName();
    $info_schedule_terms[0]->set('field_schedule_number', $new_field_schedule_number)->save();
    $reloaded_term = Term::load($info_schedule_terms[2]->id());
    $expected = $new_field_schedule_number . '-' . $info_schedule_values[1]['field_schedule_number'] . '-' . $info_schedule_values[2]['field_schedule_number'];
    $this->assertEquals($expected, $reloaded_term->field_schedule_code->value);

    // Test "Review needed" messages.
    //
    // Generate "Review needed" messages.
    $review_needed_messages = [
      'review_needed_message' => 'Review needed. ' . $this->randomString(),
      'review_overdue_message' => 'Review overdue. ' . $this->randomString(),
    ];
    // Save messages and review interval in config. This used to be done with
    // $this->config(), but that no longer works.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/config/data-catalogue');
    $edit = [
      // Ensure an item with a 1 month interval will appear as needing review.
      'edit-data-set-review-period-alert' => 40,
      'edit-review-needed-message' => $review_needed_messages['review_needed_message'],
      'edit-review-overdue-message' => $review_needed_messages['review_overdue_message'],
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->drupalLogin($this->users['Test Data catalogue administrator']);

    // No "Review needed" message appears.
    $this->drupalGet('node/2');
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "dc-review")]');

    // Set field_review_interval so that review_needed_message should appear.
    $data_set = Node::load(2);
    $data_set->set('field_review_interval', 1)->save();
    $this->drupalGet('node/2');
    $args = [
      ':message' => $review_needed_messages['review_needed_message'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "badge text-bg-warning dc-review")][text() = :message]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Set field_last_review_date so that review_overdue_message should appear.
    $data_set->set('field_last_review_date', (new \DateTime('2 months ago'))->format('Y-m-d'))->save();
    $this->drupalGet('node/2');
    $args = [
      ':message' => $review_needed_messages['review_overdue_message'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "badge text-bg-danger dc-review")][text() = :message]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Test "My data sets that need review" table.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "block-views-blockdashboard-blocks-dashboard-needs-review")]//div[normalize-space(text()) = "You currently have no metadata records needing review."]');
    $args = [
      // This "Review overdue" is from View dashboard_blocks,
      // dashboard_needs_review, "Content: Review status", Rewrite results.
      ':review_overdue_message' => 'Review overdue',
      ':data_set_title' => $data_set_title,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "block-views-blockdashboard-blocks-dashboard-needs-review")]//tr
      [td[normalize-space(text()) = :data_set_title]]
      [td/span[@class = "badge text-bg-danger"][text() = :review_overdue_message]]
      [td/a[@href = "/node/2/build"]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Test bc_dc.review_reminder service, ReviewReminder class.
    $bc_dc_review_reminder = \Drupal::service('bc_dc.review_reminder');
    $data_set_url = $data_set->toUrl('canonical', ['absolute' => TRUE])->toString();
    // Test ::getReminders().
    $reminders = $bc_dc_review_reminder->getReminders();
    $expected = [
      1 => [
        $bc_dc_review_reminder::REVIEW_OVERDUE => [
          [
            'title' => $data_set_title,
            'url' => $data_set_url,
          ],
        ],
      ],
    ];
    $this->assertSame($reminders, $expected);
    // Test ::generateBody().
    // Generate empty reminder body.
    $reminderBody = $bc_dc_review_reminder->generateBody([]);
    $this->assertNull($reminderBody);
    // Generate reminder body for user 1.
    $reminderBody = $bc_dc_review_reminder->generateBody($reminders[1]);
    $expected = $review_needed_messages['review_overdue_message'] . ':

' . $data_set_title . '
' . $data_set_url;
    $this->assertSame($reminderBody, $expected);
    // Test ::sendRemindersToOneUser().
    // Non-existant user.
    $return = $bc_dc_review_reminder->sendRemindersToOneUser(10000, $reminders[1]);
    $this->assertSame($return, NULL);
    // Valid user, no data.
    $return = $bc_dc_review_reminder->sendRemindersToOneUser(1, []);
    $this->assertSame($return, NULL);
    // Valid user, valid data, message gets sent.
    $return = $bc_dc_review_reminder->sendRemindersToOneUser(1, $reminders[1]);
    $this->assertTrue($return);
    // Log entries for the above.
    $options = [
      'query' => ['type' => ['bc_dc', 'message_gcnotify']],
    ];
    $this->drupalGet('admin/reports/dblog', $options);
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-watchdog")]/div/table/tbody/tr/td/a[text() = "ReviewReminder: User 10000 has no email address."]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-watchdog")]/div/table/tbody/tr/td/a[text() = "ReviewReminder: Empty message for user 1."]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-watchdog")]/div/table/tbody/tr/td/a[text() = "Sent ReviewReminder message to user 1."]');

    // Test field_data_sets_used.
    // Create a data_set node. node/6.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $data_set_title_2 = 'Test data set Two ' . $this->randomString();
    $edit = [
      'edit-data-set-name' => $data_set_title_2,
      'edit-field-primary-responsibility-org' => 3,
    ];
    $this->submitForm($edit, 'Create');
    $this->assertSession()->pageTextContains('Metadata record created.');
    // Go to build page for this node.
    $this->click('a[href = "/node/6/build"]');
    // Message that required field is empty.
    $this->assertSession()->elementExists('xpath', '//form[@id = "bc-dc-workflow-block-form"]
      [p[text() = "The following fields must be completed before publishing:"]]
      [//ul/li[text() = "Visibility"]]');
    // No publish button.
    $this->assertSession()->buttonNotExists('Publish');
    // Complete the missing fields.
    // Section 1.
    $this->click('a[aria-label = "Edit Section 1"]');
    $edit = [
      'edit-field-data-set-type' => $data_set_type_term->id(),
    ];
    $this->submitForm($edit, 'Save');
    // Section 2.
    $this->click('a[aria-label = "Edit Section 2"]');
    $public_label = $this->xpath('//fieldset[@id = "edit-field-visibility--wrapper"]//label[text() = "Public"]');
    $public_label = reset($public_label);
    $edit = [
      'edit-body-0-value' => 'Data set description ' . $this->randomString(),
      $public_label->getAttribute('for') => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    // Section 4.
    $this->click('a[aria-label = "Edit Section 4"]');
    $edit = [
      'field_security_classification' => $security_classification_term->id(),
    ];
    $this->submitForm($edit, 'Save');
    // Publish button now exists.
    $this->assertSession()->buttonExists('Publish');
    // Add revision log message and publish.
    $edit = [
      'edit-revision-log-message' => 'Revision log message ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Publish');
    // "Personal information" badge does not appear.
    $this->assertSession()->elementNotExists('xpath', '//span[contains(@class, "badge text-bg-warning")][text() = "Personal information"]');
    // Revision log message appears on revisions tab.
    $this->clickLink('Revisions');
    $args = [
      ':revision_log' => $edit['edit-revision-log-message'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//*[@class = "revision-log"][text() = :revision_log]', $args);
    // On Build page, field_data_sets_used is empty.
    $this->clickLink('Build');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--name-field-data-sets-used")]/div[@class = "field__item"]/em[text() = "Optional"]');
    // On Build page, no workflow block when latest revision is published.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-workflow-block")]//*[contains(text(), "Latest revision is published")]');
    // Set node/2 as a data_set used by this data_set.
    $this->click('a[aria-label = "Edit Section 3"]');
    $edit = [
      'edit-field-data-sets-used-0-target-id' => 'Title (2)',
      // Add "Personal information" badge.
      'edit-field-personal-information-1' => '1',
    ];
    $this->submitForm($edit, 'Save');
    // field_data_sets_used is not empty. This demonstrates that the Build page
    // is showing the latest version not the default version.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "field--name-field-data-sets-used")]/div[@class = "field__item"]/em[text() = "Optional"]');
    $args = [
      ':data_set_title' => $data_set_title,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-data-sets-used")]/div[@class = "field__items"]/div[@class = "field__item"]/a[text() = :data_set_title]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // View page still does not have field_data_sets_used.
    $this->clickLink('View');
    $this->assertSession()->elementNotExists('xpath', '//div[text() = "Data sets used"]');
    // Publish the changes.
    $this->clickLink('Build');
    $this->submitForm([], 'Publish');
    // Page has "Data sets used" with link to node/2.
    $args = [
      ':data_set_title' => $data_set_title,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div
      [//div[text() = "Data sets used"]]
      [//a[text() = :data_set_title]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    $this->assertSession()->elementNotExists('xpath', '//div[text() = "Used-in data sets"]');
    // Check node/2 for link back.
    $this->drupalGet('node/2');
    $args = [
      ':data_set_title_2' => $data_set_title_2,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div
      [//div[text() = "Used in datasets"]]
      [//a[text() = :data_set_title_2]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    $this->assertSession()->elementNotExists('xpath', '//div[text() = "Data sets used"]');
    // "Personal information" badge appears.
    $this->drupalGet('node/6');
    $this->assertSession()->elementExists('xpath', '//span[contains(@class, "badge text-bg-warning")][text() = "Personal information"]');
    // Permalink appears.
    $this->assertSession()->elementExists('xpath', '//section[@id = "author_permalink"]//input[substring(@value, string-length(@value) - 6) = "/node/6"]');
    // Header search block appears.
    $this->assertSession()->elementExists('xpath', '//header//div[contains(@class, "block-bcbb-search-api-block")]//input[@aria-label = "Search"]');

    // Test Content page.
    $this->drupalGet('admin/content');
    // Node of type page does not have a "Build" operation.
    $this->assertSession()->elementNotExists('xpath', '//li[contains(@class, "bc-dc-build")][contains(@class, "dropbutton__item")]/a[@href = "/node/1/build?destination=/admin/content"][text() = "Build"]');
    // Node of type data_set has a "Build" operation.
    $this->assertSession()->elementExists('xpath', '//li[contains(@class, "bc-dc-build")][contains(@class, "dropbutton__item")]/a[@href = "/node/2/build?destination=/admin/content"][text() = "Build"]');

    // Check access to taxonomy term pages. They should be 404 except for
    // information_schedule.
    $this->drupalLogout();
    $taxonomy_terms = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(FALSE)
      ->execute();
    $taxonomy_terms = Term::loadMultiple($taxonomy_terms);
    foreach ($taxonomy_terms as $term) {
      $this->drupalGet('taxonomy/term/' . $term->id());
      $expected_status = $term->bundle() === 'information_schedule' ? 200 : 404;
      $this->assertSession()->statusCodeEquals($expected_status);
    }
    // Test content of information_schedule taxonomy term pages.
    $args = [
      ':title' => $record_life_cycle_duration_values['field_abbr_full_name'],
      ':text' => $record_life_cycle_duration_values['name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-active-period")]/div/abbr[@title = :title][text() = :text]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-semi-active-period")]/div/abbr[@title = :title][text() = :text]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Test Unpublishing.
    //
    // For anon, "Unpublish" link does not exist.
    $this->drupalGet('node/2');
    $this->assertSession()->linkNotExists('Unpublish');

    // For DC user, "Unpublish" link does not exist.
    $this->drupalLogin($this->users['Test Data catalogue user']);
    $this->drupalGet('node/2');
    $this->assertSession()->linkNotExists('Unpublish');

    // For DC admin, "Unpublish" link always exists.
    $this->drupalLogin($this->users['Test Data catalogue administrator']);
    $this->drupalGet('node/2');
    $this->assertSession()->linkExists('Unpublish');
    // "Unpublish" page works.
    $this->clickLink('Unpublish');
    $this->assertSession()->buttonExists('Confirm');
    $this->submitForm([], 'Confirm');
    // Redirects to "Build" page.
    $this->assertEquals('/node/2/build', parse_url($this->getUrl(), PHP_URL_PATH));
    // Confirmation message appears.
    $args = [
      ':title' => $data_set_title,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "alert-success")]/em[text() = :title]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Check that it is unpublished.
    $this->assertSession()->elementExists('xpath', '//article[contains(@class, "node--unpublished")]');
    // Now "Unpublish" page has a message.
    $this->clickLink('Unpublish');
    $text = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "alert-error")]')->getText();
    $this->assertStringContainsString('This metadata record is already unpublished.', $text);
    $this->assertSession()->buttonNotExists('Confirm');

    // For DC manager, no "Unpublish" link.
    $this->drupalLogin($this->users['Test Data catalogue manager']);
    $this->drupalGet('node/2');
    $this->assertSession()->linkNotExists('Unpublish');
    // Add user to the node's org.
    $account = User::load($this->users['Test Data catalogue manager']->id());
    $account->field_organization[] = ['target_id' => $test_orgs[2]->id()];
    $account->save();
    // Now "Unpublish" link exists.
    $this->drupalGet('node/2');
    $this->assertSession()->linkExists('Unpublish');
  }

  /**
   * Test for ISO dates in page content.
   */
  protected function isoDateTest(): void {
    $date_types = [
      'Published date',
      'Modified date',
    ];
    foreach ($date_types as $date_type) {
      $args = [
        ':date_type' => $date_type,
      ];
      $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--type-datetime")][div[text() = :date_type]]//time', $args);
      $time_element = $this->xpath($xpath);
      $time_element = reset($time_element);
      $this->assertSession()->assert((bool) $time_element, $date_type . ' element should exist.');
      $this->assertSession()->assert(preg_match('/^(\d\d\d\d-[01]\d-[0-3]\d)T/', $time_element->getAttribute('datetime'), $matches), $date_type . ' should have ISO-formatted datetime attribute.');
      $this->assertSession()->assert($time_element->getText() === $matches[1], $date_type . ' contents should match date in datetime attribute.');
    }
  }

}
