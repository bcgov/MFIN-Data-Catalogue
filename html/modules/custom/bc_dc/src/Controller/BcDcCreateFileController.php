<?php

namespace Drupal\bc_dc\Controller;

use Drupal\Core\Controller\ControllerBase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Create a file in csv or xlsx.
 */
class BcDcCreateFileController extends ControllerBase {

  /**
   * Returns a downloadable file.
   */
  public function createFile($node, $param) {
    $nid = $node->get('nid')->getValue()[0]['value'];
    $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid);
    $path = str_replace("/data-set/", "", $alias);
    $allowedExt = ['csv', 'xlsx'];
    if(!in_array($param, $allowedExt, TRUE)) {
      throw new NotFoundHttpException();
    }
    $entity = $this->entityTypeManager()->getStorage('node')->load($nid);
    $paragraph_field_items = $entity->get('field_columns')->referencedEntities();
    foreach ($paragraph_field_items as $paragraph) {
      // Get the translation.
      $paragraph = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph);
    }
    $sheetData = [
      ['column_allowed_values',
        'column_data_quality',
        'column_description',
        'column_name',
        'column_size',
        'column_transformations',
        'provenance_field_name',
        'provenance_field_number',
        'provenance_field_question',
        'provenance_form_name',
        'provenance_form_number',
      ],
      [$paragraph->get('field_column_allowed_values')->value,
        $paragraph->get('field_column_data_quality')->value,
        $paragraph->get('field_column_description')->value,
        $paragraph->get('field_column_name')->value,
        $paragraph->get('field_column_size')->value,
        $paragraph->get('field_column_transformations')->value,
        $paragraph->get('field_provenance_field_name')->value,
        $paragraph->get('field_provenance_field_number')->value,
        $paragraph->get('field_provenance_field_question')->value,
        $paragraph->get('field_provenance_form_name')->value,
        $paragraph->get('field_provenance_form_number')->value,
      ],
    ];

    $spreadsheet = new Spreadsheet();
    switch ($param) {
      case 'xlsx':
        $filename = $path . '_ID_' . $nid . '.xlsx';
        // Save to file.
        $worksheets = new Worksheet($spreadsheet, "Sheet 1");
        $spreadsheet->addSheet($worksheets, 0);
        $worksheets->fromArray($sheetData);
        foreach ($worksheets as $worksheet) {
          foreach ($worksheet->getColumnIterator() as $column) {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(TRUE);
          }
        }
        $writer = new Xlsx($spreadsheet);
        $directory = '/html/sites/default/files/' . $filename;
        $writer->save($directory);

      case 'csv':
        $filename = $path . '_ID_' . $nid . '.csv';
        $worksheets = new Worksheet($spreadsheet, "Sheet 1");
        $spreadsheet->addSheet($worksheets, 0);
        $worksheets->fromArray($sheetData);
        foreach ($worksheets as $worksheet) {
          foreach ($worksheet->getColumnIterator() as $column) {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(TRUE);
          }
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        $directory = 'html/sites/default/files/' . $filename;
        $writer->setDelimiter(',');
        $writer->setEnclosure('');
        $writer->setLineEnding("\r\n");
        $writer->setSheetIndex(0);
        $writer->save($directory);
    }

    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . basename($filename) . "\"");

    return new BinaryFileResponse($directory, 200);

  }

}
