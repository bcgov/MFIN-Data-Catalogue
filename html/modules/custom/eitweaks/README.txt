"EITweaks" (Environment Indicator Tweaks) is here to make a few alterations
to how the colours are used. Partially, because they weren't working with our
gin toolbar, and this needed fixing. But we've also made one other change:

The Environment Indicator module has a "Background Color" and "Foreground Color".
The trouble with using the foreground colour for the text (which is how they do it),
is that MANY combinations are difficult to read, and look less professional.
So instead we are keeping the text (foreground) black, and only colouring the
background... but then we are using the "fg_color" to colour the NEW STRIPE at the bottom.

To make it easier for us to know what site we're on, let's use:
  * Background colour: To represent which phase of development we're on (dev/stage/prod).
      This can be the same across many/all of our projects.
  * Foreground colour (aka the stripe): Represents which *project* we're on.
      This will be different for each project, but the same across the
      dev/stage/prod stages of any one project.

We've also nudged the background colour's hue a little to the "left" and "right",
for our two projects so far, by 6 hue-units, to make the colour-combinations just
a little more unique. So, for example, the red of 'production' is slightly purplish
on one project, and slightly orangish on another (but still mostly red on both).
The objective here is to be able to know at a glance which instance of which site
you are working on, by having them be unique.

The three project-stripe colours so far (2 in use, and one for the next project)
were extracted with the eyedropper tool from the colours on the BC flag in the
graphic we use in many headers. We could switch easily to another colour that
better compliments any site we are working on.

This spreadsheet that is included in the module is just a tool to help choose
colours, and to have them be consistent.

Note that the colours that are chosen are duplicated in two places:
* in settings.php, it says which colours to use on which site.
* in the "Environment Switcher", it says what colours to use in the switcher
    to represent the site you are about to switch to. (It seems sensible to
    have these match the actual colours on that site.)

We are using HSL (Hue/Saturation/Luminosity) to choose the colours. Each of the
colours we've chosen for backgrounds have the same Sat and Lum as each other
(255 and 200, respectively). Only the Hue changes from site to site. And then
we convert the HSL numbers to an RGB hex code, for use with Drupal. (Excel makes
it easy to use these numbers, and to convert between HSL and RGB.)

The base hues are spread equally (about 43 between each), to have
clearly distinct colours:
  Red (prod),
  yellow (test),
  green (dev), and
  cyan (localhost).
