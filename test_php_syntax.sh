#!/bin/bash
for file in $(find . -name "*.php"); do
    php -l $file > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "Syntax error in $file"
    fi
done
