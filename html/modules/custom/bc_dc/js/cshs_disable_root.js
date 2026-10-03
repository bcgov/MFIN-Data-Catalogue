/**
 * @file
 * Locks the root level (level-0) CSHS dropdown on data_set edit forms.
 */

(function (Drupal, once) {
  Drupal.behaviors.lockCshsRoot = {
    attach: function (context) {
      const wrappers = once(
        'cshs-observer',
        '.field--name-field-data-set-type',
        context
      );

      wrappers.forEach(function (wrapper) {
        const lockLevelZero = function () {
          const rootSelects = wrapper.querySelectorAll(
            '.select-wrapper--level-0 select:not([disabled])'
          );
          rootSelects.forEach(function (rootSelect) {
            rootSelect.disabled = true;
            rootSelect.style.backgroundColor = '#e9ecef';
          });
        };

        // 1. Run immediately in case CSHS already built the level-0 select.
        lockLevelZero();

        // 2. Observe the wrapper so if CSHS initializes or rebuilds the DOM
        // after our behavior runs, level-0 is immediately locked again.
        const observer = new MutationObserver(lockLevelZero);
        observer.observe(wrapper, { childList: true, subtree: true });
      });
    }
  };
})(Drupal, once);
