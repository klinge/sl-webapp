### sl-webapp
Members and activities database for a sailing club, with an accompanying web-gui.  

-## DOING:
* Mail: add mail templates for password reset and new user registration. Find something among these: 
    https://github.com/ActiveCampaign/postmark-templates/tree/main
    https://stripo.email/
    https://www.cerberusemail.com/
    https://github.com/mailchimp/email-blueprints

## TODO (v0.9): 
* Mail: test all email templates
* Refactor: remove duplicated code between save() and insertNew() in MedlemController
* Refactor: refactor the controller->render()-code to use a view-class
* Refactor: fix messy setting of path in BaseController->render()
* Refactor: handle dates in the Segling&Medlem class as proper dates and not strings
* Refactor: remove router->generate in controller classes instead use BaseController->createUrl()
* Deploy: fix site to work on Nginx instead of Apache
* Deploy: fix all hardcoded paths in the application (arghh!)
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
* DONE Refactor: write a sanitization class to handle user input
* DONE Auth: add a register user form - only allow existing members to register
* DONE Auth: verify that a user is logged in for all pages
* DONE Auth: add admin checks in AuthMiddleware
* DONE Auth: direct admin and non-admin to different pages after login

## TODO (v0.95): 
* User-site: build a proper homepage for non-admin users
* User-site: build a page where users can update their contact details
* Medlem class: keep track of who changed a record (last change or all changes?)
* Refactor: add strict_types to all php classes
* Refactor: add PSR-7 request and response classes
* Testing: add unit tests for all classes

## Longer term TODO (v1.0): 
* Selfservice: Add a page to let members update their personal information
* Medlem: Add more fields to Medlem (like last-login, received-mails, etc)
* Aktier: db, relations to Medlem, controller and views
* Roller: CRUD for Roller
* Refactor: add phpcs and PHPStan och Psalm to Github commit workflow
* Refactor: add DocBlocks for all classes
* Refactor: ensure consistent naming for CRUD operations..
* Refactor: add a util class for validation of user input or add a validation library like Respect och Rakit
* Build: add Github actions to run PHP Codesniffer and PHPStan on commits
* Auth: replace own code with a proper Auth component. Maybe Comet Auth. Integrate with Medlem class?



