    // JavaScript to handle the "Select Product" functionality
    const selectProductBtn = document.getElementById('select-product-btn');
let isSelecting = false; // Tracks the state of selection

selectProductBtn.addEventListener('click', function () {
    const productCards = document.querySelectorAll('.product-card');

    if (!isSelecting) {
        // Change button label to "Cancel"
        selectProductBtn.textContent = 'Cancel';

        // Show checkboxes
        productCards.forEach(card => {
            if (!card.querySelector('.checkbox-container')) {
                const checkboxContainer = document.createElement('div');
                checkboxContainer.classList.add('checkbox-container');

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';

                checkboxContainer.appendChild(checkbox);
                card.prepend(checkboxContainer);
            }
        });
    } else {
        // Change button label back to "Select Product"
        selectProductBtn.textContent = 'Select Product';

        // Hide checkboxes
        productCards.forEach(card => {
            const checkboxContainer = card.querySelector('.checkbox-container');
            if (checkboxContainer) {
                checkboxContainer.remove();
            }
        });
    }

    // Toggle the state
    isSelecting = !isSelecting;
});
;
	



	// Modal functionality
    const modal = document.getElementById('product-modal');
    const addButton = document.querySelector('.add-button');
    const closeModal = document.getElementById('close-modal');
    const productForm = document.getElementById('product-form');

    addButton.addEventListener('click', () => {
      modal.style.display = 'flex';
    });

    closeModal.addEventListener('click', () => {
      modal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    });

	// Event listener to update product code when category is selected
document.getElementById('category-code').addEventListener('change', function () {
  const categoryCode = this.value;  // Get the selected category
  const productCodeField = document.getElementById('product-code');

  if (categoryCode) {
    // Get the first letter of the selected category and append it with a dash
    const firstLetter = categoryCode.charAt(0).toUpperCase();
    productCodeField.value = firstLetter + '-';  // Set the product code field
  } else {
    productCodeField.value = '';  // Clear the product code if no category is selected
  }
});

	
    // Form submission handling
    productForm.addEventListener('submit', (e) => {
      e.preventDefault();
      // Capture form data here and handle it (e.g., save to database or API)
      const newProduct = {
        productCode: document.getElementById('product-code').value,
        productName: document.getElementById('product-name').value,
        minutesPreparation: document.getElementById('minutes-preparation').value,
        price: document.getElementById('price').value,
        categoryCode: document.getElementById('category-code').value,
      };
      console.log('New Product:', newProduct);
      alert('Product added successfully!');
      modal.style.display = 'none';
      productForm.reset();
    });
	
	document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('product-modal'); // Add New Category modal
  const closeModalForm = document.getElementById('close-modal-form'); // Close button

  if (modal && closeModalForm) {
    closeModalForm.addEventListener('click', () => {
      console.log('Close button clicked'); // Debug message
      modal.style.display = 'none'; // Hide the modal
    });
  } else {
    console.error('Modal or Close button not found');
  }
});


	
	// Add click event listeners to product cards
	const productCards = document.querySelectorAll('.product-card');
	const viewModal = document.getElementById('view-product-modal');
	const closeViewModal = document.getElementById('close-view-modal');
	const closeViewModalBtn = document.getElementById('close-view-modal-btn');

	productCards.forEach(card => {
	  card.addEventListener('click', () => {
		// Populate modal with product details
		const productName = card.querySelector('.product-name').textContent;
		const productPrice = card.querySelector('.product-price').textContent;
		const productImage = card.querySelector('img').src;

		document.getElementById('product-image').src = productImage;
		document.getElementById('product-name-detail').textContent = productName;
		document.getElementById('price-detail').textContent = productPrice;

		// Show the modal
		viewModal.style.display = 'flex';
	  });
	});

	// Close the modal
	[closeViewModal, closeViewModalBtn].forEach(btn =>
	  btn.addEventListener('click', () => {
		viewModal.style.display = 'none';
	  })
	);

	window.addEventListener('click', (e) => {
	  if (e.target === viewModal) {
		viewModal.style.display = 'none';
	  }
	});




  document.getElementById('edit-product-btn').addEventListener('click', function () {
    const editBtn = this;
    const uploadIcon = document.getElementById('upload-icon');
    const productNameSpan = document.getElementById('product-name-detail');
    const minutesPreparationSpan = document.getElementById('minutes-preparation-detail');
    const priceSpan = document.getElementById('price-detail');

    if (editBtn.textContent === 'Edit') {
        editBtn.textContent = 'Save';

        // Show upload icon
        uploadIcon.style.display = 'flex';

        // Replace spans with input fields for editable fields only
        productNameSpan.innerHTML = `<input type="text" value="${productNameSpan.textContent}">`;

        // Do not replace productStatusSpan, categoryCodeSpan, and productCodeSpan to keep them static
    } else {
        editBtn.textContent = 'Edit';

        // Hide upload icon
        uploadIcon.style.display = 'none';

        // Save updated values and replace input fields with spans for editable fields only
        productNameSpan.textContent = productNameSpan.querySelector('input').value;

        // No changes for productStatusSpan, categoryCodeSpan, and productCodeSpan as they remain static
    }
});

const statusButton = document.getElementById('status-product-btn');
const productStatusSpan = document.getElementById('product-status-detail');

// Function to update the status button based on product status
function updateStatusButton() {
  const currentStatus = productStatusSpan.textContent.trim();

  if (currentStatus === 'Available') {
    statusButton.textContent = 'Disable';
    statusButton.classList.remove('enable');
    statusButton.classList.add('disable');
  } else if (currentStatus === 'Not Available') {
    statusButton.textContent = 'Enable';
    statusButton.classList.remove('disable');
    statusButton.classList.add('enable');
  }
}

// Event listener for status button click
statusButton.addEventListener('click', () => {
  const currentStatus = productStatusSpan.textContent.trim();

  if (currentStatus === 'Available') {
    productStatusSpan.textContent = 'Not Available';
  } else if (currentStatus === 'Not Available') {
    productStatusSpan.textContent = 'Available';
  }

  // Update button state and style
  updateStatusButton();
});

// Initialize button state on load
updateStatusButton();
