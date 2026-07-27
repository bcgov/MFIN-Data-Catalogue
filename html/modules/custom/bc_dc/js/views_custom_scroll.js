(function (Drupal) {
  'use strict';

  Drupal.behaviors.reportBuilderScrollOnLoad = {
    attach: function (context) {
      // Only execute this once on the initial page layout processing wrapper
      if (context !== document) return;

      // Check if the URL contains active query filters (e.g., '?title=test')
      const hasQueryParams = window.location.search.length > 0;

      if (hasQueryParams) {
        // Target the results table wrapper container
        const resultsTarget = document.querySelector('.view .view-content');

        if (resultsTarget) {
          // Gather Gin administration header layout dimensions dynamically
          let adminHeaderOffset = 0;
          const adminBar = document.getElementById('gin-toolbar-bar');
          const secondaryToolbar = document.querySelector('.gin-secondary-toolbar');

          if (adminBar) adminHeaderOffset += adminBar.offsetHeight;
          if (secondaryToolbar) adminHeaderOffset += secondaryToolbar.offsetHeight;

          // Compute absolute scroll coordinate layout position
          const elementTop = resultsTarget.getBoundingClientRect().top + window.scrollY;
          const targetPosition = elementTop - (adminHeaderOffset + 24);

          // Execute a clean, smooth native scroll adjustment down to the table
          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });
        }
      }
    }
  };

})(Drupal);
