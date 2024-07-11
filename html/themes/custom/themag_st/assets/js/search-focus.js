jQuery(document).ready(function ($) {

  var searchIcon = document.querySelector('.js-toggle-search');
  var searchInput = document.querySelector('.form-search');

  searchIcon.addEventListener("click", function () {
        searchInput.focus();
  });

});