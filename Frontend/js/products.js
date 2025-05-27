
var apiBase = '../GOTapi.php';

var products = [];
var favourites = {};
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
var favouritesBtn = document.getElementById('show-favourites-btn');
var loadMoreBtn = document.getElementById('load-more-btn');

var productsPerPage = 10;   // number of products per batch
var productsShown = 0;      // how many currently shown

// function apiFetch(data) {
//     var body = JSON.stringify(data);
//     var headers = { 'Content-Type': 'application/json' };
//     if (apiKey) headers['X-API-Key'] = apiKey;

//     return fetch(apiBase, {
//         method: 'POST',
//         headers: headers,
//         body: body
//     }).then(function(res) {
//         return res.json();
//     });
// }

function apiFetch(data) {
    var headers = { 'Content-Type': 'application/json' };
    if (apiKey) headers['X-API-Key'] = apiKey;

    return fetch(apiBase, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(data)
    }).then(function(res) {
        if (!res.ok) {
            return res.json().then(function(err) {
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
    var onlyFavourites = options.onlyFavourites || false;

    var requestData = {
        type: 'GetAllProducts',
        api_key: apiKey
    };

    return apiFetch(requestData)
        .then(function(response) {
            if (response.status === 'error') {
                if (response.requires_login) {
                    alert('Guest view limit reached. Please log in to continue browsing.');
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

            if (onlyFavourites && apiKey) {
                return loadFavourites().then(function() {
                    products = products.filter(function(p) {
                        return favourites[p.tyre_id];
                    });
                    filteredProducts = products; // reset filteredProducts to favourites
                    productsShown = productsPerPage; // reset pagination
                    renderProducts(filteredProducts);
                });
            } else {
                filteredProducts = products; // reset filteredProducts on full load
                productsShown = productsPerPage; // reset pagination
                renderProducts(filteredProducts);
            }
        })
        .catch(function(err) {
            console.error('Failed to load products', err.message || err);
        });
}

function loadFavourites() {
    if (!apiKey) return Promise.resolve();

    return apiFetch({ type: 'getFavourites', api_key: apiKey })
        .then(function(response) {
            if (response.status === 'error') {
                console.warn('Failed to load favourites:', response.message);
                favourites = {};
                if (response.message.includes('Invalid API key') || response.message.includes('not logged in')) {
                    alert('Your session has expired. Please log in again.');
                    // Redirect to login or clear session:
                    sessionStorage.removeItem('userApiKey');
                    window.location.href = 'login.php'; // Or your login page
                }
                return;
            }

            favourites = {};
            var favs = response.data || [];
            for (var i = 0; i < favs.length; i++) {
                favourites[favs[i].tyre_id] = true;
            }
        });
}

function toggleFavourite(tyreId) {
    var currapiKey = sessionStorage.getItem('userApiKey');
    if (!currapiKey) {
        alert('Please log in to manage favourites.');
        return;
    }

    var isFav = favourites[tyreId];
    var type = isFav ? 'removeFavourite' : 'addFavourite';

    apiFetch({ type: type, tyre_id: tyreId, api_key: currapiKey })
        .then(function(response) {
            if (response.status === 'success') {
                if (isFav) {
                    delete favourites[tyreId];
                } else {
                    favourites[tyreId] = true;
                }
                renderProducts(filteredProducts); // re-render filtered products to reflect change
            } else {
                alert('Error updating favourites: ' + response.message);
            }
        })
        .catch(function(err) {
            console.error('Favourite update failed', err.message || err);
        });
}

function convertPrice(originalPrice) {
    return Number(originalPrice).toFixed(2);
}

// Filtering, searching, and sorting logic
var filteredProducts = [];  // holds currently displayed products after filter/search/sort

function applyFilters(productsToFilter) {
  return productsToFilter;
}


function applySearch(productsToSearch) {
    var searchTerm = searchInput.value.toLowerCase().trim();
    if (!searchTerm) return productsToSearch;

    return productsToSearch.filter(function(p) {
        // Search in tyre_id, size, serial_num, seller name, brand, category (example)
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
    var sorted = productsToSort.slice(0); // copy array

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
        sorted.sort(function(a, b) {
            return (b.seller_username || '').localeCompare(a.seller_username || '');
        });
    }

    return sorted;
}


function updateProductDisplay() {
    var tempProducts = applyFilters(products);
    tempProducts = applySearch(tempProducts);
    filteredProducts = applySort(tempProducts);

    productsShown = productsPerPage;  // reset to first batch on filters/search/sort change
    renderProducts(filteredProducts);
}

function renderProducts(productsToRender) {
    productContainer.innerHTML = '';

    if (!productsToRender || productsToRender.length === 0) {
        productContainer.innerHTML = '<p>No products found.</p>';
        loadMoreBtn.style.display = 'none';
        return;
    }

    // Maximum products guest can see
    var maxLimit = isGuest ? Math.min(guestViewLimit, productsToRender.length) : productsToRender.length;

    // Number of products to actually show based on productsShown and maxLimit
    var maxToShow = Math.min(productsShown, maxLimit);
    var productsToShow = productsToRender.slice(0, maxToShow);

    for (var i = 0; i < productsToShow.length; i++) {
        var p = productsToShow[i];
        var isFav = favourites[p.tyre_id];
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
            '<button class="fav-btn" data-id="' + p.tyre_id + '">' +
                (isFav ? '★ Remove Favourite' : '☆ Add Favourite') +
            '</button>';

        (function(tyreId) {
            productEl.querySelector('.fav-btn').addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleFavourite(tyreId);
            });
            
            // Add click handler for the entire product card if needed
            productEl.addEventListener('click', function(e) {
                if (!e.target.classList.contains('fav-btn')) {
                    window.location.href = 'viewpage.php?tyre_id=' + tyreId;
                }
            });
        })(p.tyre_id);

        productContainer.appendChild(productEl);
    }


    // Show/hide Load More button
    if (maxToShow < maxLimit) {
        loadMoreBtn.style.display = 'block';
    } else {
        loadMoreBtn.style.display = 'none';

        // Show guest limit notice
        if (isGuest && maxLimit === guestViewLimit) {
            var notice = document.createElement('p');
            notice.style.color = 'red';
            notice.style.marginTop = '1em';
            notice.textContent = 'Guest users see only ' + guestViewLimit + ' products. Please log in to view all.';
            productContainer.appendChild(notice);
        }
    }
}



favouritesBtn.addEventListener('click', function() {
    if (!apiKey) {
        alert('Please log in to view favourites.');
        return;
    }
    loadProducts({ onlyFavourites: true });
});


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
    apiKey = sessionStorage.getItem('userApikey') || null;
    userRole = sessionStorage.getItem('userRole') || null;
    productsShown = productsPerPage;

    if (apiKey) {
        isGuest = false;
        loadFavourites().then(function() {
            loadProducts().then(function() {
                if (isGuest && guestViewsUsed >= guestViewLimit) {
                    alert('Guest view limit reached. Please log in for full access.');
                }
            });
        });
    } else {
        isGuest = true;
        loadProducts().then(function() {
            if (isGuest && guestViewsUsed >= guestViewLimit) {
                alert('Guest view limit reached. Please log in for full access.');
            }
        });
    }
}

init();

