(function (Drupal) {
  Drupal.behaviors.lockCshsRoot = {
    attach: function (context) {

      // Function that finds and locks the dropdown
      const lockLevelZero = () => {
        // Find the level 0 select that hasn't been disabled yet
        const selects = context.querySelectorAll('.field--name-field-data-set-type-1 .select-wrapper--level-0 select:not([disabled])');
        selects.forEach(function (rootSelect) {
          rootSelect.setAttribute('disabled', 'disabled');
          rootSelect.style.backgroundColor = '#e9ecef';
        });
      };

      // 1. Try running it immediately in case CSHS is incredibly fast
      lockLevelZero();

      // 2. Set up an observer to watch the wrapper. When CSHS builds the
      // visual dropdowns dynamically, this will instantly catch and lock Level 0.
      const wrappers = context.querySelectorAll('.field--name-field-data-set-type-1:not(.cshs-observer-attached)');

      wrappers.forEach(function (wrapper) {
        wrapper.classList.add('cshs-observer-attached');

        const observer = new MutationObserver(function () {
          lockLevelZero();
        });

        // Watch for any child elements (like the dropdowns) being added to the wrapper
        observer.observe(wrapper, { childList: true, subtree: true });
      });

    }
  };
})(Drupal);
