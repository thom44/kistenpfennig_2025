# OPTIBACK README

# CONFIGURATION
1. Configure sh config files.
  - dev.conf
  - prod.conf
2. Configure Drupal\optiback\OptibackConfigInterface;
3. Setup cronjob to the shell scripts.

# USAGE
# RUN ORDER EXPORT:
# On production
./run-export.sh
# On development
./run-export.sh --dev

# RUN PRODUCT IMPORT MIGRATION
# @note: This process will set the site into maintenance mode.
# On production
./run-import-products.sh
# On development
./run-import-products.sh --dev

# RUN INVOICE AND TRACKING_NUMBER FILE IMPORT
# On production
./run-import-files.sh
# On development
./run-import-files.sh --dev
