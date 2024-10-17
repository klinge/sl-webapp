# SL Member System
Members and activities database for a sailing club, with an accompanying web-gui.  

## DOING:
Moved to Github issues

## TODO (v0.9): 
All items moved to Github issues

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
* DONE Refactor: remove router->generate in controller classes instead use BaseController->createUrl(), 
       also remove stop passing router to the controllers (it's already available in Application)
* DONE Auth: add a register user form - only allow existing members to register
* DONE Auth: verify that a user is logged in for all pages
* DONE Auth: add admin checks in AuthMiddleware
* DONE Auth: direct admin and non-admin to different pages after login
* DONE Deploy: fix redirection rules for Nginx instead of Apache
* DONE Deploy: fix all hardcoded paths in the application (arghh!)
* DONE Bug: fix error in SeglingEdit - medlemmar is not populated when adding a medlem
* DONE Security: move all public files to a "public" folder so the document root for the web server don't have access to application files

## TODO (v0.98) - The testing milestone: 
* All testing tasks moved to issues
* DONE: Refactor: add logging and a logging library

## TODO (v1.0): 
* User-site: build a proper homepage for non-admin users
* User-site: build a page where users can update their contact details
* Refactor: adapt middleware to PSR-15
* Refactor: handle dates in the Segling&Medlem class as proper dates and not strings
* Refactor: make sure logging has traceability on who made what changes (start with Medlem and Segling)


## Longer term TODO (v1.1): 
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

