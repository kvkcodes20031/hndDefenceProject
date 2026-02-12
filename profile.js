document.getElementById('profileForm').addEventListener('submit', function(e) {
  e.preventDefault();
  fetch('profile.php', { method: 'POST', body: new FormData(this) })
  .then(r => r.json())
  .then(d => {
    if(d.success) {
      const role = d.role ? d.role.trim().toLowerCase() : '';
      console.log('Server returned role:', role);

      if (role === 'farmer') {
        window.location.href = 'save_farmer_data.html';
      } else if (role === 'logistic_operator' || role === 'logistic operator') {
        window.location.href = 'save_logistic_data.html';
      } else if (role === 'supplier' || role === 'input supplier') {
        window.location.href = 'save_inputsupplier_data.html';
      } else if (role === 'buyer') {
        window.location.href = 'save_buyer_data.html';
      } else {
        alert('Profile saved! (No redirection for role: ' + role + ')');
      }
    } else {
      alert(d.errors.join('\n'));
    }
  })
  .catch(e => console.error(e));
});