#!/bin/bash
# Usage: ./deploy.sh <MODULE_ID> <target_directory>
# Example: ./deploy.sh SET08101 /var/www/set08101
#          ./deploy.sh SET11112 /var/www/set11112

set -e

MODULE="$1"
TARGET="$2"

if [[ -z "$MODULE" || -z "$TARGET" ]]; then
  echo "Usage: $0 <MODULE_ID> <target_dir>"
  echo "Available modules: $(ls configs/*.php 2>/dev/null | xargs -I{} basename {} .php | tr '\n' ' ')"
  exit 1
fi

CONFIG_FILE="configs/$MODULE.php"
if [[ ! -f "$CONFIG_FILE" ]]; then
  echo "Error: $CONFIG_FILE not found"
  exit 1
fi

mkdir -p "$TARGET"

# Copy all shared source files
cp -r src/* "$TARGET/"

# Deploy module config as config.php
cp "$CONFIG_FILE" "$TARGET/config.php"

# Ensure data/ and submissions/ directories exist in the target
mkdir -p "$TARGET/data"
mkdir -p "$TARGET/submissions"

echo "Deployed $MODULE → $TARGET"
