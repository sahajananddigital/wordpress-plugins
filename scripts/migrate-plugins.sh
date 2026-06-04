#!/usr/bin/env bash
# migrate-plugins.sh
# Automates copying external plugin repositories into this monorepo under the 'plugins/' folder.

set -e

REPOS=(
  "woocommerce-simple-customizations"
  "chatbot-for-woocommerce-sites"
  "wordpress-site-generator"
  "event-manager"
  "wordpress-simple-customise-helper"
  "woocommerce-conditional-shipping-and-payments"
  "HestiaCP-WordPress-Plugin"
  "wp-plugin-sahajanand-customise-helper"
)

MONOREPO_PATH=$(pwd)
TEMP_DIR=$(mktemp -d -t wp-migration-XXXXXX)

# Ensure plugins directory exists
mkdir -p "$MONOREPO_PATH/plugins"

echo "=================================================="
echo "Starting Monorepo Copy Migration of ${#REPOS[@]} plugins to 'plugins/'"
echo "=================================================="

for REPO in "${REPOS[@]}"; do
  echo ""
  echo ">>> Processing $REPO..."
  
  REPO_URL="https://github.com/sahajananddigital/$REPO.git"
  REPO_TEMP_PATH="$TEMP_DIR/$REPO"
  DEST_PATH="$MONOREPO_PATH/plugins/$REPO"
  
  # Clean destination folder
  rm -rf "$DEST_PATH"
  mkdir -p "$DEST_PATH"
  
  # Clone
  echo "Cloning $REPO_URL..."
  git clone --depth 1 "$REPO_URL" "$REPO_TEMP_PATH"
  
  # Copy files (excluding .git)
  echo "Copying files to monorepo subdirectory 'plugins/$REPO/'..."
  find "$REPO_TEMP_PATH" -maxdepth 1 ! -name "$(basename "$REPO_TEMP_PATH")" ! -name ".git" -exec cp -r {} "$DEST_PATH/" \;
  
  echo "Successfully imported $REPO!"
done

# Cleanup
echo ""
echo "Cleaning up temporary clone directory..."
rm -rf "$TEMP_DIR"

echo "=================================================="
echo "Migration complete! Add and commit files to trigger releases."
echo "=================================================="
