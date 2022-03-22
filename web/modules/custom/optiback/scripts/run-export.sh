#!/bin/bash

now=$(date +"%d_%m_%Y__%H_%M_%S")

year=$(date +"%Y")
echo $year

DRUPAL_PATH="../../../../../"
OPTIBACK_PATH="../../../../../../optiback"

LOG_DIR=logs

# Create logfile.
logfile=$OPTIBACK_PATH/$LOG_DIR/optiback-export-$now.log

# Run data backup.
tar -czf $OPTIBACK_PATH/backups/optiback-in-$now.tar.gz $OPTIBACK_PATH/data/in 2>&1 | tee -a $logfile

# Run drush command for export.
#php $DRUPAL_ROOT/vendor/drush/drush/drush.php optex m
drush optex m 2>&1 | tee -a $logfile

# Debugging option: -mmin
# Delet's all backup- and log-files which older then 30 days.
find $OPTIBACK_PATH/backups/*.tar.gz -mtime +30 -exec rm {} \; 2>&1 | tee -a $logfile
find $OPTIBACK_PATH/logs/*.log -mtime +30 -exec rm {} \; 2>&1 | tee -a $logfile
