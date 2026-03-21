document.addEventListener('DOMContentLoaded', function () {
    // Cart Logic
    let cart = JSON.parse(localStorage.getItem('agriLinkCart')) || [];
    const cartSidebar = document.getElementById('cartSidebar');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartCountSpan = document.getElementById('cartCount');
    const cartItemsContainer = document.getElementById('cartItemsContainer');
    const cartTotalDisplay = document.getElementById('cartTotalDisplay');
    const checkoutBtn = document.getElementById('checkoutBtn');

    function toggleCart() {
        if (!cartSidebar || !cartOverlay) return;
        cartSidebar.classList.toggle('translate-x-full');
        cartOverlay.classList.toggle('hidden');
    }

    if (document.getElementById('cartToggleBtn')) document.getElementById('cartToggleBtn').addEventListener('click', toggleCart);
    if (document.getElementById('closeCartBtn')) document.getElementById('closeCartBtn').addEventListener('click', toggleCart);
    if (cartOverlay) cartOverlay.addEventListener('click', toggleCart);

    window.removeFromCart = function (index) {
        cart.splice(index, 1);
        updateCartUI();
    }

    function updateCartUI() {
        localStorage.setItem('agriLinkCart', JSON.stringify(cart));
        if (cartCountSpan) cartCountSpan.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);

        let total = 0;
        if (cartItemsContainer) {
            if (cart.length === 0) {
                cartItemsContainer.innerHTML = '<p class="text-gray-500 text-center mt-10">Your cart is empty.</p>';
            } else {
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
            }
        }

        if (cartTotalDisplay) cartTotalDisplay.textContent = total.toLocaleString() + ' XAF';
    }

    function checkout() {
        if (cart.length === 0) {
            alert("Your cart is empty.");
            return;
        }
        const totalAmount = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const orderData = { total_amount: totalAmount, order_status: "Pending", items: cart };

        fetch('submit_order_data.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { "Content-Type": "application/json" },
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
                alert("Checkout failed: " + (data.errors ? data.errors.join(", ") : "Unknown error"));
            }
        })
        .catch(err => console.error("Checkout error:", err));
    }

    if (checkoutBtn) checkoutBtn.addEventListener('click', checkout);

    fetch('fetchUser.php')
        .then(response => response.json())
        .then(data => {
            if (document.getElementById('username')) {
                document.getElementById('username').textContent = `${data.first_name || ''} ${data.last_name || ''}`;
            }
            if (data.role !== 'farmer') {
                alert('Access denied. Redirecting to homepage.');
                window.location.href = 'homepage.html';
            }
        })
        .catch(err => {
            console.error('Error fetching user data:', err);
            alert('An error occurred. Please log in again.');
            window.location.href = 'login.html';
        });

    function fetchProducts() {
        const grid = document.getElementById('productsGrid');
        if (!grid) return;

        grid.innerHTML = '<p class="text-gray-500 text-center col-span-3">Loading products...</p>';

        fetch('getuserlistings.php')
            .then(response => response.json())
            .then(data => {
                if (document.getElementById('totalProduce_listed')) {
                    document.getElementById('totalProduce_listed').textContent = (data.products?.length || 0) + ' T';
                }
                if (data.success && data.products.length > 0) {
                    grid.innerHTML = data.products.map(product => `
                        <div class="bg-white p-4 rounded-xl border border-gray-100 flex items-center gap-4">
                            <img src="${product.image_path || 'placeholder.png'}" alt="${product.product_name}" class="w-16 h-16 rounded-lg object-cover">
                            <div class="flex-grow">
                                <h4 class="font-bold text-primary">${product.category || 'Produce'}</h4>
                                <p class="text-[10px] text-gray-400">(${product.quantity_available} ${product.unit})</p>
                                <p class="text-[10px] font-bold">Available From: ${product.harvest_date || 'N/A'}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-primary font-bold">${(product.price_per_unit * 1).toLocaleString()} XAF</p>
                                <p class="text-[8px] text-gray-400">per ${product.unit}</p>
                            </div>
                            <div class="flex flex-col gap-1">
                                <button class="bg-primary text-white px-4 py-1.5 rounded text-[10px] font-bold">TOTAL ${(product.price_per_unit * product.quantity_available).toLocaleString()}</button>
                                <button class="text-[10px] font-bold text-gray-400">Edit Product</button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    grid.innerHTML = '<p class="text-gray-500 col-span-3 text-center">No products found.</p>';
                }
            })
            .catch(error => {
                console.error('Error loading products:', error);
                grid.innerHTML = '<p class="text-red-500 col-span-3 text-center">Error loading products.</p>';
            });
    }

    function fetchSellerOrders() {
        const ordersGrid = document.getElementById('ordersGrid');
        if (!ordersGrid) return;

        fetch('get_orders_madeTo_seller.php')
            .then(response => response.json())
            .then(data => {
                if (document.getElementById('totalOrders')) {
                    document.getElementById('totalOrders').textContent = data.orders?.length || 0;
                }
                
                if (data.success && data.orders.length > 0) {
                    ordersGrid.innerHTML = data.orders.map(item => {
                        // Determine if order is Pending to apply styling
                        const isPending = item.order_status === 'Pending';

                        let actionButtons = '';
                        if (item.order_status === 'Pending') {
                            actionButtons = `<div class="flex gap-1 w-full sm:w-auto">
                                <button onclick="updateOrderStatus(${item.order_id}, 'Rejected')" class="bg-red-500 text-white px-2 py-1 rounded text-[10px] font-bold hover:bg-red-600">Reject</button>
                                <button onclick="updateOrderStatus(${item.order_id}, 'Accepted')" class="bg-green-600 text-white px-3 py-1 rounded text-[10px] font-bold hover:bg-green-700">Confirm Order</button>
                            </div>`;
                        } else if (item.order_status === 'Paid') {
                            actionButtons = `<div class="flex gap-1 w-full sm:w-auto">
                                <span class="text-xs font-bold text-green-600 mr-2">Paid</span>
                                <button onclick="updateOrderStatus(${item.order_id}, 'In Transit')" class="bg-agri-orange text-white px-3 py-1 rounded text-[10px] font-bold hover:bg-orange-600">Ship Order</button>
                            </div>`;
                        } else if (item.order_status === 'Accepted' || item.order_status === 'In Transit') {
                            actionButtons = `<div class="flex gap-1 w-full sm:w-auto">
                                ${item.order_status === 'Accepted' ? '<span class="text-xs font-bold text-green-600 self-center mr-2">Confirmed</span>' : ''}
                                <button onclick="updateOrderStatus(${item.order_id}, 'Completed')" class="bg-primary text-white px-3 py-1 rounded text-[10px] font-bold hover:bg-green-700">Confirm Payment Received</button>
                            </div>`;
                        } else if (item.order_status === 'Completed') {
                            actionButtons = `<span class="text-primary font-bold text-xs">Completed</span>`;
                        } else if (item.order_status === 'Awaiting Payment') {
                            actionButtons = `<span class="text-orange-500 font-bold text-xs">Waiting for Buyer Payment...</span>`;
                        } else if (item.order_status === 'Rejected') {
                            actionButtons = `<span class="text-red-500 font-bold text-xs">Order Rejected</span>`;
                        }

                        // Apply opacity/overlay style if pending
                        const containerClass = isPending 
                            ? "relative bg-orange-50 p-3 rounded-xl border border-orange-200 flex sm:flex-row items-start sm:items-center gap-4 transition-all" 
                            : "bg-white p-3 rounded-xl border border-gray-100 flex sm:flex-row items-start sm:items-center gap-4";

                        return `<div class="${containerClass}">
                            <div class="w-10 h-10 bg-gray-200 rounded-full overflow-hidden shrink-0">
                                ${item.image_path ? `<img src="${item.image_path}" alt="${item.product_name}" class="w-full h-full object-cover">` : `<div class="w-full h-full flex items-center justify-center text-xs">📦</div>`}
                            </div>
                            <div class="flex-grow w-full">
                                <p class="text-xs font-bold">${item.buyer_name} ${isPending ? '<span class="text-[10px] text-red-500 font-normal">(New Order - Please Confirm)</span>' : ''}</p>
                                <p class="text-[10px] text-gray-400">Produce: ${item.product_name}, Qty: ${item.quantity} ${item.unit}</p>
                                <p class="text-[10px] font-bold">Total: ${item.total_amount} XAF</p>
                                <p class="text-[10px] text-gray-400">Ordered on: ${new Date(item.order_date).toLocaleDateString()}</p>
                                <p class="text-[10px] font-bold text-primary">Status: ${item.order_status}</p>
                                <button class="text-[10px] text-blue-500 mt-1" id="viewDetails_${item.order_id}">View Details</button>
                                <div class="flex items-center gap-2 mt-2 hidden" id="Details_${item.order_id}"> 
                                    <p class="text-[10px] text-gray-400">From ${item.first_name} ${item.last_name}</p>
                                    <p class="text-[10px] text-gray-400">Price: ${item.agreed_price} XAF/${item.unit}</p>
                                    <p class="text-[10px] text-gray-400">Phone: ${item.seller_phone || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2 w-full sm:w-auto">
                                ${actionButtons}
                            </div>
                        </div>`;
                    }).join('');

                    data.orders.forEach(item => {
                        const btn = document.getElementById(`viewDetails_${item.order_id}`);
                        const detailsDiv = document.getElementById(`Details_${item.order_id}`);
                        if (btn && detailsDiv) {
                            btn.addEventListener('click', () => detailsDiv.classList.toggle('hidden'));
                        }
                    });
                } else {
                    ordersGrid.innerHTML = '<p class="text-gray-500 col-span-2 text-center">No orders received yet.</p>';
                }
            })
            .catch(error => {
                console.error('Error loading orders:', error);
                if(ordersGrid) ordersGrid.innerHTML = '<p class="text-red-500 col-span-2 text-center">Error loading orders.</p>';
            });
    }

    window.updateOrderStatus = function (orderId, status) {
        if (!confirm(`Are you sure you want to change status to "${status}"?`)) return;

        fetch('update_order_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, status: status })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (status === 'Accepted') {
                    // Redirect to logistics selection page
                    window.location.href = `select_logistics.html?order_id=${orderId}`;
                } else {
                    fetchSellerOrders();
                }
            } else {
                alert('Error updating status: ' + (data.errors ? data.errors.join(', ') : 'Unknown error'));
            }
        })
        .catch(err => console.error(err));
    };

    // Toggle Orders Grid Visibility
    const viewOrdersLink = document.getElementById('viewOrdersLink');
    const ordersSection = document.getElementById('ordersSection');
    if(viewOrdersLink && ordersSection) {
        viewOrdersLink.addEventListener('click', function(e) {
            e.preventDefault();
            ordersSection.classList.toggle('hidden');
        });
    }

    // Initial calls
    updateCartUI();
    fetchProducts();
    fetchSellerOrders();
});
        

 function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden')) {
                fetchNotifications();
            }
        }

        // function fetchNotifications() {
        //     fetch('get_notifications.php')
        //     .then(response => response.json())
        //     .then(data => {
        //         const list = document.getElementById('notificationList');
        //         const countBadge = document.getElementById('notificationCount');
        function fetchNotifications() {
            fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('notificationList');
                const countBadge = document.getElementById('notificationCount');
                
        //         if (data.success && data.notifications.length > 0) {
        //             const unreadCount = data.notifications.filter(n => n.is_read == 0).length;
                if (data.success && data.notifications.length > 0) {
                    const unreadCount = data.notifications.filter(n => n.is_read == 0).length;
                    
        //             if (unreadCount > 0) {
        //                 countBadge.textContent = unreadCount;
        //                 countBadge.classList.remove('hidden');
        //             } else {
        //                 countBadge.classList.add('hidden');
        //             }
                    if (unreadCount > 0) {
                        countBadge.textContent = unreadCount;
                        countBadge.classList.remove('hidden');
                    } else {
                        countBadge.classList.add('hidden');
                    }

       
                    console.log('Notification ID:', data.notifications[0].notification_id);
                    console.log('Notifications data:', data);
                     // Debug log
                    list.innerHTML = data.notifications.map(n => `
                        <li onclick="markNotificationRead(${n.notification_id}, this)" 
                        conole.log('Notification ID:', n.notification_id),
                            class="p-3 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition ${n.is_read == 0 ? 'bg-blue-50 opacity-100' : 'bg-white opacity-60'}">
                            <p class="text-xs ${n.is_read == 1 ? 'line-through text-gray-500' : 'font-medium text-gray-800'}">${n.notification_message}</p>
                            <p class="text-[10px] text-gray-400 mt-1">${new Date(n.created_at).toLocaleString()}</p>
                        </li>
                    `).join('');
                } else {
                    list.innerHTML = '<li class="p-4 text-center text-gray-500 text-xs">No notifications</li>';
                    countBadge.classList.add('hidden');
                }
                console.log(data.notifications.map(n => n.notification_id)); // Log all notification IDs
            })
            .catch(err => console.error('Error loading notifications:', err));
        }

        
        function markNotificationRead(id, element) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id})
            }).then(res => res.json()).then(data => {
                if(data.success) {
                    element.classList.remove('bg-blue-50', 'opacity-100');
                    element.classList.add('bg-white', 'opacity-60');

                    // Apply strikethrough styling
                    const textP = element.querySelector('p');
                    if(textP) {
                        textP.classList.remove('font-medium', 'text-gray-800');
                        // textP.classList.add('line-through', 'text-gray-500');
                    }

                    // Toggle orders section and scroll to it
                    const ordersSection = document.getElementById('ordersSection');
                    if (ordersSection) {
                        ordersSection.classList.remove('hidden');
                        ordersSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        const dropdown = document.getElementById('notificationDropdown');
                        if (dropdown) dropdown.classList.add('hidden');
                    }
               
                    // Optionally refresh count
                    fetchNotifications();
                    console.log('Marked notification as read:', id);
                } else {
                    console.error('Error marking notification as read:', data.errors);
                }
            });
        }

        // Load count on init
        fetchNotifications();
