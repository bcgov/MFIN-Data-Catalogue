field_count_formatter was not ready for Drupal 11. You can't fix it with a patch,
because composer checks the version-readiness BEFORE it applies patches. So we needed
to clone the module here.

It is added still via composer, but using a "path repo", where the "repo" is just a local
path. 'composer install' then puts the code into modules/contrib using a symlink.

We need to remove this, and do a normal "composer require drupal/field_count_formatter" once
they have added D11 compatibility.
