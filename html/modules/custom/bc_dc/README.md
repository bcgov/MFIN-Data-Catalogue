# BC Data Catalogue Module

This module provides functionality specific to the BC Data Catalogue.

## Data set content type

Create the content type and form modes:
- Install module form_mode_control.
- Create content type `data_set` and add the desired fields.
- On `admin/structure/display-modes/form`, create "Content" Form Modes. This
  allows breaking up the edit page onto multiple pages.
- On "Manage form display" for `data_set`, under "Custom display settings", "Use
  custom display settings for the following form modes", check the newly-created
  form modes and save.
- Using the sub-tabs for each form mode, configure what appears there.
- Configure `form_mode_control` permissions to allow content editors access to
  the modes they need.
  - Access to unpublished nodes: https://www.drupal.org/docs/7/managing-users/viewing-unpublished-content#s-drupal-9

Create the "build" page with layout builder:
- On `admin/structure/display-modes/view`, create a "Content" View Mode called
  "Data set build page" (`node.data_set_build_page`).
- On `admin/structure/types/manage/data_set/display`, under "Custom display
  settings", "Use custom display settings for the following view modes", check
  "Data set build page" and save.
- Click on the "Data set build page" sub-tab. Under "Layout options", check "Use
  Layout Builder" and save.
- Click "Manage layout" and customize the layout.
- Add the edit link blocks: Add the "Edit section button" block.
- Put `data_set_build_page` View Mode at path `node/{node}/build`: This is done
  in route `bc_dc.data_set_build_page`.
