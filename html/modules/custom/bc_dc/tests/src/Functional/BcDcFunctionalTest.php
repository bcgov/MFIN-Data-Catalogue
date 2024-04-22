<?php

namespace Drupal\Tests\bc_dc\Functional;

// This should not be needed because of autoloading, but without this, it cannot
// find BcbbBrowserTestBase.
require_once DRUPAL_ROOT . '/modules/contrib/bcbb/tests/src/Functional/BcbbBrowserTestBase.php';

use Drupal\Core\Config\FileStorage;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\bcbb\Functional\BcbbBrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Functional tests.
 *
 * @group BcDc
 */
class BcDcFunctionalTest extends BcbbBrowserTestBase {

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
  protected function setUp(): void {
    parent::setUp();

    // Import config, like `drush config:import`.
    $config_path = DRUPAL_ROOT . '/../config/sync';
    $config_source = new FileStorage($config_path);
    \Drupal::service('config.installer')->installOptionalConfig($config_source);
    // Trigger the config import events. This normally runs on
    // `drush config:import` but is not triggered by the above.
    \Drupal::service('bc_dc.config_import_event_subscriber')->onConfigImport();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    // Running tests leaves behind tmp files which are the files uploaded during
    // the upload tests. These files do not get created in normal use of the
    // site. Delete these.
    //
    // Userid of this process.
    $posix_getuid = posix_getuid();
    // These need to include any file extensions uploaded, that is,
    // $file_types_to_test plus 'txt'.
    $file_types_to_delete = [
      'csv',
      'tsv',
      'ods',
      'xlsx',
      'txt',
    ];
    // Regular expression of file names to delete.
    $file_regex = '/^[a-zA-Z0-9_+]{7}\.(' . implode('|', $file_types_to_delete) . ')$/';
    // Scan the tmp directory and delete any file, owned by the current process
    // owner, that matches the pattern of left-over filenames.
    $dir = '/tmp/';
    if (is_dir($dir) && $dir_handle = opendir($dir)) {
      while (($filename = readdir($dir_handle)) !== FALSE) {
        $filepath = $dir . $filename;
        if (fileowner($filepath) === $posix_getuid && filetype($filepath) === 'file' && preg_match($file_regex, $filename)) {
          unlink($filepath);
        }
      }
      closedir($dir_handle);
    }
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

    // Check for bc_dc entry on Status report.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->elementExists('xpath', '//div[@class = "system-status-report__status-title"][normalize-space(text()) = "BC Data Catalogue"]');

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
      'data_catalogue_manager',
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
    $test_org_names_field_access_flag = [
      'pub',
      'auth',
    ];
    $test_orgs = [];
    foreach ($test_org_names as $key => $name) {
      $test_orgs[$key] = Term::create([
        'vid' => 'organization',
        'name' => $name,
        'field_access_flag' => $test_org_names_field_access_flag[$key] ?? NULL,
      ]);
      $save = $test_orgs[$key]->save();
      $this->assertSame($save, SAVED_NEW);
    }

    // Create term in document_type vocabulary.
    $document_type_1 = Term::create([
      'vid' => 'document_type',
      'name' => 'Test document_type 1 ' . $this->randomString(),
    ]);
    $save = $document_type_1->save();
    $this->assertSame($save, SAVED_NEW);

    // Add missing permissions. These ought to have been imported with config.
    // @todo Get all permissions to import.
    $role = Role::load('authenticated');
    $role->grantPermission('access user reports');
    $role->save();

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
      $account = User::load($this->users[$username]->id());
      $this->assertSession()->assert($account->hasRole('data_catalogue_user'), 'Test user ' . $username . ' should have role data_catalogue_user.');
    }

    // Test plugin bc_dc_node_assign_owner_action.
    $bc_dc_node_assign_owner_action = \Drupal::service('plugin.manager.action')->createInstance('bc_dc_node_assign_owner_action', []);
    $expected = [
      $this->rootUser->id() => (string) $this->rootUser->id(),
      $this->users['Test Data catalogue administrator']->id() => (string) $this->users['Test Data catalogue administrator']->id(),
      $this->users['Test Data catalogue manager']->id() => (string) $this->users['Test Data catalogue manager']->id(),
      $this->users['Test Data catalogue editor']->id() => (string) $this->users['Test Data catalogue editor']->id(),
    ];
    $this->assertSame($expected, $bc_dc_node_assign_owner_action->getEditUsers());

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

    // Edit admin user.
    $user = User::load($this->rootUser->id());
    // Put admin user in one of these organizations.
    $user->field_organization[] = ['target_id' => $test_orgs[2]->id()];
    // Make email match name so that ::drupalLogin() works with auto_username.
    $user->setEmail('admin');
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

    // Create root data_set_type data term.
    $data_set_type_root_data_term = Term::create([
      'vid' => 'data_set_type',
      'name' => 'Data',
      'field_root_type' => 'data',
    ]);
    $data_set_type_root_data_term->save();
    // Create data_set_type data term.
    $data_set_type_data_term = Term::create([
      'vid' => 'data_set_type',
      'name' => 'SQL',
      'parent' => $data_set_type_root_data_term->id(),
    ]);
    $data_set_type_data_term->save();

    // Create root data_set_type report term.
    $data_set_type_root_report_term = Term::create([
      'vid' => 'data_set_type',
      'name' => 'Report',
      'field_root_type' => 'report',
    ]);
    $data_set_type_root_report_term->save();
    // Create data_set_type report term.
    $data_set_type_report_term = Term::create([
      'vid' => 'data_set_type',
      'name' => 'PowerBI',
      'parent' => $data_set_type_root_report_term->id(),
    ]);
    $data_set_type_report_term->save();

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
    $data_set_path = '/test-data-set-one-' . strtolower($randomMachineName);
    $edit = [
      'edit-data-set-name' => $data_set_title,
      'field_data_set_type' => $data_set_type_root_data_term->id(),
      'edit-field-primary-responsibility-org' => 3,
    ];
    $this->submitForm($edit, 'Create');
    $this->assertSession()->pageTextContains('Metadata record created.');
    // Link to new data_set appears.
    $args = [
      ':data_set_title' => $data_set_title,
      ':data_set_path' => $data_set_path,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//section[contains(@class, "block-views-blockdashboard-moderation-blocks-dashboard-unpublished")]
      [//td[normalize-space(text()) = :data_set_title]]
      [//a[@href = "/node/2/build"][text() = "Build"]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // View page.
    $this->drupalGet('node/2');
    $this->assertSession()->statusCodeEquals(200);
    // The "High value" badge does not appear.
    $this->assertSession()->pageTextNotContains('High value');

    // Admin has access to data_set build page.
    $this->drupalGet('node/2/build');
    $this->assertSession()->statusCodeEquals(200);
    // User toolbar link to Dashboard has correct title.
    $this->assertSession()->elementExists('xpath', '//div[@id = "toolbar-item-user-tray"]/nav/ul/li/a[text() = "Dashboard"]');
    $this->assertSession()->elementNotExists('xpath', '//div[@id = "toolbar-item-user-tray"]/nav/ul/li/a[text() = "View profile"]');
    // Page has correct breadcrumbs.
    $breadcrumbs = $this->xpath('//ol[@class = "breadcrumb"]/li/a');
    $this->assertCount(1, $breadcrumbs, 'Page has 1 breadcrumb.');
    $this->assertEquals('/', $breadcrumbs[0]->getAttribute('href'));
    // "Edit" tab does appear for data_set content type.
    $this->assertSession()->elementExists('xpath', '//a[@href = "/node/2/edit"]');
    // "Outline" tab does not appear for data_set content type.
    $this->assertSession()->elementNotExists('xpath', '//a[@href = "/node/2/outline"]');
    // Test for fields that should only appear on "Data" data_set nodes.
    $fields_to_hide = [
      'field_columns',
      'field_critical_information',
      'field_data_quality_issues',
      'field_data_set_historical_change',
      'field_data_sets_used',
      'field_source_system',
    ];
    foreach ($fields_to_hide as $field_key) {
      $args = [
        ':class' => 'field--name-' . str_replace('_', '-', $field_key),
      ];
      $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, :class)]', $args);
      $this->assertSession()->elementExists('xpath', $xpath);
    }
    // Page has ISO dates.
    $this->isoDateTest(FALSE, TRUE);
    // Page links to pathauto path for this page.
    $this->linkByHrefStartsWithExists($data_set_path);
    // Section headers and edit links.
    // Check for: A section.block-entity-viewnode that has an 'h2' child with
    // the correct contents and an 'a' descendent with button classes, and the
    // correct @aria-label, @href, and text.
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-entity-viewnode")][h2[text() = "Section 1: Details"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 1"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_1")]');
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-entity-viewnode")][h2[text() = "Section 2: Description"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 2"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_2")]');
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-entity-viewnode")][h2[text() = "Section 3: Origin and classification"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 3"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_3")]');
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-entity-viewnode")][h2[text() = "Section 4: Related documents"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 4"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_4")]');
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-entity-viewnode")][h2[text() = "Section 5: Significance"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 5"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_5")]');
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-entity-viewnode")][h2[text() = "Section 6: Data dictionary"]]//a[@class = "btn btn-primary"][@aria-label = "Edit Section 6"][text() = "Edit"][starts-with(@href, "/node/2/edit?display=section_6")]');
    $this->assertSession()->elementExists('xpath', '//*[contains(@class, "node--view-mode-section-5")]');
    $this->assertSession()->elementExists('xpath', '//*[contains(@class, "node--view-mode-section-6")]');
    // Build page does not link to referenced entities.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "field--type-entity-reference")]//a');

    // The data_set title should appear in Section 1 of the Build page.
    $args = [
      ':data_set_title' => $data_set_title,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//*[contains(@class, "node--view-mode-section-1")]//div
      [div[@class = "field__label"][text() = "Asset name"]]
      [div[@class = "field__item"][text() = :data_set_title]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Check for fields that have inline labels.
    $fields_inline_optional = [
      'field--name-field-series' => ['label' => 'Series', 'text' => 'Optional'],
      'field--name-field-asset-location' => ['label' => 'Location', 'text' => 'Optional'],
      'field--name-field-published-date' => ['label' => 'Published date', 'text' => 'Optional'],
      'field--name-field-last-review-date' => ['label' => 'Last review date', 'text' => 'Never'],
      'field--name-field-security-classification' => ['label' => 'Security classification', 'text' => 'Required'],
      'field--name-field-source-system' => ['label' => 'Source system', 'text' => 'Optional'],
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
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--label-inline")][contains(@class, "field--name-field-modified-date")][div[@class = "field__label"][text() = "Modified date"]]/div/time');

    // Create security_classification term.
    $security_classification_term = Term::create([
      'vid' => 'security_classification',
      'name' => 'Confidential - Protected eh?',
    ]);
    $security_classification_term->save();
    // Save Section 3.
    $this->click('a[aria-label = "Edit Section 3"]');
    $edit = [
      'field_personal_information' => '0',
      'field_security_classification' => $security_classification_term->id(),
    ];
    $this->submitForm($edit, 'Save');
    // Save Section 5 so that the boolean values are not empty.
    $this->click('a[aria-label = "Edit Section 5"]');
    $edit = [
      'edit-field-critical-information-value' => '1',
    ];
    $this->submitForm($edit, 'Save');
    // Check for fields that are boolean and have inline labels.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--label-inline")][contains(@class, "field--name-field-critical-information")][div[@class = "field__label"][text() = "Critical information"]]/div[text() = "Yes"]');
    // Check for fields that are optional and normally have labels above.
    // Labels are inline when the field is empty.
    $fields_inline_optional = [
      'field--name-body' => ['label' => 'Summary', 'text' => 'Required'],
      'field--name-field-data-quality-issues' => ['label' => 'Data quality issues', 'text' => 'Optional'],
      'field--name-field-data-set-historical-change' => ['label' => 'Historical change', 'text' => 'Optional'],
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
    $this->click('a[aria-label = "Edit Section 6"]');
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
    // Revisit columns page.
    $this->click('a[aria-label = "Edit Section 6"]');
    // The row for each column contains the title and nothing else.
    $args = [
      ':summary-content' => $edit['edit-field-columns-0-subform-field-column-name-0-value'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "paragraph-summary")]/div[contains(@class, "paragraphs-description")]//span[@class = "summary-content"][text() = :summary-content]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    $this->assertSession()->pageTextNotContains('Data set column 1 description');
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "paragraph-type")]');
    // Start to add a column.
    $this->click('input#field-columns-data-column-add-more');
    // Check that "Edit all" and "Collapse all" controls do not exist.
    $this->assertSession()->elementNotExists('xpath', '//input[@value = "Edit all"]');
    $this->assertSession()->elementNotExists('xpath', '//input[@value = "Collapse all"]');

    // Section 2 edit page.
    $this->clickLink('Build');
    $this->click('a[aria-label = "Edit Section 2"]');
    // Submit with some updates.
    $edit = [
      'edit-body-0-value' => 'Summary ' . $this->randomString() . ' Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
      'edit-field-visibility-1' => 1,
    ];
    $this->submitForm($edit, 'Save');
    // Section 3 edit page.
    $this->click('a[aria-label = "Edit Section 3"]');
    // Complete required fields.
    $edit = [
      'edit-field-personal-information-0' => '0',
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
    $xpath = $this->assertSession()->buildXPathQuery('//section[contains(@class, "block-views-blockdashboard-moderation-blocks-dashboard-unpublished")]//tr/td/a[text() = "View"][starts-with(@href, :data_set_path)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Build link.
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-views-blockdashboard-moderation-blocks-dashboard-unpublished")]//tr/td/a[text() = "Build"][@class = "btn btn-primary"][@href = "/node/2/build"]');
    // No empty message.
    $this->assertSession()->elementNotExists('xpath', '//section[contains(@class, "block-views-blockdashboard-moderation-blocks-dashboard-unpublished")]//div[normalize-space(text()) = "You currently do not have any draft metadata records."]');

    // Test bookmarks.
    //
    // No items bookmarked.
    $this->assertSession()->linkNotExists('Remove bookmark');
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-views-blockbookmarks-dashboard-bookmarks")]//div[normalize-space(text()) = "You currently do not have any metadata records bookmarked."]');
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
    $xpath = $this->assertSession()->buildXPathQuery('//section[contains(@class, "block-views-blockbookmarks-dashboard-bookmarks")]//a[normalize-space(text()) = :data_set_title][starts-with(@href, :data_set_path)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    $this->assertSession()->elementNotExists('xpath', '//section[contains(@class, "block-views-blockbookmarks-dashboard-bookmarks")]//div[normalize-space(text()) = "You currently do not have any metadata records bookmarked."]');
    // Metadata record count message.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-content-summary")]//*[text() = "You currently have no published metadata records."]');
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "block-bc-dc-content-summary")]//a[text() = "Manage metadata records"]');

    // Dashboard "Manage" tab exists and can be visited.
    $manage_link = $this->assertSession()->elementExists('xpath', '//div[@id = "block-dc-theme-local-tasks"]/nav/nav/ul/li/a[text() = "Manage"]');
    $this->drupalGet($manage_link->getAttribute('href'));
    $this->assertSession()->statusCodeEquals(200);

    // Revisions and diff are enabled and available.
    $this->drupalGet('node/2');
    $this->assertSession()->elementExists('xpath', '//nav[contains(@class, "tabs")]/ul/li/a[@href = "/node/2/revisions"]');
    $this->assertTrue(\Drupal::service('module_handler')->moduleExists('diff'), 'Module diff should be enabled.');
    // The "High value" badge appears.
    $this->assertSession()->elementExists('xpath', '//*[contains(@class, "badge dc-badge-high-value icon-bi-award-fill")][text() = "High value"]');

    // Publish the data_set.
    $this->drupalGet('node/2/build');
    $edit = [
      'major_edit' => '1',
      'edit-full-review' => TRUE,
    ];
    $this->submitForm($edit, 'Publish');
    $this->assertSession()->pageTextContains('Metadata record published');
    $this->isoDateTest(TRUE, FALSE);
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
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-views-blockdashboard-moderation-blocks-dashboard-unpublished")]//div[normalize-space(text()) = "You currently do not have any draft metadata records."]');
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-views-blockdashboard-blocks-dashboard-needs-review")]//div[normalize-space(text()) = "You currently have no metadata records needing review."]');
    // Metadata record count message.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "block-bc-dc-content-summary")]//*[text() = "You currently have no published metadata records."]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-content-summary")]//*[text() = "You have 1 published metadata record that has been bookmarked 1 times."]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-content-summary")]//a[text() = "Manage metadata records"]');

    // Test data set update message.
    //
    // Recently-bookmarked data set has no data set updated message.
    $this->assertSession()->pageTextNotContains('Updated:');
    // Set the updated date later than the bookmark date.
    $data_set = Node::load(2);
    $data_set->set('field_modified_date', (new \DateTime('tomorrow'))->format('Y-m-d\TH:i:s'))->save();
    // The data set updated message should appear.
    $this->drupalGet('user');
    $args = [
      ':data_set_path' => $data_set_path,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//section[contains(@class, "block-views-blockbookmarks-dashboard-bookmarks")]//*
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
    $element = $this->xpath('//div[contains(@class, "view-watchdog")]/div/table/tbody/tr/td/a[contains(@title, "A asset you have bookmarked has been updated")]');
    $element = reset($element);
    $title = $element->getAttribute('title');
    preg_match('/^GC Notify disabled[^:]+: (.+)/', $title, $matches);
    $gcnotify_request = json_decode($matches[1]);
    // Run tests on the request.
    $this->assertEquals('A asset you have bookmarked has been updated', $gcnotify_request->rows[1][1]);
    $this->assertMatchesRegularExpression('(The following asset has been updated:
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

    // "Assets used" uses fieldset/legend.
    $this->drupalGet('node/2/build');
    $this->click('a[aria-label = "Edit Section 3"]');
    $this->assertSession()->elementExists('xpath', '//div[@id = "edit-field-data-sets-used-wrapper"]/div/fieldset/legend[text() = "Assets used"]');

    // Import data columns page.
    $this->drupalGet('node/2/build');
    $this->click('a[aria-label = "Edit Section 6"]');
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
      'ods',
      'xlsx',
      'tsv',
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
    // Publish the new columns.
    $edit = [
      'major_edit' => '0',
    ];
    $this->submitForm($edit, 'Publish');
    // Column description is displayed with HTML tags.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--name-field-column-description")]/div/p/strong[text() = "Column description"]');

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
    $this->isoDateTest(TRUE, TRUE);

    // Anonymous has access to download csv for Metadata record when file has
    // been uploaded.
    $this->drupalGet('node/2/download/columns/csv');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Integer');
    $this->assertSession()->responseContains('Name');
    // Anonymous has access to download xlsx for Metadata record when file has
    // been uploaded.
    $this->drupalGet('node/2/download/columns/xlsx');
    $this->assertSession()->statusCodeEquals(200);

    // Anonymous dashboard.
    $this->drupalGet('home');
    // Content block exists.
    $this->assertSession()->elementExists('xpath', '//div[@id = "block-dc-theme-content"]');
    // Search block exists.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "bcbb-search-api-form")]');

    // Test that home page text block can be edited by the DC admin.
    $this->drupalLogin($this->users['Test Data catalogue administrator']);
    // Put test text onto the block.
    $this->drupalGet('admin/content/block');
    $this->assertSession()->statusCodeEquals(200);
    $this->clickLink('Home page text');
    $edit = [
      'edit-body-0-value' => 'Home page text edit ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    // Check it is appears on the home page.
    $this->drupalGet('home');
    $this->assertSession()->pageTextContains($edit['edit-body-0-value']);

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
    $xpath = $this->assertSession()->buildXPathQuery('//nav[@class = "book-navigation"]/ul[@aria-label = "Document navigation"]/li/a[@title = "Go to next page"]/*[contains(text(), :title)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Default "Book traversal links" header is not present.
    $this->assertSession()->pageTextNotContains('Book traversal links');
    // Printer-friendly version.
    $this->assertSession()->elementExists('xpath', '//div[@class = "node__links"]/ul/li/a[contains(text(), "Printer-friendly version")]');

    // Child page.
    $this->drupalGet($child_url);
    // Child page has book navigation block in sidebar with class on active.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "region-sidebar-second")]/div[@id = "block-dc-theme-booknavigation"]/ul/li[@class = "active"]/ul/li[not(@class)]');
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
      'field_abbr_full_name' => 'First full name ' . $this->randomString(),
    ];
    $info_schedule_terms[0] = Term::create($info_schedule_values[0]);
    $info_schedule_terms[0]->save();
    // Second.
    $info_schedule_values[1] = [
      'vid' => 'information_schedule',
      'name' => 'information schedule Two ' . $this->randomString(),
      'field_abbr_full_name' => 'Second full name ' . $this->randomString(),
      'field_schedule_number' => 'schedule_number 1 ' . $this->randomMachineName(),
      'field_classification_code' => 'classification_code 1 ' . $this->randomMachineName(),
      'parent' => $info_schedule_terms[0]->id(),
    ];
    $info_schedule_terms[1] = Term::create($info_schedule_values[1]);
    $info_schedule_terms[1]->save();
    // Third.
    $info_schedule_values[2] = [
      'vid' => 'information_schedule',
      'name' => 'information schedule Three ' . $this->randomString(),
      'field_abbr_full_name' => 'Third full name ' . $this->randomString(),
      'parent' => $info_schedule_terms[1]->id(),
      'field_schedule_number' => 'schedule_number 2 ' . $this->randomMachineName(),
      'field_classification_code' => 'classification_code 2 ' . $this->randomMachineName(),
      'field_active_period' => $record_life_cycle_duration_entity->id(),
      'field_active_period_extension' => $this->randomMachineName(),
      'field_semi_active_period' => $record_life_cycle_duration_entity->id(),
      'field_semi_active_extension' => $this->randomMachineName(),
    ];
    $info_schedule_terms[2] = Term::create($info_schedule_values[2]);
    $info_schedule_terms[2]->save();
    // Fourth.
    $info_schedule_values[3] = [
      'vid' => 'information_schedule',
      'name' => 'information schedule Four ' . $this->randomString(),
      'field_abbr_full_name' => 'Fourth full name ' . $this->randomString(),
      'parent' => $info_schedule_terms[2]->id(),
      'field_schedule_number' => 'schedule_number 3 ' . $this->randomMachineName(),
      'field_classification_code' => 'classification_code 3 ' . $this->randomMachineName(),
      'field_active_period' => $record_life_cycle_duration_entity->id(),
      'field_active_period_extension' => $this->randomMachineName(),
      'field_semi_active_period' => $record_life_cycle_duration_entity->id(),
      'field_semi_active_extension' => $this->randomMachineName(),
    ];
    $info_schedule_terms[3] = Term::create($info_schedule_values[3]);
    $info_schedule_terms[3]->save();
    // Set field_information_schedule to value with child.
    $data_set = Node::load(2);
    $data_set->set('field_information_schedule', $info_schedule_terms[1]->id())->save();
    // Test that the "Business category" does not appear.
    $this->drupalGet('node/2');
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-information-schedule")]
      [div[@class = "field__label"][normalize-space(text()) = "Business category"]]', $args);
    $this->assertSession()->elementNotExists('xpath', $xpath);

    // Set field_information_schedule to value without child.
    $data_set->set('field_information_schedule', $info_schedule_terms[3]->id())->save();

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
    // Test that the "Business category" appears with a link.
    $args = [
      ':name' => $info_schedule_values[3]['name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-information-schedule")]
      [div[@class = "field__label"][normalize-space(text()) = "Business category"]]
      [div[@class = "field__item"]/a[text() = :name]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Schedule code.
    $args = [
      ':field_schedule_code' => $info_schedule_values[1]['field_classification_code'] . '-' . $info_schedule_values[2]['field_classification_code'] . '-' . $info_schedule_values[3]['field_classification_code'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-schedule-code")]
      [div[@class = "field__label"][normalize-space(text()) = "IM classification code"]]
      [div[@class = "field__item"][text() = :field_schedule_code]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Update a parent term and see the field_schedule_code updated in children.
    $new_field_classification_code = $this->randomMachineName();
    $info_schedule_terms[0]->set('field_classification_code', $new_field_classification_code)->save();
    $reloaded_term = Term::load($info_schedule_terms[2]->id());
    $expected = $new_field_classification_code . '-' . $info_schedule_values[1]['field_classification_code'] . '-' . $info_schedule_values[2]['field_classification_code'];
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
      'edit-info-schedule-pre-title' => 'Information schedule pre-title ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->elementExists('xpath', '//div[@class = "messages-list"]//div[contains(text(), "The configuration options have been saved.")]');
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
    $this->assertSession()->elementNotExists('xpath', '//section[contains(@class, "block-views-blockdashboard-blocks-dashboard-needs-review")]//div[normalize-space(text()) = "You currently have no metadata records needing review."]');
    $args = [
      // This "Review overdue" is from View dashboard_blocks,
      // dashboard_needs_review, "Content: Review status", Rewrite results.
      ':review_overdue_message' => 'Review overdue',
      ':data_set_title' => $data_set_title,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//section[contains(@class, "block-views-blockdashboard-blocks-dashboard-needs-review")]//tr
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
      'field_data_set_type' => $data_set_type_root_report_term->id(),
      'edit-field-primary-responsibility-org' => 3,
    ];
    $this->submitForm($edit, 'Create');
    $this->assertSession()->pageTextContains('Metadata record created.');
    // Go to build page for this node.
    $this->click('a[href = "/node/6/build"]');
    // Test absence of fields that should only appear on "Data" data_set nodes.
    foreach ($fields_to_hide as $field_key) {
      // This field does appear on "Report" data_set nodes.
      if ($field_key === 'field_data_sets_used') {
        continue;
      }
      $args = [
        ':class' => 'field--name-' . str_replace('_', '-', $field_key),
      ];
      $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, :class)]', $args);
      $this->assertSession()->elementNotExists('xpath', $xpath);
    }
    // There is no Section 5 or 6 because this is a Report.
    $this->assertSession()->pageTextNotContains('Section 5');
    $this->assertSession()->elementNotExists('xpath', '//*[contains(@class, "node--view-mode-section-5")]');
    $this->assertSession()->pageTextNotContains('Section 6');
    $this->assertSession()->elementNotExists('xpath', '//*[contains(@class, "node--view-mode-section-6")]');
    // Message that required field is empty.
    $this->assertSession()->elementExists('xpath', '//form[@id = "bc-dc-workflow-block-form"]
      [p[text() = "The following fields must be completed before publishing:"]]
      [//ul/li[text() = "Visibility"]]');
    // No publish button.
    $this->assertSession()->buttonNotExists('Publish');
    // Complete the missing fields.
    // Section 2.
    $this->click('a[aria-label = "Edit Section 2"]');
    $public_label = $this->xpath('//fieldset[@id = "edit-field-visibility--wrapper"]//label[text() = "Public"]');
    $public_label = reset($public_label);
    $field_contact_name = 'Contact Name ' . $this->randomString();
    $field_contact_email = 'contact-email-' . $this->randomMachineName() . '@example.com';
    $edit = [
      'edit-body-0-value' => 'Summary ' . $this->randomString(),
      $public_label->getAttribute('for') => TRUE,
      'edit-field-visibility-3' => '3',
      'edit-field-contact-name-0-value' => $field_contact_name,
      'edit-field-contact-email-0-value' => $field_contact_email,
    ];
    $this->submitForm($edit, 'Save');
    // Section 3.
    $this->click('a[aria-label = "Edit Section 3"]');
    // Complete required fields.
    $edit = [
      'edit-field-personal-information-0' => '0',
      'field_security_classification' => $security_classification_term->id(),
    ];
    $this->submitForm($edit, 'Save');
    // Publish button now exists.
    $this->assertSession()->buttonExists('Publish');
    // Add revision log message and publish.
    $edit = [
      'edit-revision-log-message' => 'Revision log message ' . $this->randomString(),
      'major_edit' => '0',
    ];
    $this->submitForm($edit, 'Publish');
    $this->assertSession()->pageTextContains('Metadata record published');
    // "Personal information" badge does not appear.
    $this->assertSession()->elementNotExists('xpath', '//span[contains(@class, "badge text-bg-warning")][text() = "Personal information"]');
    // There are no related documents.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "field--name-field-related-document")]');
    // Test field_visibility display.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field_visibility")]/div[normalize-space(text()) = "Public"]');
    // No list when "Public".
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "field_visibility")]//ul');
    // Contact name.
    $args = [
      ':link' => 'mailto:' . $field_contact_email,
      ':text' => $field_contact_name,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field_contact")]
      [div[contains(@class, "field__label")][text() = "Contact"]]
      [div[contains(@class, "field__item")]/a[@href = :link][text() = :text]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Revision log message appears on revisions tab.
    $this->clickLink('Revisions');
    $args = [
      ':revision_log' => $edit['edit-revision-log-message'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//*[@class = "revision-log"][text() = :revision_log]', $args);
    // On Build page, field_data_sets_used is empty.
    $this->clickLink('Build');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--name-field-data-sets-used")]/div[@class = "field__item"]/em[text() = "Optional"]');
    // There are no related documents.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--name-field-related-document")]//em[@class = "field-optional"][text() = "Optional"]');
    // On Build page, no workflow block when latest revision is published.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-workflow-block")]//*[contains(text(), "Latest revision is published")]');

    // The data_set has never had a full review.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--label-inline")][contains(@class, "field--name-field-last-review-date")][div[@class = "field__label"][text() = "Last review date"]]/div/em[text() = "Never"]');
    // Submit a full review.
    $edit = [
      'edit-full-review' => '1',
    ];
    $this->submitForm($edit, 'Update');
    // Return to the Build page and the review date is now today.
    $this->clickLink('Build');
    $args = [
      ':class' => 'field--name-field-last-review-date',
      ':label' => 'Last review date',
      ':text' => date('Y-m-d'),
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--label-inline")][contains(@class, :class)][div[@class = "field__label"][text() = :label]]/div/time[text() = :text]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    $this->click('a[aria-label = "Edit Section 2"]');
    // Uncheck "Public" access in field_visibility.
    $edit = [
      $public_label->getAttribute('for') => NULL,
    ];
    $this->submitForm($edit, 'Save');

    $this->click('a[aria-label = "Edit Section 3"]');
    // Test that self-referencing is not allowed in field_data_sets_used.
    $edit = [
      'edit-field-data-sets-used-0-target-id' => 'Title (6)',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('This entity (node: 6) cannot be referenced.');
    // Set node/2 as a data_set used by this data_set.
    $edit = [
      'edit-field-data-sets-used-0-target-id' => 'Title (2)',
      // Add "Personal information" badge.
      'edit-field-personal-information-1' => '1',
    ];
    $this->submitForm($edit, 'Save');
    // Add a related document.
    $this->click('a[aria-label = "Edit Section 4"]');
    $this->submitForm([], 'Add Document');
    $related_document_title = 'Related document title ' . $this->randomString();
    $related_document_uri = 'http://' . $this->randomMachineName() . '.example.com/';
    $edit = [
      'edit-field-related-document-0-subform-field-paragraph-document-type' => $document_type_1->id(),
      'edit-field-related-document-0-subform-field-paragraph-document-title-0-value' => $related_document_title,
      'edit-field-related-document-0-subform-field-paragraph-document-link-0-value' => $related_document_uri,
    ];
    $this->submitForm($edit, 'Save');
    // There is now a related document.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--name-field-related-document")]//div[@class = "field__item"][text() = "1"]');
    // field_data_sets_used is not empty. This demonstrates that the Build page
    // is showing the latest version not the default version.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "field--name-field-data-sets-used")]/div[@class = "field__item"]/em[text() = "Optional"]');
    $args = [
      ':data_set_title' => $data_set_title,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-data-sets-used")]/div[@class = "field__items"]/div[@class = "field__item"]/a[text() = :data_set_title]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // View page still does not have field_data_sets_used.
    $this->clickLink('Current published');
    $this->assertSession()->elementNotExists('xpath', '//div[text() = "Assets used"]');
    // Publish the changes.
    $this->clickLink('Build');
    $edit = [
      'major_edit' => '0',
    ];
    $this->submitForm($edit, 'Publish');
    $this->assertSession()->pageTextContains('Metadata record published');
    // Related documents appear.
    $args = [
      ':href' => $related_document_uri,
      ':text' => $document_type_1->label() . ': ' . $related_document_title,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-related-document")]//ul/li/a[@href = :href][text() = :text]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // Page has "Assets used" with link to node/2.
    $dc_lineage = $this->assertSession()->elementExists('xpath', '//details[@class = "dc-lineage"]');
    // Uses.
    $args = [
      ':data_set_title' => $data_set_title,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//section[@aria-label = "This record uses the following records"]//a[text() = :data_set_title]', $args);
    $this->assertSession()->elementExists('xpath', $xpath, $dc_lineage);
    // This data_set.
    $args = [
      ':data_set_title' => $data_set_title_2,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[not(@aria-label)][contains(text()[2], :data_set_title)]/em[text() = "This report:"]', $args);
    $this->assertSession()->elementExists('xpath', $xpath, $dc_lineage);
    // Used-in.
    $this->assertSession()->elementNotExists('xpath', '//div[text() = "Used-in data sets"]');

    // Test field_visibility display.
    // "Public" was removed above.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "field_visibility")]/div[normalize-space(text()) = "Public"]');
    // The one organization is shown and not duplicated even though it is both
    // OPR and in field_visibility.
    $field_visibility = $this->xpath('//div[contains(@class, "field_visibility")]//ul/li');
    $this->assertCount(1, $field_visibility, 'Page has 1 field_visibility value.');
    $this->assertEquals($test_org_names[2], $field_visibility[0]->getText());
    // Re-check "Public" access in field_visibility.
    $this->clickLink('Build');
    $this->click('a[aria-label = "Edit Section 2"]');
    $edit = [
      $public_label->getAttribute('for') => '1',
    ];
    $this->submitForm($edit, 'Save');
    $edit = [
      'major_edit' => '0',
    ];
    $this->submitForm($edit, 'Publish');

    // Check node/2 for link back.
    $this->drupalGet('node/2');
    $dc_lineage = $this->assertSession()->elementExists('xpath', '//details[@class = "dc-lineage"]');
    // Uses.
    $this->assertSession()->elementNotExists('xpath', '//div[text() = "Assets used"]');
    // This data_set.
    $args = [
      ':data_set_title' => $data_set_title,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[not(@aria-label)][contains(text()[2], :data_set_title)]/em[text() = "This data:"]', $args);
    $this->assertSession()->elementExists('xpath', $xpath, $dc_lineage);
    // Used in.
    $args = [
      ':data_set_title_2' => $data_set_title_2,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//section[@aria-label = "The following records use this record"]//a[text() = :data_set_title_2]', $args);
    $this->assertSession()->elementExists('xpath', $xpath, $dc_lineage);

    // "Personal information" badge appears.
    $this->drupalGet('node/6');
    $this->assertSession()->elementExists('xpath', '//span[contains(@class, "badge text-bg-warning")][text() = "Personal information"]');
    // Permalink appears.
    $this->assertSession()->elementExists('xpath', '//div[@id = "author_permalink"]//input[substring(@value, string-length(@value) - 6) = "/node/6"]');
    // Header search block appears.
    $this->assertSession()->elementExists('xpath', '//header//div[contains(@class, "block-bcbb-search-api-block")]//input[@aria-label = "Search"]');
    // There is no add-columns page for this data_set because it is a Report.
    $this->drupalGet('node/6/add-columns');
    $this->assertSession()->statusCodeEquals(403);
    // There should be an error if there is a change in the root data_set type.
    $options = [
      'query' => ['display' => 'section_1'],
    ];
    $this->drupalGet('node/6/edit', $options);
    $edit = [
      'edit-field-data-set-type' => $data_set_type_data_term->id(),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "messages--error")]//div[contains(text(), "The metadata root record type cannot be changed.")]');
    $this->assertSession()->elementExists('xpath', '//input[@id = "edit-field-data-set-type"][contains(@class, "error")]');

    // Test Content page.
    $this->drupalGet('admin/content');
    // Node of type page does not have a "Build" operation.
    $this->assertSession()->elementNotExists('xpath', '//li[contains(@class, "bc-dc-build")][contains(@class, "dropbutton__item")]/a[@href = "/node/1/build?destination=/admin/content"][text() = "Build"]');
    // Node of type data_set has a "Build" operation.
    $this->assertSession()->elementExists('xpath', '//li[contains(@class, "bc-dc-build")][contains(@class, "dropbutton__item")]/a[@href = "/node/2/build?destination=/admin/content"][text() = "Build"]');

    // Data set dashboard.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    // Metadata record count message.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "block-bc-dc-content-summary")]//*[text() = "You currently have no published metadata records."]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-content-summary")]//*[text() = "You have 2 published metadata records that have been bookmarked 2 times."]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-bc-dc-content-summary")]//a[text() = "Manage metadata records"]');

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
      ':full' => ' (' . $record_life_cycle_duration_values['field_abbr_full_name'] . ')',
      ':text' => $record_life_cycle_duration_values['name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-active-period")]/div[abbr[text() = :text]][contains(text(), :full)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-semi-active-period")]/div[abbr[text() = :text]][contains(text(), :full)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Information schedule type.
    $args = [
      ':item' => $info_schedule_values[0]['name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-abbr-full-name")]
      [div[@class = "field__label"][text() = "Information schedule type"]]
      [div[@class = "field__item"][text() = :item]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Information schedule name.
    $args = [
      ':item' => $info_schedule_values[1]['field_abbr_full_name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-abbr-full-name")]
      [div[@class = "field__label"][text() = "Information schedule name"]]
      [div[@class = "field__item"][text() = :item]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Business function.
    $args = [
      ':item' => $info_schedule_values[2]['field_abbr_full_name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-abbr-full-name")]
      [div[@class = "field__label"][text() = "Business function"]]
      [div[@class = "field__item"][text() = :item]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Business category.
    $args = [
      ':item' => $info_schedule_values[3]['field_abbr_full_name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-abbr-full-name")]
      [div[@class = "field__label"][text() = "Business category"]]
      [div[@class = "field__item"][text() = :item]]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);

    // For ARCS, the Information schedule name is the root term full name.
    // Since this term has not yet been visited, it will show the updated name
    // for the root term.
    //
    // Set the name of the root term.
    $info_schedule_terms[0]->setName('ARCS')->save();
    // Create a Fifth term, sibling of Fourth.
    $info_schedule_values[4] = [
      'vid' => 'information_schedule',
      'name' => 'information schedule Five ' . $this->randomString(),
      'field_abbr_full_name' => 'Fifth full name ' . $this->randomString(),
      'parent' => $info_schedule_terms[2]->id(),
      'field_schedule_number' => $this->randomMachineName(),
      'field_classification_code' => $this->randomMachineName(),
      'field_active_period' => $record_life_cycle_duration_entity->id(),
      'field_active_period_extension' => $this->randomMachineName(),
      'field_semi_active_period' => $record_life_cycle_duration_entity->id(),
      'field_semi_active_extension' => $this->randomMachineName(),
    ];
    $info_schedule_terms[4] = Term::create($info_schedule_values[4]);
    $info_schedule_terms[4]->save();
    // Visit the page for the fifth term.
    $this->drupalGet('taxonomy/term/' . $info_schedule_terms[4]->id());
    $args = [
      ':item' => $info_schedule_values[0]['field_abbr_full_name'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--name-field-abbr-full-name")]
      [div[@class = "field__label"][text() = "Information schedule name"]]
      [div[@class = "field__item"][text() = :item]]', $args);
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
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "node--unpublished")]');
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
    // Re-publish node/2.
    $this->drupalGet('node/2/build');
    $edit = [
      'major_edit' => '0',
    ];
    $this->submitForm($edit, 'Publish');
    $this->assertSession()->pageTextContains('Metadata record published');

    // Test Dashboard for DC user.
    $this->drupalLogin($this->users['Test Data catalogue user']);
    $this->drupalGet('user');
    // Block bc_dc_content_summary does not exist.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "block-bc-dc-content-summary")]');
    // View block dashboard_blocks dashboard_needs_review should not appear.
    $this->assertSession()->elementNotExists('xpath', '//section[contains(@class, "block-views-blockdashboard-blocks-dashboard-needs-review")]');
    // View block bookmarks appears.
    $this->assertSession()->elementExists('xpath', '//section[contains(@class, "block-views-blockbookmarks-dashboard-bookmarks")]');
    // View block saved_searches appears.
    // This test is in ExistingSite because search does not work in Functional.
    // $this->assertSession()->elementExists('xpath', '//div[contains(@class,
    // "block-views-blocksaved-searches-dashboard-saved-search")]');
    // "Manage" tab does not exist and cannot be visited.
    $this->assertSession()->elementNotExists('xpath', '//div[@id = "block-dc-theme-local-tasks"]/nav/nav/ul/li/a[text() = "Manage"]');
    $this->drupalGet('user/' . $this->users['Test Data catalogue manager']->id() . '/manage');
    $this->assertSession()->statusCodeEquals(404);

    // Test Dashboard for DC user.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $this->clickLink('Reports');
    $this->assertSession()->statusCodeEquals(200);
    // Dependency report.
    $this->clickLink('Dependency report');
    $this->assertSession()->statusCodeEquals(200);
    $args = [
      ':data_set_title' => $data_set_title . ' (referenced by 1 assets)',
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//details[summary[contains(text(), :data_set_title)]]', $args);
    $details = $this->assertSession()->elementExists('xpath', $xpath);
    $args = [
      ':data_set_title_2' => $data_set_title_2,
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//ol/li//a[contains(text(), :data_set_title_2)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath, $details);
    // Access to bookmarks page.
    $this->drupalGet('user/1/bookmarks');
    $this->assertSession()->statusCodeEquals(200);
    // Access to saved searches page.
    // This test is in ExistingSite because search does not work in Functional.
    // $this->drupalGet('user/1/saved-searches');
    // $this->assertSession()->statusCodeEquals(200);
    //
    // Access by authenticated user to documentation.
    $this->drupalGet('documentation');
    $this->assertSession()->statusCodeEquals(200);

    // Anonymous.
    $this->drupalLogout();
    // No access to bookmarks page.
    $this->drupalGet('user/1/bookmarks');
    $this->assertSession()->statusCodeEquals(404);
    // No access to saved searches page.
    $this->drupalGet('user/1/saved-searches');
    $this->assertSession()->statusCodeEquals(404);
    // No access to documentation.
    $this->drupalGet('documentation');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Test for ISO dates in page content.
   *
   * @param bool $published_date_should_exist
   *   Whether the "Published date" field should exist.
   * @param bool $modified_date_should_exist
   *   Whether the "Modified date" field should exist.
   */
  protected function isoDateTest(bool $published_date_should_exist, bool $modified_date_should_exist): void {
    $date_types = [
      'Published date' => $published_date_should_exist,
      'Modified date' => $modified_date_should_exist,
    ];
    foreach ($date_types as $date_type => $should_appear) {
      $args = [
        ':date_type' => $date_type,
      ];
      $xpath = $this->assertSession()->buildXPathQuery('//div[contains(@class, "field--type-datetime")][div[text() = :date_type]]//time', $args);

      if ($should_appear) {
        $time_element = $this->assertSession()->elementExists('xpath', $xpath);
        $datetime = $time_element->getAttribute('datetime');
        $this->assertSession()->assert(preg_match('/^(\d\d\d\d-[01]\d-[0-3]\d)T/', $datetime, $matches), $date_type . ' should have ISO-formatted datetime attribute.');
        $formatted_date = \Drupal::service('date.formatter')->format(strtotime($datetime), 'html_date');
        $this->assertSession()->assert($time_element->getText() === $formatted_date, $date_type . ' contents should match date in datetime attribute.');
      }
      else {
        $this->assertSession()->elementNotExists('xpath', $xpath);
      }
    }
  }

}
