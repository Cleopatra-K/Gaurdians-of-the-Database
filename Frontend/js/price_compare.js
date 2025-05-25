console.log("✅ Entered products.js");

const baseUrl = "../GOTapi.php";
const productsContainer = document.getElementById('products-container');
const filterGeneral = document.getElementById('filter-general');
const filterSize = document.getElementById('filter-size');
const filterLoadIndex = document.getElementById('filter-load-index');
const filterHasTube = document.getElementById('filter-has-tube');
const searchBar = document.querySelector('.search-bar');
const GUEST_PAGE_SIZE = 5;
const LOGGED_IN_PAGE_SIZE = 10;
let allProducts = [];
let isLoggedIn = false;
let currentPage = 1;

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

// Check login status using cookies
function checkLoggedInStatus() {
    // return document.cookie.split(';').some((item) => item.trim().startsWith('PHPSESSID='));
    const apiKey = localStorage.getItem('api_key') || getCookie('api_key');
    return !!apiKey;
}

function handleLoadMore() {
    if (!isLoggedIn) {
        showLoginPrompt();
        return;
    }
    currentPage++;
    getProd();
}

// Initialize the page
document.addEventListener('DOMContentLoaded', async () => {
    try {
        if (!productsContainer) {
            document.body.innerHTML = '<div style="color: red; text-align: center; margin-top: 50px;">Error: Product display area not found.</div>';
            return;
        }

        isLoggedIn = checkLoggedInStatus();
        getProd();
        setupEventListeners();
        if (isLoggedIn) {
            updateFavoritesBadge();
        }
    } catch (error) {
        showError(error.message);
    }
});

// Fetch products
async function getProd(params = {}) {
    showBuffering();
    
    try {
        // Build filter parameters
        const filterParams = {
            type: 'GetAllProducts',
            page: currentPage,
            page_size: isLoggedIn ? LOGGED_IN_PAGE_SIZE : GUEST_PAGE_SIZE
        };

        // Add filters if they exist
        if (params.brand) filterParams.brand = params.brand;
        if (params.category) filterParams.category = params.category;
        if (params.distributor) filterParams.distributor = params.distributor;
        if (params.size) filterParams.size = params.size;
        if (params.load_index) filterParams.load_index = params.load_index;
        if (params.has_tube) filterParams.has_tube = params.has_tube;
        if (params.sort) filterParams.sort = params.sort;
        if (params.order) filterParams.order = params.order;
        if (params.search) filterParams.search = params.search;
        if (params.fuzzy) filterParams.fuzzy = params.fuzzy;

        const response = await fetch(baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                type: 'GetAllProducts',
                ...params
            })
        });

        const result = await response.json();
        
        if (result.status === 'error') {
            showError(result.message);
            return;
        }

        allProducts = transformApiResponse(result.products);
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


function toggleLoadMoreButton() {
    const loadMoreBtn = document.getElementById('load-more-btn');
    
    if (!loadMoreBtn) {
        const btn = document.createElement('button');
        btn.id = 'load-more-btn';
        btn.className = 'load-more-btn';
        btn.textContent = 'Load More';
        btn.addEventListener('click', handleLoadMore);
        productsContainer.appendChild(btn);
        return;
    }

    const pageSize = isLoggedIn ? LOGGED_IN_PAGE_SIZE : GUEST_PAGE_SIZE;
    const hasMore = allProducts.length > pageSize * currentPage;
    loadMoreBtn.style.display = hasMore ? 'block' : 'none';
}

// Transform API response
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
                brand: product.brand,
                category: product.category,
                distributor: product.distributor,
                listings: []
            };
        }
        
        const listing = {
            listing_id: product.tyre_id,
            tyre_id: product.tyre_id,
            name: product.seller_name || 'Unknown',
            role: product.seller_role || 'Unknown',
            user_id: product.user_id || null,
            original_price: product.original_price || null,
            selling_price: product.selling_price || calculateMockPrice(product),
            serial_num: product.serial_num,
            img_url: product.img_url || '../img/construction.png',
            brand: product.brand,
            category: product.category,
            distributor: product.distributor
        };
        
        productGroups[groupKey].listings.push(listing);
    });
    
    return Object.values(productGroups).map(group => {
        group.listings.sort((a, b) => a.selling_price - b.selling_price);
        return group;
    });
}

function displayProds(productGroups) {
    if (!productsContainer) {
        console.error("productsContainer is null in displayProds");
        return;
    }
    
    // Clear existing products but keep the load more button
    const loadMoreBtn = document.getElementById('load-more-btn');
    productsContainer.innerHTML = '';
    if (loadMoreBtn) {
        productsContainer.appendChild(loadMoreBtn);
    }

    if (!productGroups || productGroups.length === 0) {
        productsContainer.innerHTML = '<p>No matching products found.</p>';
        return;
    }

    productGroups.forEach(group => {
        // Create a Set to track unique listings
        const uniqueListings = [];
        const seenListings = new Set();

        // Process listings to remove exact duplicates
        group.listings.forEach(listing => {
            if (!listing.name) return; // Skip if no seller name
            
            // Create a unique key for each listing combination
            const listingKey = `${listing.name.toLowerCase().trim()}_${group.size}_${listing.selling_price}`;
            
            if (!seenListings.has(listingKey)) {
                seenListings.add(listingKey);
                uniqueListings.push(listing);
            }
        });

        const productCard = document.createElement('div');
        productCard.classList.add('product-card');

        productCard.innerHTML = `
            <div class="product-header">
                <div class="product-image-container">
                    <img src="${uniqueListings[0]?.img_url || '../img/construction.png'}" 
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
                    ${uniqueListings.map((listing, index) => `
                        <div class="seller-row">
                            <div class="seller-info">
                                <span class="seller-name ${getSellerDotColor(index)}">
                                    ${listing.name || 'Unknown Seller'}
                                </span>
                                <div class="price-container">
                                    <span class="current-price">$${parseFloat(listing.selling_price).toFixed(2)}</span>
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

// Add to favorites with redirect
async function addToFavorites(event) {
    const button = event.target;
    const tyreId = button.getAttribute('data-tyre-id');

    if (!isLoggedIn) {
        showTempMessage('Please log in to add favorites', 'error');
        return;
    }

    showBuffering("Adding to favorites...");

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
            
            // Redirect after 6 seconds
            showBuffering("Redirecting to favorites...");
            setTimeout(() => {
                window.location.href = 'favorites.php';
            }, 6000);
        } else if (result.message && result.message.includes("already in user's favourites")) {
            showTempMessage('This item is already in your favorites!', 'info');
        } else {
            throw new Error(result.message || 'Failed to add favorite');
        }
    } catch (error) {
        showTempMessage(error.message, 'error');
    } finally {
        hideBuffer();
    }
}

// Update favorites badge count
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

// Setup event listeners
function setupEventListeners() {
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('add-to-favorites')) {
            addToFavorites(e);
        }
    });

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
            }
            currentPage = 1;
            getProd(params);
        });
    }

    if (filterSize) {
        filterSize.addEventListener('change', (e) => {
            const size = e.target.value;
            currentPage = 1;
            getProd({ size: size || undefined });
        });
    }

    if (filterLoadIndex) {
        filterLoadIndex.addEventListener('change', (e) => {
            const loadIndex = e.target.value;
            currentPage = 1;
            getProd({ load_index: loadIndex !== '' ? parseInt(loadIndex) : undefined });
        });
    }

    if (filterHasTube) {
        filterHasTube.addEventListener('change', (e) => {
            const hasTube = e.target.value;
            currentPage = 1;
            getProd({ has_tube: hasTube !== '' ? parseInt(hasTube) : undefined });
        });
    }

    if (searchBar) {
        searchBar.addEventListener('input', debounce((e) => {
            const query = e.target.value.trim();
            currentPage = 1;
            if (query.length > 2) {
                getProd({
                    search: { size: query },
                    fuzzy: true
                });
            } else if (query.length === 0) {
                getProd();
            }
        }, 300));
    }

    const filterBrand = document.getElementById('filter-brand');
    const filterCategory = document.getElementById('filter-category');
    const filterDistributor = document.getElementById('filter-distributor');

    if (filterBrand) {
        filterBrand.addEventListener('change', (e) => {
            const brand = e.target.value;
            currentPage = 1;
            getProd({ brand: brand || undefined });
        });
    }

    if (filterCategory) {
        filterCategory.addEventListener('change', (e) => {
            const category = e.target.value;
            currentPage = 1;
            getProd({ category: category || undefined });
        });
    }

    if (filterDistributor) {
        filterDistributor.addEventListener('change', (e) => {
            const distributor = e.target.value;
            currentPage = 1;
            getProd({ distributor: distributor || undefined });
        });
    }

    // Load filter options on page load
    loadFilterOptions();
}

async function loadFilterOptions() {
    try {
        const response = await fetch(baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                type: 'GetFilterOptions'
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            populateFilterOptions('filter-brand', result.data.brands || []);
            populateFilterOptions('filter-category', result.data.categories || []);
            populateFilterOptions('filter-distributor', result.data.distributors || []);
        }
    } catch (error) {
        console.error('Error loading filter options:', error);
    }
}

function populateFilterOptions(selectId, options) {
    const select = document.getElementById(selectId);
    if (!select) return;

    // Clear existing options except the first one
    while (select.options.length > 1) {
        select.remove(1);
    }

    // Add new options
    options.forEach(option => {
        const opt = document.createElement('option');
        opt.value = option.value || option;
        opt.textContent = option.label || option;
        select.appendChild(opt);
    });
}


// Helper function for seller dot color
function getSellerDotColor(index) {
    const colors = ['seller-dot-red', 'seller-dot-blue', 'seller-dot-green', 'seller-dot-yellow'];
    return colors[index % colors.length];
}

// Helper function to calculate a mock price
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

// Loading and error handling functions
function showBuffering(message = "Loading...") {
    if (productsContainer) {
        productsContainer.innerHTML = `
            <div class="loading-spinner" id="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p class="loading-text">${message}</p>
            </div>
        `;
    }
}

function hideBuffer() {
    const spin = document.getElementById('loading-spinner');
    if (spin) spin.remove();
}

function showError(message) {
    if (productsContainer) {
        productsContainer.innerHTML = `
            <div class="error-message">
                <h5>Error: ${message}</h5>
                <button onclick="window.location.reload()">Try Again</button>
            </div>
        `;
    }
}

function showTempMessage(message, type) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `temp-message ${type}`;
    msgDiv.textContent = message;
    msgDiv.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 2000;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: bold;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        background-color: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#e63946' : '#2196F3'};
        color: white;
        animation: slideIn 0.3s ease-out;
    `;

    document.body.appendChild(msgDiv);

    setTimeout(() => {
        msgDiv.style.animation = 'fadeOut 0.5s ease-out forwards';
        setTimeout(() => msgDiv.remove(), 500);
    }, 1500);
}

function debounce(func, timeout = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { func.apply(this, args); }, timeout);
    };
}