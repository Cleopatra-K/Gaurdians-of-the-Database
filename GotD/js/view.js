document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('product-details-container');
    
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const tyreId = urlParams.get('tyre_id');
        
        if (!tyreId) throw new Error('Missing product ID in URL');

        container.innerHTML = '<div class="loading">Loading product details...</div>';
        
        const product = await loadProductDetails(tyreId);
        displayProductDetails(product);
        
    } catch (error) {
        console.error('Error:', error);
        container.innerHTML = `
            <div class="error-message">
                <h3>Error Loading Product</h3>
                <p>${error.message}</p>
                <a href="price_compare.php" class="btn">Return to Home</a>
                <button onclick="window.location.reload()" class="btn">Try Again</button>
            </div>
        `;
    }
});

async function loadProductDetails(tyreId) {
    const apiUrl = window.location.href.includes('localhost') 
        ? 'http://localhost/your-project/GOTapi(1).php'
        : 'GOTapi.php';

    const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            type: 'GetProductDetails',
            tyre_id: tyreId
        })
    });

    const text = await response.text();
    
    // Handle HTML error responses
    if (text.trim().startsWith('<')) {
        const errorMatch = text.match(/<b>(.*?)<\/b>/);
        throw new Error(errorMatch ? errorMatch[1] : 'Server returned HTML error');
    }

    const result = JSON.parse(text);
    
    if (result.status === 'error') {
        throw new Error(result.message);
    }

    return result.product;
}