/**
 * @file
 * Toggle open/close for a group of `details` elements.
 */

/*jslint browser indent2 long this*/

(function (document) {

  /**
   * Return all the details elements that need to be toggled.
   *
   * @return NodeList
   *   A the details elements as returned by querySelectorAll().
   */
  function getDetailsElements() {
    return document.querySelectorAll(".bcbb-toggle-details-wrapper details");
  }

  /**
   * Update the text and aria-expanded attribute of the button.
   *
   * @param button
   *   The button to update.
   * @param detailsElements
   *   The the details elements that are toggled.
   */
  function updateButtonText(button, detailsElements) {
    // Check if any details are closed.
    const anyOpen = Array.from(detailsElements).some((details) => details.open);

    // Update button text based on whether any details elements are closed.
    button.textContent = (
      anyOpen
      ? "Close all"
      : "Open all"
    );

    // Update aria-expanded attribute.
    button.setAttribute("aria-expanded", anyOpen.toString());
  }

  /**
   * Toggle the open state of the details elements.
   */
  function toggleDetails() {
    const detailsElements = getDetailsElements();

    // Check the current action required based on @aria-expanded.
    const shouldOpen = this.getAttribute("aria-expanded") === "false";

    // Set all details elements to the new state.
    detailsElements.forEach(function (details) {
      details.open = shouldOpen;
    });

    // Update the button text and aria-expanded attribute.
    updateButtonText(this, detailsElements);
  }

  /**
   * Event listener for DOMContentLoaded.
   */
  document.addEventListener("DOMContentLoaded", function () {
    // The button to act on.
    const button = document.getElementById("bcbb-toggle-details-button");
    if (button) {
      // Display the button. Without JS it is hidden by CSS.
      button.style.display = "block";
      // Initialize button text.
      updateButtonText(button, getDetailsElements());
      // Attach event listener.
      button.addEventListener("click", toggleDetails);
    }
  });

}(document));
