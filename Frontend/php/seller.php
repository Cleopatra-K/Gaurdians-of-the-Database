<?php
// session_start();
// if (!isset($_SESSION['admin_logged_in'])) {
//     header("Location: admin.php");
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller's Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" id="products-tab">
                                <i class="bi bi-box-seam"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="requests-tab">
                                <i class="bi bi-envelope"></i> Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Seller Dashboard</h1>
                </div>

                <!-- Products Tab Content -->
                <div id="products-content" class="tab-content">
                    <div class="d-flex justify-content-between mb-3">
                        <h3>Product Management</h3>
                        <button class="btn btn-primary" id="add-product-btn">Add Product</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Size</th>
                                    <th>Load Index</th>
                                    <th>Tube</th>
                                    <th>Serial</th>
                                    <th>Original Price</th>
                                    <th>Selling Price</th>
                                    <th>Seller</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="products-table-body">
                                <!-- Products will be loaded here via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Requests Tab Content -->
                <div id="requests-content" class="tab-content" style="display: none;">
                    <div class="d-flex justify-content-between mb-3">
                        <h3>Product Requests</h3>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary filter-btn active" data-filter="pending">Pending</button>
                            <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="approved">Approved</button>
                            <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="rejected">Rejected</button>
                            <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="all">All</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Seller</th>
                                    <th>Action</th>
                                    <th>Product Data</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="requests-table-body">
                                <!-- Requests will be loaded here via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add/Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <div class="mb-3">
                            <label for="tyre_id" class="form-label">Tyre ID</label>
                            <input type="text" class="form-control" id="tyre_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="size" class="form-label">Size</label>
                            <input type="text" class="form-control" id="size" required>
                        </div>
                        <div class="mb-3">
                            <label for="load_index" class="form-label">Load Index</label>
                            <input type="number" class="form-control" id="load_index" required>
                        </div>
                        <div class="mb-3">
                            <label for="has_tube" class="form-label">Has Tube</label>
                            <select class="form-control" id="has_tube" required>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="serial_num" class="form-label">Serial Number</label>
                            <input type="text" class="form-control" id="serial_num" required>
                        </div>
                        <div class="mb-3">
                            <label for="original_price" class="form-label">Original Price</label>
                            <input type="number" step="0.01" class="form-control" id="original_price" required>
                        </div>
                        <div class="mb-3">
                            <label for="selling_price" class="form-label">Selling Price</label>
                            <input type="number" step="0.01" class="form-control" id="selling_price" required>
                        </div>
                        <div class="mb-3">
                            <label for="img_url" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="img_url">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveProductBtn">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Request ID:</strong> <span id="request-id"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Seller:</strong> <span id="request-seller"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Action:</strong> <span id="request-action"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong> <span id="request-status"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Date:</strong> <span id="request-date"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Description:</strong> <span id="request-description"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Product Data:</strong>
                        <pre id="request-product-data" class="bg-light p-3 rounded"></pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="approveRequestBtn">Approve</button>
                    <button type="button" class="btn btn-danger" id="rejectRequestBtn">Reject</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>