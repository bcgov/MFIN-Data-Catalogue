# Theme notes

1. This theme supports dark mode.
2. Global layout styles are not included in the base css. Those will be dealt with in whatever framework or application is using the theme. For example, BC MFIN is using this theme to extend Bootstrap5 (BS5). BS5 provides classes for layout.
3. Some styles have Bootstrap5 dependencies, those are identified in the notes. The bcbb_theme styles will work without BS5, but additional tweaking of the SCSS files will be required.

## Typography

[BC style guide reference](https://developer.gov.bc.ca/Design-System/Typography)

Difference from BC style:

1. Font size is responsive, ranging from 16px on mobile to 22px on wide screens.
2. Slight transparency added to base font. This is easier on the eyes.
3. This theme has a dependency on BS5 for typography. While basics like colour, size and line-height are controlled by the theme, other things such as spacing are managed by bootstrap.

## Icons

[BC style guide reference]()

1. FontAwasome icons are local. To use them, uncomment FontAwsome lines in `style.scss`.
2. SVG data URIs are used for icons required by the base theme. This eliminates the FontAwsome dependency, SVGs are easier to position, no additional markup is needed for A11y, eliminates issue of odd characters if FontAwesome can't be loaded. See [xIcon Fonts vs SVG](https://www.lambdatest.com/blog/its-2019-lets-end-the-debate-on-icon-fonts-vs-svg-icons/)

## Alert banners

[BC style guide reference](https://developer.gov.bc.ca/Design-System/Alert-Banners)

Difference from BC style:

1. Icons are data URIs and are controlled by sass variables
2. Support is added for instances where an alert has a heading
3. SASS colour formulas are used for the background, link and border colours. This allows for the colours to change using a single variable. This keeps colours harmonious as they are all adjusted by the same amount.
4. Backgrounds are lighter, this improves A11y. Note that this and the existing scheme both pass WCAG 2.0 AA, however this version has a bit more contrast

## Badges

Difference from BC style:

The BC style guide does not have a badges class. The closest is [Beta status](https://developer.gov.bc.ca/Design-System/Beta-Status)

1. Added new badge classes for general use
2. Create a class for Beta status

**TBD:** Is beta status actually a unique context, a regular alert might be a better option.

## Header - basic

[BC style guide reference](https://developer.gov.bc.ca/Design-System/Header-Basic)

1. This theme uses the colours of the style guide
2. Layout styles are omitted, those will be application dependant

## Footer - basic

[BC style guide reference](https://developer.gov.bc.ca/Design-System/Footer-Basic)

1. This theme uses the colours of the style guide
2. Layout styles are omitted, those will be application dependant

## Navigation bar

[BC style guide reference](https://developer.gov.bc.ca/Design-System/Navigation-Bar-Basic)

The navigation bar is not included in the example files. The provided example uses JS and markup that will be different in Drupal.
