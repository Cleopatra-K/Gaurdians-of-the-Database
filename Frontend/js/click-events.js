(function () {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "GOTapi.php", true);
    xhr.setRequestHeader("Content-Type", "application/json");

    var apiKey = localStorage.getItem("apikey");

    if (!apiKey) {
        console.error("API key is missing. User might not be logged in.");
        document.getElementById("clickEventsBody").innerHTML =
            '<tr><td colspan="3">Missing API key. Please log in.</td></tr>';
        return;
    }

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.length > 0) {
                        renderClickEvents(response.data);
                    } else {
                        document.getElementById("clickEventsBody").innerHTML =
                            '<tr><td colspan="3">No click events found.</td></tr>';
                    }
                } catch (e) {
                    console.error("Failed to parse JSON:", e);
                    document.getElementById("clickEventsBody").innerHTML =
                        '<tr><td colspan="3">Invalid server response.</td></tr>';
                }
            } else {
                console.error("Server error:", xhr.status, xhr.responseText);
                document.getElementById("clickEventsBody").innerHTML =
                    '<tr><td colspan="3">Server error: ' + xhr.status + '</td></tr>';
            }
        }
    };

    xhr.send(JSON.stringify({
        type: "Click",
        apikey: apiKey
    }));



    function renderClickEvents(clickData) {
        var tbody = document.getElementById("clickEventsBody");

        const clicksPerUser = clickData.reduce((counts, click) => {
            if (click.customer_id) { 
            counts[click.customer_id] = (counts[click.customer_id] || 0) + 1;
            }
            return counts;
        }, {});

        let html = "";
        for (const click of clickData) {
            const totalClicks = click.customer_id ? clicksPerUser[click.customer_id] : 'N/A';

            html += "<tr>" +
            "<td>" + escapeHtml(click.click_id || 'N/A') + "</td>" +
            "<td>" + escapeHtml(click.tyre_id || 'N/A') + "</td>" +
            "<td>" + escapeHtml(totalClicks) + "</td>" +
            "</tr>";
        }

        tbody.innerHTML = html;
        }

        function escapeHtml(text) {
        if (!text) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
        }

})();
