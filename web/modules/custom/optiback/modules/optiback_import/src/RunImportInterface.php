<?php

namespace Drupal\optiback_import;

/**
 * Run's the order export.
 */
interface RunImportInterface {

  /**
   * Starts the import.
   *
   * @param (optional) string $logfile
   *
   * @return boolish TRUE|FALSE
   */
  public function run($logfile = '');

}
