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
  const DRUSH = 'php ../vendor/drush/drush/drush';

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
  const EMAIL_FROM = 'info@kistenpfennig.net';

  /**
   * The email address for error notification.
   */
  const EMAIL_TO = 'thom@licht.local';

    /**
     * The bbc email address for error notification.
     */
  const EMAIL_BCC = 'info@thomas-schuh.com';

  /**
   * The Drupal tax_rate key.
   */
  const DRUPAL_TAX_DE_19 = 'default|cb66c91d-1ac6-4e64-9a9f-af14f1b8dcf1';

  /**
   * The Drupal tax_rate key.
   */
  const DRUPAL_TAX_DE_7 = 'default|9b0ae25c-0e66-41bd-b54a-6c6411869634';

  /**
   * The number of weeks the backup should be kept.
   */
  const KEEP_BACKUP = '21';
}
