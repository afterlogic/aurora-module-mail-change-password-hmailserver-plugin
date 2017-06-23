# aurora-module-mail-change-password-hmailserver-plugin

The module allows users to change passwords on their email accounts hosted by [hMailServer](https://www.hmailserver.com/).

Refer to the following page regarding how to install a module, taking WebMail Lite as an example of the product built on Aurora framework: [Adding modules in WebMail Lite](https://afterlogic.com/docs/webmail-lite-8/installation/adding-modules)

It is assumed that the product and hMailServer run on the same server. PHP COM extension is required.

In admin interface, under "Hmailserver password change" tab, you need to supply list of mailserver hostnames or IP addresses the feature is enabled for, one host per line. If you put "*" character there, it means the feature is enabled for all the accounts.

On the same tab, you need to provide login and password for administrative account of hMailServer.
