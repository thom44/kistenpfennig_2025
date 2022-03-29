<?php

namespace Drupal\optiback_import;

/**
 * Run's the order export.
 */
interface RunImportInterface {

  /**
   * Starts the import.
   *
   * @param (optional) boolean $env
   *  The environment dev skips backups.
   * @param (optional) string $options
   *  The options product|invoice|tracking.
   *
   * @return boolish TRUE|FALSE
   */
  public function run($env = 'prod', $options = []);

}
