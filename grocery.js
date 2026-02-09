// grocery.js
// Page-specific JS for grocery.php

function handlePrint() {
  window.print();

  setTimeout(function () {
    if (confirm("Do you want to clear the shopping list?")) {
      var clearForm = document.getElementById("clear_form");
      if (clearForm) clearForm.submit();
    }
  }, 500);
}
