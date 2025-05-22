
function filterAndSortProducts(filters = {}, sortBy = "") {
    fetchProducts((products) => {
        let filteredProducts = products;
        if (filters.brand) {
            filteredProducts = filteredProducts.filter(product =>
                product.brand && product.brand.toLowerCase() === filters.brand.toLowerCase()
            );
        }
        if (filters.category) {
            filteredProducts = filteredProducts.filter(product => {
                try {
                    const parsedCategories = JSON.parse(product.categories);
                    return Array.isArray(parsedCategories) && parsedCategories
                        .map(cat => cat.toLowerCase())
                        .includes(filters.category.toLowerCase());
                } catch {
                    return false;
                }
            });
        }
        if (filters.distributor) {
            filteredProducts = filteredProducts.filter(product =>
                product.distributor && product.distributor.toLowerCase() === filters.distributor.toLowerCase()
            );
        }
        if (filters.availability !== undefined) {
            filteredProducts = filteredProducts.filter(product =>
                product.availability === filters.availability
            );
        }
        if (sortBy === "title-desc") {
            filteredProducts.sort((a, b) => b.title.localeCompare(a.title));
        }
        populateProducts(filteredProducts);
    });
}

// below is added by Neo 
function fetchProducts(callback) {
    fetch('../php/GOTapi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            type: 'GetAllProducts',
            api_key: apiKey
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            callback(data.products);
        } else {
            console.error("API error:", data.message);
        }
    })
    .catch(error => console.error("Fetch failed:", error));
}

function populateProducts(products) {
    const container = document.getElementById('product-container');
    container.innerHTML = ""; // Clear previous content

    products.forEach(product => {
        const div = document.createElement('div');
        div.className = "product";
        div.setAttribute("data-category", product.has_tube == 1 ? "bike" : "accessory");
        div.setAttribute("data-price", product.selling_price);

        div.innerHTML = `
            <a href="view.php?id=${product.tyre_id}">
                <img src="../${product.img_url}" alt="${product.serial_num}">
            </a>
            <h2>${product.serial_num}</h2>
            <p>Price: R${parseFloat(product.selling_price).toLocaleString()}</p>
            <button>Add to Cart</button>
            <button class="wishlist-btn"><span class="heart-icon">&#9825;</span> Wishlist</button>
        `;

        container.appendChild(div);
    });
}

// Initial population
fetchProducts(populateProducts);
