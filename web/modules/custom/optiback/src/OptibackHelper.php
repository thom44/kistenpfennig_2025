<?php

namespace Drupal\optiback;

class OptibackHelper implements OptibackHelperInterface {

  /**
   * {@inheritDoc}
   */
  public function dbBackup($env = 'prod') {
    $backup_dir = ObtibackConfigInterface::OPTIBACK_BAK;

    if ($env == 'dev') {
      $db_user = ObtibackConfigInterface::DEV_DB_USER;
      $db_name = ObtibackConfigInterface::DEV_DB_NAME;
      $db_pwd = ObtibackConfigInterface::DEV_DB_PWD;

    } else {
      $db_user = ObtibackConfigInterface::DB_USER;
      $db_name = ObtibackConfigInterface::DB_NAME;
      $db_pwd = ObtibackConfigInterface::DB_PWD;

    }

    // Run database backup.
    $cmd = 'mysqldump -u ' . $db_user . ' -p' . $db_pwd . ' ' . $db_name . ' > ' . $backup_dir . '/' . $db_name . '_' . date("Y-m-d") . '.sql';

    $message .= $this->shellExecWithError($cmd, 'The mysqldump failed.');

  }

  /**
   * {@inheritDoc}
   */
  public function backupCleanup() {

    // Run database backup.
    $cmd = 'find ' . ObtibackConfigInterface::OPTIBACK_BAK . '/*.tar.gz -mtime +8 -exec rm {} \;';

    $message .= $this->shellExecWithError($cmd, 'The mysqldump failed.');
  }

  /**
   * {@inheritDoc}
   */
  public function shellExecWithError($cmd, $message) {

    $result = exec($cmd);

    if (
      strpos($result,"error") !== FALSE
      ||
      strpos($result,"failed") !== FALSE
    ) {
      return $message . "\n". $result . "\n";
    }

    return $result . "\n";
  }
}


