# Okta Login
A module to log in Drupal using an Okta account.

## Set up
You need an Okta application in order to use this module. That aplication should be configured using [url-of-your-website]/user/okta-signin as Login redirect URIs and as Initiate login URI. From that application you are going to need the Org URL and Client ID to set those settings in Drupal.

For testing purposes you can create an an Okta Developer Edition Account following this guide: https://developer.okta.com/quickstart/#/widget/php/generic, as explained in Add and Configure an OpenID Connect Client.

## Install
This module uses some Okta PHP libraries for verification, that requires the gmp php extension installed.

If you are using docker and a Ubuntu/Debian image, you might need to run:
```
apt-get update -y
apt-get install -y libgmp-dev
docker-php-ext-install gmp
```
