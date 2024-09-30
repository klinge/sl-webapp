#!/bin/bash
TRIGGER_DIR="/var/www/.deploy_triggers"
PROCESSED_DIR="/var/www/.deploy_triggers/processed"
DEPLOY_SCRIPT="/var/www/sl.klin.ge/scripts/deployScript.sh"
LOG_FILE="/var/www/html/deploy.log"

mkdir -p "$PROCESSED_DIR"

# Check if there are any trigger files
if ls "$TRIGGER_DIR"/*.trigger 1> /dev/null 2>&1; then
    # Run deployment script once
    "$DEPLOY_SCRIPT" > "$LOG_FILE" 2>&1
    
    # Move all trigger files to processed directory
    for trigger in "$TRIGGER_DIR"/*.trigger; do
        mv "$trigger" "$PROCESSED_DIR/$(basename "$trigger").$(date +%Y%m%d%H%M%S)"
    done
fi

# Clean up old processed files (older than 60 days)
find "$PROCESSED_DIR" -type f -mtime +60 -delete
