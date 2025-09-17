#!/bin/sh
set -e

# Run composer install if the vendor directory does not exist
if [ ! -d "vendor" ]; then
    composer install
fi

# Check the value of SERVER_DEBUG and execute the corresponding command
if [ "$SERVER_DEBUG" = "false" ]; then
    php start.php start -d
    exec tail -f /dev/null
else
    exec php start.php start
fi
