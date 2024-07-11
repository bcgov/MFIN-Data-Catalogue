
const copy_groupings = document.getElementsByClassName("copy_grouping");

// Loop through all the "copy groupings", and set them up.

for (let i = 0; i < copy_groupings.length; i++) {

  var text_to_copy_element = copy_groupings[i].getElementsByClassName("text_to_copy"    )[0];
  var copy_button_element  = copy_groupings[i].getElementsByClassName("text_copy_button")[0];

  // Set the click-handler.
  copy_button_element.addEventListener("click", (click_event) => {
    // Get the text that we want to copy.
    var text_to_copy = click_event.target.parentElement.getElementsByClassName("text_to_copy")[0].childNodes[2].nodeValue.trim();

    // Put it on the clipboard.
    navigator.clipboard.writeText(text_to_copy);

    // Activate the tooltip.
    click_event.target.classList.add("copy_button_clicked");

  }, false);


  // Hide the tooltip when the mouse leaves the button.
  copy_button_element.addEventListener("mouseout", (mouseout_event) => {
      mouseout_event.target.classList.remove("copy_button_clicked");
    },
    false
  );

}


/*
  The forms are presented in an HTML table. However, each form takes up TWO rows:
  One clickable row for the name, and one expandable/collapsable row for the details.
  This complicates things for us.

  We want to be able to highlight BOTH of these rows at once when the mouse hovers
  over just one of them. You can't group them together with a <div>. You can apparently
  use a <tbody></tbody> to group them, one for each pair of <td> elements... But
  the <tbody> tag is already auto-generated and used by Views.

  So instead, we use this bit of Javascript to add/remove the "form-hover" class to
  EACH of these two rows when the mouse goes into EITHER of them, and removes that
  class when it leaves.
  */

// Get all of the FIRST of these pairs of rows: the "form-name-row" <td> elements.
const form_header_rows_td = document.getElementsByClassName("form-name-row");

// Loop through them.
for (let i = 0; i < form_header_rows_td.length; i++) {

  // Set the mouseenter-handler to the "form-name-row".
  form_header_rows_td[i].addEventListener("mouseenter", (mouseenter_event) => {
    mouseenter_event.target.classList.add("form-hover");
    mouseenter_event.target.nextElementSibling.classList.add("form-hover");
  }, false);
  // Set the mouseenter-handler for the associated collapsible row.
  form_header_rows_td[i].nextElementSibling.addEventListener("mouseenter", (mouseenter_event) => {
    mouseenter_event.target.previousElementSibling.classList.add("form-hover");
    mouseenter_event.target.classList.add("form-hover");
  }, false);

  // Set the mouseleave-handler to the "form-name-row".
  form_header_rows_td[i].addEventListener("mouseleave", (mouseleave_event) => {
    mouseleave_event.target.classList.remove("form-hover");
    mouseleave_event.target.nextElementSibling.classList.remove("form-hover");
  }, false);
  // Set the mouseleave-handler for the associated collapsible row.
  form_header_rows_td[i].nextElementSibling.addEventListener("mouseleave", (mouseleave_event) => {
    mouseleave_event.target.previousElementSibling.classList.remove("form-hover");
    mouseleave_event.target.classList.remove("form-hover");
  }, false);

}