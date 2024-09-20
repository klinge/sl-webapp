# Sailing Club Database
Members and activities database for a sailing club, with an accompanying web-gui.  

## DOING:
* Refactor: remove duplicated code between save() and insertNew() in MedlemController
* Refactor: remove router->generate in controller classes instead use BaseController->createUrl(), 
  also maybe no need to pass router to controller classes (they already have router in the Application class)
* Remove PHPStan errors level 4

## TODO (v0.9): 
* Mail: add mail templates for password reset and new user registration. Find something among these: 
    https://github.com/ActiveCampaign/postmark-templates/tree/main
    https://stripo.email/
    https://www.cerberusemail.com/
    https://github.com/mailchimp/email-blueprints
* Mail: test all email templates
* Refactor: add strict_types to all Utils
* Refactor: add strict_types to all Models
* Refactor: add strict_types to all Middlewares
* Refactor: add PSR-7 request and response handling
* Deploy: import csv file with all current member data into database

## DONE for v0.9: 
* DONE Segling: Fix database for members on Seglingar
* DONE Segling: Fix viewSeglingEdit to allow adding and deleting participants on a segling
* DONE Segling: delete a Segling
* DONE Segling: Create new Segling
* DONE Other: make a proper 404 page
* DONE Auth: add a reset password flow
* DONE Auth: remove SMTP credentials from AuthController
* DONE Refactor: make a Mail class
* DONE Refactor: remove config.php and fix depending code (mostly views)
* DONE Refactor: create an Application class and use dotenv for global config
* DONE Refactor: introduce namespaces in entire application and move to a proper directory structure
* DONE Refactor: remove PHP CodeSniff errors
* DONE Refactor: database class
* DONE Refactor: refactor the controller->render()-code to use a view-class
* DONE Refactor: fix messy setting of path in BaseController->render()
* DONE Refactor: write a sanitization class to handle user input
* DONE Refactor: add strict_types to all Controllers
* DONE Auth: add a register user form - only allow existing members to register
* DONE Auth: verify that a user is logged in for all pages
* DONE Auth: add admin checks in AuthMiddleware
* DONE Auth: direct admin and non-admin to different pages after login
* DONE Deploy: fix redirection rules for Nginx instead of Apache
* DONE Deploy: fix all hardcoded paths in the application (arghh!)
* DONE Bug: fix error in SeglingEdit - medlemmar is not populated when adding a medlem
* DONE Security: move all public files to a "public" folder so the document root for the web server don't have access to application files

## TODO (v0.95): 
* User-site: build a proper homepage for non-admin users
* User-site: build a page where users can update their contact details
* Medlem class: keep track of who changed a record (last change or all changes?)
* Refactor: adapt middleware to PSR-15
* Refactor: add logging and a logging library
* Refactor: handle dates in the Segling&Medlem class as proper dates and not strings
* Testing: install PHPUnit
* Testing: add unit tests for all Controllers
* Testing: add unit tests for Models
* Testing: add unit tests for remaining classes

## Longer term TODO (v1.0): 
* Selfservice: Add a page to let members update their personal information
* Medlem: Add more fields to Medlem (like last-login, received-mails, etc)
* Aktier: db, relations to Medlem, controller and views
* Roller: CRUD for Roller
* Refactor: add phpcs and PHPStan och Psalm to Github commit workflow
* Refactor: add DocBlocks for all classes
* Refactor: ensure consistent naming for CRUD operations..
* Refactor: move to a validation library like Respect och Rakit
* Build: add Github actions to run PHP Codesniffer and PHPStan on commits
* Auth: replace own code with a proper Auth component. Maybe Comet Auth. Integrate with Medlem class?

## Notes on deploying to a new server
1. Put proper values in the .env-EDITME file
2. The webserver needs to be configured to set the document root to the "public" folder. 
3. The webserver need to redirect all requests to index.php. If the server is Apache
   the .htacess in the repo does this. Remember to allow redirects in the site config. 
   If nginx try_files in a location block need to point to index.php for not-found files. 

