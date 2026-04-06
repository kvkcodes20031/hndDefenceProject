document.addEventListener("DOMContentLoaded", function () {
  // Mobile Menu Logic for Farmer Dashboard
  const mobileMenuBtn = document.getElementById("mobileMenuBtn");
  const sidebar = document.getElementById("sidebar");
  const sidebarOverlay = document.getElementById("sidebarOverlay");
  const closeSidebarBtn = document.getElementById("closeSidebar");

  function toggleMobileMenu() {
    if (sidebar) sidebar.classList.toggle("-translate-x-full");
    if (sidebarOverlay) sidebarOverlay.classList.toggle("hidden");
  }

  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener("click", toggleMobileMenu);
  }
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", toggleMobileMenu);
  }
  if (closeSidebarBtn) {
    closeSidebarBtn.addEventListener("click", toggleMobileMenu);
  }

  // Cart Logic
  let cart = JSON.parse(localStorage.getItem("agriLinkCart")) || [];
  const cartSidebar = document.getElementById("cartSidebar");
  const cartOverlay = document.getElementById("cartOverlay");
  const cartCountSpan = document.getElementById("cartCount");
  const cartItemsContainer = document.getElementById("cartItemsContainer");
  const cartTotalDisplay = document.getElementById("cartTotalDisplay");
  const checkoutBtn = document.getElementById("checkoutBtn");

  function toggleCart() {
    if (!cartSidebar || !cartOverlay) return;
    cartSidebar.classList.toggle("translate-x-full");
    cartOverlay.classList.toggle("hidden");
  }

  if (document.getElementById("cartToggleBtn"))
    document
      .getElementById("cartToggleBtn")
      .addEventListener("click", toggleCart);
  if (document.getElementById("closeCartBtn"))
    document
      .getElementById("closeCartBtn")
      .addEventListener("click", toggleCart);
  if (cartOverlay) cartOverlay.addEventListener("click", toggleCart);

  window.removeFromCart = function (index) {
    cart.splice(index, 1);
    updateCartUI();
  };

  function updateCartUI() {
    localStorage.setItem("agriLinkCart", JSON.stringify(cart));
    if (cartCountSpan)
      cartCountSpan.textContent = cart.reduce(
        (sum, item) => sum + item.quantity,
        0,
      );

    let total = 0;
    if (cartItemsContainer) {
      if (cart.length === 0) {
        cartItemsContainer.innerHTML =
          '<p class="text-gray-500 text-center mt-10">Your cart is empty.</p>';
      } else {
        cartItemsContainer.innerHTML = cart
          .map((item, index) => {
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
          })
          .join("");
      }
    }

    if (cartTotalDisplay)
      cartTotalDisplay.textContent = total.toLocaleString() + " XAF";
  }

  function checkout() {
    if (cart.length === 0) {
      alert("Your cart is empty.");
      return;
    }
    const totalAmount = cart.reduce(
      (sum, item) => sum + item.price * item.quantity,
      0,
    );
    const orderData = {
      total_amount: totalAmount,
      order_status: "Pending",
      items: cart,
    };

    fetch("submit_order_data.php", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(orderData),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          alert("Order placed successfully!");
          cart = [];
          localStorage.removeItem("agriLinkCart");
          updateCartUI();
          toggleCart();
        } else {
          alert(
            "Checkout failed: " +
              (data.errors ? data.errors.join(", ") : "Unknown error"),
          );
        }
      })
      .catch((err) => console.error("Checkout error:", err));
  }

  if (checkoutBtn) checkoutBtn.addEventListener("click", checkout);

  fetch("fetchUser.php")
    .then((response) => response.json())
    .then((data) => {
      if (document.getElementById("username")) {
        document.getElementById("username").textContent =
          `${data.first_name || ""} ${data.last_name || ""}`;
      }
      if (data.role !== "farmer") {
        alert("Access denied. Redirecting to homepage.");
        window.location.href = "homepage.html";
      }
    })
    .catch((err) => {
      console.error("Error fetching user data:", err);
      alert("An error occurred. Please log in again.");
      window.location.href = "login.html";
    });

  function fetchProducts() {
    const grid = document.getElementById("productsGrid");
    if (!grid) return;

    grid.innerHTML =
      '<p class="text-gray-500 text-center col-span-3">Loading products...</p>';

    fetch("getuserlistings.php")
      .then((response) => response.json())
      .then((data) => {
        if (document.getElementById("totalProduce_listed")) {
          document.getElementById("totalProduce_listed").textContent =
            (data.products?.length || 0) + " T";
        }
        if (data.success && data.products.length > 0) {
          grid.innerHTML = data.products
            .map(
              (product) => `
                        <div class="bg-white p-4 rounded-xl border border-gray-100 flex items-center gap-4">
                            <img src="${product.image_path || "placeholder.png"}" alt="${product.product_name}" class="w-16 h-16 rounded-lg object-cover">
                            <div class="flex-grow">
                                <h4 class="font-bold text-primary">${product.category || "Produce"}</h4>
                                <p class="text-[10px] text-gray-400">(${product.quantity_available} ${product.unit})</p>
                                <p class="text-[10px] font-bold">Available From: ${product.harvest_date || "N/A"}</p>
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
                    `,
            )
            .join("");
        } else {
          grid.innerHTML =
            '<p class="text-gray-500 col-span-3 text-center">No products found.</p>';
        }
      })
      .catch((error) => {
        console.error("Error loading products:", error);
        grid.innerHTML =
          '<p class="text-red-500 col-span-3 text-center">Error loading products.</p>';
      });
  }

  // ================= FARMER ORDER LOGIC =================
  function fetchSellerOrders() {
    const ordersGrid = document.getElementById("ordersGrid");
    if (!ordersGrid) return;

    fetch("get_orders_madeTo_seller.php")
      .then((response) => response.json())
      .then((data) => {
        if (document.getElementById("totalOrders")) {
          document.getElementById("totalOrders").textContent =
            data.orders?.length || 0;
        }

        if (data.success && data.orders.length > 0) {
          ordersGrid.innerHTML = data.orders
            .map((item) => {
              const currentStatus = (item.order_status || "").toLowerCase();
              const isPending = currentStatus === "pending";

                        return `<div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 border rounded-lg bg-white">
                            <div class="w-12 h-12 bg-gray-200 rounded-full overflow-hidden shrink-0">
                                ${item.image_path ? `<img src="${item.image_path}" alt="${item.product_name}" class="w-full h-full object-cover">` : `<div class="w-full h-full flex items-center justify-center text-xs">📦</div>`}
                            </div>
                            <div class="flex-grow w-full">
                                
                                <p class="text-xs font-bold">${item.buyer_name} ${isPending ? '<span class="text-[10px] text-red-500 font-normal">(New Order - Please Confirm)</span>' : ''}</p>
                                <p class="text-[10px] text-gray-400">Produce: ${item.product_name}, Qty: ${item.quantity} ${item.unit}</p>
                                <p class="text-[10px] font-bold">Total: ${item.total_amount} XAF</p>
                                <p class="text-[10px] text-gray-400">Ordered on: ${new Date(item.order_date).toLocaleDateString()}</p>
                                <p class="text-[10px] font-bold ${isPending ? 'text-orange-500' : 'text-primary'}">Status: ${item.order_status}</p>
                                <button class="text-[10px] text-blue-500 mt-1" id="viewDetails_${item.order_id}">View Details</button>
                                <div class="flex items-center gap-2 mt-2 hidden" id="Details_${item.order_id}"> 
                                    <p class="text-[10px] text-gray-400">From ${item.first_name} ${item.last_name}</p>
                                    <p class="text-[10px] text-gray-400">Price: ${item.agreed_price} XAF/${item.unit}</p>
                                    <p class="text-[10px] text-gray-400">Phone: ${item.seller_phone || 'N/A'}</p>
                                    <p class="text-[10px] text-gray-400">Community: ${item.seller_city || 'N/A'} ${item.seller_city || ''}</p>
                                </div>
                            </div>
                            <div class="flex flex-row sm:flex-col gap-2 w-full sm:w-auto mt-3 sm:mt-0 justify-end">
                               <button class="flex-1 sm:flex-none bg-primary hover:bg-green-700 text-white px-5 py-2 rounded-lg text-xs font-bold transition-all shadow-sm hover:shadow-md ${!isPending ? 'hidden' : ''}" onclick="updateOrderStatus(${item.order_id}, 'Accepted',this)" id="acceptBtn_${item.order_id}">
                                    Accept
                               </button>
                               <button class="flex-1 sm:flex-none bg-white border border-red-200 text-red-500 hover:bg-red-50 px-5 py-2 rounded-lg text-xs font-bold transition-all shadow-sm hover:shadow-md ${!isPending ? 'hidden' : ''}" onclick="updateOrderStatus(${item.order_id}, 'Rejected',this)" id="rejectBtn_${item.order_id}">
                                    Reject
                               </button>

                                
                                <p class="flex-1 sm:flex-none bg-gray-200 text-gray-500 px-5 py-2 rounded-lg text-xs font-bold transition-all shadow-sm hover:shadow-md ${isPending ? 'hidden' : ''}" disabled>
                                    ${item.order_status === 'Accepted' ? 'Accepted' : 'Rejected'}
                               </p>
                                    
                               <div class="flex-1 sm:flex-none bg-gray-200 text-gray-500 px-5 py-2 rounded-lg text-xs font-bold transition-all shadow-sm hover:shadow-md" onclick="goToStep2(${item.order_id})">
                               
                                     
                                 <button class="text-[10px] text-blue-500 mt-1 ${item.order_status === 'Accepted' ? '' : 'hidden'}">Select Logistics</button>
                            
                            <button class="text-[10px] text-blue-500 mt-1 ${item.order_status === 'rejected' ? '' : 'hidden'}" id="deleteBtn_${item.order_id}">delete order</button>
                            
                            </div>
                            </div>
                        </div>`;


                    }).join('');


          data.orders.forEach((item) => {
            const btn = document.getElementById(`viewDetails_${item.order_id}`);
            const detailsDiv = document.getElementById(
              `Details_${item.order_id}`,
            );
            if (btn && detailsDiv) {
              btn.addEventListener("click", () =>
                detailsDiv.classList.toggle("hidden"),
              );
            }
          });
        } else {
          ordersGrid.innerHTML =
            '<p class="text-gray-500 col-span-2 text-center">No orders received yet.</p>';
        }
      })
      .catch((error) => {
        console.error("Error loading orders:", error);
        if (ordersGrid)
          ordersGrid.innerHTML =
            '<p class="text-red-500 col-span-2 text-center">Error loading orders.</p>';
      });
  }


  window.goToStep2 = function (orderId) {
  fetch("store_order_id.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ order_id: orderId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      window.location.href = `select_logistics.html?order_id=${orderId}`;
    }
  });
}
  // Toggle Orders Grid Visibility
  const viewOrdersLink = document.getElementById("viewOrdersLink");
  const ordersSection = document.getElementById("ordersSection");
  if (viewOrdersLink && ordersSection) {
    viewOrdersLink.addEventListener("click", function (e) {
      e.preventDefault();
      ordersSection.classList.toggle("hidden");
    });
  }

  // Toggle Orders Section Button Logic
  const toggleOrdersBtn = document.getElementById("toggleOrdersBtn");
  if (toggleOrdersBtn && ordersSection) {
    toggleOrdersBtn.addEventListener("click", function () {
      const productsGrid = document.getElementById("productsGrid");
      if (ordersSection.classList.contains("hidden")) {
        // Show Orders, Hide Products
        ordersSection.classList.remove("hidden");
        if (productsGrid) productsGrid.classList.add("hidden");
      } else {
        // Hide Orders, Show Products
        ordersSection.classList.add("hidden");
        if (productsGrid) productsGrid.classList.remove("hidden");
      }
    });
  }

  const myProduceBtn = document.getElementById("myProduceBtn");
  if (myProduceBtn) {
    myProduceBtn.addEventListener("click", function () {
      const productsGrid = document.getElementById("productsGrid");
      const ordersSection = document.getElementById("ordersSection");
      if (productsGrid) productsGrid.classList.remove("hidden");
      if (ordersSection) ordersSection.classList.add("hidden");
    });
  }

  // Initial calls
  updateCartUI();
  fetchProducts();
  fetchSellerOrders();
});

function toggleNotifications() {
  const dropdown = document.getElementById("notificationDropdown");
  dropdown.classList.toggle("hidden");
  if (!dropdown.classList.contains("hidden")) {
    fetchNotifications();
  }
}

function fetchNotifications() {
  fetch("get_notifications.php")
    .then((response) => response.json())
    .then((data) => {
      const list = document.getElementById("notificationList");
      const countBadge = document.getElementById("notificationCount");

      if (data.success && data.notifications.length > 0) {
        const unreadCount = data.notifications.filter(
          (n) => n.is_read == 0,
        ).length;

        if (unreadCount > 0) {
          countBadge.textContent = unreadCount;
          countBadge.classList.remove("hidden");
        } else {
          countBadge.classList.add("hidden");
        }

        // Debug log
        // console.log('Notifications data:', data);

        list.innerHTML = data.notifications
          .map(
            (n) => `
                        <li onclick="markNotificationRead(${n.notification_id}, this)" 
                        
                            class="p-3 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition ${n.is_read == 0 ? "bg-blue-50 opacity-100" : "bg-white opacity-60"}">
                            <p class="text-xs ${n.is_read == 1 ? "line text-gray-500" : "font-medium text-gray-800"}">${n.notification_message}</p>
                            <p class="text-[10px] text-gray-400 mt-1">${new Date(n.created_at).toLocaleString()}</p>
                        </li>
                    `,
          )
          .join("");
      } else {
        list.innerHTML =
          '<li class="p-4 text-center text-gray-500 text-xs">No notifications</li>';
        countBadge.classList.add("hidden");
      }
    })
    .catch((err) => console.error("Error loading notifications:", err));
}

function markNotificationRead(id, element) {
  fetch("mark_notification_read.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id: id }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        element.classList.remove("bg-blue-50", "opacity-100");
        element.classList.add("bg-white", "opacity-60");

        // Apply strikethrough styling
        const textP = element.querySelector("p");
        if (textP) {
          textP.classList.remove("font-medium", "text-gray-800");
          textP.classList.add("text-gray-500");
        }

        // Toggle orders section and scroll to it
        const ordersSection = document.getElementById("ordersSection");
        const productsGrid = document.getElementById("productsGrid");

        if (ordersSection) {
          ordersSection.classList.remove("hidden");
          // Hide the products grid to show only orders
          if (productsGrid) productsGrid.classList.add("hidden");
          ordersSection.scrollIntoView({ behavior: "smooth", block: "start" });
          const dropdown = document.getElementById("notificationDropdown");
          if (dropdown) dropdown.classList.add("hidden");
        }

        // Optionally refresh count
        fetchNotifications();
        console.log("Marked notification as read:", id);
      } else {
        console.error("Error marking notification as read:", data.errors);
      }
    });
}

// Load count on init
fetchNotifications();

//cooperative logtic
function loadMyCooperatives() {
  const list = document.getElementById("myCoopsList");
  fetch("get_my_cooperatives.php")
    .then((r) => r.json())
    .then((data) => {
      console.log("cooperatives data:", data);
      if (data.success && data.cooperatives.length > 0) {
        list.innerHTML = data.cooperatives
          .map(
            (c) => `
                            <li onclick="switchCoop(${c.cooperative_id})" class="hover:text-accent cursor-pointer truncate transition-colors" title="${c.name}">
                                • ${c.name}
                            </li>
                        `,
          )
          .join("");
      } else {
        list.innerHTML =
          '<p class="text-gray-400 text-[10px]">No memberships found.</p>';
      }
    });
}

function switchCoop(coopId) {
  fetch("set_coop_context.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ coop_id: coopId }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) window.location.href = "coperativ.html";
    });
}

function loadMyCooperatives() {
  const list = document.getElementById("myCoopsList");
  fetch("get_my_cooperatives.php")
    .then((r) => r.json())
    .then((data) => {
      if (data.success && data.cooperatives.length > 0) {
        list.innerHTML = data.cooperatives
          .map(
            (c) => `
                            <li onclick="switchCoop(${c.cooperative_id})" class="hover:text-accent cursor-pointer truncate transition-colors" title="${c.name}">
                                • ${c.name}
                            </li>
                        `,
          )
          .join("");
      } else {
        list.innerHTML =
          '<p class="text-gray-400 text-[10px]">No memberships found.</p>';
      }
    });
}

function switchCoop(coopId) {
  fetch("set_coop_context.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ coop_id: coopId }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) window.location.href = "coperativ.html";
    });
}

loadMyCooperatives();

function updateOrderStatus(orderId, status, buttonElement) {
  fetch("update_order_status.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      order_id: orderId,
      status: status
    }),
  })
  
    .then((res) => res.json())
    .then((data) => {
      console.log("sending order status update:", { orderId, status });
      if (data.success) {
        alert(`Order ${orderId} status updated to ${status}.`);
        const statusTextElement = document.getElementById(
          `statusText_${orderId}`,
        );
        if (statusTextElement) {
          statusTextElement.textContent = status;
          statusTextElement.className = `px-3 py-1 rounded text-[10px] font-bold uppercase ${status === "Accepted" ? "text-primary" : "text-red-600"}`;
        }
        const acceptBtn = document.getElementById(`acceptBtn_${orderId}`);
        const rejectBtn = document.getElementById(
          `rejectBtn_${orderId}`,
        );
        if (acceptBtn) acceptBtn.disabled = true;
        if (rejectBtn) rejectBtn.disabled = true;
      } else {
        alert(
          "Error updating order status: " +
            (data.errors ? data.errors.join(", ") : "Unknown error"),
        );
      }
    })
    .catch((err) => console.error("Error updating order status:", err));
}

function acceptOrder(orderId, buttonElement) {
  updateOrderStatus(orderId, "Accepted", buttonElement);
}

function rejectOrder(orderId, buttonElement) {
  if (confirm("Are you sure you want to reject this offer?")) {
    updateOrderStatus(orderId, "Rejected", buttonElement);
  }
}



loadMyCooperatives();

 function fetchBuyerOrders() {
        fetch("get_orders_madeBy_buyer.php")
          .then((response) => response.json())
          .then((data) => {
            const list = document.getElementById("recentOrdersList");
             console.log("Fetched orders data:", data);
            if (data.success && data.orders.length > 0) {
              list.innerHTML = data.orders
                .map((order) => {
                  // Determine color based on status
                  let statusClass = "text-gray-400";
                  if (["Pending"].includes(order.order_status))
                    statusClass = "text-orange-500";
                  if (["Awaiting Payment"].includes(order.order_status))
                    statusClass = "text-red-500";
                  if (["In Transit", "Active"].includes(order.order_status))
                    statusClass = "text-accent";
                  if (["Rejected"].includes(order.order_status))
                    statusClass = "text-red-600";
                  if (
                    ["Delivered", "Completed", "Paid"].includes(
                      order.order_status,
                    )
                  )
                    statusClass = "text-primary";

                  const isRejected = order.order_status === "Rejected";
                  const isAccepted = order.order_status === "Accepted";
                  // Show Pay button if not paid or completed
                  const showPayBtn = ![
                    "Paid",
                    "Completed",
                    "Delivered",
                    "Rejected",
                  ].includes(order.order_status);

                  return `
                        <li id="order_${order.order_id}" class="flex justify-between items-center border-b pb-3 border-gray-50 last:border-0">
                            <div>
                                <span class="font-bold block">${order.product_name}</span>
                                <span class="text-xs text-gray-500">${order.qauntity} ${order.unit} • ${parseInt(order.total_amount || 0).toLocaleString()} XAF</span>
                                <span class="text-[10px] text-gray-400 block">Seller: ${order.seller_name}</span>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="${statusClass} font-bold text-xs uppercase">${order.order_status}</span>
                                ${isRejected ? `
                                  <button disabled class="bg-red-500 text-white text-[10px] px-3 py-1 rounded shadow-md font-bold cursor-not-allowed">Rejected</button>
                                  <button onclick="deleteOrder(${order.order_id}, this.closest('li'))" class="text-xs text-gray-400 hover:text-red-500 font-bold transition-colors">Delete</button>
                                ` : (showPayBtn ? `
                                  <a href="paymentpage.html?order_id=${order.order_id}" 
                                     class="${isAccepted ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-primary hover:bg-secondary'} text-white text-[10px] px-3 py-1 rounded transition shadow-md font-bold ${isAccepted ? '' : 'cursor-not-allowed opacity-50'}" 
                                     id="payBtn_${order.order_id}">Pay Now</a>` : "")}
                            </div>
                        </li>
                    `;
                })
                .join("");

              data.orders.forEach((order) => {
                const btn = document.getElementById(`payBtn_${order.order_id}`);
                if (btn) {
                  btn.addEventListener("click", function (e) {
                    // Only allow navigation if the order is Accepted
                    if (order.order_status !== "Accepted") {
                      e.preventDefault();
                    }
                  });
                }
                // Apply payment button logic
                callForPayment(order.order_id, order.order_status);
              });

              // Optional: Update stats counters if needed
              // document.getElementById('ordersPlacedCount').innerText = data.orders.length;
            } else {
              list.innerHTML =
                '<li class="text-gray-500">No orders found.</li>';
            }
          })
          .catch((error) => {
            console.error("Error fetching orders:", error);
            document.getElementById("recentOrdersList").innerHTML =
              '<li class="text-red-500">Error loading orders.</li>';
          });
      }

      function deleteOrder(orderId, element) {
        if (!confirm("Are you sure you want to remove this order?")) return;
        
        fetch('delete_order.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ order_id: orderId })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) element.remove();
          else alert("Error deleting order: " + (data.errors ? data.errors.join(', ') : 'Unknown error'));
        })
        .catch(err => { console.error(err); element.remove(); /* Fallback for local UI removal */ });
      }
