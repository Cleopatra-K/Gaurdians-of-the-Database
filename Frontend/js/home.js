document.addEventListener('DOMContentLoaded', function() {
            const homePage = document.getElementById('home-page');
            const loadingSpinner = document.getElementById('loading-spinner');
            const productsPage = document.getElementById('products-page');
            
            // Click anywhere on home page
            homePage.addEventListener('click', function() {
                // Show loading spinner
                loadingSpinner.style.display = 'flex';
                
                // Hide home page with fade out
                homePage.classList.add('fade-out');
                
                // Simulate loading time (replace with actual product loading)
                setTimeout(function() {
                    // Hide spinner and show products
                    loadingSpinner.style.display = 'none';
                    productsPage.style.display = 'block';
                    
                    // Load your products here (example):
                    // fetchProducts().then(displayProducts);
                    
                }, 1500); // 1.5 second loading time
            });
        });