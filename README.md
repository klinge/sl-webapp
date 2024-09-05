### sl-webapp
Members and activities database for a sailing club, with an accompanying web-gui.  


## TODO (v0.9): 

* Auth: add a reset password flow
* Auth: add controller->requireAuth() to access all pages
* Auth: direct admin and non-admin to different pages after login
* Segling: Fix database for members on Seglingar
* Segling: Fix viewSeglingEdit to allow adding and deleting participants on a segling
* Segling: Create new Segling
* Segling: delete a Segling
* Other: make a proper 404 page
* Other: add mail templetes for password reset and new user registration (https://github.com/ActiveCampaign/postmark-templates/tree/main)
* Refactor: make a Mail class
* Refactor: refactor the controller->render()-code to use a view-class
* Refactor: database class
* Refactor: remove PHP CodeSniff errors
* Refactor: ensure consistent naming for CRUD operations..
* Refactor: add DocBlocks for all classes
* DONE Refactor: remove config.php and fix depending code (mostly views)
* DONE Refactor: create an Application class and use dotenv for global config
* DONE Refactor: introduce namespaces in entire application and move to a proper directory structure
* DONE Auth: add a register user form - only allow existing members to register #1
* DONE Auth: remove SMTP credentials from AuthController

## Longer term TODO (v1.0): 
* Selfservice: Add a page to let members update their personal information
* Medlem: Add more fields to Medlem (like last-login, received-mails, etc)
* Aktier: db, relations to Medlem, controller and views
* Other: add phpcs and PHPStan och Psalm to Github commit workflow



