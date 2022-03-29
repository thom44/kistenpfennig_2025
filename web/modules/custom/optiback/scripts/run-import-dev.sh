#!/bin/bash

now=$(date +"%d_%m_%Y__%H_%M_%S")
year=$(date +"%Y")

DRUPAL_PATH="../../../../../"
OPTIBACK_PATH="../../../../../../optiback"

LOG_DIR=logs

# Create logfile.
file=optiback-import-$now.log
logfile=$OPTIBACK_PATH/$LOG_DIR/$file

# Run the complete import at once.
drush run_import dev 2>&1 | tee -a $logfile

drush send_log $file

# Debugging option: -mmin
# Delet's all log-files which older then 30 days.
find $OPTIBACK_PATH/logs/*.log -mtime +8 -exec rm {} \; 2>&1 | tee -a $logfile
