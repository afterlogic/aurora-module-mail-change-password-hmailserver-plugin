# aurora-module-mail-change-password-hmailserver-plugin

Allows users to change passwords on their email accounts hosted by [hMailServer](https://www.hmailserver.com/).

How to install a module (taking WebMail Lite as an example of the product built on Aurora framework): [Adding modules in WebMail Lite](https://afterlogic.com/docs/webmail-lite-8/installation/adding-modules)

The product and hMailServer must run on the same server. PHP COM extension is required.

In admin interface, under "Hmailserver password change" tab, you need to supply list of mailserver hostnames or IP addresses the feature is enabled for, one host per line. If you put "*" character there, it means the feature is enabled for all accounts.

On the same tab, you need to provide login and password for administrative account of hMailServer.

To activate "Change password" button in the interface, set "Disabled" to **false** in `data/settings/modules/ChangePasswordWebclient.config.json` configuration file. 

# Development
This repository has a pre-commit hook. To make it work you need to configure git to use the particular hooks folder.

`git config --local core.hooksPath .githooks/`

# License
This module is licensed under AGPLv3 license if free version of the product is used or Afterlogic Software License if commercial version of the product was purchased.
