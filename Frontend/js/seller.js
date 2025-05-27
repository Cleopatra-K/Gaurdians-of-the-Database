// seller.js - Seller Dashboard Functionality

document.addEventListener('DOMContentLoaded', function() {
    // Get API key from session
    const apiKey = '<?php echo $_SESSION["api_key"]; ?>';
    const userId = '<?php echo $_SESSION["seller_id"]; ?>';
    
    // Initialize Chart
    let clicksChart;
    
    // Navigation
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.dashboard-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove active class from all items
            navItems.forEach(navItem => navItem.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Hide all sections
            sections.forEach(section => section.classList.add('hidden'));
            
            // Show selected section
            const sectionId = this.getAttribute('data-section') + '-section';
            document.getElementById(sectionId).classList.remove('hidden');
            
            // Load data for the section
            switch(this.getAttribute('data-section')) {
                case 'dashboard':
                    loadDashboardData();
                    break;
                case 'products':
                    loadProducts();
                    break;
                case 'analytics':
                    loadAnalytics();
                    break;
                case 'requests':
                    loadRequests('pending');
                    break;
                case 'favorites':
                    loadFavorites();
                    break;
            }
        });
    });
    
    // Logout
    document.getElementById('logoutBtn').addEventListener('click', function() {
        // Clear session and redirect to login
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'Logout',
                api_key: apiKey
            })
        })
        .then(response => response.json())
        .then(data => {
            window.location.href = 'login.php';
        })
        .catch(error => {
            console.error('Logout error:', error);
        });
    });
    
    // Product Modal
    const productModal = document.getElementById('productModal');
    const addProductBtn = document.getElementById('addProductBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const submitProductBtn = document.getElementById('submitProductBtn');
    const productForm = document.getElementById('productForm');
    
    addProductBtn.addEventListener('click', function() {
        document.getElementById('modalTitle').textContent = 'Add New Product';
        document.getElementById('tyreId').value = '';
        productForm.reset();
        productModal.style.display = 'flex';
    });
    
    closeModalBtn.addEventListener('click', function() {
        productModal.style.display = 'none';
    });
    
    cancelBtn.addEventListener('click', function() {
        productModal.style.display = 'none';
    });
    
    // Submit Product Form
    submitProductBtn.addEventListener('click', function() {
        const tyreId = document.getElementById('tyreId').value;
        const isEdit = tyreId !== '';
        
        const productData = {
            action: isEdit ? 'update' : 'add',
            tyre_id: isEdit ? tyreId : undefined,
            product_data: {
                size: document.getElementById('size').value,
                load_index: document.getElementById('loadIndex').value,
                has_tube: document.getElementById('hasTube').value,
                serial_num: document.getElementById('serialNum').value,
                original_price: document.getElementById('originalPrice').value,
                selling_price: document.getElementById('sellingPrice').value,
                img_url: document.getElementById('imgUrl').value
            },
            description: document.getElementById('description').value || 'No description provided',
            api_key: apiKey
        };
        
        // Remove undefined fields
        Object.keys(productData).forEach(key => productData[key] === undefined && delete productData[key]);
        
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'MakeRequest',
                ...productData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert('success', `Product ${isEdit ? 'update' : 'add'} request submitted successfully!`);
                loadProducts();
                productModal.style.display = 'none';
            } else {
                showAlert('danger', data.message || 'An error occurred');
            }
        })
        .catch(error => {
            showAlert('danger', 'Failed to submit product request');
            console.error('Error:', error);
        });
    });
    
    // Request Filter Buttons
    document.getElementById('showPendingBtn').addEventListener('click', function() {
        loadRequests('pending');
    });
    
    document.getElementById('showApprovedBtn').addEventListener('click', function() {
        loadRequests('approved');
    });
    
    document.getElementById('showRejectedBtn').addEventListener('click', function() {
        loadRequests('rejected');
    });
    
    // Request Modal
    const requestModal = document.getElementById('requestModal');
    const closeRequestModalBtn = document.getElementById('closeRequestModalBtn');
    const closeRequestBtn = document.getElementById('closeRequestBtn');
    
    closeRequestModalBtn.addEventListener('click', function() {
        requestModal.style.display = 'none';
    });
    
    closeRequestBtn.addEventListener('click', function() {
        requestModal.style.display = 'none';
    });
    
    // Initial load
    loadDashboardData();
    
    // Functions
    function loadDashboardData() {
        // Load stats
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'GetAllProducts',
                api_key: apiKey
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Count seller's products
                const sellerProducts = data.products.filter(product => product.user_id == userId);
                document.getElementById('total-products').textContent = sellerProducts.length;
            }
        });
        
        // Load click stats
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'Click',
                api_key: apiKey
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const totalClicks = data.data.reduce((sum, item) => sum + (item.total_clicks || 0), 0);
                document.getElementById('total-clicks').textContent = totalClicks;
            }
        });
        
        // Load pending requests count
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'ShowRequests',
                api_key: apiKey,
                filter: 'pending'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('pending-requests').textContent = data.count;
            }
        });
        
        // Load recent activity
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'ShowRequests',
                api_key: apiKey,
                filter: 'all'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const recentActivity = document.getElementById('recent-activity');
                recentActivity.innerHTML = '';
                
                // Sort by date (newest first) and take last 5
                const sortedRequests = data.requests.sort((a, b) => 
                    new Date(b.request_date) - new Date(a.request_date)
                ).slice(0, 5);
                
                if (sortedRequests.length === 0) {
                    recentActivity.innerHTML = '<p>No recent activity</p>';
                    return;
                }
                
                const activityList = document.createElement('ul');
                activityList.style.listStyle = 'none';
                activityList.style.padding = '0';
                
                sortedRequests.forEach(request => {
                    const li = document.createElement('li');
                    li.style.padding = '10px 0';
                    li.style.borderBottom = '1px solid #eee';
                    
                    let statusBadge;
                    switch(request.status) {
                        case 'approved':
                            statusBadge = '<span class="badge badge-success">Approved</span>';
                            break;
                        case 'rejected':
                            statusBadge = '<span class="badge badge-danger">Rejected</span>';
                            break;
                        default:
                            statusBadge = '<span class="badge badge-warning">Pending</span>';
                    }
                    
                    li.innerHTML = `
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <strong>${request.action.toUpperCase()}</strong> request for 
                                ${request.tyre_id ? `tyre #${request.tyre_id}` : 'new tyre'}
                            </div>
                            <div>
                                ${statusBadge}
                            </div>
                        </div>
                        <div style="font-size: 0.9rem; color: #666;">
                            ${new Date(request.request_date).toLocaleString()}
                        </div>
                    `;
                    
                    activityList.appendChild(li);
                });
                
                recentActivity.appendChild(activityList);
            }
        });
    }
    
    function loadProducts() {
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'GetAllProducts',
                api_key: apiKey
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const productsTableBody = document.getElementById('products-table-body');
                productsTableBody.innerHTML = '';
                
                // Filter products for this seller
                const sellerProducts = data.products.filter(product => product.user_id == userId);
                
                if (sellerProducts.length === 0) {
                    productsTableBody.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align: center;">No products found</td>
                        </tr>
                    `;
                    return;
                }
                
                sellerProducts.forEach(product => {
                    const row = document.createElement('tr');
                    
                    row.innerHTML = `
                        <td>${product.tyre_id}</td>
                        <td>
                            ${product.img_url ? 
                                `<img src="${product.img_url}" alt="Tyre" style="width: 50px; height: 50px; object-fit: cover;">` : 
                                'No image'}
                        </td>
                        <td>${product.serial_num}</td>
                        <td>${product.size}</td>
                        <td>${product.load_index}</td>
                        <td>£${product.selling_price.toFixed(2)}</td>
                        <td>
                            <button class="btn btn-primary edit-product" data-id="${product.tyre_id}">Edit</button>
                            <button class="btn btn-danger delete-product" data-id="${product.tyre_id}">Delete</button>
                        </td>
                    `;
                    
                    productsTableBody.appendChild(row);
                });
                
                // Add event listeners to edit buttons
                document.querySelectorAll('.edit-product').forEach(button => {
                    button.addEventListener('click', function() {
                        const tyreId = this.getAttribute('data-id');
                        editProduct(tyreId);
                    });
                });
                
                // Add event listeners to delete buttons
                document.querySelectorAll('.delete-product').forEach(button => {
                    button.addEventListener('click', function() {
                        const tyreId = this.getAttribute('data-id');
                        deleteProduct(tyreId);
                    });
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Failed to load products');
        });
    }
    
    function editProduct(tyreId) {
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'GetAllProducts',
                api_key: apiKey
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const product = data.products.find(p => p.tyre_id == tyreId && p.user_id == userId);
                
                if (product) {
                    document.getElementById('modalTitle').textContent = 'Edit Product';
                    document.getElementById('tyreId').value = product.tyre_id;
                    document.getElementById('size').value = product.size;
                    document.getElementById('loadIndex').value = product.load_index;
                    document.getElementById('hasTube').value = product.has_tube ? '1' : '0';
                    document.getElementById('serialNum').value = product.serial_num;
                    document.getElementById('originalPrice').value = product.original_price;
                    document.getElementById('sellingPrice').value = product.selling_price;
                    document.getElementById('imgUrl').value = product.img_url || '';
                    
                    productModal.style.display = 'flex';
                } else {
                    showAlert('danger', 'Product not found or you do not have permission to edit it');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Failed to load product details');
        });
    }
    
    function deleteProduct(tyreId) {
        if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
            return;
        }
        
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'MakeRequest',
                action: 'remove',
                tyre_id: tyreId,
                description: 'Request to remove product',
                api_key: apiKey
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert('success', 'Product removal request submitted successfully!');
                loadProducts();
            } else {
                showAlert('danger', data.message || 'Failed to submit removal request');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Failed to submit removal request');
        });
    }
    
    // function loadAnalytics() {
    //     fetch('api.php', {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json',
    //         },
    //         body: JSON.stringify({
    //             type: 'Click',
    //             api_key: apiKey
    //         })
    //     })
    //     .then(response => response.json())
    //     .then(data => {
    //         if (data.status === 'success') {
    //             const analyticsTableBody = document.getElementById('analytics-table-body');
    //             analyticsTableBody.innerHTML = '';
                
    //             if (data.data.length === 0) {
    //                 analyticsTableBody.innerHTML = `
    //                     <tr>
    //                         <td colspan="4" style="text-align: center;">No analytics data available</td>
    //                     </tr>
    //                 `;
    //                 return;
    //             }
                
    //             // Sort by clicks (descending)
    //             const sortedData = [...data.data].sort((a, b) => b.total_clicks - a.total_clicks);
                
    //             sortedData.forEach(item => {
    //                 const row = document.createElement('tr');
                    
    //                 row.innerHTML = `
    //                     <td>
    //                         ${item.img_url ? 
    //                             `<img src="${item.img_url}" alt="Tyre" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">` : 
    //                             ''}
    //                         ${item.serial_num}
    //                     </td>
    //                     <td>${item.serial_num}</td>
    //                     <td>${item.total_clicks}</td>
    //                     <td>Not available</td>
    //                 `;
                    
    //                 analyticsTableBody.appendChild(row);
    //             });
                
    //             // Prepare data for chart
    //             const labels = sortedData.map(item => item.serial_num);
    //             const clickData = sortedData.map(item => item.total_clicks);
                
    //             // Create or update chart
    //             const ctx = document.getElementById('clicksChart').getContext('2d');
                
    //             if (clicksChart) {
    //                 clicksChart.destroy();
    //             }
                
    //             clicksChart = new Chart(ctx, {
    //                 type: 'bar',
    //                 data: {
    //                     labels: labels,
    //                     datasets: [{
 });