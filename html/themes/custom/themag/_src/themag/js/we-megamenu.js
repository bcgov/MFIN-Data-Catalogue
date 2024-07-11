/**
 * Inserts an icon in the menu item, depending on the CSS
 * class that is used under WE Mega Menu UI.
 */

(($, Drupal) => {
  const { behaviors } = Drupal;

  behaviors.themagWeMegaMenu = {
    attach(context) {
      $(context).find(".we-mega-menu-li").each((key, value) => {
        let menuItem = $(value);
        let icon = menuItem.data('icon');
        let menuItemLink = menuItem.find('> span') || menuItem.find('> a');

        if(icon) {
          $('<i class="'+ icon +'"></i>').prependTo(menuItemLink);
        }

      });
    }
  }

})(jQuery, Drupal)
