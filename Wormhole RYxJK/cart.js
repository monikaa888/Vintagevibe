function getCart() {
    let cart = localStorage.getItem('cart');
    return cart ? JSON.parse(cart) : [];
}

function saveCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
}

function updateCartCount() {
    let cart = getCart();
    let totalQuantity = 0;
    cart.forEach(item => {
        totalQuantity += item.quantity;
    });
    let cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = totalQuantity;
    }
}

function showModal(message, redirectUrl) {
    let modal = document.createElement('div');
    modal.id = 'custom-modal';
    modal.style.position = 'fixed';
    modal.style.top = '50%';
    modal.style.left = '50%';
    modal.style.transform = 'translate(-50%, -50%)';
    modal.style.backgroundColor = '#fff';
    modal.style.border = '1px solid #ddd';
    modal.style.borderRadius = '8px';
    modal.style.padding = '20px';
    modal.style.zIndex = '1000';
    modal.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
    modal.style.width = '300px';
    modal.style.textAlign = 'center';
    modal.style.fontFamily = 'Arial, sans-serif';

    let msgElement = document.createElement('p');
    msgElement.textContent = message;
    msgElement.style.fontSize = '16px';
    msgElement.style.color = '#333';

    let okButton = document.createElement('button');
    okButton.textContent = 'OK';
    okButton.style.backgroundColor = '#007bff';
    okButton.style.color = '#fff';
    okButton.style.border = 'none';
    okButton.style.borderRadius = '5px';
    okButton.style.padding = '10px 20px';
    okButton.style.marginTop = '10px';
    okButton.style.cursor = 'pointer';
    okButton.style.fontSize = '16px';
    okButton.addEventListener('click', () => {
        modal.remove();
        if (redirectUrl) {
            window.location.href = redirectUrl;
        }
    });

    let cancelButton = document.createElement('button');
    cancelButton.textContent = 'Cancel';
    cancelButton.style.backgroundColor = '#ccc';
    cancelButton.style.color = '#333';
    cancelButton.style.border = 'none';
    cancelButton.style.borderRadius = '5px';
    cancelButton.style.padding = '10px 20px';
    cancelButton.style.marginTop = '10px';
    cancelButton.style.marginLeft = '10px';
    cancelButton.style.cursor = 'pointer';
    cancelButton.style.fontSize = '16px';
    cancelButton.addEventListener('click', () => {
        modal.remove();
    });

    modal.appendChild(msgElement);
    modal.appendChild(okButton);
    modal.appendChild(cancelButton);

    document.body.appendChild(modal);
}

function showLoginPrompt() {
    showModal('You need to log in to add items to your cart.', 'signinup/login.php');
}

function showSuccessDialog(itemName) {
    showModal(`${itemName} has been added to your cart.`);
}

function checkUserLoginStatus() {
    fetch('signinup/get_user_info.php')  
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('user-welcome').textContent = 'Welcome, Guest!';
                document.getElementById('logout-button').style.display = 'none';
            } else {
                document.getElementById('user-welcome').textContent = `Welcome, ${data.first_name}!`;
                document.getElementById('logout-button').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error fetching user info:', error);
            document.getElementById('user-welcome').textContent = 'Welcome, Guest!';
            document.getElementById('logout-button').style.display = 'none';
        });
}

function addToCart(productId, productName, productPrice) {
    fetch('signinup/get_user_info.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showLoginPrompt();
                return;
            }

            let cart = getCart();
            let item = cart.find(i => i.id === productId);

            if (item) {
                item.quantity += 1;
            } else {
                cart.push({ id: productId, name: productName, price: productPrice, quantity: 1 });
            }

            saveCart(cart);
            showSuccessDialog(productName);
            loadCartItems();
        })
        .catch(error => console.error('Error:', error));
}

function addPackageToCart(packageId, packageName, packagePrice) {
    fetch('signinup/get_user_info.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showLoginPrompt();
                return;
            }

            let cart = getCart();
            let item = cart.find(i => i.id === packageId);

            if (item) {
                item.quantity += 1;
            } else {
                cart.push({ id: packageId, name: packageName, price: packagePrice, quantity: 1 });
            }

            saveCart(cart);
            showSuccessDialog(packageName);
            loadCartItems();
        })
        .catch(error => console.error('Error:', error));
}

function removeFromCart(itemId) {
    let cart = getCart();
    cart = cart.filter(item => item.id !== itemId);
    saveCart(cart);
    loadCartItems();
}

function loadCartItems() {
    let cart = getCart();
    let cartContainer = document.getElementById('cart-items');
    let total = 0;

    if (cartContainer) {
        cartContainer.innerHTML = '';
        cart.forEach(item => {
            let itemDiv = document.createElement('div');
            itemDiv.className = 'cart-item';
            itemDiv.innerHTML = `
                <span class="cart-item-name">${item.name}</span> 
                <span class="cart-item-price">Rs ${item.price}</span> 
                <span class="cart-item-quantity">Quantity: ${item.quantity}</span>
                <button onclick="removeFromCart('${item.id}')" class="remove-button">Remove</button>
            `;
            cartContainer.appendChild(itemDiv);
            total += item.price * item.quantity;
        });
        let cartTotalElement = document.getElementById('cart-total');
        if (cartTotalElement) {
            cartTotalElement.textContent = `Rs ${total}`;
        }
    }
}

function clearCart() {
    localStorage.removeItem('cart');
    showModal('Your cart has been cleared.');
    updateCartCount();
    loadCartItems();
}

function isCartEmpty() {
    let cart = getCart();
    return cart.length === 0;
}

function handleCheckout(event) {
    if (isCartEmpty()) {
        event.preventDefault();
        showModal('Your cart is empty. Please add items to your cart before proceeding to checkout.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateCartCount();
    checkUserLoginStatus();

    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            let productId = button.getAttribute('data-product-id');
            let productName = button.closest('.slide').querySelector('h3').innerText;
            let productPrice = parseFloat(button.closest('.slide').querySelector('.price').innerText.replace('Rs ', '').replace(/,/g, ''));
            addToCart(productId, productName, productPrice);
        });
    });

    document.querySelectorAll('.book-now').forEach(button => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            let packageId = button.getAttribute('data-package-id');
            let packageName = button.closest('.box').querySelector('h3').innerText;
            let packagePrice = parseFloat(button.closest('.box').querySelector('.price').innerText.replace('Rs ', '').replace(/,/g, ''));
            addPackageToCart(packageId, packageName, packagePrice);
        });
    });

    let clearCartButton = document.getElementById('clear-cart');
    if (clearCartButton) {
        clearCartButton.addEventListener('click', clearCart);
    }

    let checkoutButton = document.querySelector('.checkout');
    if (checkoutButton) {
        checkoutButton.addEventListener('click', handleCheckout);
    }

    if (document.getElementById('cart-items')) {
        loadCartItems();
    }
});
