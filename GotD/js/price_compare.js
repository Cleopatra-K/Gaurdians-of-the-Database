// price_compare.js - Updated with Navigation Search

const baseUrl = "../GOTapi.php";
const productsContainer = document.getElementById('products-container');
const searchBar = document.getElementById('nav-search');
const searchButton = document.getElementById('search-button');
const filterSeller = document.getElementById('filter-seller');
const filterTube = document.getElementById('filter-tube');
let allProducts = [];
let isLoggedIn = false;
let currentPage = 1;
const GUEST_PAGE_SIZE = 5;  
const LOGGED_IN_PAGE_SIZE = 10;
let searchTimer;

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

// Check login status using cookies
function checkLoggedInStatus() {
    // return document.cookie.split(';').some((item) => item.trim().startsWith('PHPSESSID='));
    const apiKey = sessionStorage.getItem('api_key') || getCookie('api_key');
    return !!apiKey;
}


// Initialize the page
document.addEventListener('DOMContentLoaded', async () => {
    try {
        if (!productsContainer) {
            showError("Product display area not found");
            return;
        }

        isLoggedIn = checkLoggedInStatus();
        await loadSellers();
        setupEventListeners();
        setupSearch();
        getProd();
        
        const urlParams = new URLSearchParams(window.location.search);
        const sizeParam = urlParams.get('size');
        if (sizeParam && searchBar) {
            searchBar.value = sizeParam;
        }

        getFilteredProducts();

        if (isLoggedIn) {
            updateFavoritesBadge();
        }
    } catch (error) {
        showError(error.message);
    }
});

// Setup search functionality
function setupSearch() {
    if (searchButton) {
        searchButton.addEventListener('click', () => {
            currentPage = 1;
            getFilteredProducts();
        });
    }

    if (searchBar) {
        searchBar.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                currentPage = 1;
                getFilteredProducts();
            }, 500);
        });

        searchBar.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                currentPage = 1;
                getFilteredProducts();
            }
        });
    }
}

// Get filtered products based on search and filters
function getFilteredProducts() {
    const params = {};
    
    // Search by tyre size
    if (searchBar && searchBar.value.trim()) {
        params.size = searchBar.value.trim();
    }
    
    // Seller filter
    if (filterSeller && filterSeller.value) {
        params.seller_id = filterSeller.value;
    }
    
    // Tube/Tubeless filter
    if (filterTube && filterTube.value !== '') {
        params.has_tube = filterTube.value;
    }
    
    getProd(params);
}

// Fetch products from API
async function getProd(params = {}) {
    showBuffering();
    
    try {
        const response = await fetch(baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                type: 'GetAllProducts',
                ...params,
                page: currentPage,
                page_size: isLoggedIn ? LOGGED_IN_PAGE_SIZE : GUEST_PAGE_SIZE
            })
        });

        const result = await response.json();
        
        if (result.status === 'error') {
            showError(result.message);
            return;
        }

        allProducts = transformApiResponse(result.products);
        const totalProducts = result.total_count || allProducts.length;
        const pageSize = isLoggedIn ? LOGGED_IN_PAGE_SIZE : GUEST_PAGE_SIZE;
        const productsToShow = allProducts.slice(0, pageSize * currentPage);
        displayProds(productsToShow);
        toggleLoadMoreButton();
        
    } catch (error) {
        showError('Error: ' + error.message);
    } finally {
        hideBuffer();
    }
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
        if (data.status === 'success') {
            data.products.forEach(product => {
                const element = document.querySelector(`[data-tyre-id="${product.tyre_id}"]`);
                if (element) {
                    element.classList.add('popular-product');
                    element.innerHTML += `
                        <div class="popular-badge">
                            <span class="badge-icon">🔥</span>
                            <span class="badge-text">Popular</span>
                        </div>
                    `;
                }
            });
        }
    });
}

// Load sellers for filter dropdown
async function loadSellers() {
    try {
        const response = await fetch(baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'getSellers'
            })
        });

        const result = await response.json();
        
        if (result.status === 'success' && filterSeller) {
            result.sellers.forEach(seller => {
                const option = document.createElement('option');
                option.value = seller.user_id;
                option.textContent = seller.name;
                filterSeller.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading sellers:', error);
    }
}

// Transform API response to grouped products
function transformApiResponse(products) {
    if (!products || !Array.isArray(products)) return [];
    
    const productGroups = {};
    
    products.forEach(product => {
        const groupKey = `${product.size}-${product.load_index}-${product.has_tube}`;
        
        if (!productGroups[groupKey]) {
            productGroups[groupKey] = {
                size: product.size,
                load_index: product.load_index,
                has_tube: product.has_tube,
                listings: []
            };
        }
        
        const listing = {
            tyre_id: product.tyre_id,
            name: product.seller_name || 'Unknown Seller',
            original_price: product.original_price || null,
            selling_price: product.selling_price || calculateMockPrice(product),
            img_url: product.img_url || '../img/construction.png'
        };
        
        productGroups[groupKey].listings.push(listing);
    });
    
    return Object.values(productGroups);
}

// Display products
function displayProds(productGroups) {
    if (!productsContainer) return;
    
    const loadMoreBtn = document.getElementById('load-more-btn');
    const temp = loadMoreBtn ? loadMoreBtn.outerHTML : '';
    productsContainer.innerHTML = '';
    if (temp) productsContainer.innerHTML = temp;

    if (!productGroups || productGroups.length === 0) {
        productsContainer.innerHTML = '<p class="no-results">No matching products found.</p>';
        return;
    }

    productGroups.forEach(group => {
        const productCard = document.createElement('div');
        productCard.classList.add('product-card');
        productCard.innerHTML = `
            <div class="product-header">
                <div class="product-image-container">
                    <img src="${group.listings[0]?.img_url}" 
                         alt="${group.size} Tyre" 
                         class="product-image"
                         onerror="this.src='../img/construction.png'">
                </div>
                <div class="product-specs-container">
                    <div class="specs-grid">
                        <div class="spec-item">
                            <span class="spec-label">Size</span>
                            <span class="spec-value">${group.size}</span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Load Index</span>
                            <span class="spec-value">${group.load_index}</span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Type</span>
                            <span class="spec-value">${group.has_tube ? 'Tube' : 'Tubeless'}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="seller-section">
                <h3 class="seller-section-title">Available Sellers</h3>
                <div class="seller-listings">
                    ${group.listings.map((listing, index) => `
                        <div class="seller-row">
                            <div class="seller-info">
                            <span class="seller-name ${getSellerDotColor(index)}" data-tyre-id="${listing.tyre_id}" style="cursor: pointer;">
                                        ${listing.name}
                            </span>
                                <div class="price-container">
                                    <span class="current-price">$${(parseFloat(listing.selling_price) || 0).toFixed(2)}</span>
                                    ${listing.original_price ? 
                                     `<span class="original-price">$${parseFloat(listing.original_price).toFixed(2)}</span>` : ''}
                                </div>
                            </div>
                            <button class="favorite-btn" 
                                    data-tyre-id="${listing.tyre_id}"
                                    ${!isLoggedIn ? 'disabled' : ''}>
                                ${!isLoggedIn ? '♡' : '❤️'}
                                <span class="tooltip">${!isLoggedIn ? 'Login to save' : 'Save to favorites'}</span>
                            </button>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        productsContainer.insertBefore(productCard, loadMoreBtn);
    });

    // Add event listeners for favorite buttons
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', addToFavorites);
    });
}

// function viewProductDetails(tyreId) {
//     // Redirect to the product view page with the tyre ID

//     window.location.href = `viewpage.php?tyre_id=${tyreId}`;
// }
function viewProductDetails(tyreId) {
    // Option 1: If you already know the size
    const tyreGroup = allProducts.find(group => 
        group.listings.some(listing => listing.tyre_id === tyreId)
    );
    const size = tyreGroup?.size || '';
    
    // Redirect with size in query
    window.location.href = `price_compare.php?size=${encodeURIComponent(size)}`;
}


// Setup event listeners for filters
function setupEventListeners() {
    productsContainer.addEventListener('click', (e) => {
    const sellerName = e.target.closest('.seller-name');
    if (sellerName) {
        const tyreId = sellerName.getAttribute('data-tyre-id');
        if (tyreId) {
            viewProductDetails(tyreId);
        }
    }
});

    if (filterSeller) {
        filterSeller.addEventListener('change', () => {
            currentPage = 1;
            getFilteredProducts();
        });
    }

    if (filterTube) {
        filterTube.addEventListener('change', () => {
            currentPage = 1;
            getFilteredProducts();
        });
    }
}

// Helper functions
function getSellerDotColor(index) {
    const colors = ['seller-dot-red', 'seller-dot-blue', 'seller-dot-green', 'seller-dot-yellow'];
    return colors[index % colors.length];
}

function calculateMockPrice(product) {
    let basePrice = 50;
    if (product.size) {
        const sizeNum = parseFloat(product.size.split('/')[0]) || 0;
        basePrice += sizeNum * 2;
    }
    if (product.load_index) {
        basePrice += parseInt(product.load_index) || 0;
    }
    return basePrice.toFixed(2);
}

// Loading and error handling
function showBuffering(message = "Loading...") {
    productsContainer.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>${message}</p>
        </div>
    `;
}

function hideBuffer() {
    const spinner = document.querySelector('.loading-spinner');
    if (spinner) spinner.remove();
}

function showError(message) {
    productsContainer.innerHTML = `
        <div class="error-message">
            <p>${message}</p>
            <button onclick="window.location.reload()">Try Again</button>
        </div>
    `;
}

function toggleLoadMoreButton() {
    let loadMoreBtn = document.getElementById('load-more-btn');
    if (!loadMoreBtn) {
        loadMoreBtn = document.createElement('button');
        loadMoreBtn.id = 'load-more-btn';
        loadMoreBtn.textContent = 'Load More';
        loadMoreBtn.onclick = handleLoadMore;
        productsContainer.appendChild(loadMoreBtn);
    }
    
    const pageSize = isLoggedIn ? LOGGED_IN_PAGE_SIZE : GUEST_PAGE_SIZE;
    const hasMore = allProducts.length >= pageSize * currentPage;
    loadMoreBtn.style.display = hasMore ? 'block' : 'none';
}

function handleLoadMore() {
    if (!isLoggedIn) {
        showLoginPrompt();
        return;
    }
    currentPage++;
    getFilteredProducts();
}

// Favorite functionality
async function addToFavorites(event) {
    const button = event.currentTarget;
    const tyreId = button.getAttribute('data-tyre-id');

    if (!isLoggedIn) {
        showTempMessage('Please log in to add favorites', 'error');
        return;
    }

    try {
        const response = await fetch(baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({ 
                type: 'addFavourite',
                tyre_id: parseInt(tyreId)
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            showTempMessage('Added to Favorites!', 'success');
            updateFavoritesBadge();
        } else {
            throw new Error(result.message || 'Failed to add favorite');
        }
    } catch (error) {
        showTempMessage(error.message, 'error');
    }
}

async function updateFavoritesBadge() {
    try {
        const response = await fetch(baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                type: 'getFavourites'
            })
        });
        
        const result = await response.json();
        const favBadge = document.getElementById('favorites-badge');
        
        if (favBadge && result.data) {
            favBadge.textContent = result.data.length;
            favBadge.style.display = result.data.length > 0 ? 'block' : 'none';
        }
    } catch (error) {
        console.error('Error updating favorites badge:', error);
    }
}

function showTempMessage(message, type) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `temp-message ${type}`;
    msgDiv.textContent = message;
    document.body.appendChild(msgDiv);

    setTimeout(() => {
        msgDiv.remove();
    }, 3000);
}

function showLoginPrompt() {
    showTempMessage('Please log in to view more products', 'info');
}