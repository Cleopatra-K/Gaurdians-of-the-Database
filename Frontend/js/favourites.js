// === CONFIG ===
var API_BASE_URL = "../GOTapi.php";

var apiKey = sessionStorage.getItem('userApiKey');

function getCurrentUserId() {
  var userId = sessionStorage.getItem("user_id");
  return userId ? parseInt(userId) : null;
}

function getApiKey() {
  var key = apiKey
  if (!key) {
    alert("Missing API key. Please log in again.");
  }
  return key;
}

// Remove a tyre from favourites
function removeFavourite(tyreId) {
  var userId = getCurrentUserId();
  var apiKey = getApiKey();
  if (!userId || !apiKey) {
    alert("User not logged in.");
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
    .then(res => res.json())
    .then(result => {
      if (result.status === "success" || result.message?.includes("removed")) {
        alert("Tyre removed from favourites.");
        location.reload();
      } else {
        alert("Error removing favourite: " + result.message);
      }
    })
    .catch(err => {
      console.error("Error:", err);
    });
}

function addFavourite(tyreId, buttonElement) {
  var userId = getCurrentUserId();
  var apiKey = getApiKey();
  if (!userId || !apiKey) {
    alert("User not logged in.");
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
    .then(res => res.json())
    .then(data => {
      if (data.status === "success") {
        alert("Added to favourites!");
        buttonElement.classList.add("favourited");
        buttonElement.disabled = true;
      } else {
        alert("Failed to add favourite: " + (data.message || "Unknown error"));
      }
    })
    .catch(err => {
      console.error("Error adding favourite:", err);
      alert("Network error or server problem.");
    });
}

// Fetch and display the user's favourite tyres
document.addEventListener("DOMContentLoaded", () => {
  var userId = getCurrentUserId();
  var apiKey = getApiKey();
  var tbody = document.getElementById("favouritesBody");
  if (!userId || !apiKey || !tbody) return;

  fetch(API_BASE_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      type: "getFavourites",
      user_id: userId,
      api_key: apiKey
    })
  })
    .then(res => res.json())
    .then(data => {
if (!data || !data.favourites || data.favourites.length === 0) { // Changed data.data to data.favourites        tbody.innerHTML = `<tr><td colspan="8">No favourite tyres found.</td></tr>`;
        return;
      }

      console.log("Fetched favourites:", data.data);


      tbody.innerHTML = "";
      data.data.forEach(item => {
          console.log("Rendering item:", item); // Add this
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
      console.error("Error fetching favourites:", err);
    });

  // Add favourite button listeners (if applicable on this page)
  document.querySelectorAll(".favourite-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      var tyreId = parseInt(btn.dataset.tyreId);
      if (!tyreId) {
        alert("Tyre ID not found.");
        return;
      }
      addFavourite(tyreId, btn);
    });
  });
});

