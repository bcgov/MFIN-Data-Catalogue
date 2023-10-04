<?php

namespace Drupal\bc_dc\Controller;

use Drupal\Core\Controller\ControllerBase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use vendor\PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use vendor\PhpOffice\PhpSpreadsheet\Writer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class BcDcCreateFileController extends ControllerBase {

  /**
   * Returns a downloadable file.
   */
  public function createFile($node, $param) {
    // $paramsAllowed [] = [['csv'], ['xlsx']]; 
  
    if($param === 'csv' || $param === 'excel') {
      $nid = $node->get('nid')->getValue()[0]['value'];
      $title = $node->get('title')->getValue()[0]['value'];
      $paragraph = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $paragraph_field_items = $paragraph->get('field_columns')->referencedEntities();
      foreach ($paragraph_field_items as $paragraph) {
        // Get the translation
        $paragraph = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph);
      }
      $sheetData= [
        ['Column allowed values',
          'Column data quality',
          'Column description',
          'Column name',
          'Column size',
          'Column transformations',
          'Provenance field name',
          'Provenance field number',
          'Provenance field question',
          'Provenance form name',
          'Provenance form number'],
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
          $paragraph->get('field_provenance_form_number')->value,]
      ];
      
      $spreadsheet = new Spreadsheet();
      
      if ($param === 'excel') {
        $filename = $title . '_ID_' . $nid . '.xlsx';
        // Save to file.
        $worksheets = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, "Sheet 1");
        $spreadsheet->addSheet($worksheets, 0);
        $worksheets->fromArray($sheetData);
        foreach ($worksheets as $worksheet)
        {
          foreach ($worksheet->getColumnIterator() as $column)
          {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
          }
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $directory = 'public://data_dictionary/' . $filename;
        $writer->save($directory);

      }
      else if ($param === 'csv') {
        $filename = $title . '_ID_' . $nid . '.csv';
        $mySpreadsheet = new Spreadsheet();
        $worksheets = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($mySpreadsheet, "Sheet 1");
        $mySpreadsheet->addSheet($worksheets, 0);
        $worksheets->fromArray($sheetData);
        foreach ($worksheets as $worksheet)
        {
          foreach ($worksheet->getColumnIterator() as $column)
          {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
          }
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($mySpreadsheet);
        $directory = 'public://data_dictionary/' . $filename;
        $writer->setDelimiter(';');
        $writer->setEnclosure('"');
        $writer->setLineEnding("\r\n");
        $writer->setSheetIndex(0);
        $writer->save($directory);
      
      }  

    }
    else {
      throw new NotFoundHttpException();
    }

    $uri = $directory;
    $file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $uri]);
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . basename($filename) . "\"");

    return new BinaryFileResponse($uri, 200);
    
  }
}
