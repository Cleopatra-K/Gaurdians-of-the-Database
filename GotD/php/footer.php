<!-- makes the current year to show -->
    <footer>
    <div class="theme-switcher">
        <span>Theme:</span>
        <button id="light-theme">Light Mode</button>
        <button id="dark-theme">Dark Mode</button>
        <button id="impaired-theme">High Contrast</button>
    </div>

        <p>&copy; <?php echo date("Y");?> My Website. All rights reserved.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const lightThemeButton = document.getElementById('light-theme');
        const darkThemeButton = document.getElementById('dark-theme');
        const impairedBtn = document.getElementById('impaired-theme');

        const body = document.body;
        const storedTheme = sessionStorage.getItem('theme') || 'light'; // Default to light

        function setTheme(theme) {
            body.classList.remove('light-theme', 'dark-theme');
            body.classList.add(`${theme}-theme`);
            sessionStorage.setItem('theme', theme);
        }

        // Apply stored theme on load
        setTheme(storedTheme);

        lightThemeButton.addEventListener('click', () => {
            setTheme('light');
        });

        darkThemeButton.addEventListener('click', () => {
            setTheme('dark');
        });

        impairedBtn.addEventListener('click', () => {
            setTheme('impaired');
        });
    });
</script>

<style>
    /* Basic styling for the theme switcher in the footer */
    .theme-switcher {
    text-align: center;
    margin-top: 20px;
    margin-bottom: 10px;
}

.theme-switcher span {
    margin-right: 8px;
    font-size: 14px;
    color: #2d3748; /* Navy blue */
}

.theme-switcher button {
    padding: 6px 12px; /* Smaller padding */
    font-size: 12px; /* Smaller font */
    cursor: pointer;
    border: 1px solid #a0aec0; /* Medium grey */
    border-radius: 4px; /* Slightly less rounded */
    margin: 0 3px; /* Reduced margin */
    transition: all 0.2s ease;
    font-family: 'Playfair Display', serif;
    text-transform: uppercase;
}
</style>