<?php

namespace Drupal\optiback;

/**
 * Provides an interface for optiback constants.
 */
interface OptibackHelperInterface {

  /**
   * @return mixed
   */
  public function dbBackup();

  /**
   * @param $cmd
   * @param $message
   * @return mixed
   */
  public function shellExecWithError($cmd, $message);

}
