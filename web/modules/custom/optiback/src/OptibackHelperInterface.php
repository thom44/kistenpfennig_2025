<?php

namespace Drupal\optiback;

/**
 * Provides an interface for optiback constants.
 */
interface OptibackHelperInterface {

  /**
   * @param $env
   * @return mixed
   */
  public function dbBackup($env = 'prod');

  /**
   * @param $cmd
   * @param $message
   * @return mixed
   */
  public function shellExecWithError($cmd, $message);

}
