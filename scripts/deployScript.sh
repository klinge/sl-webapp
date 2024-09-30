#!/bin/bash

# This is a script to deploy a github repo to the web server
# It copies files, installs dependencies, sets up the database, 
# sets file and folder permissions, and restarts the server

# Schedule using the "at" command like this
# echo "/var/www/html/sl-webapp/scripts/deployScript.sh > /var/www/html/deploy.log 2>&1" | at now + 2 minutes


WEBSERVER_FOLDER="/var/www/sl.klin.ge"
REPO_FOLDER="/var/www/.repos/sl-webapp"
REPO_NAME="sl-webapp"
DB_FILE="sldb.sqlite"
LOG_FILE="app.log"
HAS_ERRORS=false
SCRIPT_USER="johan"

# Logging function
# Usage: log LOGLEVEL "This is a log message"
log () {
    local level="$1"
    local message="$2"
    local timestamp=$(date +"%Y-%m-%d %H:%M:%S")
    echo "[$timestamp] $level: $message"
}

log INFO "CHECKING PREREQS--------------------"
#Verify that the script is run as user: johan
if [ $(whoami) != ${SCRIPT_USER} ]; then
    log ERROR "The script must be run as user: ${SCRIPT_USER}."
    exit 1
fi
#Check if the repo folder exists
if [ ! -d "$REPO_FOLDER" ]; then
    log ERROR "Repo folder ${REPO_FOLDER} does not exist, exiting"
    exit 1
fi
#Check if the web server folder exists
if [ ! -d "$WEBSERVER_FOLDER" ]; then
    log ERROR "Web server folder ${WEBSERVER_FOLDER} does not exist, exiting"
    exit 1
fi
#Check if the repo folder is a git repo
if [ ! -d "$REPO_FOLDER/.git" ]; then
    log ERROR "Repository folder ${REPO_FOLDER} is not a git repo, exiting"
    exit 1
fi
#Check that database file exists
if [ ! -f "$WEBSERVER_FOLDER/db/$DB_FILE" ]; then
    log ERROR "Database file ${WEBSERVER_FOLDER}/db/${DB_FILE} does not exist, exiting"
    exit 1
fi
log INFO "PREREQS OK---------------------------"
log INFO "STARTING DEPLOY----------------------"

# Backup database and .env file
if ! cp "$WEBSERVER_FOLDER/db/$DB_FILE" "$WEBSERVER_FOLDER/.env" "$WEBSERVER_FOLDER/../"; then
    log ERROR "Failed to backup database and .env file"
    exit 1
fi
log "DEBUG" "1. Backed up database and .env file to ${WEBSERVER_FOLDER}/../"

# Delete contents
if ! rm -rf "$WEBSERVER_FOLDER"/.[!.]* "$WEBSERVER_FOLDER"/* 2>/dev/null; then
    log ERROR "Failed to delete contents in ${WEBSERVER_FOLDER}"
    exit 1
fi
log "DEBUG" "2. Deleted all contents in ${WEBSERVER_FOLDER}"

# Copy contents
if ! cp -r "$REPO_FOLDER"/* "$WEBSERVER_FOLDER"; then
    log ERROR "Failed to copy contents from ${REPO_FOLDER} to ${WEBSERVER_FOLDER}"
    exit 1
fi
log "DEBUG" "3. Copied all contents from ${REPO_FOLDER} to ${WEBSERVER_FOLDER}"


#Install dependencies
cd $WEBSERVER_FOLDER
composer_output=$(timeout 300 /usr/local/bin/composer update --no-dev --no-interaction --no-progress --quiet 2>&1)
composer_exit_code=$?
if [ $composer_exit_code -eq 124 ]; then
    log ERROR "Composer update timed out after 5 minutes"
    HAS_ERRORS=true
elif [ $composer_exit_code -ne 0 ]; then
    log ERROR "Failed to install composer dependencies, error code was: $?"
    log ERROR "Composer output: $composer_output"
    HAS_ERRORS=true
else
    log DEBUG "4. Installed composer dependencies successfully"
fi

#Create logs directory and log file
mkdir -p $WEBSERVER_FOLDER/logs
touch $WEBSERVER_FOLDER/logs/$LOG_FILE
log DEBUG "6. Created logs directory and log file"

#Restore backup database and .env files
mv $WEBSERVER_FOLDER/../$DB_FILE $WEBSERVER_FOLDER/db/
mv $WEBSERVER_FOLDER/../.env $WEBSERVER_FOLDER/.env
log DEBUG "5. Restored database and .env files from ${WEBSERVER_FOLDER}/../"

#Set file and folder permissions
if ! chown -R johan:www-data "$WEBSERVER_FOLDER"; then
    log ERROR "Failed to set folder ownership"
    $HAS_ERRORS=true
fi
log "DEBUG" "7. Set folder ownership"

#Set permissions for db and logs directories
if ! chmod 770 "$WEBSERVER_FOLDER/db" "$WEBSERVER_FOLDER/logs"; then
    log ERROR "Failed to set permissions for db and logs directories"
    $HAS_ERRORS=true
fi

#Set permissions for database file
if ! chmod 660 "$WEBSERVER_FOLDER/db/$DB_FILE" "$WEBSERVER_FOLDER/logs/$LOG_FILE"; then
    log ERROR "Failed to set permissions for database file"
    $HAS_ERRORS=true
fi
log "DEBUG" "8. Set permissions for db and logs directories and database file"

#Finish up and report if there were any errors
if [ $HAS_ERRORS = true ]; then
    log INFO "DEPLOY COMPLETED WITH ERRORS, check app.log------------"
    exit 1
else
    log INFO "DEPLOY SUCCESSSFUL----------------------"
    exit 0
fi