document.addEventListener('DOMContentLoaded', function () {

    
    // Handle favorite button click
    document.querySelectorAll('.favorite-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const tyreId = form.dataset.tyreId;
            const action = form.querySelector('input[name="favorite_action"]').value;
            const endpointAction = action === 'add' ? 'addFavourite' : 'removeFavourite';

            try {
                const response = await fetch('../GOTapi.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: endpointAction,
                        api_key: USER_API_KEY,
                        tyre_id: tyreId
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    const button = form.querySelector('button');
                    const isFavorite = action === 'remove';

                    // Toggle button appearance
                    button.innerHTML = `<i class="fas fa-heart"></i> ${isFavorite ? 'Add to Favorites' : 'Remove Favorite'}`;
                    button.style.background = isFavorite ? 'transparent' : 'var(--secondary)';
                    button.style.color = isFavorite ? 'var(--secondary)' : 'white';

                    // Update form for next click
                    form.querySelector('input[name="favorite_action"]').value = isFavorite ? 'add' : 'remove';
                } else {
                    alert('Failed to update favorites: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating favorites');
            }
        });
    });

    // Handle star rating hover
    document.querySelectorAll('.star-rating-input label').forEach(star => {
        star.addEventListener('mouseover', (e) => {
            const stars = e.target.closest('.star-rating-input').querySelectorAll('label');
            const currentIndex = Array.from(stars).indexOf(e.target);

            stars.forEach((s, index) => {
                s.style.color = index <= currentIndex ? '#f39c12' : '#ddd';
            });
        });

        star.addEventListener('mouseout', (e) => {
            const stars = e.target.closest('.star-rating-input').querySelectorAll('label');
            const checked = e.target.closest('.star-rating-input').querySelector('input:checked');

            stars.forEach((s, index) => {
                s.style.color = (!checked || index >= Array.from(stars).indexOf(checked.nextElementSibling)) ? '#ddd' : '#f39c12';
            });

            if (checked && checked.nextElementSibling) {
                checked.nextElementSibling.style.color = '#f39c12';
            }
        });
    });

    // Update star colors when a rating is selected
    document.querySelectorAll('.star-rating-input input').forEach(input => {
        input.addEventListener('change', (e) => {
            const selectedValue = parseInt(e.target.value);
            const form = e.target.closest('.rating-form');
            const stars = form.querySelectorAll('.star-rating-input label');

            stars.forEach((label, index) => {
                const input = form.querySelector(`input#${label.getAttribute('for')}`);
                const val = parseInt(input.value);
                label.style.color = val <= selectedValue ? '#f39c12' : '#ddd';
            });
        });
    });


    // Handle rating form submission
    //have the option to submit a description, fix the css as well, and have more info about a product, because the tyre_id function returns alot of info
    const ratingForm = document.querySelector('.rating-form');
    if (ratingForm) {
        ratingForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const tyreId = ratingForm.dataset.tyreId;
            const rating = ratingForm.querySelector('input[name="rating"]:checked')?.value;

            if (!rating) {
                alert('Please select a rating before submitting.');
                return;
            }

            try {
                const TyreDescription = ratingForm.querySelector('textarea[name="description"]')?.value || null;

                const response = await fetch('../GOTapi.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'SubmitRating',
                        api_key: USER_API_KEY,
                        tyre_id: tyreId,
                        rating: parseInt(rating),
                        description: TyreDescription  // include this
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    window.location.reload(); // Refresh to update the visible rating
                } else {
                    alert('Failed to submit rating: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while submitting your rating');
            }
        });
    }

    const tyreId = document.querySelector('.some-element')?.dataset.tyreId;
    if (tyreId) {
        fetchTyreById(tyreId);
    }

    const tyreIdForReviews = document.querySelector('#tyre-reviews-container')?.dataset.tyreId;
    if (tyreIdForReviews) {
        fetchAllReviews(tyreIdForReviews);
    }


    async function fetchTyreById(tyreId) {
        try {
            const response = await fetch('../GOTapi.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'getTyreById',
                    tyre_id: tyreId
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                console.log('Tyre info:', result.product);
            } else {
                alert('Failed to fetch tyre: ' + result.message);
            }
        } catch (error) {
            alert('An error occurred while fetching tyre details');
        }
    }

    async function fetchAllReviews(tyreId) {
        try {
            const response = await fetch('../GOTapi.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'GetAllProductRatings',
                    tyre_id: tyreId
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                displayReviews(result.ratings || []);  // Changed from result.reviews to result.ratings
            } else {
                alert('Failed to fetch reviews: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Review fetch error:', error);
            alert('An error occurred while fetching reviews');
        }
    }

    function displayReviews(ratings) {  // Changed parameter name from reviews to ratings
        const container = document.getElementById('tyre-reviews-container');
        container.innerHTML = ''; // Clear existing content

        if (ratings.length === 0) {
            container.innerHTML = '<p>No reviews available for this tyre yet.</p>';
            return;
        }

        ratings.forEach(rating => {  // Changed from review to rating
            const reviewElement = document.createElement('div');
            reviewElement.classList.add('tyre-review');

            const stars = '★'.repeat(rating.rating) + '☆'.repeat(5 - rating.rating);
            const user = rating.username || 'Anonymous';  // Changed from review.user to rating.username

            reviewElement.innerHTML = `
                <div class="review-header">
                    <strong>${user}</strong> <span class="stars" style="color:#f39c12">${stars}</span>
                </div>
                <p class="review-description">${rating.description || ''}</p>
                <hr>
            `;

            container.appendChild(reviewElement);
        });
    }



});