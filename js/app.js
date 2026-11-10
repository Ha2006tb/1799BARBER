// API Base URL
const API_BASE = 'api/';

// Helper: Hiển thị Toast thông báo
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-icon">${type === 'success' ? '✓' : '✕'}</div>
        <div class="toast-msg">${message}</div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Helper: Gọi API chung
async function apiFetch(endpoint, options = {}) {
    try {
        const res = await fetch(API_BASE + endpoint, options);
        const data = await res.json();
        return data;
    } catch (err) {
        console.error('API Error:', err);
        showToast('Có lỗi xảy ra khi kết nối máy chủ', 'error');
        return { success: false, message: 'Lỗi kết nối' };
    }
}

// Helper: Định dạng tiền tệ VND
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// --- QUẢN LÝ TÀI KHOẢN (AUTH) ---
function getCurrentUser() {
    const user = localStorage.getItem('gentle_user');
    return user ? JSON.parse(user) : null;
}

function setCurrentUser(user) {
    if (user) {
        localStorage.setItem('gentle_user', JSON.stringify(user));
    } else {
        localStorage.removeItem('gentle_user');
    }
    updateNavbarAuth();
}

function logout() {
    setCurrentUser(null);
    showToast('Đã đăng xuất thành công');
    setTimeout(() => window.location.href = 'index.html', 1000);
}

function updateNavbarAuth() {
    const user = getCurrentUser();
    const authArea = document.querySelector('.nav-actions');
    if (!authArea) return;

    if (user) {
        authArea.innerHTML = `
            <a href="cart.html" class="cart-icon">
                🛒 <span class="cart-badge" id="cartBadge">0</span>
            </a>
            <a href="dashboard.html" class="btn-outline">👋 ${user.fullname}</a>
            <button onclick="logout()" class="btn-gold">Đăng xuất</button>
        `;
    } else {
        authArea.innerHTML = `
            <a href="cart.html" class="cart-icon">
                🛒 <span class="cart-badge" id="cartBadge">0</span>
            </a>
            <a href="login.html" class="btn-outline">Đăng nhập</a>
            <a href="login.html?signup=1" class="btn-gold">Đăng ký</a>
        `;
    }
    updateCartBadge();
}

// --- QUẢN LÝ GIỎ HÀNG (CART) ---
function getCart() {
    const cart = localStorage.getItem('gentle_cart');
    return cart ? JSON.parse(cart) : [];
}

function saveCart(cart) {
    localStorage.setItem('gentle_cart', JSON.stringify(cart));
    updateCartBadge();
}

function addToCart(product) {
    const cart = getCart();
    const index = cart.findIndex(item => item.product_id === product.id);
    
    if (index > -1) {
        cart[index].quantity += 1;
    } else {
        cart.push({
            product_id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: 1
        });
    }
    
    saveCart(cart);
    showToast('Đã thêm sản phẩm vào giỏ hàng!');
}

function updateCartBadge() {
    const cart = getCart();
    const badge = document.getElementById('cartBadge');
    if (badge) {
        const total = cart.reduce((sum, item) => sum + item.quantity, 0);
        badge.innerText = total;
        badge.style.display = total > 0 ? 'flex' : 'none';
    }
}

// --- MODAL HELPERS ---
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

// Cài đặt chung khi trang tải xong
document.addEventListener('DOMContentLoaded', () => {
    updateNavbarAuth();
    
    // Xử lý nút đóng modal chung
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) modal.classList.remove('active');
        });
    });
});

// --- LIGHTBOX GALLERY FOR LOOKBOOK ---
let currentGalleryIndex = 0;
let galleryItems = [];
let currentSlideIndex = 0;

function openLookbookGallery(index, items) {
    galleryItems = items;
    currentGalleryIndex = index;
    currentSlideIndex = 0;
    
    let modal = document.getElementById('lookbookLightbox');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'lookbookLightbox';
        modal.className = 'lightbox-modal';
        modal.innerHTML = `
            <div class="lightbox-content">
                <span class="lightbox-close" onclick="closeLookbookGallery()">&times;</span>
                <div class="lightbox-grid">
                    <!-- Left: Slideshow -->
                    <div class="lightbox-slider-container">
                        <span class="lightbox-nav-slide prev-slide" onclick="prevSlide()">&lt;</span>
                        <img id="lightboxImg" class="lightbox-img" src="" alt="">
                        <span class="lightbox-nav-slide next-slide" onclick="nextSlide()">&gt;</span>
                        <div class="lightbox-dots" id="lightboxDots"></div>
                    </div>
                    <!-- Right: Article details -->
                    <div class="lightbox-article">
                        <div>
                            <h3 id="lightboxTitle"></h3>
                            <div class="lightbox-meta">
                                <span class="lightbox-tag" id="lightboxFace"></span>
                            </div>
                            <div class="lightbox-body" id="lightboxBody"></div>
                        </div>
                        <div class="lightbox-action">
                            <a id="lightboxBookingBtn" href="" class="btn-gold" style="display: block; text-align: center; margin-top: 20px;">Đặt Lịch Kiểu Này</a>
                        </div>
                    </div>
                </div>
                <!-- Global navigation to next/prev lookbook -->
                <span class="lightbox-global-nav prev-global" onclick="prevGalleryItem()" title="Xem kiểu tóc trước">&#10094;</span>
                <span class="lightbox-global-nav next-global" onclick="nextGalleryItem()" title="Xem kiểu tóc tiếp">&#10095;</span>
            </div>
        `;
        document.body.appendChild(modal);

        // Close on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeLookbookGallery();
            }
        });

        // Close/Nav with keyboard
        document.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('active')) return;
            if (e.key === 'Escape') closeLookbookGallery();
            if (e.key === 'ArrowLeft') prevSlide();
            if (e.key === 'ArrowRight') nextSlide();
            if (e.key === 'PageUp') prevGalleryItem();
            if (e.key === 'PageDown') nextGalleryItem();
        });
    }

    modal.classList.add('active');
    updateGalleryItem();
}

function closeLookbookGallery() {
    const modal = document.getElementById('lookbookLightbox');
    if (modal) modal.classList.remove('active');
}

function prevGalleryItem() {
    if (galleryItems.length === 0) return;
    currentGalleryIndex = (currentGalleryIndex - 1 + galleryItems.length) % galleryItems.length;
    currentSlideIndex = 0;
    updateGalleryItem();
}

function nextGalleryItem() {
    if (galleryItems.length === 0) return;
    currentGalleryIndex = (currentGalleryIndex + 1) % galleryItems.length;
    currentSlideIndex = 0;
    updateGalleryItem();
}

function setSlide(slideIndex) {
    currentSlideIndex = slideIndex;
    const item = galleryItems[currentGalleryIndex];
    if (!item) return;
    
    const slides = [item.image, item.image2, item.image3].filter(img => img && img.trim() !== '');
    if (currentSlideIndex >= slides.length) currentSlideIndex = 0;
    if (currentSlideIndex < 0) currentSlideIndex = slides.length - 1;
    
    const imgEl = document.getElementById('lightboxImg');
    imgEl.style.opacity = 0;
    setTimeout(() => {
        imgEl.src = slides[currentSlideIndex];
        imgEl.style.opacity = 1;
    }, 150);

    const dots = document.querySelectorAll('.lightbox-dot');
    dots.forEach((dot, idx) => {
        if (idx === currentSlideIndex) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

function prevSlide() {
    const item = galleryItems[currentGalleryIndex];
    if (!item) return;
    const slides = [item.image, item.image2, item.image3].filter(img => img && img.trim() !== '');
    setSlide((currentSlideIndex - 1 + slides.length) % slides.length);
}

function nextSlide() {
    const item = galleryItems[currentGalleryIndex];
    if (!item) return;
    const slides = [item.image, item.image2, item.image3].filter(img => img && img.trim() !== '');
    setSlide((currentSlideIndex + 1) % slides.length);
}

function updateGalleryItem() {
    const item = galleryItems[currentGalleryIndex];
    if (!item) return;
    
    document.getElementById('lightboxTitle').innerText = item.title;
    document.getElementById('lightboxFace').innerText = `✓ Phù hợp: ${item.face_shape_suitable}`;
    document.getElementById('lightboxBody').innerText = item.content || item.description;
    document.getElementById('lightboxBookingBtn').href = `booking.html?notes=${encodeURIComponent('Chọn kiểu: ' + item.title)}`;
    
    const slides = [item.image, item.image2, item.image3].filter(img => img && img.trim() !== '');
    
    const dotsContainer = document.getElementById('lightboxDots');
    dotsContainer.innerHTML = slides.map((_, idx) => `
        <div class="lightbox-dot ${idx === currentSlideIndex ? 'active' : ''}" onclick="setSlide(${idx})"></div>
    `).join('');
    
    setSlide(currentSlideIndex);
}

