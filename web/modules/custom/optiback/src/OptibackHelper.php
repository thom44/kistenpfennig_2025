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
    // Hosteurope needs -no-tablespaces otherwise we get this error.
    // mysqldump: Error: 'Access denied; you need (at least one of) the PROCESS
    // privilege(s) for this operation' when trying to dump tablespaces
    $cmd = 'mysqldump -u ' . $db_user . ' -p' . $db_pwd . ' --no-tablespaces ' . $db_name . ' > ' . $backup_dir . '/' . $db_name . '_' . date("Y-m-d-H-i") . '.sql';

    return $this->shellExecWithError($cmd, 'The mysqldump failed.');
  }

  /**
   * {@inheritdoc}
   */
  public function dirBackup($directory, $name = '') {

    $date = date("Y-m-d_H-i-s");

    $cmd = 'tar -cf ' . ObtibackConfigInterface::OPTIBACK_BAK . $name .'-' . $date . '.tar.gz ' . $directory;

    return $this->shellExecWithError($cmd, 'The backup from optiback/in directory failed.');
  }

  /**
   * {@inheritDoc}
   */
  public function backupCleanup() {

    $message = '';

    // Run database backup.
    $cmd = 'find ' . ObtibackConfigInterface::OPTIBACK_BAK . '/*.sql -mtime +' . ObtibackConfigInterface::KEEP_BACKUP . ' -exec rm {} \;';

    $message .= $this->shellExecWithError($cmd, 'The .sql backup cleanup failed.');


    // Run directory backup.
    $cmd = 'find ' . ObtibackConfigInterface::OPTIBACK_BAK . '/*.tar.gz -mtime +' . ObtibackConfigInterface::KEEP_BACKUP . ' -exec rm {} \;';

    $message .= $this->shellExecWithError($cmd, 'The .tar.gz backup cleanup failed.');

    return $message;
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

  /**
   * @param string $filename
   * @param $delimiter
   * @return array|false
   */
  public function csvToArray($filename, $delimiter){

    if(!file_exists($filename) || !is_readable($filename)) {
      return FALSE;
    }

    $header = NULL;
    $data = array();

    if (($handle = fopen($filename, 'r')) !== FALSE ) {
      while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
        if(!$header) {
          $header = $row;
        } else {
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }

    return $data;
  }
}


