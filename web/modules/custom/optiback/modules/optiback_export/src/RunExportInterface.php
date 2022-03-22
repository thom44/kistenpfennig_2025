<?php

namespace Drupal\optiback_export;

/**
 * Run's the order export.
 */
interface RunExportInterface {

  /**
   * Starts the export.
   *
   * @param (optional) string $logfile
   *
   * @return boolish TRUE|FALSE
   */
  public function run($logfile = '');

}
