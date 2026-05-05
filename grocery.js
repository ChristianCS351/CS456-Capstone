// grocery.js
// Page-specific JS for grocery.php

//This asks the user with a pop up if they want to clear their shopping list. If they hit yes the shopping list clears itself, if they hit no it remains.

function handlePrint() {
  window.print();

  setTimeout(function () {
    if (confirm("Do you want to clear the shopping list?")) {
      var clearForm = document.getElementById("clear_form");
      if (clearForm) clearForm.submit();
    }
  }, 500);
}
