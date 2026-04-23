// delete.js
// Page-specific JS for delete.php that asks user if they want to delete their currrent signed in account. Asks twice for safe measure.
function deleteOut() {
    if (confirm("Are you sure you want to delete this account forever?")) {
      document.getElementById("clear_form").submit();
    }
}
