// === CONFIG ===
var API_BASE_URL = "../GOTapi.php";

// apiKey is retrieved directly in getApiKey() for freshness
// var apiKey = sessionStorage.getItem('userApiKey'); // <-- This line is not needed here if getApiKey() retrieves it directly

function getCurrentUserId() {
    var userId = sessionStorage.getItem("user_id");
    console.log("DEBUG(JS - Favourites): getCurrentUserId() retrieved from sessionStorage:", userId);
    return userId ? parseInt(userId) : null;
}

function getApiKey() {
    var key = sessionStorage.getItem('userApiKey'); // Always get fresh from session storage
    console.log("DEBUG(JS - Favourites): getApiKey() retrieved from sessionStorage:", key);
    if (!key) {
        alert("Missing API key. Please log in again.");
    }
    return key;
}

// Remove a tyre from favourites
function removeFavourite(tyreId) {
    var userId = getCurrentUserId();
    var apiKey = getApiKey();
    console.log(`DEBUG(JS - Favourites): Attempting to remove favourite. User ID: ${userId}, Tyre ID: ${tyreId}, API Key: ${apiKey ? 'Present' : 'Missing'}`);

    if (!userId || !apiKey) {
        alert("User not logged in or API key missing. Cannot remove favourite.");
        return;
    }

    fetch(API_BASE_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            type: "removeFavourite",
            user_id: userId,
            tyre_id: tyreId,
            api_key: apiKey
        })
    })
    .then(res => {
        console.log("DEBUG(JS - Favourites): removeFavourite fetch response status:", res.status, res.statusText);
        return res.json();
    })
    .then(result => {
        console.log("DEBUG(JS - Favourites): removeFavourite API response:", result);
        if (result.status === "success" || result.message?.includes("removed")) {
            alert("Tyre removed from favourites.");
            location.reload();
        } else {
            alert("Error removing favourite: " + result.message);
        }
    })
    .catch(err => {
        console.error("DEBUG(JS - Favourites): Error removing favourite:", err);
        alert("Network error or server problem when removing favourite.");
    });
}

function addFavourite(tyreId, buttonElement) {
    var userId = getCurrentUserId();
    var apiKey = getApiKey();
    console.log(`DEBUG(JS - Favourites): Attempting to add favourite. User ID: ${userId}, Tyre ID: ${tyreId}, API Key: ${apiKey ? 'Present' : 'Missing'}`);

    if (!userId || !apiKey) {
        alert("User not logged in or API key missing. Cannot add favourite.");
        return;
    }

    fetch(API_BASE_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            type: "addFavourite",
            user_id: userId,
            tyre_id: tyreId,
            api_key: apiKey
        })
    })
    .then(res => {
        console.log("DEBUG(JS - Favourites): addFavourite fetch response status:", res.status, res.statusText);
        return res.json();
    })
    .then(data => {
        console.log("DEBUG(JS - Favourites): addFavourite API response:", data);
        if (data.status === "success") {
            alert("Added to favourites!");
            buttonElement.classList.add("favourited");
            buttonElement.disabled = true;
        } else {
            alert("Failed to add favourite: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        console.error("DEBUG(JS - Favourites): Error adding favourite:", err);
        alert("Network error or server problem when adding favourite.");
    });
}

// Fetch and display the user's favourite tyres
document.addEventListener("DOMContentLoaded", () => {
    console.log("DEBUG(JS - Favourites): DOMContentLoaded fired on Favourites page.");

    var userId = getCurrentUserId();
    var apiKey = getApiKey();
    var tbody = document.getElementById("favouritesBody");

    console.log("DEBUG(JS - Favourites): Initial checks for favourites display - userId:", userId, "apiKey:", apiKey, "tbody exists:", !!tbody);

    if (!userId || !apiKey || !tbody) {
        console.log("DEBUG(JS - Favourites): Skipping favourites fetch. Reason: Missing userId or apiKey, or tbody element not found.");
        let errorMessage = "Please log in to view your favourites.";
        if (!tbody) errorMessage = "Error: Favourites table body not found.";
        else if (!userId) errorMessage = "User ID missing. Please log in.";
        else if (!apiKey) errorMessage = "API Key missing. Please log in.";

        if (tbody) { // Only set innerHTML if tbody actually exists
            tbody.innerHTML = `<tr><td colspan="8">${errorMessage}</td></tr>`;
        } else {
            console.error("DEBUG(JS - Favourites): Cannot display message, tbody element is null.");
        }
        return;
    }

    console.log("DEBUG(JS - Favourites): Sending fetch request for getFavourites with userId:", userId, "and API Key (present).");
    fetch(API_BASE_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            type: "getFavourites",
            user_id: userId,
            api_key: apiKey
        })
    })
    .then(res => {
        console.log("DEBUG(JS - Favourites): getFavourites fetch response status:", res.status, res.statusText);
        if (!res.ok) {
            return res.json().then(errorData => {
                console.error("DEBUG(JS - Favourites): getFavourites API returned error JSON:", errorData);
                throw new Error(errorData.message || 'Failed to fetch favourites due to server error.');
            }).catch(() => {
                console.error("DEBUG(JS - Favourites): getFavourites API returned non-JSON error (check PHP logs for raw output).");
                throw new Error('An unexpected error occurred while fetching favourites.');
            });
        }
        return res.json();
    })
    .then(data => {
        console.log("DEBUG(JS - Favourites): getFavourites API success response data:", data);

        if (!data || !data.favourites || data.favourites.length === 0) {
            console.log("DEBUG(JS - Favourites): No favourite tyres found or 'favourites' array is empty/missing in response.");
            tbody.innerHTML = `<tr><td colspan="8">No favourite tyres found.</td></tr>`;
            return;
        }

        console.log("DEBUG(JS - Favourites): Successfully fetched favourite tyres array. Count:", data.favourites.length);

        tbody.innerHTML = ""; // Clear existing content
        data.favourites.forEach(item => {
            console.log("DEBUG(JS - Favourites): Rendering favourite item:", item);
            var row = document.createElement("tr");
            row.innerHTML = `
                <td><img src="${item.img_url}" alt="Tyre Image" class="tyre-img" /></td>
                <td>${item.tyre_id}</td>
                <td>${item.size}</td>
                <td>${item.load_index}</td>
                <td>${item.has_tube == 1 ? "Yes" : "No"}</td>
                <td>${item.serial_num}</td>
                <td>${item.selling_price} ZAR</td>
                <td><button onclick="removeFavourite(${item.tyre_id})">Remove</button></td>
            `;
            tbody.appendChild(row);
        });
    })
    .catch(err => {
        console.error("DEBUG(JS - Favourites): Error fetching favourites in .catch block:", err);
        tbody.innerHTML = `<tr><td colspan="8">Error loading favourites: ${err.message}. Please try again later.</td></tr>`;
    });

    // Add favourite button listeners 
    document.querySelectorAll(".favourite-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            var tyreId = parseInt(btn.dataset.tyreId);
            if (isNaN(tyreId)) { 
                alert("Tyre ID not found for favouriting.");
                return;
            }
            addFavourite(tyreId, btn);
        });
    });
});