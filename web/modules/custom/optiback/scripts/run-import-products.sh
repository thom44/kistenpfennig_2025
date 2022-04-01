#!/bin/bash

now=$(date +"%d_%m_%Y__%H_%M_%S")
year=$(date +"%Y")

if [ "$1" == "--dev" ]; then
  source dev.conf
  option=dev
else
  source prod.conf
  option=prod
fi

LOG_DIR=logs

# Create logfile.
file=optiback-import-$now.log
logfile=$OPTIBACK_PATH/$LOG_DIR/$file

echo "run-import-products.sh" 2>&1 | tee -a $logfile
echo "Logfile = $logfile"
echo "Drush path = $DRUSH" 2>&1 | tee -a $logfile

# Run the complete import at once.
$DRUSH run_import $option product 2>&1 | tee -a $logfile

$DRUSH send_log $file

# Debugging option: -mmin
# Delet's all log-files which older then 30 days.
find $OPTIBACK_PATH/logs/*.log -mtime +8 -exec rm {} \; 2>&1 | tee -a $logfile
