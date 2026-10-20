<?php

declare(strict_types=1);

$cutoff_options = [
  'text' =>'2026-11-01 00:00:00',
  'timestamp' => 1793516400,
];

// --- OLDE ---
$cutoff_options = [
  'text' =>'2024-11-01 00:00:00',
  'timestamp' => 1730444400,
];



$date_types = [
  'datetime',
  'daterange',
  'smartdate',
  'smartdate_recurring',
];

$entity_type_manager = \Drupal::entityTypeManager();
$database = \Drupal::database();

$field_storage_storage = $entity_type_manager->getStorage('field_storage_config');
$field_config_storage = $entity_type_manager->getStorage('field_config');

printf(
  "%-25s %-18s %-12s %-45s %-10s\n",
  'Field',
  'Type',
  'Storage',
  'Bundles',
  'Count'
);

echo str_repeat('=', 130) . PHP_EOL;

foreach ($field_storage_storage->loadMultiple() as $field_storage) {

  $field_type = $field_storage->getType();

  if (!in_array($field_type, $date_types, TRUE)) {
    continue;
  }

  $field_name = $field_storage->getName();
  $entity_type = $field_storage->getTargetEntityTypeId();

  //
  // Skip Date Only fields.
  //
  if (
    in_array($field_type, ['datetime', 'daterange'], TRUE)
    && $field_storage->getSetting('datetime_type') === 'date'
  ) {
    continue;
  }

  //
  // Human-readable storage description.
  //
  switch ($field_type) {

    case 'datetime':
    case 'daterange':
      $storage_description = $field_storage->getSetting('datetime_type');
      $cutoff = $cutoff_options['text'];
      break;

    case 'smartdate':
    case 'smartdate_recurring':
      $storage_description =
        ($field_storage->getSetting('allday') ?? FALSE)
        ? 'all_day'
        : 'date_time';
      $cutoff = $cutoff_options['timestamp'];
      break;

    default:
      $storage_description = '';
      $cutoff = 'unknown';
  }

  //
  // Find bundles.
  //
  $instances = $field_config_storage->loadByProperties([
    'field_name' => $field_name,
    'entity_type' => $entity_type,
  ]);

  $bundles = [];

  foreach ($instances as $instance) {
    $bundles[] = $instance->getTargetBundle();
  }

  sort($bundles);

  //
  // Tables that may contain the field.
  //
  $tables = [];

  $data_table = "{$entity_type}__{$field_name}";
  if ($database->schema()->tableExists($data_table)) {
    $tables[] = $data_table;
  }

  $revision_table = "{$entity_type}_revision__{$field_name}";
  if ($database->schema()->tableExists($revision_table)) {
    $tables[] = $revision_table;
  }

  //
  // Discover actual SQL columns.
  //
  $columns = array_keys(
    $field_storage->getSchema()['columns'] ?? []
  );

  foreach ($tables as $table) {

    foreach ($columns as $column) {

      //
      // Drupal schema column -> SQL column.
      //
      $sql_column = "{$field_name}_{$column}";

      //
      // Only inspect date-bearing columns.
      //
      if (
        !str_ends_with($column, 'value') &&
        $column !== 'value'
      ) {
        continue;
      }

      try {

        $count = (int) $database->select($table, 't')
          ->condition($sql_column, $cutoff, '>')
          ->countQuery()
          ->execute()
          ->fetchField();

        if ($count === 0) {
          continue;
        }

        $min = $database->select($table, 't')
          ->fields('t', [$sql_column])
          ->condition($sql_column, $cutoff, '>')
          ->orderBy($sql_column, 'ASC')
          ->range(0, 1)
          ->execute()
          ->fetchField();

        $max = $database->select($table, 't')
          ->fields('t', [$sql_column])
          ->condition($sql_column, $cutoff, '>')
          ->orderBy($sql_column, 'DESC')
          ->range(0, 1)
          ->execute()
          ->fetchField();

        $examples = $database->select($table, 't')
          ->fields('t', [$sql_column])
          ->condition($sql_column, $cutoff, '>')
          ->orderBy($sql_column, 'ASC')
          ->range(0, 5)
          ->execute()
          ->fetchCol();

        echo PHP_EOL;
        echo str_repeat('-', 130) . PHP_EOL;
        echo "Field     : {$field_name}\n";
        echo "Type      : {$field_type}\n";
        echo "Storage   : {$storage_description}\n";
        echo "Bundles   : " . implode(', ', $bundles) . "\n";
        echo "Table     : {$table}\n";
        echo "Column    : {$sql_column}\n";
        echo "Count     : {$count}\n";
        echo "Earliest  : " . format_time($min) . "\n";
        echo "Latest    : " . format_time($max) . "\n";

        echo "Examples:\n";
        foreach ($examples as $example) {
          echo "  - " . format_time($example) . "\n";
        }

      }
      catch (\Throwable $e) {
        echo "ERROR {$table}.{$sql_column}: {$e->getMessage()}\n";
      }
    }
  }
}

/** function, where you pass in a time (either in timestamp format, or human-readable).
 * If human readable, it just returns it. 
 * If timestamp, it returns a string like this:
 *  "1793546400 (2026-11-01 8:20:00am)"
 */
function format_time($time) {
  if (preg_match('/^\s*\d+\s*$/', $time)) {
    $time = intval($time);
    return "{$time}   (" . date('Y-m-d g:ia', $time) . ")";
  } 
  else {
    return $time;
  }
}

