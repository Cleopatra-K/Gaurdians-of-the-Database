// products.js
console.log("✅ Entered products.js");
//u23547121 CT Kwenda

const baseUrl = "../GOTapi.php"; // Adjust as per your API's file path

// DOM Elements for products page
// *** IMPORTANT FIX: Use getElementById for products-container consistently ***
const productsContainer = document.getElementById('products-container'); // This is the main container for all product cards

// Ensure these elements exist in your HTML with these IDs
const filterGeneral = document.getElementById('filter-general');
const filterSize = document.getElementById('filter-size');
const filterLoadIndex = document.getElementById('filter-load-index');
const filterHasTube = document.getElementById('filter-has-tube');
const searchBar = document.querySelector('.search-bar'); // Use .search-bar for the input field

// Configuration for guest Browse limit
const GUEST_VIEW_LIMIT = 5; // Allow 5 product views before prompting
const GUEST_VIEW_KEY = 'guestProductViews'; // Key for localStorage

//////////////////////////////////////////////////////////
///////////////         PRODUCTS                /////////////
//////////////////////////////////////////////////////////

// Initialize the page
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Check for the products container immediately
        if (!productsContainer) {
            console.error("Error: '#products-container' element not found in the DOM.");
            // Display a user-friendly error message if the container is missing
            document.body.innerHTML = '<div style="color: red; text-align: center; margin-top: 50px;">Error: Product display area not found. Please check the page structure.</div>';
            return; // Stop execution if critical element is missing
        }

        getProd(); // Always attempt to get products
        setupEventListeners();
        //updateOrdersCount(); // This function will gracefully handle missing API key
    } catch (error) {
        showError(error.message);
    }
});

let minLoad = 1000; // for min 1 sec load time
let loadStart;

// Fetch products from API using XMLHttpRequest
async function getProd(params = {}) {
    // Increment view count if user is not logged in
    const apiKey = localStorage.getItem('userApiKey');
    let apiKeyPresent = !!apiKey; // Boolean flag if API key exists

    if (!apiKeyPresent) { // Only track for guests
        let viewCount = parseInt(localStorage.getItem(GUEST_VIEW_KEY) || '0');
        viewCount++;
        localStorage.setItem(GUEST_VIEW_KEY, viewCount.toString());

        if (viewCount > GUEST_VIEW_LIMIT) {
            promptLogin();
            return; // Stop fetching products if limit is exceeded
        }
    }

    showBuffering(); // Show buffering animation

    const xmlhr = new XMLHttpRequest();
    xmlhr.open('POST', baseUrl, true);
    xmlhr.setRequestHeader('Content-Type', 'application/json');

    xmlhr.onload = function () {
        const elapseTime = Date.now() - loadStart;
        const timeLeft = Math.max(minLoad - elapseTime, 0);

        setTimeout(() => {
            if (xmlhr.status >= 200 && xmlhr.status < 300) {
                console.log("Response Text:", xmlhr.responseText);
                try {
                    const prod = JSON.parse(xmlhr.responseText);
                    console.log("Parsed Products:", prod);
                    if (prod.status === 'error') {
                        showError(prod.data || prod.message || 'An API error occurred.'); // Use prod.data for error message if available
                    } else {
                        displayProds(prod.data, apiKeyPresent); // Pass apiKeyPresent to displayProds
                        // Populate filters after products are fetched and displayed
                        // You need the full list of products before grouping to populate filters correctly.
                        // This logic needs to be adjusted if your getAllProducts no longer returns a flat list first.
                        // For now, let's assume `prod.data` still has individual products in its sub-arrays if needed for filtering.
                        // Alternatively, you can make a separate API call for filter options.
                        // For the current structure (grouped by size), populating filters based on ALL available generic products might be tricky.
                        // A simple approach is to get filter options from the first call.
                        // The API's `getAllProducts` now returns data grouped by size,
                        // so `populateFilterOptions` needs to iterate through `products_of_size` from `tyreGroup`.
                        // For a simple implementation, let's pass the raw `products_of_size` from the first grouped element, or fetch all generic products once if that's easier for filters.
                        // For now, we'll assume `populateFilterOptions` can work with the `groupedTyreSizes` structure.
                        // It might be better to have an API endpoint that returns just filter options.
                        // For simplicity, let's assume products[0] (the first grouped size) contains products_of_size which can be used for filters.
                        if (prod.data && prod.data.length > 0) {
                            // Collect all individual products from all grouped sizes to populate filters accurately
                            let allIndividualProducts = [];
                            prod.data.forEach(tyreGroup => {
                                if (tyreGroup.products_of_size) {
                                    allIndividualProducts = allIndividualProducts.concat(tyreGroup.products_of_size);
                                }
                            });
                            populateFilterOptions(allIndividualProducts);
                        }
                    }
                } catch (e) {
                    showError('Error parsing the response: ' + e.message);
                }
            } else {
                console.log('Response status: ' + xmlhr.status);
                console.log('Response text: ', xmlhr.responseText);
                showError('Network response failed: ' + xmlhr.status + ' - ' + xmlhr.statusText);
            }
            hideBuffer();
        }, timeLeft);
    };

    xmlhr.onerror = function () {
        const elapseTime = Date.now() - loadStart;
        const timeLeft = Math.max(minLoad - elapseTime, 0);
        setTimeout(() => {
            showError('Request failed');
            hideBuffer();
        }, timeLeft);
    };

    console.log("🛠 Handling getAllProducts");

    const requestData = {
        type: 'getallproducts',
        ...(apiKey && { apikey: apiKey }), // Conditionally add apikey if it exists
        // The 'return' parameter for getAllProducts is handled server-side
        // by returning grouped data. We don't need to specify individual columns here
        // as the server decides the structure.
        limit: 15,
        ...params
    };

    xmlhr.send(JSON.stringify(requestData));
}

async function addToOrders(event) {
    const button = event.target;
    const listingId = button.getAttribute('data-listing-id');

    const apiKey = localStorage.getItem('userApiKey');
    if (!apiKey) {
        showTempMessage(document.body, 'Please log in to add items to your cart.', 'error');
        promptLogin();
        return;
    }

    showBuffering();

    try {
        const orderData = {
            destination_latitude: 0, // Temporary value
            destination_longitude: 0, // Temporary value
            products: [{
                listing_id: parseInt(listingId),
                quantity: 1
            }]
        };

        const response = await fetch(baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                apikey: apiKey,
                type: 'createorder',
                ...orderData
            })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log("API Response:", result);

        if (result.success) {
            const productCard = button.closest('.product-card') || document.body;
            showTempMessage(productCard, 'Added to Orders!', 'success');
            updateOrdersCount();
        } else {
            throw new Error(result.message || 'Failed to add to orders');
        }
    } catch (error) {
        console.error('Order error:', error);
        showTempMessage(document.body, error.message || 'Failed to add to orders. Please try again.', 'error');
    } finally {
        hideBuffer();
    }
}

// Helper function to show temporary messages
function showTempMessage(element, message, type) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `temp-message ${type}`;
    msgDiv.textContent = message;

    msgDiv.style.position = 'fixed';
    msgDiv.style.top = '20px';
    msgDiv.style.left = '50%';
    msgDiv.style.transform = 'translateX(-50%)';
    msgDiv.style.zIndex = '2000';
    msgDiv.style.padding = '10px 20px';
    msgDiv.style.borderRadius = '25px';
    msgDiv.style.fontWeight = 'bold';
    msgDiv.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';

    if (type === 'success') {
        msgDiv.style.backgroundColor = '#4CAF50';
        msgDiv.style.color = 'white';
    } else {
        msgDiv.style.backgroundColor = '#e63946';
        msgDiv.style.color = 'white';
    }

    document.body.appendChild(msgDiv);

    msgDiv.style.animation = 'slideIn 0.3s ease-out';

    setTimeout(() => {
        msgDiv.style.animation = 'fadeOut 0.5s ease-out forwards';
        setTimeout(() => msgDiv.remove(), 500);
    }, 1500);
}

async function updateOrdersCount() {
    try {
        const apiKey = localStorage.getItem('userApiKey');
        if (!apiKey) {
            const badge = document.getElementById('orders-badge');
            if (badge) badge.style.display = 'none';
            const mobileBadge = document.getElementById('mobile-orders-badge');
            if (mobileBadge) mobileBadge.style.display = 'none';
            return;
        }

        const response = await fetch(baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                apikey: apiKey,
                type: 'getallorders',
                count_only: true
            })
        });

        const result = await response.json();

        if (result.success) {
            const badge = document.getElementById('orders-badge');
            if (!badge) return;

            const count = result.data.count || 0;
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';

            const mobileBadge = document.getElementById('mobile-orders-badge');
            if (mobileBadge) {
                mobileBadge.textContent = count;
                mobileBadge.style.display = count > 0 ? 'inline-block' : 'none';
            }
        }
    } catch (error) {
        console.error('Error updating orders count:', error);
        const badge = document.getElementById('orders-badge');
        if (badge) badge.style.display = 'none';
        const mobileBadge = document.getElementById('mobile-orders-badge');
        if (mobileBadge) mobileBadge.style.display = 'none';
    }
}

// Passed apiKeyPresent from getProd for consistent button state
function displayProds(groupedTyreSizes, apiKeyPresent) {
    if (!productsContainer) { // Double check if container is missing before trying to manipulate
        console.error("productsContainer is null in displayProds. Cannot display products.");
        return;
    }
    productsContainer.innerHTML = ''; // Clear previous products

    if (!groupedTyreSizes || groupedTyreSizes.length === 0) {
        productsContainer.innerHTML = '<p>No tyre sizes found.</p>';
        return;
    }

    groupedTyreSizes.forEach(tyreGroup => {
        const productCard = document.createElement('div');
        productCard.classList.add('product-card');

        const imageUrl = '../img/construction.png';

        productCard.innerHTML = `
            <div class="product-card-left">
                <img src="${imageUrl}" alt="${tyreGroup.size} Tyre" class="product-image">
            </div>
            <div class="product-card-middle">
                <h3 class="tyre-size">${tyreGroup.size}</h3>
            </div>
            <div class="product-card-right">
                <h5>Available from:</h5>
                <div class="seller-listings">
                    ${tyreGroup.all_listings_for_size && tyreGroup.all_listings_for_size.length > 0 ?
                        tyreGroup.all_listings_for_size.map((listing, index) => `
                            <div class="seller-option"
                                 data-listing-id="${listing.listing_id}"
                                 data-tyre-id="${listing.tyre_id}">
                                <span class="seller-type ${getSellerDotColor(index)}">${listing.username || 'Unknown Seller'}</span>
                                <span class="seller-price">
                                    ${listing.original_price && listing.original_price > listing.selling_price ?
                                        `<span class="original-price">$${parseFloat(listing.original_price).toFixed(2)}</span>` : ''}
                                    <span class="selling-price">$${parseFloat(listing.selling_price).toFixed(2)}</span>
                                </span>
                                <button class="add-to-orders"
                                    data-listing-id="${listing.listing_id}"
                                    ${!apiKeyPresent ? 'disabled title="Log in to View Details"' : ''}>
                                    ${!apiKeyPresent ? 'Log In to Add' : 'View Details'}
                                </button>
                            </div>
                        `).join('')
                        : '<p>No listings for this tyre size yet.</p>'
                    }
                </div>
            </div>
        `;
        productsContainer.appendChild(productCard);
    });

    // Add event listeners for clicking on a seller option
    document.querySelectorAll('.seller-option').forEach(sellerOption => {
        sellerOption.addEventListener('click', (event) => {
            event.stopPropagation();
            const listingId = sellerOption.dataset.listingId;
            const tyreId = sellerOption.dataset.tyreId;
            showListingDetails(tyreId, listingId);
        });
    });

    // Add event listeners for "Add to Cart" buttons
    document.querySelectorAll('.add-to-orders').forEach(button => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            if (apiKeyPresent) { // Use the passed apiKeyPresent state
                const listingId = event.currentTarget.dataset.listingId;
                addToOrders(event); // Call addToOrders, which uses the event object
            } else {
                alert('Please log in to add items to your cart.');
            }
        });
    });
}

// Helper function for seller dot color
function getSellerDotColor(index) {
    const colors = ['seller-dot-red', 'seller-dot-blue', 'seller-dot-green', 'seller-dot-yellow'];
    return colors[index % colors.length];
}

// Function to handle showing listing details
function showListingDetails(tyreId, listingId) {
    console.log(`Displaying details for Generic Tyre ID: ${tyreId}, Specific Listing ID: ${listingId}`);

    const apiKey = localStorage.getItem('userApiKey'); // Get API key for this call
    if (!apiKey) {
        alert('Please log in to view full listing details.');
        promptLogin();
        return;
    }

    fetch(baseUrl, { // Use baseUrl
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            type: 'getfulllistingdetails',
            tyre_id: tyreId,
            listing_id: listingId,
            apikey: apiKey // Pass the API key here
        })
    })
    .then(response => {
        if (!response.ok) {
            // If response is not OK, try to parse JSON error message
            return response.json().then(errorData => {
                throw new Error(errorData.data || errorData.message || `HTTP error! status: ${response.status}`);
            }).catch(() => {
                // If it's not JSON, just throw a generic error
                throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success' && data.data) {
            const product = data.data.product;
            const listing = data.data.listing;

            alert(`
                Tyre Size: ${product.size}
                Generic Serial: ${product.generic_serial_num}
                Rating (Generic): ${product.rating}
                Seller: ${listing.username}
                Selling Price: $${parseFloat(listing.selling_price).toFixed(2)}
                Original Price: ${listing.original_price ? `$${parseFloat(listing.original_price).toFixed(2)}` : 'N/A'}
                Listing Serial Num: ${listing.serial_num}
                Image: ${product.img_url || 'N/A'}
                Load Index: ${product.load_index}
                Has Tube: ${product.has_tube ? 'Yes' : 'No'}
                `);
            // Here you would typically open a modal or navigate to a dedicated page
        } else {
            alert('Error fetching listing details: ' + (data.data || data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error fetching listing details:', error);
        alert('Failed to retrieve listing details: ' + error.message);
    });
}

// Populate filter dropdowns with available options
function populateFilterOptions(products) {
    // Collect unique values from the flat list of individual products
    const sizes = [...new Set(products.map(p => p.size).filter(Boolean))].sort();
    if (filterSize) {
        filterSize.innerHTML = '<option value="">All Sizes</option>' +
            sizes.map(size => `<option value="${size}">${size}</option>`).join('');
    }

    const loadIndices = [...new Set(products.map(p => p.load_index).filter(val => val !== null))].sort((a, b) => a - b);
    if (filterLoadIndex) {
        filterLoadIndex.innerHTML = '<option value="">All Load Indices</option>' +
            loadIndices.map(index => `<option value="${index}">${index}</option>`).join('');
    }

    if (filterHasTube) {
        filterHasTube.innerHTML = `
            <option value="">All (Has Tube)</option>
            <option value="1">Yes</option>
            <option value="0">No</option>
        `;
    }
}

// Set up event listeners
function setupEventListeners() {

    if (filterGeneral) {
        filterGeneral.addEventListener('change', (e) => {
            const filterValue = e.target.value;
            let params = {};

            switch (filterValue) {
                case 'newest':
                    params = { sort: 'tyre_id', order: 'DESC' };
                    break;
                case 'oldest':
                    params = { sort: 'tyre_id', order: 'ASC' };
                    break;
                case 'size-asc':
                    params = { sort: 'size', order: 'ASC' };
                    break;
                case 'size-desc':
                    params = { sort: 'size', order: 'DESC' };
                    break;
                case 'rating-asc':
                    params = { sort: 'rating', order: 'ASC' };
                    break;
                case 'rating-desc':
                    params = { sort: 'rating', order: 'DESC' };
                    break;
                // Add more cases for other general filters if needed
            }
            getProd(params);
        });
    }

    if (filterSize) {
        filterSize.addEventListener('change', (e) => {
            const size = e.target.value;
            getProd({ size: size || undefined });
        });
    }

    if (filterLoadIndex) {
        filterLoadIndex.addEventListener('change', (e) => {
            const loadIndex = e.target.value;
            getProd({ load_index: loadIndex !== '' ? parseInt(loadIndex) : undefined });
        });
    }

    if (filterHasTube) {
        filterHasTube.addEventListener('change', (e) => {
            const hasTube = e.target.value;
            getProd({ has_tube: hasTube !== '' ? parseInt(hasTube) : undefined });
        });
    }

    if (searchBar) {
        searchBar.addEventListener('input', debounce((e) => {
            const query = e.target.value.trim();
            if (query.length > 2) {
                getProd({
                    search: { size: query }, // Defaulting search to 'size' for now
                    fuzzy: true
                });
            } else if (query.length === 0) {
                getProd(); // Fetch all products again if search is cleared
            }
        }, 300));
    }

    // Event delegation for Add to Orders buttons
    // This is more efficient as it doesn't add a listener to every button
    document.addEventListener('click', function(e) {
        // Check if the clicked element or its parent is an 'add-to-orders' button
        const button = e.target.closest('.add-to-orders');
        if (button && !button.disabled) {
            e.preventDefault();
            addToOrders(e); // Pass the event object to addToOrders
        }
    });
}

//////////////////////////////////////////////////////////
///////////         Helper functions            ////////////
//////////////////////////////////////////////////////////
//for my loading symbol

function showBuffering() {
    loadStart = Date.now();
    // Ensure productsContainer is not null before setting innerHTML
    if (productsContainer) {
        productsContainer.innerHTML = `
            <div class="loading-spinner" id="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p class="loading-text">Curating Your Parcellas...</p>
            </div>
        `;
    } else {
        console.error("productsContainer is null. Cannot display buffering spinner.");
    }
}

function hideBuffer() {
    const spin = document.getElementById('loading-spinner');
    if (spin) {
        spin.remove();
    }
}

function showError(message) {
    // Ensure productsContainer is not null before setting innerHTML
    if (productsContainer) {
        productsContainer.innerHTML = `
            <div class="error-message">
                <h5>Error: ${message}</h5>
                <button onclick="window.location.reload()">Try Again</button>
            </div>
        `;
    } else {
        console.error("productsContainer is null. Cannot display error message.");
        alert("An error occurred: " + message + ". Please try refreshing the page.");
    }
}

function debounce(func, timeout = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { func.apply(this, args); }, timeout);
    };
}

// Function to prompt login
function promptLogin() {
    if (document.getElementById('login-prompt-modal')) {
        return;
    }

    const modalHtml = `
        <div id="login-prompt-modal" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        ">
            <div style="
                background-color: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                text-align: center;
                max-width: 400px;
                margin: 20px;
            ">
                <h3 style="margin-top: 0; color: #333;">Welcome Back!</h3>
                <p style="color: #666; line-height: 1.5;">You've viewed several products. Please log in or register to continue Browse and add items to your cart!</p>
                <div style="margin-top: 20px;">
                    <button id="modal-login-btn" style="
                        background-color: #f7a32c;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 20px;
                        cursor: pointer;
                        font-size: 1em;
                        margin-right: 10px;
                        transition: background-color 0.2s ease;
                    ">Log In</button>
                    <button id="modal-signup-btn" style="
                        background-color: #007bff;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 20px;
                        cursor: pointer;
                        font-size: 1em;
                        transition: background-color 0.2s ease;
                    ">Sign Up</button>
                    <button id="modal-close-btn" style="
                        background-color: #6c757d;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 20px;
                        cursor: pointer;
                        font-size: 1em;
                        margin-top: 10px;
                        transition: background-color 0.2s ease;
                    ">Continue Browse (Limited)</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    document.getElementById('modal-login-btn').addEventListener('click', () => {
        localStorage.removeItem(GUEST_VIEW_KEY);
        window.location.href = 'signup.php';
    });

    document.getElementById('modal-signup-btn').addEventListener('click', () => {
        localStorage.removeItem(GUEST_VIEW_KEY);
        window.location.href = 'signup.php';
    });

    document.getElementById('modal-close-btn').addEventListener('click', () => {
        const modal = document.getElementById('login-prompt-modal');
        if (modal) {
            modal.remove();
        }
    });
}

// Function to get cookie (if you don't have one)
function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for(let i=0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}