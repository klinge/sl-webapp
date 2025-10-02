[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=klinge_sl-webapp&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=klinge_sl-webapp) [![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=klinge_sl-webapp&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=klinge_sl-webapp) [![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=klinge_sl-webapp&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=klinge_sl-webapp) [![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=klinge_sl-webapp&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=klinge_sl-webapp) [![Coverage Status](https://coveralls.io/repos/github/klinge/sl-webapp/badge.svg?branch=main)](https://coveralls.io/github/klinge/sl-webapp?branch=main)

# SL Member System
Members and activities database for a sailing club, with an accompanying web-gui.  

## Basic application structure
* namespaces are used in all new code
* source code is in the App folder
* the application architecture divides logic into Controllers, Models and Services
* web requests are handled via routing (using AltoRoute)
* PSR-7 requests and responses are implemented using Laminas Diactoros
* the application uses middleware for authentication and error handling
* the application uses PHP League Container as its DI Container
* templates for views are in /public/views
* type hinting is done everywhere and all new files should have strict_types=1

## DOING:
Issues and enhancements are handled via Github Issues and project planning

## Upcoming features
* User-site: build a proper homepage for non-admin users
* User-site: build a page where users can update their contact details
* Refactor: adapt middleware to PSR-15
* Refactor: handle dates in the Segling&Medlem class as proper dates and not strings
* Refactor: make sure logging has traceability on who made what changes (start with Medlem and Segling)


## Longer term features
* Selfservice: Add a page to let members update their personal information
* Medlem: Add more fields to Medlem (like last-login, received-mails, etc)
* Aktier: db, relations to Medlem, controller and views
* Roller: CRUD for Roller
* Refactor: add DocBlocks for all classes
* Refactor: ensure consistent naming for CRUD operations..
* Refactor: move to a validation library like Respect och Rakit
* Build/CI: add Github actions to run PHP Codesniffer and PHPStan on commits
* Auth: replace own code with a proper Auth component. Maybe Comet Auth. Integrate with Medlem class?

## Notes on deploying to a new server
1. Put proper values in the .env-EDITME file
2. The webserver need to redirect all requests to index.php. If the server is Apache
   the .htacess in the repo does this. Remember to allow redirects in the site config. 
   If nginx try_files in a location block need to point to index.php for not-found files. 

## Requirements
* Server needs PHP 8.1 or newer
* For testing PHP 8.2 is needed since it's required for the PHPUnit tests

