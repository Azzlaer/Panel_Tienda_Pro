# ComercioSocial Enterprise v6.2 — Security Hardening (stage 1)

## Changes
- Application version bumped to 6.2.0.
- Root Apache rules now deny README version files, environment variants and common SQL/config/backup extensions.
- New `public/uploads/.htaccess` blocks script-like uploads and CGI execution and disables directory listings.
- CI continues checking all PHP syntax on PHP 8.2/8.3.

## Operational notes
The upload protection requires Apache to honor .htaccess overrides, including Options and Require. Confirm your XAMPP/hosting Apache configuration supports these directives before deploying. If Options is forbidden by hosting, move equivalent restrictions into the virtual host.
Do not publish application credentials. Existing files already committed into Git history are NOT removed by .gitignore.

## Validation still needed
- Install with new and existing databases on XAMPP, cPanel PHP 8.2/8.3.
- Verify image upload and viewing still work, and requests to an uploaded `.php` file are forbidden.
- Test root rules with Apache RewriteEngine enabled, including direct requests for README files and private directories.
- Verify PHP sessions, account permissions, CSRF, Theme Studio and the map on a populated MySQL database.
- Audit installer reset-database flow separately before enabling production installation.
This release does not claim a complete penetration test or end-to-end functional test.
