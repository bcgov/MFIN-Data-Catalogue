<?php

namespace Drupal\bc_dc\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a form to add data_set columns from CSV or spreadsheet import.
 *
 * Normal flow:
 * 1. ::buildForm() displays page 1 of the form. User uploads a file.
 * 2. ::validateForm() validates and saves the file contents in the form state.
 * 3. ::submitFormPage1() sets page 2 to be displayed.
 * 4. ::buildForm() displays page 2 of the form. User confirms their upload.
 * 5. ::submitForm() adds the columns to the data_set.
 */
class BcDcAddColumnsForm extends FormBase {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bc_dc_add_columns_form';
  }

  /**
   * Return the field definitions for the data_column field in data_set.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definitions, keyed by field name.
   */
  public static function getDataSetFieldDefinitions(): array {
    return \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('paragraph', 'data_column');
  }

  /**
   * Return an array of data_column field names that start with "field_".
   *
   * @return string[]
   *   An array of field names with "field_" removed.
   */
  public static function getDataSetFields(): array {
    $fields = [];
    $field_definitions = static::getDataSetFieldDefinitions();
    foreach (array_keys($field_definitions) as $name) {
      if (str_starts_with($name, 'field_')) {
        $fields[] = substr($name, 6);
      }
    }
    return $fields;
  }

  /**
   * Return the values in a taxonomy vocabulary.
   *
   * @param string[] $vid
   *   The vocabulary ID.
   *
   * @return string[]
   *   The labels of the terms in a vocabulary keyed by tid.
   */
  protected function getVocabularyValues(string $vid): array {
    $term_objects = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $vid,
    ]);

    $terms = [];
    foreach ($term_objects as $tid => $term) {
      $terms[$tid] = $term->label();
    }

    return $terms;
  }

  /**
   * Format a table of data_set columns.
   *
   * Display entity references fields as their labels.
   *
   * @param string[] $import_file_header
   *   An array of import file headers.
   * @param array[] $import_file_contents
   *   An array of import file contents.
   *
   * @return array
   *   A Drupal render array.
   */
  protected function buildTable(array $import_file_header, array $import_file_contents): array {
    $taxonomy_term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Convert any cells that are entity references to their label.
    foreach ($import_file_contents as $row_index => $row) {
      foreach ($row as $column_index => $cell) {
        if (is_array($cell)) {
          if (isset($cell['target_id'])) {
            // Valid value, display label.
            $cell_value = $taxonomy_term_storage->load($cell['target_id'])->label();
          }
          else {
            // Invalid value, display error.
            $cell_value = [
              'data' => 'Invalid: ' . $cell['invalid_value'],
              'class' => ['error'],
            ];
          }
          $import_file_contents[$row_index][$column_index] = $cell_value;
        }
      }
    }

    return [
      '#type' => 'table',
      '#header' => $import_file_header,
      '#rows' => $import_file_contents,
      '#prefix' => '<div class="table-responsive">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Read a spreadsheet file and return an array of data from the first sheet.
   *
   * All formats supported by PhpSpreadsheet are supported.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file to read.
   *
   * @return array
   *   An array of file data.
   */
  private static function readSpreadsheet(FileInterface $file): array {
    // Get the filename.
    $filename = $file->getFilename();
    if (!$filename) {
      throw new \Exception('Unable to get file name.');
    }

    $filePath = $file->getFileUri();
    // The Xlsx reader cannot handle stream wrappers.
    // @see https://github.com/PHPOffice/PhpSpreadsheet/issues/1931
    $filePath = \Drupal::service('file_system')->realpath($filePath);

    // Create a reader for this type of file.
    $inputFileType = IOFactory::identify($filePath);
    $reader = IOFactory::createReader($inputFileType);

    // If the format supports multiple sheets per file, import the first.
    if (method_exists($reader, 'listWorksheetNames')) {
      $worksheetNames = $reader->listWorksheetNames($filePath);
      $first_sheet_name = reset($worksheetNames);
      $reader->setLoadSheetsOnly($first_sheet_name);
    }

    // Load the file.
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($filePath);

    // Get array of cell data.
    $data = $spreadsheet->getActiveSheet()->toArray();

    $data = static::removeEmptyRowsCols($data);

    return $data;
  }

  /**
   * Remove empty trailing rows and columns from an array.
   *
   * @param array $array
   *   The array to act on.
   *
   * @return array
   *   The array with trailing rows and columns removed if all values are NULL.
   */
  private static function removeEmptyRowsCols(array $array): array {
    // Remove trailing rows that consist entirely of cells that are NULL.
    $row_empty = TRUE;
    while ($row_empty) {
      $last_row_key = array_key_last($array);
      // Empty array.
      if (is_null($last_row_key)) {
        break;
      }
      foreach ($array[$last_row_key] as $cell) {
        if (!is_null($cell)) {
          $row_empty = FALSE;
          break;
        }
      }
      if ($row_empty) {
        unset($array[$last_row_key]);
      }
    }

    // Remove trailing columns that consist entirely of cells that are NULL.
    // This assumes that all rows are the same length, which ::toArray() does.
    $col_empty = TRUE;
    $first_row_key = array_key_first($array);
    while ($col_empty && !is_null($first_row_key)) {
      $last_col_key = array_key_last($array[$first_row_key]);
      foreach ($array as $row) {
        if (!is_null($row[$last_col_key])) {
          $col_empty = FALSE;
          break;
        }
      }
      if ($col_empty) {
        foreach (array_keys($array) as $row_key) {
          unset($array[$row_key][$last_col_key]);
        }
      }
    }

    return $array;
  }

  /**
   * Process uploaded data, converting values in entity_reference fields.
   *
   * Convert field values to entity references for entity_reference fields.
   * Invalid values are convered to an array represeting the error.
   *
   * @param string[] $header
   *   An array of column headers.
   * @param string[] $contents
   *   An array of column contents. This is altered to convert field values.
   *
   * @return string[]
   *   Array of field keys which have at least one invalid value in $contents.
   */
  public function processSpreadsheet(array $header, array &$contents): array {
    $definitions = static::getDataSetFieldDefinitions();

    // Array of field keys which have at least one invalid value in $contents.
    $error_columns = [];

    // Process $contents one *column* at a time, only taxonomy term fields.
    foreach ($header as $column_index => $field) {
      // Act only on entity reference fields that refer to taxonomy terms.
      $field_key = 'field_' . $field;
      if (empty($definitions[$field_key]) || $definitions[$field_key]->getType() !== 'entity_reference') {
        continue;
      }
      $settings = $definitions[$field_key]->getSettings();
      if ($settings['handler'] !== 'default:taxonomy_term') {
        continue;
      }

      // Determine which taxonomy vocabulary.
      // This assumes the entity reference field has only one target bundle.
      $vid = reset($settings['handler_settings']['target_bundles']);

      // Get the possible values for this field.
      $terms = $this->getVocabularyValues($vid);

      // Interate over the content, looking at the current column in each row.
      // Update each row to have an array representing the term or an error.
      foreach ($contents as $row_index => $row) {
        // Get the value of this column in this row. In a TSV file, trailing
        // whitespace may have been trimmed. This means that empty fields at the
        // end of a line may not exist at all. Skip them.
        $this_value = $row[$column_index] ?? NULL;
        if (isset($this_value)) {
          $this_tid = array_search($this_value, $terms);
          if ($this_tid) {
            // Valid value.
            $this_value = [
              'target_type' => 'taxonomy_term',
              'target_id' => $this_tid,
            ];
          }
          else {
            // Invalid value, set error.
            $this_value = [
              'vid' => $vid,
              'invalid_value' => $this_value,
            ];

            $error_columns[$field] = $vid;
          }
          $contents[$row_index][$column_index] = $this_value;
        }
      }
    }

    return $error_columns;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL): array {
    // Return 404 if there is not a data_set node provided.
    if (!$node || $node->bundle() !== 'data_set') {
      throw new NotFoundHttpException();
    }

    // Select which page of the form to display.
    switch ($form_state->get('page') ?? 1) {
      // Page to allow file upload. May display errors from previous upload.
      case 1:
        // Save the nid for use in the submit handler.
        $form_state->set('nid', $node->id());

        // Add the error and unset it from the form so it does not appear again.
        if ($error = $form_state->get('error')) {
          $this->messenger()->addError($error);
          $form_state->set('error', NULL);
        }

        // If there are any invalid values, display a table of valid values for
        // entity reference columns that contain at least one invalid value.
        if ($error && $error_columns = $form_state->get('error_columns')) {
          // Unset so that this table will not appear for next upload unless
          // that upload sets it to appear.
          $form_state->set('error_columns', NULL);

          // Table of uploaded data with invalid values shown.
          $form['import_data_table'] = $this->buildTable($form_state->get('import_file_header'), $form_state->get('import_file_contents'));

          // List of valid values for columns containing at least one invalid.
          // This helps users to fix their data.
          $items = [];
          foreach ($error_columns as $field_key => $vid) {
            $items[] = $field_key . ': ' . implode(', ', $this->getVocabularyValues($vid));
          }
          $form['valid_values'] = [
            '#theme' => 'item_list',
            '#title' => $this->t('Value values for columns containing invalid values'),
            '#items' => $items,
          ];
        }

        // Sample file download link.
        $fields = static::getDataSetFields();
        $sample_file = implode(',', $fields) . "\n";
        $form['help_text'] = [
          '#markup' => $this->t('Add columns to this data set. Upload a CSV or spreadsheet file. For each row, it will add a column to this data set based on the contents of that row. <a href="data:text/plain,@file" download="sample.csv">Download sample file</a>.', ['@file' => urlencode($sample_file)]),
        ];

        // Upload component.
        $form['import_file_upload'] = [
          '#type' => 'file',
          '#title' => $this->t('Import file'),
          '#required' => TRUE,
        ];

        // Submit button.
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#submit' => ['::submitFormPage1'],
          '#value' => $this->t('Upload'),
          '#button_type' => 'primary',
        ];
        break;

      // Page to review and confirm upload data.
      case 2:
        // Warn that current columns will be removed.
        if ($node->field_columns->count()) {
          $this->messenger()->addWarning($this->t('Existing columns will be deleted when these new columns are imported.'));
        }

        // Table showing uploaded data.
        $form['check_text'] = [
          '#markup' => $this->t('Does this look right?'),
        ];
        $form['import_data_table'] = $this->buildTable($form_state->get('import_file_header'), $form_state->get('import_file_contents'));

        // Submit button.
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Import'),
          '#button_type' => 'primary',
        ];
        break;
    }

    // Cancel link back to where we came from.
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromUserInput($this->getRedirectDestination()->get()),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Validation of file upload.
    //
    // Normally, validation errors are set with $form_state->setErrorByName().
    // Instead, we set an 'error' property. If the property is set, the form
    // submission handler will not advance to the next page of the form and when
    // the form is rebuilt, the error will be added for display.
    //
    // It is done this way because if the error_columns check fails,
    // ::buildForm() needs access to data from the uploaded file to display the
    // table of invalid values. We would use $form_state->set() to send that
    // information. However, ::set() does nothing if ::setErrorByName() is
    // called as well. So, to get the information back to ::buildForm(), we need
    // to set the error without calling ::setErrorByName().
    //
    // It could be done so that only the error_columns check uses this method
    // and the others use ::setErrorByName(). However, if it is done that way
    // and a user uploads a file that fails the error_columns check and then
    // uploads a file that fails a different check, the error_columns check
    // table will still appear because we cannot use ::set() to un-set the
    // properties that cause it to appear.
    //
    // @see https://www.drupal.org/project/drupal/issues/3374239
    //
    // Act only on forms with import_file_upload.
    if (!$form_state->hasValue('import_file_upload')) {
      return;
    }

    // Save the uploaded file to the file system, checking its extension.
    $validators = [
      'file_validate_extensions' => ['csv tsv gnm gnumeric ods xls xlsx xml'],
    ];
    $import_file_upload = file_save_upload('import_file_upload', $validators);
    $import_file_upload = is_array($import_file_upload) ? reset($import_file_upload) : NULL;
    if (!($import_file_upload instanceof FileInterface)) {
      // File did not upload. Set error.
      $form_state->set('error', $this->t('Invalid file uploaded.'));
      return;
    }

    // File uploaded. Read its contents into array and delete.
    $import_file_contents = static::readSpreadsheet($import_file_upload);
    $import_file_upload->delete();
    // Handle errors. In normal use, this will not happen.
    if ($import_file_contents === NULL) {
      throw new \Exception('Unable to read uploaded file.');
    }

    // Check for empty file.
    if (!$import_file_contents) {
      $form_state->set('error', $this->t('Uploaded file was empty.'));
      return;
    }

    // The first line of the file is the header. The rest is the data.
    $import_file_header = array_shift($import_file_contents);

    // Check import file for data rows longer than the header row.
    // This will likely never happen because PhpSpreadsheet always returns an
    // array with all rows the same length.
    $header_col_count = count($import_file_header);
    foreach ($import_file_contents as $import_line) {
      if (isset($import_line[$header_col_count])) {
        $form_state->set('error', $this->t('Uploaded file has at least one data row with no header (row is longer than header row).'));
        return;
      }
    }

    // Remove the last column if it is completely empty, header and data.
    $last_col_key = array_key_last($import_file_header);
    if ($import_file_header[$last_col_key] === NULL) {
      // Check all the rows to see if they have a value in the last col.
      $last_col_empty = TRUE;
      foreach ($import_file_contents as $import_line) {
        if (isset($import_line[$last_col_key]) && $import_line[$last_col_key] !== NULL) {
          $last_col_empty = FALSE;
          break;
        }
      }
      // Remove if empty.
      if ($last_col_empty) {
        array_pop($import_file_header);
      }
    }

    // Check for empty headers.
    if (array_search(NULL, $import_file_header, TRUE)) {
      $form_state->set('error', $this->t('Uploaded file has at least one column with an empty header.'));
      return;
    }

    // Check for duplicate headers.
    $duplicate_fields = [];
    foreach (array_count_values($import_file_header) as $value => $count) {
      if ($count > 1) {
        $duplicate_fields[] = $value;
      }
    }
    if ($duplicate_fields) {
      $form_state->set('error', $this->t('Uploaded file contains duplicate column headers: @duplicate_fields', ['@duplicate_fields' => implode(', ', $duplicate_fields)]));
      return;
    }

    // Check for unknown headers.
    if ($unknown_fields = array_diff($import_file_header, static::getDataSetFields())) {
      $form_state->set('error', $this->t('File contains unknown fields: @unknown_fields', ['@unknown_fields' => implode(', ', $unknown_fields)]));
      return;
    }

    // Check for a file with no data rows.
    if (!$import_file_contents) {
      $form_state->set('error', $this->t('Uploaded file had no data rows. The first row must be column headers.'));
      return;
    }

    $column_name_key = array_search('column_name', $import_file_header, TRUE);

    // Check for missing column_name field.
    if ($column_name_key === FALSE) {
      $form_state->set('error', $this->t('Uploaded file does not have a column_name field.'));
      return;
    }

    // Check for invalid values and save with form if there are any. Invalid
    // values will be displayed to the user to allow them to fix their upload.
    // This check does not call return because it needs the header and content.
    if ($error_columns = $this->processSpreadsheet($import_file_header, $import_file_contents)) {
      $form_state->set('error_columns', $error_columns);
      $form_state->set('error', $this->t('Uploaded file had invalid values in some columns. The invalid values are shown below.'));
    }

    // Save the file data for use in the next page of the form.
    $form_state->set('import_file_header', $import_file_header);
    $form_state->set('import_file_contents', $import_file_contents);
  }

  /**
   * Submit handler for page 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitFormPage1(array &$form, FormStateInterface $form_state): void {
    // Set so that page 2 is displayed next when there are no errors.
    $error = $form_state->get('error');
    if (!$error) {
      $form_state->set('page', 2);
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $node = $this->entityTypeManager->getStorage('node')->load($form_state->get('nid'));
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');

    $import_file_header = $form_state->get('import_file_header');
    $import_file_contents = $form_state->get('import_file_contents');

    // Remove existing paragraph entities.
    // Get all the current entities.
    $old_paragraph_entities = $node->field_columns->referencedEntities();
    // Delete the first entity reference as many times as there are items.
    // The entities themselves will be deleted later after the node is saved.
    $count_of_paragraph_entities = $node->field_columns->count();
    for ($counter = 0; $counter < $count_of_paragraph_entities; $counter++) {
      $node->field_columns->removeItem(0);
    }

    // Create paragraph entities for each row of $import_file_contents.
    foreach ($import_file_contents as $row) {
      // Initial values for new entity.
      $paragraph_fields = [
        'type' => 'data_column',
      ];

      // Set the field for any values that are included.
      foreach ($import_file_header as $key => $field) {
        // Include any value that is either an entity reference (array) or a
        // non-empty string.
        if (isset($row[$key]) && (is_array($row[$key]) || strlen($row[$key]))) {
          $paragraph_fields['field_' . $field] = $row[$key];
        }
      }
      // Create the paragraph entity and add it to the node.
      $paragraph = $paragraph_storage->create($paragraph_fields);
      $node->field_columns->appendItem($paragraph);
      $paragraph->save();
    }
    $node->save();

    // Delete the old entities.
    foreach ($old_paragraph_entities as $entity) {
      $entity->delete();
    }

    // Set success message.
    $context = [
      '@count' => count($import_file_contents),
    ];
    $this->messenger()->addStatus($this->t('Added @count data columns from imported file.', $context));
  }

}
