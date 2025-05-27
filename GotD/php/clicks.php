<?php
session_start();
// require_once __DIR__ . '/configuration/config.php'; // Adjust path as needed
require_once '../configuration/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Analytics Test</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #2f95a7;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .product-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
        }
        .popular-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #FFA500, #FF6347);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .chart-container {
            margin: 40px 0;
            height: 400px;
        }
        .top-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .top-product {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        button {
            background-color: #2f95a7;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background-color: #247a8a;
        }
        .show-more {
            text-align: center;
            margin: 20px 0;
        }
        .show-more-btn {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Click Analytics Test Page</h1>
        
        <div id="user-info">
            <?php if (isset($_SESSION['user_id'])): ?>
                <p>Logged in as <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</p>
                <button onclick="logout()">Logout</button>
            <?php else: ?>
                <div id="login-form">
                    <h3>Test Login</h3>
                    <input type="text" id="test-username" placeholder="Username">
                    <input type="password" id="test-password" placeholder="Password">
                    <button onclick="testLogin()">Login</button>
                </div>
            <?php endif; ?>
        </div>

        <h2>Product List</h2>
        <div class="product-grid" id="product-container">
            <!-- First 8 products will be loaded here -->
        </div>
        
        <div class="show-more">
            <button class="show-more-btn" onclick="showAllProducts()">Show All Products</button>
        </div>

        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Seller', 'Admin'])): ?>
            <div id="analytics-section">
                <h2>Click Analytics</h2>
                <button onclick="loadClickAnalytics()">Refresh Analytics</button>
                <div class="chart-container">
                    <canvas id="clicksChart"></canvas>
                </div>
                <h3>Top Products</h3>
                <div class="top-products" id="top-products">
                    <!-- Top products will be loaded here -->
                </div>
            </div>
        <?php endif; ?>
        
        <div class="product-grid" id="all-products-container" style="display: none;">
            <!-- All products will be loaded here when "Show All" is clicked -->
        </div>
    </div>

    <script>
        // Global variables
        let products = [];
        let clickChart = null;
        const PRODUCTS_TO_SHOW = 8;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            if (sessionStorage.getItem('userApiKey')) {
                highlightPopularProducts();
            }
        });

        // Test login function
        function testLogin() {
            const username = document.getElementById('test-username').value;
            const password = document.getElementById('test-password').value;
            
            fetch('/GotD/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'Login',
                    username: username,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.user) {
                    sessionStorage.setItem('userApiKey', data.api_key);
                    sessionStorage.setItem('userName', data.user.name);
                    sessionStorage.setItem('userRole', data.user.role);
                    alert('Login successful! Refreshing page...');
                    location.reload();
                } else {
                    alert('Login failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                alert('Login failed');
            });
        }

        function logout() {
            sessionStorage.removeItem('userApiKey');
            sessionStorage.removeItem('userName');
            sessionStorage.removeItem('userRole');
            fetch('/GotD/logout.php')
                .then(() => location.reload());
        }

        // Load products and track clicks
        function loadProducts() {
            fetch('/GotD/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'GetAllProducts'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.products) {
                    products = data.products;
                    renderInitialProducts();
                    renderAllProducts(); // Prepare all products but keep hidden
                }
            })
            .catch(error => console.error('Error loading products:', error));
        }

        function renderInitialProducts() {
            const container = document.getElementById('product-container');
            const productsToShow = products.slice(0, PRODUCTS_TO_SHOW);
            
            container.innerHTML = productsToShow.map(product => `
                <div class="product-card" data-tyre-id="${product.tyre_id}" onclick="trackProductClick(${product.tyre_id})">
                    <img src="${product.img_url || 'https://via.placeholder.com/250'}" alt="${product.serial_num}">
                    <h3>${product.serial_num}</h3>
                    <p>Size: ${product.size}</p>
                    <p>Price: $${product.selling_price}</p>
                </div>
            `).join('');
        }

        function renderAllProducts() {
            const container = document.getElementById('all-products-container');
            container.innerHTML = products.map(product => `
                <div class="product-card" data-tyre-id="${product.tyre_id}" onclick="trackProductClick(${product.tyre_id})">
                    <img src="${product.img_url || 'https://via.placeholder.com/250'}" alt="${product.serial_num}">
                    <h3>${product.serial_num}</h3>
                    <p>Size: ${product.size}</p>
                    <p>Price: $${product.selling_price}</p>
                </div>
            `).join('');
        }

        function showAllProducts() {
            document.getElementById('product-container').style.display = 'none';
            document.getElementById('all-products-container').style.display = 'grid';
            document.querySelector('.show-more').style.display = 'none';
        }

        function trackProductClick(tyreId) {
            const apiKey = sessionStorage.getItem('userApiKey');
            if (!apiKey) {
                console.log('Guest click not tracked');
                return;
            }
            
            fetch('/GotD/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'TrackClick',
                    api_key: apiKey,
                    tyre_id: tyreId
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Click tracked:', data);
                // Refresh analytics if on seller view
                if (document.getElementById('analytics-section')) {
                    loadClickAnalytics();
                }
            })
            .catch(error => console.error('Error tracking click:', error));
        }

        function highlightPopularProducts() {
            fetch('/GotD/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'GetPopularProducts'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.products) {
                    // Highlight in initial view
                    highlightProductsInContainer(data.products, 'product-container');
                    // Highlight in all products view
                    highlightProductsInContainer(data.products, 'all-products-container');
                }
            })
            .catch(error => console.error('Error loading popular products:', error));
        }

        function highlightProductsInContainer(products, containerId) {
            products.forEach(product => {
                const element = document.querySelector(`#${containerId} .product-card[data-tyre-id="${product.tyre_id}"]`);
                if (element) {
                    element.innerHTML += `
                        <div class="popular-badge">
                            🔥 Popular (${product.click_count} clicks)
                        </div>
                    `;
                    element.style.border = '2px solid #FFA500';
                }
            });
        }

        function loadClickAnalytics() {
            const apiKey = sessionStorage.getItem('userApiKey');
            if (!apiKey) return;
            
            fetch('/GotD/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'GetClickAnalytics',
                    api_key: apiKey
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.daily_analytics && data.top_products) {
                    renderAnalytics(data);
                }
            })
            .catch(error => console.error('Error loading analytics:', error));
        }

        function renderAnalytics(data) {
            // Destroy previous chart if exists
            if (clickChart) {
                clickChart.destroy();
            }
            
            // Prepare data for chart - show top 5 products by clicks
            const topProducts = data.daily_analytics
                .reduce((acc, item) => {
                    const existing = acc.find(p => p.serial_num === item.serial_num);
                    if (existing) {
                        existing.total_clicks += item.daily_clicks;
                    } else {
                        acc.push({
                            serial_num: item.serial_num,
                            total_clicks: item.daily_clicks
                        });
                    }
                    return acc;
                }, [])
                .sort((a, b) => b.total_clicks - a.total_clicks)
                .slice(0, 5);
            
            const dates = [...new Set(data.daily_analytics.map(item => item.click_date))].sort();
            
            const ctx = document.getElementById('clicksChart').getContext('2d');
            clickChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: topProducts.map(product => {
                        const productData = data.daily_analytics.filter(item => item.serial_num === product.serial_num);
                        return {
                            label: product.serial_num,
                            data: dates.map(date => {
                                const item = productData.find(d => d.click_date === date);
                                return item ? item.daily_clicks : 0;
                            }),
                            borderColor: getRandomColor(),
                            backgroundColor: 'rgba(0,0,0,0)',
                            borderWidth: 2,
                            tension: 0.1
                        };
                    })
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Clicks'
                            }
                        }
                    }
                }
            });
            
            // Render top products
            const topProductsContainer = document.getElementById('top-products');
            topProductsContainer.innerHTML = data.top_products.map(product => `
                <div class="top-product">
                    <h4>${product.serial_num}</h4>
                    <p>Size: ${product.size}</p>
                    <p>Price: $${product.selling_price}</p>
                    <p>Total Clicks: ${product.total_clicks || 0}</p>
                </div>
            `).join('');
        }

        function getRandomColor() {
            return `hsl(${Math.floor(Math.random() * 360)}, 70%, 50%)`;
        }
    </script>
</body>
</html>