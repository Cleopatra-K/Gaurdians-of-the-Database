var apiBase = '../GOTapi.php';

var products = [];
var favourites = {}; // Re-added: To store favourite status
var apiKey = sessionStorage.getItem('userApiKey'); // Keep this for general authentication
var userRole = null;
var isGuest = true;
var guestViewLimit = 20;
var guestViewsUsed = 0;
var currentCurrency = 'ZAR';

var productContainer = document.getElementById('product-list');
var currencySelect = document.getElementById('currency-select');
var filterForm = document.getElementById('filter-form');
var searchInput = document.getElementById('search-input');
var sortSelect = document.getElementById('sort-select');
// favouritesBtn is NOT re-added here as the "See My Favourites" button is removed from products.php
// var favouritesBtn = document.getElementById('show-favourites-btn');
var loadMoreBtn = document.getElementById('load-more-btn');

var productsPerPage = 10;   // number of products per batch
var productsShown = 0;      // how many currently shown

function apiFetch(data) {
    var headers = { 'Content-Type': 'application/json' };
    if (apiKey) headers['X-API-Key'] = apiKey; // Ensure API key is sent with all requests

    return fetch(apiBase, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(data)
    }).then(function(res) {
        if (!res.ok) {
            return res.json().then(function(err) {
                // Handle specific API key/login errors
                if (err.message && (err.message.includes('Invalid API key') || err.message.includes('not logged in'))) {
                    console.warn('API Key invalid/session expired. Clearing session.');
                    sessionStorage.removeItem('userApiKey');
                    sessionStorage.removeItem('user_id');
                    sessionStorage.removeItem('userRole');
                    sessionStorage.removeItem('username');
                    alert('Your session has expired. Please log in again.');
                    window.location.href = 'login.php'; // Redirect to login
                }
                throw new Error(err.message || 'Unknown error from server.');
            }).catch(function() {
                throw new Error('Failed to connect to the API.');
            });
        }
        return res.json();
    });
}

function loadProducts(options) {
    options = options || {};
    // 'onlyFavourites' parameter is no longer used for filtering display on this page,
    // but the original `loadProducts` function might have relied on its presence, so leaving it for now.
    // var onlyFavourites = options.onlyFavourites || false;

    var requestData = {
        type: 'GetAllProducts',
        api_key: apiKey // Always send apiKey for GetAllProducts
    };

    return apiFetch(requestData)
        .then(function(response) {
            if (response.status === 'error') {
                if (response.requires_login) {
                    alert('Guest view limit reached. Please log in to continue Browse.');
                    window.location.href = 'signup.php';
                } else {
                    alert('Error loading products: ' + response.message);
                }
                return;
            }

            products = response.products || [];
            isGuest = response.requires_login === false ? false : true;
            guestViewsUsed = response.guest_views || 0;
            guestViewLimit = response.guest_view_limit || 50;

            // Load favourites AFTER products are loaded, if user is logged in
            if (apiKey) {
                return loadFavourites().then(function() {
                    // No filtering by favourites here, just load and then render normally
                    filteredProducts = products;
                    productsShown = productsPerPage;
                    renderProducts(filteredProducts);
                });
            } else {
                // If not logged in, just render products without favourite status
                filteredProducts = products;
                productsShown = productsPerPage;
                renderProducts(filteredProducts);
            }
        })
        .catch(function(err) {
            console.error('Failed to load products', err.message || err);
        });
}

// Re-added: Function to load user's favourites
function loadFavourites() {
    if (!apiKey) {
        favourites = {}; // Ensure favourites object is empty if no API key
        return Promise.resolve(); // Resolve immediately if not logged in
    }

    // Pass user_id for getFavourites if your API requires it, otherwise api_key is enough.
    // Assuming your API can derive user_id from api_key as done in favourites.js
    return apiFetch({ type: 'getFavourites', api_key: apiKey })
        .then(function(response) {
            if (response.status === 'error') {
                console.warn('Failed to load favourites:', response.message);
                favourites = {}; // Clear favourites if there's an error
                // Specific API key/login errors are handled in apiFetch, so no need to repeat alert/redirect here.
                return;
            }

            favourites = {};
            // Corrected from response.data to response.favourites as per your API output
            var favs = response.favourites || [];
            for (var i = 0; i < favs.length; i++) {
                favourites[favs[i].tyre_id] = true;
            }
        })
        .catch(function(err) {
            console.error('Error loading favourites:', err);
            favourites = {}; // Ensure favourites is empty on network errors too
        });
}

// Re-added: Function to toggle favourite status
function toggleFavourite(tyreId) {
    if (!apiKey) { // Use the global apiKey variable
        alert('Please log in to manage favourites.');
        return;
    }

    var isFav = favourites[tyreId];
    var type = isFav ? 'removeFavourite' : 'addFavourite';

    apiFetch({ type: type, tyre_id: tyreId, api_key: apiKey }) // Use global apiKey
        .then(function(response) {
            if (response.status === 'success') {
                if (isFav) {
                    delete favourites[tyreId];
                    alert("Tyre removed from favourites."); // Optional: provide feedback
                } else {
                    favourites[tyreId] = true;
                    alert("Tyre added to favourites!"); // Optional: provide feedback
                }
                renderProducts(filteredProducts); // Re-render to update star icon
            } else {
                alert('Error updating favourites: ' + response.message);
            }
        })
        .catch(function(err) {
            console.error('Favourite update failed', err.message || err);
            alert('Network error or server problem when updating favourite.');
        });
}

function convertPrice(originalPrice) {
    return Number(originalPrice).toFixed(2);
}

var filteredProducts = [];

function applyFilters(productsToFilter) {
    return productsToFilter;
}

function applySearch(productsToSearch) {
    var searchTerm = searchInput.value.toLowerCase().trim();
    if (!searchTerm) return productsToSearch;

    return productsToSearch.filter(function(p) {
        return (
            (p.tyre_id && p.tyre_id.toString().indexOf(searchTerm) !== -1) ||
            (p.size && p.size.toLowerCase().indexOf(searchTerm) !== -1) ||
            (p.serial_num && p.serial_num.toLowerCase().indexOf(searchTerm) !== -1) ||
            (p.seller_username && p.seller_username.toLowerCase().indexOf(searchTerm) !== -1) ||
            (p.brand && p.brand.toLowerCase().indexOf(searchTerm) !== -1) ||
            (p.category && p.category.toLowerCase().indexOf(searchTerm) !== -1)
        );
    });
}

function applySort(productsToSort) {
    var sortValue = sortSelect.value;
    var sorted = productsToSort.slice(0);

    if (sortValue === 'priceLowToHigh') {
        sorted.sort(function(a, b) {
            return parseFloat(a.selling_price) - parseFloat(b.selling_price);
        });
    } else if (sortValue === 'priceHighToLow') {
        sorted.sort(function(a, b) {
            return parseFloat(b.selling_price) - parseFloat(a.selling_price);
        });
    } else if (sortValue === 'tyreIDAtoZ') {
        sorted.sort(function(a, b) {
            return (a.seller_username || '').localeCompare(b.seller_username || '');
        });
    } else if (sortValue === 'tyreIDZtoA') {
            return (b.seller_username || '').localeCompare(a.seller_username || '');
    }

    return sorted;
}



function updateProductDisplay() {
    var tempProducts = applyFilters(products);
    tempProducts = applySearch(tempProducts);
    filteredProducts = applySort(tempProducts);

    productsShown = productsPerPage;
    renderProducts(filteredProducts);
}

function renderProducts(productsToRender) {
    productContainer.innerHTML = '';

    if (!productsToRender || productsToRender.length === 0) {
        productContainer.innerHTML = '<p>No products found.</p>';
        loadMoreBtn.style.display = 'none';
        return;
    }

    var maxLimit = isGuest ? Math.min(guestViewLimit, productsToRender.length) : productsToRender.length;
    var maxToShow = Math.min(productsShown, maxLimit);
    var productsToShow = productsToRender.slice(0, maxToShow);

    for (var i = 0; i < productsToShow.length; i++) {
        var p = productsToShow[i];
        var isFav = favourites[p.tyre_id]; // Re-added: Check favourite status
        var price = convertPrice(p.selling_price);

        var productEl = document.createElement('div');
        productEl.className = 'product-card';

        productEl.innerHTML =
            '<a href="viewpage.php?tyre_id=' + p.tyre_id + '" class="product-link">' +
                '<img src="' + p.img_url + '" alt="Tyre ' + p.tyre_id + '" />' +
                '<h3>Tyre ' + p.tyre_id + ' - ' + p.size + ' ' + p.load_index + ' ' + (p.has_tube ? 'Tube' : 'Tubeless') + '</h3>' +
                '<p>Serial: ' + p.serial_num + '</p>' +
                '<p>Seller: ' + (p.seller_username || p.seller_name || 'Unknown') + '</p>' +
                '<p>Price: ' + price + ' ' + currentCurrency + '</p>' +
            '</a>' +
            // Re-added: Favourite button HTML
            '<button class="fav-btn" data-id="' + p.tyre_id + '">' +
                (isFav ? '<i class="fas fa-star"></i> Remove Favourite' : '<i class="far fa-star"></i> Add Favourite') +
            '</button>';

        (function(tyreId) {
            // Re-added: Favourite button event listener
            productEl.querySelector('.fav-btn').addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent card link from being followed
                toggleFavourite(tyreId);
            });

            productEl.addEventListener('click', function(e) {
                if (!e.target.closest('.fav-btn')) { // Ensure click isn't on the favourite button or its icon
                    window.location.href = 'viewpage.php?tyre_id=' + tyreId;
                }
            });
        })(p.tyre_id);

        productContainer.appendChild(productEl);
    }

    if (maxToShow < maxLimit) {
        loadMoreBtn.style.display = 'block';
    } else {
        loadMoreBtn.style.display = 'none';

        if (isGuest && maxLimit === guestViewLimit) {
            var notice = document.createElement('p');
            notice.style.color = 'red';
            notice.style.marginTop = '1em';
            notice.textContent = 'Guest users see only ' + guestViewLimit + ' products. Please log in to view all.';
            productContainer.appendChild(notice);
        }
    }
}

searchInput.addEventListener('input', function() {
    updateProductDisplay();
});

sortSelect.addEventListener('change', function() {
    updateProductDisplay();
});

loadMoreBtn.addEventListener('click', function() {
    productsShown += productsPerPage;
    renderProducts(filteredProducts);
});

// Init
function init() {
    apiKey = sessionStorage.getItem('userApiKey') || null;
    userRole = sessionStorage.getItem('userRole') || null;
    productsShown = productsPerPage;

    if (apiKey) {
        isGuest = false;
        // Load favourites first, then products
        loadFavourites().then(function() {
            loadProducts().then(function() {
                if (isGuest && guestViewsUsed >= guestViewLimit) {
                    alert('Guest view limit reached. Please log in for full access.');
                }
            });
        });
    } else {
        isGuest = true;
        // If not logged in, just load products. Favourites will be empty.
        loadProducts().then(function() {
            if (isGuest && guestViewsUsed >= guestViewLimit) {
                alert('Guest view limit reached. Please log in for full access.');
            }
        });
    }
}

init();