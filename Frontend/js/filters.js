
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

