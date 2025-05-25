// const API_BASE_URL = "GOTapi.php"; 
// const apiKey = localStorage.getItem("apiKey"); 

// document.addEventListener("DOMContentLoaded", () => {
//   if (!apiKey) {
//     alert("You must be logged in to view favourites.");
//     return;
//   }
//   loadFavourites();
// });

// function loadFavourites() {
//   fetch(`${API_BASE_URL}?type=getFavourites`, {
//     method: "GET",
//     headers: {
//       "Authorization": apiKey
//     }
//   })
//     .then(response => response.json())
//     .then(data => {
//       if (data.data.length === 0) {
//         document.getElementById("favourites-container").innerHTML = "<p>No favourites found.</p>";
//       } else {
//         renderFavourites(data.data);
//       }
//     })
//     .catch(error => {
//       console.error("Error loading favourites:", error);
//       alert("Failed to load favourites.");
//     });
// }

// function renderFavourites(favourites) {
//   const container = document.getElementById("favourites-container");
//   container.innerHTML = ""; // Clear any previous content

//   favourites.forEach(item => {
//     const card = document.createElement("div");
//     card.classList.add("favourite-card");

//     card.innerHTML = `
//       <img src="${item.img_url}" alt="Tyre Image" />
//       <div class="info">
//         <h3>Size: ${item.size}</h3>
//         <p>Load Index: ${item.load_index}</p>
//         <p>Tube: ${item.has_tube ? "Yes" : "No"}</p>
//         <p>Serial #: ${item.serial_num}</p>
//         <p>Original Price: R${item.original_price}</p>
//         <p>Selling Price: R${item.selling_price}</p>
//         <p>Rating: ${item.rating}/5</p>
//         <p>Seller: ${item.seller_username} (${item.seller_email})</p>
//         <button onclick="removeFromFavourites(${item.tyre_id})">Remove</button>
//       </div>
//     `;

//     container.appendChild(card);
//   });
// }

// function removeFromFavourites(tyreId) {
//   fetch(`${API_BASE_URL}?type=removeFavourite`, {
//     method: "POST",
//     headers: {
//       "Content-Type": "application/json",
//       "Authorization": apiKey
//     },
//     body: JSON.stringify({ tyre_id: tyreId })
//   })
//     .then(response => response.json())
//     .then(data => {
//       alert(data.message || "Tyre removed.");
//       loadFavourites();
//     })
//     .catch(error => {
//       console.error("Error removing favourite:", error);
//       alert("Failed to remove favourite.");
//     });
// }

// === CONFIG ===
const API_BASE_URL = "GOTapi.php";

function getCurrentUserId() {
  const userId = localStorage.getItem("user_id");
  return userId ? parseInt(userId) : null;
}

function getApiKey() {
  const key = localStorage.getItem("apikey");
  if (!key) {
    alert("Missing API key. Please log in again.");
  }
  return key;
}

// Remove a tyre from favourites
function removeFavourite(tyreId) {
  const userId = getCurrentUserId();
  const apiKey = getApiKey();
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
  const userId = getCurrentUserId();
  const apiKey = getApiKey();
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
  const userId = getCurrentUserId();
  const apiKey = getApiKey();
  const tbody = document.getElementById("favouritesBody");
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
      if (!data || !data.data || data.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8">No favourite tyres found.</td></tr>`;
        return;
      }

      tbody.innerHTML = "";
      data.data.forEach(item => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td><img src="${item.img_url}" alt="Tyre Image" class="tyre-img" /></td>
          <td>${item.tyre_id}</td>
          <td>${item.size}</td>
          <td>${item.load_index}</td>
          <td>${item.has_tube === "1" ? "Yes" : "No"}</td>
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
      const tyreId = parseInt(btn.dataset.tyreId);
      if (!tyreId) {
        alert("Tyre ID not found.");
        return;
      }
      addFavourite(tyreId, btn);
    });
  });
});

