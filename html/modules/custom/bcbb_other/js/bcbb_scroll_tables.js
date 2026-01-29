document.addEventListener("DOMContentLoaded", function () {

  const tableResponsive = document.querySelector('.table-responsive');
  const parentElement = tableResponsive.parentElement;

  if (tableResponsive.scrollWidth <= tableResponsive.clientWidth) {
    return; // Exit early if the full table is visible
  }

  const scrollTableSection = document.createElement('section');
  scrollTableSection.classList.add('scroll-table');

  const leftButton = document.createElement('button');
  leftButton.classList.add('scroll-button', 'left-button');
  leftButton.setAttribute('type', 'button');
  leftButton.setAttribute('aria-label', 'Scroll table left'); // A11y label
  leftButton.setAttribute('tabindex', '0'); // keyboard navigation
  leftButton.innerHTML = '<span class="scroll-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-left-fill" viewBox="0 0 16 16"><path d="m3.86 8.753 5.482 4.796c.646.566 1.658.106 1.658-.753V3.204a1 1 0 0 0-1.659-.753l-5.48 4.796a1 1 0 0 0 0 1.506z"/></svg></span>';

  const rightButton = document.createElement('button');
  rightButton.classList.add('scroll-button', 'right-button');
  rightButton.setAttribute('type', 'button');
  rightButton.setAttribute('aria-label', 'Scroll table right'); // A11y label
  rightButton.setAttribute('tabindex', '0'); // keyboard navigation
  rightButton.innerHTML = '<span class="scroll-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-right-fill" viewBox="0 0 16 16"><path d="m12.14 8.753-5.482 4.796c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z"/></svg></span>';

  const tableContainer = document.createElement('div');
  tableContainer.classList.add('table-container');

  // Wrap buttons and table container in scroll table section
  scrollTableSection.appendChild(leftButton);
  scrollTableSection.appendChild(rightButton);
  scrollTableSection.appendChild(tableContainer);

  // Insert scroll table section before table responsive
  parentElement.insertBefore(scrollTableSection, tableResponsive);

  // Move table responsive inside table container
  tableContainer.appendChild(tableResponsive);

  let scrollTimeout;
  let isLeftButtonDown = false;
  let isRightButtonDown = false;

  function scrollLeft() {
    tableResponsive.scrollLeft -= 20; // Adjust scroll amount
    scrollTimeout = setTimeout(scrollLeft, 50); // Repeat every 50ms
  }

  function scrollRight() {
    tableResponsive.scrollLeft += 20; // Adjust scroll amount
    scrollTimeout = setTimeout(scrollRight, 50); // Repeat every 50ms
  }

  function stopScroll() {
    clearTimeout(scrollTimeout);
    isLeftButtonDown = false;
    isRightButtonDown = false;
  }

  function updateScrollOverlays() {
    const isLeftOverflowing = tableResponsive.scrollLeft > 0;
    const isRightOverflowing = tableResponsive.scrollLeft < (tableResponsive.scrollWidth - tableResponsive.clientWidth);

    const isFirstColumnVisible = tableResponsive.scrollLeft === 0;
    const isLastColumnVisible = tableResponsive.scrollLeft + tableResponsive.clientWidth >= tableResponsive.scrollWidth;

    const scrollTableRect = scrollTableSection.getBoundingClientRect();
    const tableRect = tableResponsive.getBoundingClientRect();

    if (scrollTableRect.bottom < tableRect.top || scrollTableRect.top > tableRect.bottom) {
      leftButton.style.display = 'none';
      rightButton.style.display = 'none';
    } else {
      leftButton.style.display = isLeftOverflowing && !isFirstColumnVisible ? 'block' : 'none';
      rightButton.style.display = isRightOverflowing && !isLastColumnVisible ? 'block' : 'none';
    }
  }

  function handleKeydown(event) {
    if (event.key === 'Enter') {
      event.preventDefault(); // Prevent default behavior
      if (event.target === leftButton && !isLeftButtonDown) {
        isLeftButtonDown = true;
        scrollLeft();
      } else if (event.target === rightButton && !isRightButtonDown) {
        isRightButtonDown = true;
        scrollRight();
      }
    }
  }

  function handleKeyup(event) {
    if (event.key === 'Enter') {
      event.preventDefault(); // Prevent default behavior
      if (event.target === leftButton) {
        stopScroll();
      } else if (event.target === rightButton) {
        stopScroll();
      }
    }
  }

  tableResponsive.addEventListener('scroll', updateScrollOverlays);
  leftButton.addEventListener('keydown', handleKeydown);
  rightButton.addEventListener('keydown', handleKeydown);
  leftButton.addEventListener('keyup', handleKeyup);
  rightButton.addEventListener('keyup', handleKeyup);

  if ('ontouchstart' in window) { // Check if touch events are supported
    leftButton.addEventListener('touchstart', function (event) {
      event.preventDefault(); // Prevent default touch behavior (e.g., zooming)
      scrollLeft();
    });
    rightButton.addEventListener('touchstart', function (event) {
      event.preventDefault(); // Prevent default touch behavior (e.g., zooming)
      scrollRight();
    });
    leftButton.addEventListener('touchend', stopScroll);
    rightButton.addEventListener('touchend', stopScroll);
  } else { // If touch events are not supported, use mouse events
    leftButton.addEventListener('mousedown', scrollLeft);
    rightButton.addEventListener('mousedown', scrollRight);
    leftButton.addEventListener('mouseup', stopScroll);
    rightButton.addEventListener('mouseup', stopScroll);
    leftButton.addEventListener('mouseleave', stopScroll);
    rightButton.addEventListener('mouseleave', stopScroll);
  }

  updateScrollOverlays(); // Initial check
});
