#!/bin/sh
# Composer install script.
# This file must be located in drupal root directory.

# Corrent php version
PHP="/usr/bin/php8.4"
COMPOSER="/is/htdocs/wp11130752_8O4HH60ZR4/bin/composer.phar"
# LOCAL COMPOSER PATH:
#COMPOSER="/usr/local/bin/composer"

DRUPAL_ROOT=$(pwd)

echo $DRUPAL_ROOT;

cp $DRUPAL_ROOT/web/.htaccess $DRUPAL_ROOT/web/.htaccess-temp
cp $DRUPAL_ROOT/web/robots.txt $DRUPAL_ROOT/web/robots-temp.txt
echo "Save temporary web/.htaccess and web/robots.txt"

# Run composer install
$PHP -f $COMPOSER install

rm $DRUPAL_ROOT/web/.htaccess
rm $DRUPAL_ROOT/web/robots.txt
mv $DRUPAL_ROOT/web/.htaccess-temp $DRUPAL_ROOT/web/.htaccess
mv $DRUPAL_ROOT/web/robots-temp.txt $DRUPAL_ROOT/web/robots.txt
echo "Use former web/.htaccess and web/robots.txt"
if [ -f $DRUPAL_ROOT/web/.htaccess ]; then
    echo "$DRUPAL_ROOT/web/.htaccess exits - Okay!"
fi
if [ -f $DRUPAL_ROOT/web/robots.txt ]; then
    echo "$DRUPAL_ROOT/web/robots.txt exits - Okay!"
fi
