<?php

namespace Drupal\optiback;

/**
 * Provides an interface for optiback constants.
 *
 * @note: run on production:
 * - install mailsystem, swiftmailer
 * - add key to mailsystem for all emails.
 * - update const for production database.
 * - upload optiback folder.
 */
interface ObtibackConfigInterface {

  /**
   * PHP Version path to run scripts on production server.
   */
  const PHP_PATH = '/usr/bin/php8.1';

  /**
   * The db user.
   */
  const DB_USER = 'db11130752-d9';

  /**
   * The db user.
   */
  const DB_NAME = 'db11130752-d9';

  /**
   * The db user.
   */
  const DB_PWD = 'Um-73FtQ3193j';

  /**
   * The path to drush.
   */
  const DRUSH = ObtibackConfigInterface::PHP_PATH . ' ../vendor/drush/drush/drush';

  /**
   * The db user.
   */
  const DEV_DB_USER = 'root';

  /**
   * The db user.
   */
  const DEV_DB_NAME = 'kd_kistenpfennig_d9';

  /**
   * The db user.
   */
  const DEV_DB_PWD = 'root';

  /**
   * The path to drush.
   */
  const DEV_DRUSH = 'drush';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_DIR = '../../optiback/data/';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_LOG = '../../optiback/logs/';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_BAK = '../../optiback/backups/';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_IN = ObtibackConfigInterface::OPTIBACK_DIR . 'in';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_OUT = ObtibackConfigInterface::OPTIBACK_DIR . 'out';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_INVOICE = ObtibackConfigInterface::OPTIBACK_OUT . '/Invoice';

  /**
   * A prefix for moved pdf invoice filename.
   */
  const OPTIBACK_TRACKING = ObtibackConfigInterface::OPTIBACK_OUT . '/Fulfillment';

  /**
   * A prefix for moved pdf invoice filename.
   */
  const OPTIBACK_CREDIT = ObtibackConfigInterface::OPTIBACK_OUT . '/credit';

  /**
   * A prefix for exported order state subdirectory name.
   * Without slash.
   */
  const OPTIBACK_CANCEL = 'state';

  /**
   * The relative path to the private drupal invoice dir.
   */
  const DRUPAL_INVOICE = '../private/account-invoice';

  /**
   * The relative path to the private drupal invoice dir.
   */
  const DRUPAL_INVOICE_URI = 'private://account-invoice';

  /**
   * A prefix for moved pdf invoice filename.
   */
  const INVOICE_PREFIX = '';

  /**
   * The relative path to the private drupal invoice dir.
   */
  const DRUPAL_CREDIT = '../private/account-credit';

  /**
   * The relative path to the private drupal invoice dir.
   */
  const DRUPAL_CREDIT_URI = 'private://account-credit';

  /**
   * A prefix for moved pdf invoice filename.
   */
  const CREDIT_PREFIX = 'Gutschrift_';

  /**
   * The email addresses.
   */
  const EMAIL_FROM = 'onlineshop@kistenpfennig.net';

  /**
   * The email address for error notification.
   */
  const EMAIL_TO = 'info@thomas-schuh.com';

    /**
     * The bbc email address for error notification.
     */
  const EMAIL_BCC = 'info@thomas-schuh.com';

  /**
   * The Drupal tax_rate key.
   */
  const DRUPAL_TAX_DE_19 = 'default|690008f4-db2c-4382-86ce-dbe9a6032f97';

  /**
   * The Drupal tax_rate key.
   */
  const DRUPAL_TAX_DE_7 = 'default|cb25a5eb-5c48-4243-a111-59d0ecb2219b';

  /**
   * The number of weeks the backup should be kept.
   */
  const KEEP_BACKUP = '3';
}
