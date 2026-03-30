# bcbb-theme

[![Lifecycle:Experimental](https://img.shields.io/badge/Lifecycle-Experimental-339999)](<Redirect-URL>)

A theme for the Drupal base-build used at Ministry of Finance.

[Bootstrap 5](https://www.drupal.org/project/bootstrap5) subtheme.

## Development

### CSS compilation

Prerequisites: install [sass](https://sass-lang.com/install).

To compile, run from subtheme directory:
`sass scss/style.scss css/style.css && sass scss/ck5style.scss css/ck5style.css`

## Future development

This theme is created based on a design system that does not have a stable release. We made adjustments on a somewhat ad hoc basis. The theme works fine, however to be a fully enterprise ready theme, it needs work.

Once a BC design system is established, this theme should be adjusted to reflect those changes. Ideally, you would use the same classes as the BC design system, which would mean updating Drupal sites using he theme. 

One approach would be to transition the theme when the design system is ready. Add the new classes, but support the existing classes until sites are updated, at which point the old ones can be removed.
