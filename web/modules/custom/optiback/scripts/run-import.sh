#!/bin/bash

now=$(date +"%d_%m_%Y__%H_%M_%S")
year=$(date +"%Y")

DRUPAL_PATH="../../../../../"
OPTIBACK_PATH="../../../../../../optiback"

LOG_DIR=logs

# Create logfile.
file=optiback-import-$now.log
logfile=$OPTIBACK_PATH/$LOG_DIR/$file

# Run data backup.
tar -czf $OPTIBACK_PATH/backups/optiback-out-$now.tar.gz $OPTIBACK_PATH/data/out 2>&1 | tee -a $logfile

# Run the complete import at once.
drush run_import 2>&1 | tee -a $logfile


# Run database backup.
# @todo: db backup with mysqldump.

# Sets site in maintance mode.
# @todo: set sit in maintance mode with drush command.

# Sets all products to status 0 = unpublished.
# @todo: write custom drush command.

# Run drush command for export.
#php $DRUPAL_ROOT/vendor/drush/drush/drush.php optex m
#drush migrate:import optiback_import_product_variation 2>&1 | tee -a $logfile
#drush migrate:import optiback_import_product 2>&1 | tee -a $logfile

# Copy and process new invoices.
#drush copy_invoice

# Import tracking numbers.
#drush tracking_number

# Debugging option: -mmin
# Delet's all backup- and log-files which older then 30 days.
find $OPTIBACK_PATH/backups/*.tar.gz -mtime +8 -exec rm {} \; 2>&1 | tee -a $logfile
find $OPTIBACK_PATH/logs/*.log -mtime +8 -exec rm {} \; 2>&1 | tee -a $logfile
