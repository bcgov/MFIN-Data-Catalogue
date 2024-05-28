<?php

namespace Drush\Commands\bc_dc;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Add to the core:status command.
 */
class BcDcStatusFieldDrushCommands extends DrushCommands {

  /**
   * Causes bc-dc-status-field to appear by default.
   *
   * @param \Symfony\Component\Console\Event\ConsoleCommandEvent $event
   *   The command event.
   *
   * @see ::addCoreStatusField()
   *
   * @hook command-event core:status
   */
  public function addCoreStatusDefaultField(ConsoleCommandEvent $event): void {
    $options = $event->getCommand()->getDefinition()->getOptions();

    // Add the custom field to the fields to be displayed when core:status runs.
    $default_fields = $options['fields']->getDefault();
    // If no default is provided, use this list.
    if (!$default_fields) {
      $default_fields = 'drupal-version,uri,db-driver,db-hostname,db-port,db-username,db-name,db-status,bootstrap,theme,admin-theme,php-bin,php-conf,php-os,php-version,drush-script,drush-version,drush-temp,drush-conf,install-profile,root,site,files,private,temp';
    }
    // Add the custom field.
    $default_fields .= ',bc-dc-status-field';
    $options['fields']->setDefault($default_fields);
  }

  /**
   * Add to the core:status command.
   *
   * @param mixed $result
   *   The result of the core:status command before alteration.
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The Drush command data.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *   The result of the core:status command with the new field added.
   *
   * @see ::addCoreStatusDefaultField()
   *
   * @hook alter core:status
   */
  public function addCoreStatusField(mixed $result, CommandData $commandData): mixed {
    /**
     * @var \Consolidation\OutputFormatters\Options\FormatterOptions
     */
    $formatter_options = $commandData->formatterOptions();

    // Add label for custom field.
    $field_labels = $formatter_options->get($formatter_options::FIELD_LABELS);
    $field_labels['bc-dc-status-field'] = 'Data catalogue';
    $formatter_options->setFieldLabels($field_labels);

    // Add content for custom field.
    $result['bc-dc-status-field'] = 'Reminder to execute the following command:
source /vault/secrets/secrets.env';

    return $result;
  }

}
