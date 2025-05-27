document.addEventListener('DOMContentLoaded', function() {
    // Get API key from session or local storage
    const apiKey = localStorage.getItem('userApiKey') || sessionStorage.getItem('userApiKey');
    if (!apiKey) {
    window.location.href = '../php/admin.php';       
    return;
    }

    // Tab switching
    document.getElementById('products-tab').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('products-content').style.display = 'block';
        document.getElementById('requests-content').style.display = 'none';
        this.classList.add('active');
        document.getElementById('requests-tab').classList.remove('active');
        loadProducts();
    });

    document.getElementById('requests-tab').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('products-content').style.display = 'none';
        document.getElementById('requests-content').style.display = 'block';
        this.classList.add('active');
        document.getElementById('products-tab').classList.remove('active');
        loadRequests('Pending');
    });

    // Request filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            loadRequests(this.dataset.filter);
        });
    });

    // Initialize modals
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    const requestModal = new bootstrap.Modal(document.getElementById('requestModal'));

    // Add product button
    document.getElementById('add-product-btn').addEventListener('click', function() {
        document.getElementById('modalTitle').textContent = 'Add Product';
        document.getElementById('productForm').reset();
        document.getElementById('tyre_id').value = '';
        productModal.show();
    });

    // Save product
    document.getElementById('saveProductBtn').addEventListener('click', function() {
        saveProduct();
    });

    // Request action buttons
    document.getElementById('approveRequestBtn').addEventListener('click', function() {
        processRequest('approved');
    });

    document.getElementById('rejectRequestBtn').addEventListener('click', function() {
        const reason = prompt('Please enter the reason for rejection:');
        if (reason !== null) {
            processRequest('rejected', reason);
        }
    });

    // Load initial data
    loadProducts();

    function loadProducts() {
        fetch('../GOTapi.php', {
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
                const tbody = document.getElementById('products-table-body');
                tbody.innerHTML = '';
                
                data.products.forEach(product => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${product.tyre_id}</td>
                        <td><img src="${product.img_url || 'placeholder.jpg'}" alt="Product" style="height: 50px;"></td>
                        <td>${product.size}</td>
                        <td>${product.load_index}</td>
                        <td>${product.has_tube ? 'Yes' : 'No'}</td>
                        <td>${product.serial_num}</td>
                        <td>$${product.original_price}</td>
                        <td>$${product.selling_price}</td>
                        <td>${product.seller_name || 'N/A'}</td>
                        <td>
                            <button class="btn btn-sm btn-primary edit-product" data-id="${product.tyre_id}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-product" data-id="${product.tyre_id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Add event listeners to edit/delete buttons
                document.querySelectorAll('.edit-product').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const productId = this.dataset.id;
                        editProduct(productId);
                    });
                });

                document.querySelectorAll('.delete-product').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const productId = this.dataset.id;
                        if (confirm('Are you sure you want to delete this product?')) {
                            deleteProduct(productId);
                        }
                    });
                });
            } else {
                alert('Error loading products: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading products');
        });
    }

    function editProduct(productId) {
        fetch('../GOTapi.php', {
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
                const product = data.products.find(p => p.tyre_id == productId);
                if (product) {
                    document.getElementById('modalTitle').textContent = 'Edit Product';
                    document.getElementById('tyre_id').value = product.tyre_id;
                    document.getElementById('size').value = product.size;
                    document.getElementById('load_index').value = product.load_index;
                    document.getElementById('has_tube').value = product.has_tube ? '1' : '0';
                    document.getElementById('serial_num').value = product.serial_num;
                    document.getElementById('original_price').value = product.original_price;
                    document.getElementById('selling_price').value = product.selling_price;
                    document.getElementById('img_url').value = product.img_url || '';
                    
                    productModal.show();
                } else {
                    alert('Product not found');
                }
            } else {
                alert('Error loading product details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading product details');
        });
    }

    function saveProduct() {
        const productId = document.getElementById('tyre_id').value;
        const isNew = productId === '';
        
        const productData = {
            size: document.getElementById('size').value,
            load_index: document.getElementById('load_index').value,
            has_tube: document.getElementById('has_tube').value,
            serial_num: document.getElementById('serial_num').value,
            original_price: document.getElementById('original_price').value,
            selling_price: document.getElementById('selling_price').value,
            img_url: document.getElementById('img_url').value
        };

        const requestData = {
            type: 'MakeRequest',
            api_key: apiKey,
            action: isNew ? 'add' : 'update',
            product_data: productData,
            description: isNew ? 'Admin adding new product' : 'Admin updating product'
        };

        if (!isNew) {
            requestData.tyre_id = productId;
        }

        fetch('../GOTapi.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Product ' + (isNew ? 'added' : 'updated') + ' successfully!');
                productModal.hide();
                loadProducts();
            } else {
                alert('Error: ' + (data.message || 'Failed to save product'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving product');
        });
    }

    function deleteProduct(productId) {
        const requestData = {
            type: 'MakeRequest',
            api_key: apiKey,
            action: 'remove',
            tyre_id: productId,
            description: 'Admin deleting product'
        };

        fetch('../GOTapi.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Product deletion requested successfully!');
                loadProducts();
            } else {
                alert('Error: ' + (data.message || 'Failed to request product deletion'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error requesting product deletion');
        });
    }

    function loadRequests(filter = 'Pending') {
        fetch('../GOTapi.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'ShowRequests',
                api_key: apiKey,
                filter: filter
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const tbody = document.getElementById('requests-table-body');
                tbody.innerHTML = '';
                
                data.requests.forEach(request => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${request.request_id}</td>
                        <td>${request.username}</td>
                        <td>${request.action}</td>
                        <td>${request.product_data ? request.product_data.size || 'N/A' : 'N/A'}</td>
                        <td><span class="badge ${getStatusBadgeClass(request.status)}">${request.status}</span></td>
                        <td>${new Date(request.request_date).toLocaleString()}</td>
                        <td>
                            <button class="btn btn-sm btn-info view-request" data-id="${request.request_id}">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Add event listeners to view buttons
                document.querySelectorAll('.view-request').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const requestId = this.dataset.id;
                        viewRequest(requestId);
                    });
                });
            } else {
                alert('Error loading requests: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading requests');
        });
    }

    function viewRequest(requestId) {
        fetch('../GOTapi.php', {
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
                const request = data.requests.find(r => r.request_id == requestId);
                if (request) {
                    document.getElementById('request-id').textContent = request.request_id;
                    document.getElementById('request-seller').textContent = request.username;
                    document.getElementById('request-action').textContent = request.action;
                    document.getElementById('request-status').textContent = request.status;
                    document.getElementById('request-date').textContent = new Date(request.request_date).toLocaleString();
                    document.getElementById('request-description').textContent = request.description || 'N/A';
                    
                    const productData = request.product_data ? JSON.stringify(request.product_data, null, 2) : 'No product data';
                    document.getElementById('request-product-data').textContent = productData;
                    
                    // Store current request ID for processing
                    document.getElementById('requestModal').dataset.requestId = requestId;
                    document.getElementById('requestModal').dataset.requestAction = request.action;
                    document.getElementById('requestModal').dataset.tyreId = request.tyre_id || '';
                    
                    // Show/hide action buttons based on status
                    const isPending = request.status === 'Pending';
                    document.getElementById('approveRequestBtn').style.display = isPending ? 'block' : 'none';
                    document.getElementById('rejectRequestBtn').style.display = isPending ? 'block' : 'none';
                    
                    requestModal.show();
                } else {
                    alert('Request not found');
                }
            } else {
                alert('Error loading request details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading request details');
        });
    }

    function processRequest(newStatus, reason = '') {
        const requestId = document.getElementById('requestModal').dataset.requestId;
        const requestAction = document.getElementById('requestModal').dataset.requestAction;
        const tyreId = document.getElementById('requestModal').dataset.tyreId;
        
        const requestData = {
            type: 'ProcessRequests',
            api_key: apiKey,
            request_id: requestId,
            status: newStatus,
            ...(reason && { reason: reason })
        };

        fetch('../GOTapi.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                let message = `Request ${newStatus} successfully!`;
                
                // If approved, show what action was performed
                if (newStatus === 'approved') {
                    message += ` Action performed: ${requestAction}`;
                }
                
                alert(message);
                requestModal.hide();
                
                // Refresh both products and requests if it was an approval
                if (newStatus === 'approved') {
                    loadProducts();
                }
                loadRequests(document.querySelector('.filter-btn.active').dataset.filter);
            } else {
                alert('Error: ' + (data.message || `Failed to ${newStatus} request`));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(`Error processing request as ${newStatus}`);
        });
    }

    function getStatusBadgeClass(status) {
        switch (status.toLowerCase()) {
            case 'Pending': return 'bg-warning text-dark';
            case 'approved': return 'bg-success';
            case 'rejected': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }
});