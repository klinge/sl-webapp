#!/bin/bash

#
# Schedule this script to run regularly via cron
# "*/5 * * * * /var/www/sl.klin.ge/scripts/runDeploy.sh >> /var/www/.deploy_log/deploy.log 2>&1"

TRIGGER_DIR="/var/www/.deploy_triggers"
PROCESSED_DIR="/var/www/.deploy_triggers/processed"
DEPLOY_SCRIPT="/var/www/sl.klin.ge/scripts/deployScript.sh"
LOG_FILE="/var/www/.deploy_log/deploy.log"

# Logging function
# Usage: log LOGLEVEL "This is a log message"
log () {
    local level="$1"
    local message="$2"
    local timestamp=$(date +"%Y-%m-%d %H:%M:%S")
    echo "[$timestamp] $level: $message"
}

mkdir -p "$PROCESSED_DIR"

# Check if there are any trigger files
if ls "$TRIGGER_DIR"/*.trigger 1> /dev/null 2>&1; then
    log INFO "runDeploy.sh triggered. Starting deploy--------------------"
    # Run deployment script once
    "$DEPLOY_SCRIPT" >> "$LOG_FILE" 2>&1
    
    # Move all trigger files to processed directory
    for trigger in "$TRIGGER_DIR"/*.trigger; do
        mv "$trigger" "$PROCESSED_DIR/$(basename "$trigger").$(date +%Y%m%d%H%M%S)"
    done
fi

# Clean up old processed files (older than 60 days)
find "$PROCESSED_DIR" -type f -mtime +60 -delete
log INFO "runDeploy.sh finished--------------------"