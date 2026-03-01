// account.js
// Page-specific JS for account.php asking user if they want to log out or not

function handleOut() {
    if (confirm("Are you sure you want to log out?")) {
      document.getElementById("clear_form").submit();
    }
}
