## Initialisation
Set a github username in the package administration panel for this bundle.
## Commands
These commands will fetch sources from the vendor you pick, and push on the fork of your github username.
### Repository initialization
php app/console claroline:git:translations VENDOR_NAME BUNDLE_NAME --init
### Repository commit & push
php app/console claroline:git:translations VENDOR_NAME BUNDLE_NAME --commit
### Repository pull
php app/console claroline:git:translations VENDOR_NAME BUNDLE_NAME --pull

