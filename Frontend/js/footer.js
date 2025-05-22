 // Load saved theme from local storage
 const savedTheme = localStorage.getItem('theme') || 'light-theme';
 document.body.className = savedTheme;

 // Add event listeners for theme buttons
 document.getElementById('light-theme-btn').addEventListener('click', () => {
     document.body.className = 'light-theme';
     localStorage.setItem('theme', 'light-theme');
 });

 document.getElementById('dark-theme-btn').addEventListener('click', () => {
     document.body.className = 'dark-theme';
     localStorage.setItem('theme', 'dark-theme');
 });
