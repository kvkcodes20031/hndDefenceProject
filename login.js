document.getElementById('loginForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch('loginPage.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.text()) // first get text to debug PHP errors
  .then(text => {
     // log raw response for debugging
      console.log('Raw server response:', text);
    try {
     ; // log raw response for debugging
      const data = JSON.parse(text); // parse JSON
      if (data.success) {
        console.log('Login successful:', data);
        const role = data.role ? data.role.trim().toLowerCase() : '';
        const profileCompleted = data.profile_completed == 1;
        const roleCompleted = data.role_completed == 1;

        console.log('Login Debug:', { role, profileCompleted, roleCompleted });

        // Define role-based redirection paths
        const rolePaths = {
          'admin': { setup: 'admin.html', dashboard: 'admin.html' },
          'farmer': { setup: 'save_farmer_data.html', dashboard: 'farmerdashboard.html' },
          'logistic_operator': { setup: 'save_logistic_data.html', dashboard: 'logistics_dashboard.html' },
          'logistic operator': { setup: 'save_logistic_data.html', dashboard: 'logistics_dashboard.html' },
          'supplier': { setup: 'save_inputsupplier_data.html', dashboard: 'inputsupplier_dashboard.html' },
          'input supplier': { setup: 'save_inputsupplier_data.html', dashboard: 'inputsupplier_dashboard.html' },
          'input supplyer': { setup: 'save_inputsupplier_data.html', dashboard: 'inputsupplier_dashboard.html' },
          'buyer': { setup: 'save_buyer_data.html', dashboard: 'buyer_dashboard.html' }
        };


        if (!profileCompleted) {
          window.location.href = 'profilepage.html';
        } else {
          const paths = rolePaths[role];
          if (paths) {
            window.location.href = !roleCompleted ? paths.setup : paths.dashboard;

            console.log('Redirecting to:', data);
            
          } else {
            console.warn('Unknown role:', role);
            window.location.href = 'homepage.html'; // Fallback
          }
        }
      } else {
        alert(data.errors.join('\n')); // show errors
      }
    } catch (e) {
      console.error('Server Error:', text);
      alert('Login failed. Check the console (F12) for the server error message.');
    }
  })
  .catch(error => console.error('Error:', error));
});