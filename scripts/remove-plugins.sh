#!/bin/bash

cd /app/wp-content/plugins
rm -Rf /app/wp-content/plugins/*
touch index.php
echo "All plugins removed!"
