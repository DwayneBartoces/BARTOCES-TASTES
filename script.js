/**
 * BARTOCES TASTES - EST. 2026
 * Interactive Engine & Shopping Cart System
 */

document.addEventListener('DOMContentLoaded', () => {

    // ==========================================================
    // 1. DISH DATABASE
    // ==========================================================
    const menuDatabase = {
        1: {
            id: 1,
            name: "Chicken Inasal",
            price: 249,
            category: "classics",
            image: "images/chicken-inasal.jpg",
            desc: "Authentic grilled chicken thigh and leg marinated in achiote, lemongrass, ginger, and calamansi. Served with steaming garlic rice and spiced soy-calamansi dip.",
            stars: "★★★★★ (4.9)",
            badge: "Bestseller"
        },
        2: {
            id: 2,
            name: "Beef Steak",
            price: 399,
            category: "classics",
            image: "images/beef-steak.jpg",
            desc: "Filipino Bistek Tagalog - tender ribeye beef slices braised to perfection in citrusy soy glaze, garnished with caramelized onion rings and herb butter.",
            stars: "★★★★★ (5.0)",
            badge: "Signature"
        },
        3: {
            id: 3,
            name: "Pork Sisig",
            price: 199,
            category: "classics",
            image: "images/pork-sisig.jpg",
            desc: "Legendary Filipino sizzling dish made with crispy diced pork belly, seasoned with onions, chili peppers, and calamansi, topped with a fresh farm egg.",
            stars: "★★★★★ (4.8)",
            badge: "Hot & Sizzling"
        },
        4: {
            id: 4,
            name: "Creamy Carbonara",
            price: 250,
            category: "pasta",
            image: "images/carbonara.jpg",
            desc: "Handcrafted al dente spaghetti tossed in velvety egg yolk parmesan cream, loaded with crispy smoked bacon bits, and cracked black peppercorn.",
            stars: "★★★★★ (4.9)",
            badge: "Chef Choice"
        },
        5: {
            id: 5,
            name: "Gourmet Burger",
            price: 120,
            category: "fastfood",
            image: "images/burger.jpg",
            desc: "Flame-grilled artisan beef patty topped with melted aged cheddar, caramelized balsamic onions, and crisp greens inside a buttered brioche bun.",
            stars: "★★★★★ (4.7)",
            badge: "Popular"
        },
        6: {
            id: 6,
            name: "Crispy Fries",
            price: 99,
            category: "fastfood",
            image: "images/fries.jpg",
            desc: "Hand-cut golden russet potato fries, crisped to crunchy perfection and lightly dusted with sea salt. Served with artisanal garlic herb aioli.",
            stars: "★★★★★ (4.8)",
            badge: "Sides"
        },
        7: {
            id: 7,
            name: "Artisan Pizza",
            price: 300,
            category: "pasta",
            image: "images/pizza.jpg",
            desc: "Woodfired thin-crust pizza topped with rich San Marzano tomato sauce, bubbly melted mozzarella, pepperoni, and fresh aromatic basil leaves.",
            stars: "★★★★★ (4.9)",
            badge: "Specialty"
        },
        8: {
            id: 8,
            name: "Grilled Chicken Inasal",
            price: 249,
            category: "classics",
            image: "images/grilled-chicken.jpg",
            desc: "Chef Kenji's recommendation: Charcoal flame-grilled chicken specialty steeped in traditional Ilonggo marinade and basted with golden achiote oil.",
            stars: "★★★★★ (4.9)",
            badge: "Chef's Pick"
        },
        // International Burger Varieties
        9: {
            id: 9,
            name: "Bacon Cheeseburger",
            price: 180,
            category: "fastfood",
            image: "images/bacon-cheeseburger.jpg",
            desc: "Juicy beef patty topped with crispy bacon, melted cheddar, lettuce, tomato, and special sauce on a toasted bun.",
            stars: "★★★★★ (4.6)",
            badge: "Chef Special"
        },
        10: {
            id: 10,
            name: "Mushroom Swiss Burger",
            price: 185,
            category: "fastfood",
            image: "images/mushroom-swiss-burger.jpg",
            desc: "Savory beef patty with sautéed mushrooms, melted Swiss cheese, caramelized onions, and garlic aioli on a brioche bun.",
            stars: "★★★★★ (4.5)",
            badge: "Gourmet"
        },
        11: {
            id: 11,
            name: "Spicy Chicken Burger",
            price: 160,
            category: "fastfood",
            image: "images/spicy-chicken-burger.jpg",
            desc: "Crispy chicken fillet with pepper jack cheese, lettuce, tomato, and spicy mayo on a toasted bun.",
            stars: "★★★★★ (4.4)",
            badge: "Spicy"
        },
        // International Pasta Varieties
        12: {
            id: 12,
            name: "Spicy Arrabbiata",
            price: 220,
            category: "pasta",
            image: "images/spicy-arrabbiata.jpg",
            desc: "Penne pasta in a spicy tomato sauce with garlic, chili flakes, and fresh basil, topped with parmesan.",
            stars: "★★★★★ (4.6)",
            badge: "Spicy"
        },
        13: {
            id: 13,
            name: "Mushroom Alfredo",
            price: 240,
            category: "pasta",
            image: "images/mushroom-alfredo.jpg",
            desc: "Fettuccine in a creamy Alfredo sauce with sautéed mushrooms, garlic, and parmesan cheese.",
            stars: "★★★★★ (4.7)",
            badge: "Chef Choice"
        },
        14: {
            id: 14,
            name: "Seafood Linguine",
            price: 280,
            category: "pasta",
            image: "images/seafood-linguine.jpg",
            desc: "Linguine with shrimp, mussels, clams in a white wine garlic sauce with cherry tomatoes and parsley.",
            stars: "★★★★★ (4.8)",
            badge: "Specialty"
        },
        // Asian Cuisine
        15: {
            id: 15,
            name: "Beef Teriyaki Bowl",
            price: 220,
            category: "classics",
            image: "images/beef-teriyaki.jpg",
            desc: "Grilled beef strips with steamed rice, stir-fried vegetables, and homemade teriyaki sauce.",
            stars: "★★★★★ (4.6)",
            badge: "Asian"
        },
        16: {
            id: 16,
            name: "Chicken Teriyaki",
            price: 195,
            category: "classics",
            image: "images/chicken-teriyaki.jpg",
            desc: "Grilled chicken thigh with steamed rice, stir-fried vegetables, and sweet teriyaki glaze.",
            stars: "★★★★★ (4.7)",
            badge: "Asian"
        },
        17: {
            id: 17,
            name: "Vegetable Stir Fry",
            price: 175,
            category: "classics",
            image: "images/vegetable-stir-fry.jpg",
            desc: "Mixed seasonal vegetables stir-fried with tofu in ginger-garlic sauce, served with steamed rice.",
            stars: "★★★★★ (4.5)",
            badge: "Healthy"
        }
    };


    // ==========================================================
    // 2. STATE MANAGEMENT
    // ==========================================================
    // Current PHP session user (injected by index.php)
    const currentUser = (window.BARTOCES_SESSION && window.BARTOCES_SESSION.loggedIn)
        ? window.BARTOCES_SESSION.user
        : null;

    let cart = [];
    if (currentUser) {
        try {
            const storedCart = localStorage.getItem('bartoces_cart_2026');
            if (storedCart) {
                cart = JSON.parse(storedCart);
            }
        } catch (e) {
            console.error("Could not load cart from storage", e);
            cart = [];
        }
    } else {
        // Clear cart for logged-out visitors
        try {
            localStorage.removeItem('bartoces_cart_2026');
        } catch (e) {}
    }

    let currentOrderType = 'dinein'; // 'dinein' | 'takeout' | 'delivery'

    // Selected dish state for Quick View Modal
    let selectedQuickViewItem = null;
    let quickViewQty = 1;


    // ==========================================================
    // 3. DOM ELEMENTS SELECTION
    // ==========================================================
    const toastContainer = document.getElementById('toastContainer');
    const modalBackdrop = document.getElementById('modalBackdrop');

    // Cart Elements
    const cartDrawer = document.getElementById('cartDrawer');
    const cartBtn = document.getElementById('cartBtn');
    const cartBadge = document.getElementById('cartBadge');
    const closeCartBtn = document.getElementById('closeCartBtn');
    const cartItemsList = document.getElementById('cartItemsList');
    const drawerItemCount = document.getElementById('drawerItemCount');
    const billSubtotal = document.getElementById('billSubtotal');
    const billDeliveryFee = document.getElementById('billDeliveryFee');
    const deliveryFeeRow = document.getElementById('deliveryFeeRow');
    const billTotal = document.getElementById('billTotal');
    const checkoutForm = document.getElementById('checkoutForm');
    const deliveryAddressGroup = document.getElementById('deliveryAddressGroup');
    const tableNumberGroup = document.getElementById('tableNumberGroup');
    const custAddress = document.getElementById('custAddress');
    const custTable = document.getElementById('custTable');

    // Order Type Tabs in Cart
    const orderTypeTabs = document.querySelectorAll('.order-type-tab');

    // Other Modals
    const receiptModal = document.getElementById('receiptModal');
    const closeReceiptBtn = document.getElementById('closeReceiptBtn');
    const btnDoneOrder = document.getElementById('btnDoneOrder');
    const btnPrintReceipt = document.getElementById('btnPrintReceipt');
    const receiptOrderNum = document.getElementById('receiptOrderNum');
    const receiptBody = document.getElementById('receiptBody');

    const reservationModal = document.getElementById('reservationModal');
    const closeReservationBtn = document.getElementById('closeReservationBtn');
    const reservationForm = document.getElementById('reservationForm');

    const contactModal = document.getElementById('contactModal');
    const closeContactBtn = document.getElementById('closeContactBtn');
    const contactForm = document.getElementById('contactForm');

    const quickViewModal = document.getElementById('quickViewModal');
    const closeQuickViewBtn = document.getElementById('closeQuickViewBtn');
    const qvImage = document.getElementById('qvImage');
    const qvCategory = document.getElementById('qvCategory');
    const qvTitle = document.getElementById('qvTitle');
    const qvStars = document.getElementById('qvStars');
    const qvPrice = document.getElementById('qvPrice');
    const qvDesc = document.getElementById('qvDesc');
    const qvQtyVal = document.getElementById('qvQtyVal');
    const qvQtyMinus = document.getElementById('qvQtyMinus');
    const qvQtyPlus = document.getElementById('qvQtyPlus');
    const qvBtnPrice = document.getElementById('qvBtnPrice');
    const qvAddToCartBtn = document.getElementById('qvAddToCartBtn');

    // Navigation & Buttons
    const menuToggle = document.getElementById('menuToggle');
    const navigation = document.getElementById('navigation');
    const headerOrderBtn = document.getElementById('headerOrderBtn');
    const heroOrderBtn = document.getElementById('heroOrderBtn');
    const heroViewMenuBtn = document.getElementById('heroViewMenuBtn');
    const featuredOrderBtn = document.getElementById('featuredOrderBtn');
    const featuredQuickViewBtn = document.getElementById('featuredQuickViewBtn');
    const storyReservationBtn = document.getElementById('storyReservationBtn');
    const footerReserveLink = document.getElementById('footerReserveLink');
    const serviceDineInBtn = document.getElementById('serviceDineInBtn');
    const serviceTakeOutBtn = document.getElementById('serviceTakeOutBtn');
    const serviceDeliveryBtn = document.getElementById('serviceDeliveryBtn');
    const contactUsBtn = document.getElementById('contactUsBtn');

    // User Profile & Admin Controls (currentUser already declared above from BARTOCES_SESSION)
    const adminTopBar = document.getElementById('adminTopBar');
    const adminUserPill = document.getElementById('adminUserPill');
    const adminOrderCounter = document.getElementById('adminOrderCounter');
    const adminResCounter = document.getElementById('adminResCounter');
    const userNameDisplay = document.getElementById('userNameDisplay');
    const userRoleLabel = document.getElementById('userRoleLabel');
    const userRoleIcon = document.getElementById('userRoleIcon');
    const userAvatarWrap = document.querySelector('.user-avatar-wrap');
    const headerLogoutBtn = document.getElementById('headerLogoutBtn');

    // Load real admin stats from MySQL API
    async function updateAdminMetrics() {
        const orderEl = document.getElementById('adminOrderCounter');
        const resEl = document.getElementById('adminResCounter');
        if (!orderEl && !resEl) return;
        try {
            const resp = await fetch('api/admin_stats.php');
            if (resp.ok) {
                const data = await resp.json();
                if (data.success) {
                    if (orderEl) orderEl.textContent = data.order_count;
                    if (resEl) resEl.textContent = data.reservation_count;
                }
            }
        } catch (e) {
            console.warn('Admin stats fetch failed:', e);
        }
    }

    if (currentUser) {
        if (userRoleLabel) {
            if (currentUser.role === 'admin') {
                userRoleLabel.classList.add('admin-label');
            }
        }
        if (currentUser.role === 'admin') {
            if (userRoleIcon) userRoleIcon.className = 'fa-solid fa-crown';
            if (userAvatarWrap) userAvatarWrap.classList.add('admin-avatar');
            // Load admin stats from MySQL
            updateAdminMetrics();
        }

        // Auto-fill customer checkout details from session
        const custNameInput = document.getElementById('custName');
        const custPhoneInput = document.getElementById('custPhone');
        if (custNameInput && !custNameInput.value) custNameInput.value = currentUser.name;
        if (custPhoneInput && !custPhoneInput.value && currentUser.phone) custPhoneInput.value = currentUser.phone;

        // Auto-fill reservation details
        const resNameInput = document.getElementById('resName');
        const resPhoneInput = document.getElementById('resPhone');
        if (resNameInput && !resNameInput.value) resNameInput.value = currentUser.name;
        if (resPhoneInput && !resPhoneInput.value && currentUser.phone) resPhoneInput.value = currentUser.phone;
    }

    // Logout link is now an <a href="logout.php"> — no JS needed


    // ==========================================================
    // 4. TOAST NOTIFICATION UTILITY
    // ==========================================================
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'toast';

        let icon = 'fa-solid fa-check-circle';
        if (type === 'cart') icon = 'fa-solid fa-bag-shopping';
        if (type === 'error') icon = 'fa-solid fa-triangle-exclamation';

        toast.innerHTML = `
            <i class="${icon} toast-icon"></i>
            <span class="toast-message">${message}</span>
        `;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('toast-exit');
            toast.addEventListener('animationend', () => {
                toast.remove();
            });
        }, 3200);
    }

    // Helper: Require user authentication before ordering or reserving (immediate redirect)
    function requireLogin() {
        if (!currentUser) {
            window.location.href = 'login.php';
            return false;
        }
        return true;
    }


    // ==========================================================
    // 5. MODAL & DRAWER OPEN / CLOSE HELPERS
    // ==========================================================
    function openModal(modalEl) {
        modalBackdrop.classList.add('active');
        modalEl.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalEl) {
        modalEl.classList.remove('active');
        // Check if any other modal is active
        const anyActive = document.querySelector('.custom-modal.active, .cart-drawer.active');
        if (!anyActive) {
            modalBackdrop.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    function closeAllModals() {
        document.querySelectorAll('.custom-modal').forEach(m => m.classList.remove('active'));
        cartDrawer.classList.remove('active');
        modalBackdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    modalBackdrop.addEventListener('click', closeAllModals);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });


    // ==========================================================
    // 6. CART MANAGEMENT LOGIC
    // ==========================================================
    function saveCart() {
        try {
            localStorage.setItem('bartoces_cart_2026', JSON.stringify(cart));
        } catch (e) {
            console.error("Storage error", e);
        }
        updateCartBadge();
        renderCart();
    }

    function updateCartBadge() {
        const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartBadge.textContent = totalCount;

        // Trigger bump animation
        cartBadge.classList.remove('bump');
        void cartBadge.offsetWidth;
        cartBadge.classList.add('bump');
    }

    async function addItemToCart(dish, options = [], quantity = 1) {
        if (!requireLogin()) {
            return false;
        }

        // Check stock availability first
        try {
            const response = await fetch(`api/admin_inventory.php`);
            const data = await response.json();

            if (data.success && data.data) {
                const item = data.data.find(i => parseInt(i.id) === parseInt(dish.id));
                if (item && parseInt(item.stock) <= 0) {
                    showToast(`Sorry, ${dish.name} is currently out of stock`, "error");
                    return false;
                }

                if (item && parseInt(item.stock) < quantity) {
                    showToast(`Only ${item.stock} ${dish.name}(s) left in stock`, "error");
                    return false;
                }
            }
        } catch (error) {
            console.warn('Could not check stock:', error);
            // Continue with adding to cart if stock check fails
        }

        // Calculate add-ons price
        const addonsPrice = options.reduce((sum, opt) => sum + opt.price, 0);
        const unitPrice = dish.price + addonsPrice;

        // Create unique signature based on dish ID and sorted options
        const optKey = options.map(o => o.name).sort().join('|');
        const existingIndex = cart.findIndex(ci => ci.id === dish.id && ci.optKey === optKey);

        if (existingIndex > -1) {
            cart[existingIndex].quantity += quantity;
        } else {
            cart.push({
                id: dish.id,
                name: dish.name,
                unitPrice: unitPrice,
                quantity: quantity,
                image: dish.image,
                options: options,
                optKey: optKey
            });
        }

        saveCart();
        showToast(`Added ${dish.name} to order!`, 'cart');
        return true;
    }

    function updateItemQuantity(index, delta) {
        if (cart[index]) {
            cart[index].quantity += delta;
            if (cart[index].quantity <= 0) {
                const removedName = cart[index].name;
                cart.splice(index, 1);
                showToast(`Removed ${removedName} from order.`);
            }
            saveCart();
        }
    }

    function removeCartItem(index) {
        if (cart[index]) {
            const removedName = cart[index].name;
            cart.splice(index, 1);
            saveCart();
            showToast(`Removed ${removedName} from order.`);
        }
    }

    function renderCart() {
        const totalItemsCount = cart.reduce((sum, i) => sum + i.quantity, 0);
        drawerItemCount.textContent = `(${totalItemsCount} items)`;

        if (cart.length === 0) {
            cartItemsList.innerHTML = `
                <div class="empty-cart-state">
                    <i class="fa-solid fa-utensils empty-cart-icon"></i>
                    <p>Your order basket is currently empty.</p>
                    <button class="btn-start-order" id="btnStartOrder">
                        Explore Our Menu
                    </button>
                </div>
            `;

            const btnStartOrder = document.getElementById('btnStartOrder');
            if (btnStartOrder) {
                btnStartOrder.addEventListener('click', () => {
                    closeModal(cartDrawer);
                    document.getElementById('menu').scrollIntoView({ behavior: 'smooth' });
                });
            }

            billSubtotal.textContent = "₱0";
            billTotal.textContent = "₱0";
            deliveryFeeRow.style.display = 'none';
            return;
        }

        // Render cart items
        let html = '';
        let subtotal = 0;

        cart.forEach((item, index) => {
            const itemTotal = item.unitPrice * item.quantity;
            subtotal += itemTotal;

            const optionsHtml = item.options && item.options.length > 0
                ? `<div class="cart-item-options">${item.options.map(o => `+ ${o.name}`).join(', ')}</div>`
                : '';

            html += `
                <div class="cart-item">
                    <img src="${item.image}" alt="${item.name}" class="cart-item-img">
                    <div class="cart-item-info">
                        <div>
                            <h4 class="cart-item-title">${item.name}</h4>
                            ${optionsHtml}
                        </div>
                        <div class="cart-item-bottom">
                            <span class="cart-item-price">₱${itemTotal.toLocaleString()}</span>
                            <div class="cart-qty-ctrl">
                                <button type="button" class="cart-qty-btn" data-action="dec" data-index="${index}" aria-label="Decrease quantity">-</button>
                                <span class="cart-qty-val">${item.quantity}</span>
                                <button type="button" class="cart-qty-btn" data-action="inc" data-index="${index}" aria-label="Increase quantity">+</button>
                            </div>
                        </div>
                    </div>
                    <button class="cart-item-remove" data-action="remove" data-index="${index}" aria-label="Remove item">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            `;
        });

        cartItemsList.innerHTML = html;

        // Delivery calculation
        let deliveryFee = 0;
        if (currentOrderType === 'delivery') {
            deliveryFee = 49;
            deliveryFeeRow.style.display = 'flex';
            billDeliveryFee.textContent = `₱${deliveryFee}`;
        } else {
            deliveryFeeRow.style.display = 'none';
        }

        const grandTotal = subtotal + deliveryFee;
        billSubtotal.textContent = `₱${subtotal.toLocaleString()}`;
        billTotal.textContent = `₱${grandTotal.toLocaleString()}`;

        // Bind item actions
        cartItemsList.querySelectorAll('.cart-qty-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idx = parseInt(btn.getAttribute('data-index'), 10);
                const action = btn.getAttribute('data-action');
                updateItemQuantity(idx, action === 'inc' ? 1 : -1);
            });
        });

        cartItemsList.querySelectorAll('.cart-item-remove').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.getAttribute('data-index'), 10);
                removeCartItem(idx);
            });
        });
    }

    // Set Order Type (Dine In / Take Out / Delivery)
    function setOrderType(type) {
        currentOrderType = type;
        orderTypeTabs.forEach(t => {
            t.classList.toggle('active', t.getAttribute('data-type') === type);
        });

        if (type === 'delivery') {
            deliveryAddressGroup.style.display = 'block';
            custAddress.required = true;
            tableNumberGroup.style.display = 'none';
            custTable.required = false;
        } else if (type === 'dinein') {
            deliveryAddressGroup.style.display = 'none';
            custAddress.required = false;
            tableNumberGroup.style.display = 'block';
        } else { // takeout
            deliveryAddressGroup.style.display = 'none';
            custAddress.required = false;
            tableNumberGroup.style.display = 'none';
            custTable.required = false;
        }

        renderCart();
    }

    orderTypeTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            setOrderType(tab.getAttribute('data-type'));
        });
    });


    // ==========================================================
    // 7. CART DRAWER TRIGGERS
    // ==========================================================
    cartBtn.addEventListener('click', (e) => {
        if (!requireLogin()) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        openModal(cartDrawer);
    });

    closeCartBtn.addEventListener('click', () => {
        closeModal(cartDrawer);
    });

    headerOrderBtn.addEventListener('click', (e) => {
        if (!requireLogin()) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        openModal(cartDrawer);
    });

    heroOrderBtn.addEventListener('click', (e) => {
        if (!requireLogin()) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        openModal(cartDrawer);
    });

    heroViewMenuBtn.addEventListener('click', () => {
        document.getElementById('menu').scrollIntoView({ behavior: 'smooth' });
    });


    // ==========================================================
    // 8. CARD ACTIONS (ADD TO ORDER & QUICK VIEW)
    // ==========================================================
    // Delegate clicks for product cards and menu cards
    document.addEventListener('click', (e) => {
        const target = e.target.closest('[data-action]');
        if (!target) return;

        const action = target.getAttribute('data-action');
        const card = target.closest('[data-id]');
        const dishId = card ? parseInt(card.getAttribute('data-id'), 10) : null;
        const dish = menuDatabase[dishId];

        if (action === 'add-to-cart') {
            if (!requireLogin()) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            if (dish) addItemToCart(dish);
        } else if (action === 'quickview' && dish) {
            openQuickView(dish);
        } else if (action === 'order-featured') {
            if (!requireLogin()) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            const featuredDish = menuDatabase[8];
            if (featuredDish) {
                addItemToCart(featuredDish);
                openModal(cartDrawer);
            }
        } else if (action === 'quickview-featured') {
            const featuredDish = menuDatabase[8];
            if (featuredDish) {
                openQuickView(featuredDish);
            }
        }
    });


    // ==========================================================
    // 9. QUICK VIEW MODAL LOGIC
    // ==========================================================
    function openQuickView(dish) {
        selectedQuickViewItem = dish;
        quickViewQty = 1;

        qvImage.src = dish.image;
        qvCategory.textContent = dish.category;
        qvTitle.textContent = dish.name;
        qvStars.textContent = dish.stars;
        qvPrice.textContent = `₱${dish.price}`;
        qvDesc.textContent = dish.desc;
        qvQtyVal.textContent = "1";

        // Reset check boxes
        document.getElementById('optExtraRice').checked = false;
        document.getElementById('optExtraSauce').checked = false;
        document.getElementById('optExtraCheese').checked = false;

        updateQuickViewPrice();
        openModal(quickViewModal);
    }

    function updateQuickViewPrice() {
        if (!selectedQuickViewItem) return;

        let total = selectedQuickViewItem.price;
        if (document.getElementById('optExtraRice').checked) total += 25;
        if (document.getElementById('optExtraSauce').checked) total += 15;
        if (document.getElementById('optExtraCheese').checked) total += 35;

        const grandTotal = total * quickViewQty;
        qvBtnPrice.textContent = `₱${grandTotal.toLocaleString()}`;
    }

    qvQtyMinus.addEventListener('click', () => {
        if (quickViewQty > 1) {
            quickViewQty--;
            qvQtyVal.textContent = quickViewQty;
            updateQuickViewPrice();
        }
    });

    qvQtyPlus.addEventListener('click', () => {
        quickViewQty++;
        qvQtyVal.textContent = quickViewQty;
        updateQuickViewPrice();
    });

    ['optExtraRice', 'optExtraSauce', 'optExtraCheese'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', updateQuickViewPrice);
        }
    });

    qvAddToCartBtn.addEventListener('click', (e) => {
        if (!requireLogin()) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        if (!selectedQuickViewItem) return;

        const options = [];
        if (document.getElementById('optExtraRice').checked) {
            options.push({ name: 'Extra Garlic Rice', price: 25 });
        }
        if (document.getElementById('optExtraSauce').checked) {
            options.push({ name: 'Signature Dipping Sauce', price: 15 });
        }
        if (document.getElementById('optExtraCheese').checked) {
            options.push({ name: 'Melted Cheese Topping', price: 35 });
        }

        addItemToCart(selectedQuickViewItem, options, quickViewQty);
        closeModal(quickViewModal);
    });

    closeQuickViewBtn.addEventListener('click', () => {
        closeModal(quickViewModal);
    });


    // ==========================================================
    // 10. CHECKOUT FORM SUBMISSION & RECEIPT MODAL
    // ==========================================================
    checkoutForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!requireLogin()) return;

        if (cart.length === 0) {
            showToast("Your cart is empty. Please add delicious items first!", "error");
            return;
        }

        const name = document.getElementById('custName').value.trim();
        const phone = document.getElementById('custPhone').value.trim();
        const notes = document.getElementById('orderNotes').value.trim();
        const address = custAddress.value.trim();
        const table = custTable.value.trim();
        const payment = document.querySelector('input[name="paymentMethod"]:checked').value;

        if (currentOrderType === 'delivery' && !address) {
            showToast("Please enter your delivery street address.", "error");
            custAddress.focus();
            return;
        }

        const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ', ' + new Date().toLocaleDateString();
        let subtotal = cart.reduce((sum, i) => sum + (i.unitPrice * i.quantity), 0);
        let deliveryFee = currentOrderType === 'delivery' ? 49 : 0;
        let total = subtotal + deliveryFee;

        // Disable submit button while posting
        const submitBtn = document.getElementById('btnConfirmOrder');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Placing Order...'; }

        let orderNumber = `BT-${Date.now()}`; // fallback

        try {
            const payload = {
                customer_name: name,
                customer_phone: phone,
                order_type: currentOrderType,
                table_number: table,
                delivery_address: address,
                payment_method: payment,
                special_notes: notes,
                subtotal: subtotal,
                delivery_fee: deliveryFee,
                total_amount: total,
                items: cart.map(item => ({
                    id: item.id,
                    name: item.name,
                    price: item.unitPrice,
                    qty: item.quantity,
                    addons: item.options ? item.options.map(o => o.name).join(', ') : ''
                }))
            };

            const resp = await fetch('api/place_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await resp.json();
            if (result.success) {
                orderNumber = result.order_number.replace('#', '');
            }
        } catch (err) {
            console.warn('Order API error (continuing with local receipt):', err);
        }

        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'PLACE ORDER NOW'; }

        // Render Receipt Modal Content
        receiptOrderNum.textContent = `#${orderNumber}`;

        let itemsSummaryHtml = '';
        cart.forEach(item => {
            itemsSummaryHtml += `
                <div class="receipt-item-line">
                    <span>${item.quantity}x ${item.name}</span>
                    <span>\u20B1${(item.unitPrice * item.quantity).toLocaleString()}</span>
                </div>
            `;
        });

        let typeDetails = '';
        if (currentOrderType === 'dinein') {
            typeDetails = `Dine In ${table ? `(Table #${table})` : '(Seated)'}`;
        } else if (currentOrderType === 'takeout') {
            typeDetails = 'Pick-Up / Take Out';
        } else {
            typeDetails = `Delivery to: ${address}`;
        }

        let etaText = currentOrderType === 'delivery' ? 'Estimated Delivery: 30 - 45 mins' : 'Estimated Preparation: 20 - 25 mins';

        receiptBody.innerHTML = `
            <div class="receipt-row">
                <span class="text-muted">Customer:</span>
                <strong>${name} (${phone})</strong>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Order Type:</span>
                <strong>${typeDetails}</strong>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Payment:</span>
                <strong>${payment}</strong>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Time:</span>
                <span>${timestamp}</span>
            </div>
            ${notes ? `<div class="receipt-row"><span class="text-muted">Notes:</span><em>${notes}</em></div>` : ''}

            <div class="receipt-items-list">
                <strong style="display:block;margin-bottom:6px;">Order Summary:</strong>
                ${itemsSummaryHtml}
            </div>

            <div class="receipt-row">
                <span>Subtotal:</span>
                <span>\u20B1${subtotal.toLocaleString()}</span>
            </div>
            ${deliveryFee > 0 ? `
            <div class="receipt-row">
                <span>Delivery Fee:</span>
                <span>\u20B1${deliveryFee}</span>
            </div>` : ''}
            <div class="receipt-row receipt-total">
                <span>Total Paid:</span>
                <span>\u20B1${total.toLocaleString()}</span>
            </div>

            <div class="receipt-eta">
                <i class="fa-solid fa-clock"></i>
                <span>${etaText}</span>
            </div>
        `;

        // Empty Cart & Close Drawer
        cart = [];
        saveCart();
        closeModal(cartDrawer);
        checkoutForm.reset();

        // Refresh admin metrics
        if (currentUser && currentUser.role === 'admin') updateAdminMetrics();

        // Open Receipt Modal & show notification
        openModal(receiptModal);
        showToast(`Order #${orderNumber} placed successfully!`, 'cart');
    });

    closeReceiptBtn.addEventListener('click', () => {
        closeModal(receiptModal);
    });

    btnDoneOrder.addEventListener('click', () => {
        closeModal(receiptModal);
    });

    btnPrintReceipt.addEventListener('click', () => {
        window.print();
    });


    // ==========================================================
    // 11. TABLE RESERVATION MODAL & ACTIONS
    // ==========================================================
    function openReservation() {
        if (!requireLogin()) return;

        // Pre-fill today's date + 1 day
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const yyyy = tomorrow.getFullYear();
        const mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const dd = String(tomorrow.getDate()).padStart(2, '0');
        const dateInput = document.getElementById('resDate');
        if (dateInput) {
            dateInput.min = `${yyyy}-${mm}-${dd}`;
            dateInput.value = `${yyyy}-${mm}-${dd}`;
        }
        openModal(reservationModal);
    }

    storyReservationBtn.addEventListener('click', openReservation);
    footerReserveLink.addEventListener('click', openReservation);

    serviceDineInBtn.addEventListener('click', () => {
        openReservation();
    });

    serviceTakeOutBtn.addEventListener('click', (e) => {
        if (!requireLogin()) {
            e.preventDefault();
            return;
        }
        setOrderType('takeout');
        openModal(cartDrawer);
    });

    serviceDeliveryBtn.addEventListener('click', (e) => {
        if (!requireLogin()) {
            e.preventDefault();
            return;
        }
        setOrderType('delivery');
        openModal(cartDrawer);
    });

    closeReservationBtn.addEventListener('click', () => {
        closeModal(reservationModal);
    });

    reservationForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('resName').value.trim();
        const phone = document.getElementById('resPhone').value.trim();
        const date = document.getElementById('resDate').value;
        const time = document.getElementById('resTime').value;
        const guests = document.getElementById('resGuests').value;
        const seating = document.getElementById('resSeating').value;
        const notes = document.getElementById('resNotes').value.trim();

        try {
            await fetch('api/save_reservation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, phone, date, time, guests, seating, notes })
            });
        } catch (err) {
            console.warn('Reservation API error:', err);
        }

        closeModal(reservationModal);
        reservationForm.reset();

        // Refresh admin metrics
        if (currentUser && currentUser.role === 'admin') updateAdminMetrics();

        showToast(`Table confirmed for ${name}! (${guests} on ${date} at ${time})`);
    });


    // ==========================================================
    // 12. CONTACT US MODAL
    // ==========================================================
    if (contactUsBtn) {
        contactUsBtn.addEventListener('click', () => {
            openModal(contactModal);
        });
    }

    closeContactBtn.addEventListener('click', () => {
        closeModal(contactModal);
    });

    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('contactName').value.trim();
        const email = document.getElementById('contactEmail').value.trim();
        const subject = document.getElementById('contactSubject').value.trim();
        const message = document.getElementById('contactMessage').value.trim();

        try {
            await fetch('api/save_contact.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, subject, message })
            });
        } catch (err) {
            console.warn('Contact API error:', err);
        }

        closeModal(contactModal);
        contactForm.reset();
        showToast(`Thank you, ${name}! Your message has been sent to our dining staff.`);
    });


    // ==========================================================
    // 13. MENU CATEGORY FILTER TABS
    // ==========================================================
    const filterPills = document.querySelectorAll('.filter-pill');
    const allDishCards = document.querySelectorAll('.product-card, .menu-card');

    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            filterPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');

            const filter = pill.getAttribute('data-filter');

            allDishCards.forEach(card => {
                const category = card.getAttribute('data-category');
                if (filter === 'all' || category === filter) {
                    card.style.display = '';
                    card.style.animation = 'fadeIn 0.35s ease';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });


    // ==========================================================
    // 14. FOOTER MENU TARGET LINKS
    // ==========================================================
    const footerMenuButtons = document.querySelectorAll('[data-menu-target]');
    footerMenuButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-menu-target');
            const targetCard = document.querySelector(`[data-id="${targetId}"]`);
            if (targetCard) {
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Flash highlight effect
                targetCard.style.outline = '3px solid var(--accent-gold)';
                setTimeout(() => {
                    targetCard.style.outline = '';
                }, 1600);
            } else {
                document.getElementById('menu').scrollIntoView({ behavior: 'smooth' });
            }
        });
    });


    // ==========================================================
    // 15. ANIMATED STATISTICS COUNTER
    // ==========================================================
    const counters = document.querySelectorAll('.counter');
    let counted = false;

    function startCounters() {
        counters.forEach(counter => {
            const target = parseFloat(counter.getAttribute('data-target'));
            const isDecimal = counter.getAttribute('data-decimal');
            const duration = 1800; // ms
            const startTime = performance.now();

            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // Ease out quad
                const eased = 1 - (1 - progress) * (1 - progress);
                const currentVal = target * eased;

                if (isDecimal) {
                    counter.textContent = currentVal.toFixed(1);
                } else {
                    counter.textContent = Math.floor(currentVal).toLocaleString();
                }

                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    if (isDecimal) {
                        counter.textContent = target.toFixed(1);
                    } else {
                        counter.textContent = target.toLocaleString();
                    }
                }
            }

            requestAnimationFrame(update);
        });
    }

    const statsSection = document.getElementById('statistics');
    if (statsSection && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !counted) {
                    counted = true;
                    startCounters();
                }
            });
        }, { threshold: 0.3 });
        observer.observe(statsSection);
    } else {
        startCounters();
    }


    // ==========================================================
    // 16. MOBILE NAVIGATION TOGGLE & SCROLLSPY
    // ==========================================================
    menuToggle.addEventListener('click', () => {
        navigation.classList.toggle('active');
        const icon = document.getElementById('menuToggleIcon');
        if (navigation.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-xmark');
        } else {
            icon.classList.remove('fa-xmark');
            icon.classList.add('fa-bars');
        }
    });

    // Close mobile nav when link is clicked
    document.querySelectorAll('.navigation .nav-link').forEach(link => {
        link.addEventListener('click', () => {
            navigation.classList.remove('active');
            const icon = document.getElementById('menuToggleIcon');
            if (icon) {
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            }
        });
    });

    // Scrollspy for active nav link
    const sections = document.querySelectorAll('section[id], footer[id]');
    const navLinks = document.querySelectorAll('.navigation .nav-link');

    window.addEventListener('scroll', () => {
        let current = '';
        const scrollPos = window.scrollY + 120;

        sections.forEach(sec => {
            const top = sec.offsetTop;
            const height = sec.offsetHeight;
            if (scrollPos >= top && scrollPos < top + height) {
                current = sec.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });


    // ==========================================================
    // 17. INITIALIZATION
    // ==========================================================
    updateCartBadge();
    renderCart();

});
