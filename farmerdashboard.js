// Cart Logic
        let cart = JSON.parse(localStorage.getItem('agriLinkCart')) || [];
        const cartSidebar = document.getElementById('cartSidebar');
        const cartOverlay = document.getElementById('cartOverlay');
        const cartCountSpan = document.getElementById('cartCount');
        const cartItemsContainer = document.getElementById('cartItemsContainer');
        const cartTotalDisplay = document.getElementById('cartTotalDisplay');
let checkoutBtn = document.getElementById('checkoutBtn');
        // Toggle Cart
        function toggleCart() {
            const isClosed = cartSidebar.classList.contains('translate-x-full');
            if (isClosed) {
                cartSidebar.classList.remove('translate-x-full');
                cartOverlay.classList.remove('hidden');
            } else {
                cartSidebar.classList.add('translate-x-full');
                cartOverlay.classList.add('hidden');
            }
        }

        document.getElementById('cartToggleBtn').addEventListener('click', toggleCart);
        document.getElementById('closeCartBtn').addEventListener('click', toggleCart);
        cartOverlay.addEventListener('click', toggleCart);

        // Remove from Cart Function
        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        fetch('fetchUser.php')
        .then(response=>response.json())
        .then(data=>{ 
            document.getElementById('username').textContent =  `${data.first_name} ${data.last_name}`;
            console.log(data)
            console.log('User data fetched successfully:', data.user_id, data.role, data.last_name, data.first_name);
            if(data.role !== 'farmer') {
                alert('Access denied. Redirecting to homepage.');
                window.location.href = 'homepage.html';
                console.warn('Unauthorized access attempt by user with role:', data.role, 'User ID:', data.user_id);

            }
            console.log('User data fetched successfully:', data.user_id, data.role);
        })
        .catch(err=>{
            console.error('Error fetching user data:', err);
            alert('An error occurred. Redirecting to homepage.');
            // window.location.href = 'homepage.html';
        });

       function checkout() {
    if (cart.length === 0) {
        alert("Your cart is empty.");
        return;
    }

    const totalAmount = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    const orderData = {
        total_amount: totalAmount,
        order_status: "Pending",
        items: cart
    };

    fetch('submit_order_data.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(orderData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Order placed successfully!");

            cart = [];
            localStorage.removeItem("agriLinkCart");

            updateCartUI();
            toggleCart();
        } else {
            alert("Checkout failed: " + data.errors.join(", "));
        }
    })
    .catch(err => console.error("Checkout error:", err));
}
    checkoutBtn.addEventListener('click', checkout);

    // Remove from Cart Function
    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCartUI();
    }


        function updateCartUI() {
            localStorage.setItem('agriLinkCart', JSON.stringify(cart));
            if(cartCountSpan) cartCountSpan.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            let total = 0;
            if(cartItemsContainer) {
                cartItemsContainer.innerHTML = cart.map((item, index) => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    return `
                        <div class="flex justify-between items-center border-b pb-2">
                            <div>
                                <h4 class="font-bold text-sm">${item.name}</h4>
                                <p class="text-xs text-gray-500">${item.quantity} x ${item.price} XAF / ${item.unit}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-primary">${itemTotal.toLocaleString()} XAF</span>
                                <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-700 text-xs font-bold border border-red-200 bg-red-50 px-2 py-1 rounded">
                                    Remove
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');

                if (cart.length === 0) {
                    cartItemsContainer.innerHTML = '<p class="text-gray-500 text-center mt-10">Your cart is empty.</p>';
                }
            }

            if(cartTotalDisplay) cartTotalDisplay.textContent = total.toLocaleString() + ' XAF';
        }

        // Initial render
        updateCartUI();

        // Fetch and List Products
        function fetchProducts() {
            const grid = document.getElementById('productsGrid');
            if (!grid) return;

            grid.innerHTML = '<p class="text-gray-500 text-center col-span-3">Loading products...</p>';


            try {
            fetch('getuserlistings.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('totalProduce_listed').textContent = data.products.length + ' T';
                console.log('Total products listed:', data.products.length);

                console.log('Product data fetched:', data.products.length, 'products for user ID:', data.user_id);

                
                
                if (data.success && data.products.length > 0) {
                    grid.innerHTML = data.products.map(product => `
                     

                     <div class="space-y-4 " id="productsGrid">
                <div class="bg-white p-4 rounded-xl flex border border-gray-100 flex items-center gap-4">
                
                    <img src="${product.image_path}" alt="${product.product_name}" class="w-16 h-16 rounded-lg object-cover" alt="Maize">
                    

                    <div class="flex-grow">
                        <h4 class="font-bold text-primary">${product.category || 'Produce'}</h4>
                        <p class="text-[10px] text-gray-400"> ( ${product.quantity_available} ${product.unit}   )</p>
                        <p class="text-[10px] font-bold">Available From: ${product.harvest_date || 'N/A'}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-primary font-bold">$${product.price_per_unit}</p>
                        <p class="text-[8px] text-gray-400">10,000 XAF</p>
                    </div>
                    <div class="flex flex-col gap-1">
                        <button class="bg-primary text-white px-4 py-1.5 rounded text-[10px] font-bold"> TOTAL ${product.price_per_unit*product.quantity_available}</button>
                        <button class="text-[10px] font-bold text-gray-400">Edit Product</button>
                    </div>
                </div>


                    `).join('');
                    console.log('Products loaded:', data.user_id, data.products.length);
                } else {
                    grid.innerHTML = '<p class="text-gray-500 col-span-3 text-center">No products found.</p>';
                }
               
            })
        } catch (error) {
            console.error('Error loading products:', error);
            // .catch(error => console.error('Error loading products:', error));
        }
    }

        // Initialize product fetch
        fetchProducts();

        // Fetch and List Orders (Sales)
        function fetchSellerOrders() {
            const ordersGrid = document.getElementById('ordersGrid');
            if (!ordersGrid) return; // Exit if the element doesn't exist on the page

            // ordersGrid.innerHTML = '<p class="text-gray-500 text-center col-span-2">Loading orders...</p>';

            fetch('get_orders_madeTo_seller.php')
            .then(response => response.json())
            .then(data => {
                console.log("this is the data fetched: ", data)
                console.log("Orders fetch response:", data);

                console.log('Orders data fetched:', data.orders.length, 'orders for user ID:', data.user_id, data.orders);
                 document.getElementById('totalOrders').textContent = data.orders.length;
                if (data.success && data.orders.length > 0) {
                    ordersGrid.innerHTML = data.orders.map(item => {
                        const statusColor = item.order_status === 'Pending' ? 'bg-orange-100 text-orange-600' : 'bg-green-100 text-green-600';
                        return `
                        <div class="bg-white p-3 rounded-xl border border-gray-100 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                            <div class="w-10 h-10 bg-gray-200 rounded-full overflow-hidden shrink-0">
                                <img src="${item.image_path || 'uploads/default_product.png'}" alt="${item.product_name}" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-grow w-full">
                                <p class="text-xs font-bold">${item.buyer_name}</p>
                                <p class="text-[10px] text-gray-400">Produce: ${item.product_name}, Qty: ${item.quantity} ${item.unit}</p>
                                <p class="text-[10px] font-bold">Total: ${item.total_amount} XAF</p>
                                <p class="text-[10px] text-gray-400">Ordered on: ${new Date(item.order_date).toLocaleDateString()}</p>
                                <button class="text-[10px] text-blue-500 mt-1" id="viewDetails_${item.order_id}">View Details</button>

                                <div class="flex items-center gap-2 mt-2 hidden" id="Details_${item.order_id}"> 
                                <p class="text-[10px] text-gray-400">From ${item.first_name} ${item.last_name}</p>
                                <p class="text-[10px] text-gray-400">Price: ${item.agreed_price} XAF/${item.unit}</p>
                                    
                                </div>
                            </div>
                            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                                <button class="bg-gray-100 px-4 py-1 rounded text-[10px] font-bold">Invoice</button>
                                <button class="text-[10px] font-bold text-gray-400  py-1 rounded w-full bg-gray-100 sm:w-auto">confirm payment</button>
                                <span class="${statusColor} px-3 py-1 rounded text-[10px] font-bold uppercase">${item.order_status}</span>
                            </div>
                        </div>

                    `
                    
                }).join('');
                data.orders.forEach(item => {
                    const btn = document.getElementById(`viewDetails_${item.order_id}`);
                    const detailsDiv = document.getElementById(`Details_${item.order_id}`);
                    if(btn && detailsDiv) {
                        btn.addEventListener('click', () => detailsDiv.classList.toggle('hidden'));
                    }
                });
                    
                    console.log('Orders loaded:', data.user_id, data.orders.length);
                } else {
                    ordersGrid.innerHTML = '<p class="text-gray-500 col-span-2 text-center">No orders received yet.</p>';
                }
                
            
            }
            )
            .catch(error => console.error('Error loading orders:', error));
        }
        
        // Initialize orders fetch
        fetchSellerOrders();

        // fetching from the users table to get the user details and display them on the dashboard
        
