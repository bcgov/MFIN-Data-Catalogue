// Function to update the text and aria-expanded attribute of the button
function updateButtonText() {
  const container = document.querySelector('.field--name-field-columns');
  const detailsElements = container.querySelectorAll('details');
  const button = document.getElementById('toggle-button');

  // Check if any details are closed
  const anyClosed = Array.from(detailsElements).some(details => !details.open);

  // Update button text based on whether any details elements are closed
  button.textContent = anyClosed ? 'Open all data columns' : 'Close all data columns';

  // Update aria-expanded attribute
  button.setAttribute('aria-expanded', (!anyClosed).toString());
}

// Function to toggle the open state of all details elements
function toggleDetails() {
  const container = document.querySelector('.field--name-field-columns');
  const detailsElements = container.querySelectorAll('details');
  const button = document.getElementById('toggle-button');

  // Check the current action required based on button text
  const shouldOpen = button.textContent.includes('Open all data columns');

  // Set all details elements to the new state
  detailsElements.forEach(details => {
      details.open = shouldOpen;
  });

  // Update the button text and aria-expanded attribute
  updateButtonText();
}

// Event listener for DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
  // Show the button if JS is enabled
  const button = document.getElementById('toggle-button');
  if (button) {
      button.style.display = 'block';
  }

  // Initialize button text and attach event listener
  updateButtonText();
  button.addEventListener('click', toggleDetails);
});
