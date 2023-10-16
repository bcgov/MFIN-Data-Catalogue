<?php

namespace Drupal\bc_dc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Create a file in csv or xlsx.
 */
class BcDcCreateFileController extends ControllerBase {

  const SUPPORTED_EXTENSIONS = [
    'csv',
    'xlsx',
  ];

  /**
   * Create a BcDcCreateFileController instance.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity.repository service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file_system service.
   * @param \Drupal\path_alias\AliasManagerInterface $pathAliasManager
   *   The path_alias.manager service.
   */
  public function __construct(
    protected EntityRepositoryInterface $entityRepository,
    protected FileSystemInterface $fileSystem,
    protected AliasManagerInterface $pathAliasManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): object {
    return new static(
      $container->get('entity.repository'),
      $container->get('file_system'),
      $container->get('path_alias.manager'),
    );
  }

  /**
   * Generate the download file and serves it to the browser.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $format
   *   The format of the file to serve.
   *
   * @return Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The file to download.
   */
  public function createFile(NodeInterface $node, string $format): BinaryFileResponse {
    // Not Found if $format is not a supported file extension.
    if (!in_array($format, static::SUPPORTED_EXTENSIONS, TRUE)) {
      throw new NotFoundHttpException();
    }
    $columnHeaders = [
[
        'column_allowed_values',
        'column_data_quality',
        'column_description',
        'column_name',
        'column_size',
        'column_transformations',
        'column_type',
        'instance_of',
        'metadata_type',
        'provenance_field_name',
        'provenance_field_number',
        'provenance_field_question',
        'provenance_form_name',
        'provenance_form_number',
]
    ];
    $results = array();
//    $results = array_merge($results, $columnHeaders);
    $paragraph_field_items = $node->get('field_columns')->referencedEntities();
    foreach ($paragraph_field_items as $paragraph) {
      // Get the translation.
      $paragraph = $this->entityRepository->getTranslationFromContext($paragraph);
      $rows = [
        $paragraph->get('field_column_allowed_values')->value,
        $paragraph->get('field_column_data_quality')->value,
        $paragraph->get('field_column_description')->value,
        $paragraph->get('field_column_name')->value,
        $paragraph->get('field_column_size')->value,
        $paragraph->get('field_column_transformations')->value,
        $paragraph->get('field_column_type')->value,
        $paragraph->get('field_instance_of')->value,
        $paragraph->get('field_metadata_type')->value,
        $paragraph->get('field_provenance_field_name')->value,
        $paragraph->get('field_provenance_field_number')->value,
        $paragraph->get('field_provenance_field_question')->value,
        $paragraph->get('field_provenance_form_name')->value,
        $paragraph->get('field_provenance_form_number')->value,
      ];

//      $results = array_merge($results, $rows);
      $results[] = $rows;
    }

    $sheetData = array_merge($columnHeaders, $results);


    // Make a filename like "node-file-path_ID_12.csv".
    $node_path = $this->pathAliasManager->getAliasByPath('/node/' . $node->id());
    $node_path = basename($node_path);
    $filename = $node_path . '_ID_' . $node->id() . '.' . $format;

    // Create a file path to the temp directory. PhpSpreadsheet does not work
    // with stream wrappers.
    $file_path = $this->fileSystem->getTempDirectory() . '/' . $filename;

    // Generate the spreadsheet object and save to $file_path.
    $spreadsheet = new Spreadsheet();
    $worksheets = new Worksheet($spreadsheet);
    $spreadsheet->addSheet($worksheets);
    $worksheets->fromArray($sheetData);
    // Remove first blank worksheet
    $sheetIndex = $spreadsheet->getIndex(
      $spreadsheet->getSheetByName('Worksheet')
    );
    $spreadsheet->removeSheetByIndex($sheetIndex);

    switch ($format) {
      case 'xlsx':
        $writer = new Xlsx($spreadsheet);
        break;

      case 'csv':
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(',');
        $writer->setEnclosure('');
        $writer->setLineEnding("\r\n");
        $writer->setSheetIndex(0);
        break;
    }
    $writer->save($file_path);

    // Cause the browser to save the file with the specified filename.
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // Serve the file.
    return new BinaryFileResponse($file_path, 200);
  }

}
