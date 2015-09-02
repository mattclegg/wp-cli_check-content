wp content-check
===========

This package that implements the `wp check-content` command for [WP-CLI](http://wp-cli.org).

It returns information about the content on the current wordpress installation and environment.
By default (no parameters) it returns all information formatted and colourized.
See `wp-cli help content-check` for full documentation.

Extending check-content
--

More 'checks' can be added dynamically by adding your own custom check ie `MyCustomCheck` to the folder `code/checks/` and they will be included automatically in the test.

If the check requires that the HTML be valid first you can implement like;
```
class MyCustomCheck extends InvalidHTML
{
```

Otherwise you should use;
```
class MyCustomCheck implements checks
{
```

