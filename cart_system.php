<!-- Cart Modal -->
<div class="modal-overlay" id="cartModal">
    <div class="modal">
        <div class="modal-header">
            <h3>🛒 ตะกร้าสินค้า</h3>
            <button class="modal-close" onclick="closeModal('cartModal')">&times;</button>
        </div>
        <div class="modal-body" id="cartBody">
            <!-- Cart items injected by JS -->
        </div>
        <div class="modal-footer" style="flex-direction:column;gap:12px;">
            <div class="sale-summary" style="width:100%;margin:0;">
                <div class="total-row">
                    <span>ยอดรวม</span>
                    <span id="cartTotal">฿0.00</span>
                </div>
            </div>
            <div style="display:flex;gap:12px;width:100%;justify-content:flex-end;">
                <button class="btn btn-ghost" onclick="clearCart()">🗑️ ล้างตะกร้า</button>
                <button class="btn btn-primary" onclick="checkout()">💳 ชำระเงิน</button>
            </div>
        </div>
    </div>
</div>

<!-- Floating Cart Button -->
<button class="cart-floating" id="cartFloating" onclick="openCartModal()">
    🛒 <span class="cart-badge" id="cartCount">0</span>
</button>

<script>
    // --- Cart System (localStorage) ---
    let cart = JSON.parse(localStorage.getItem('nournia_cart') || '[]');
    
    // Initial UI sync
    document.addEventListener('DOMContentLoaded', () => {
        updateCartUI();
    });

    function addToCart(id, name, price, image) {
        const existing = cart.find(item => item.id === id);
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({ id, name, price, image, qty: 1 });
        }
        saveCart();
        updateCartUI();

        // Animate button if exists (for index.php store cards)
        const btn = document.getElementById('cartBtn-' + id);
        if (btn) {
            const originalText = btn.innerHTML;
            btn.textContent = '✅ เพิ่มแล้ว!';
            btn.style.background = 'var(--success)';
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '';
            }, 1200);
        }
    }

    function removeFromCart(id) {
        cart = cart.filter(item => item.id !== id);
        saveCart();
        updateCartUI();
        renderCartModal();
    }

    function updateQty(id, delta) {
        const item = cart.find(i => i.id === id);
        if (item) {
            item.qty += delta;
            if (item.qty <= 0) {
                removeFromCart(id);
                return;
            }
        }
        saveCart();
        updateCartUI();
        renderCartModal();
    }

    function clearCart() {
        if (!confirm('ยืนยันล้างตะกร้าสินค้าทั้งหมด?')) return;
        cart = [];
        saveCart();
        updateCartUI();
        renderCartModal();
    }

    function saveCart() {
        localStorage.setItem('nournia_cart', JSON.stringify(cart));
    }

    function updateCartUI() {
        const totalItems = cart.reduce((sum, i) => sum + i.qty, 0);
        const floatBtn = document.getElementById('cartFloating');
        const countBadge = document.getElementById('cartCount');
        const navCountBadge = document.getElementById('navCartCount');

        if (totalItems > 0) {
            if (floatBtn) floatBtn.style.display = 'flex';
            if (countBadge) countBadge.textContent = totalItems;
            if (navCountBadge) navCountBadge.textContent = totalItems;
        } else {
            if (floatBtn) floatBtn.style.display = 'none';
            if (navCountBadge) navCountBadge.textContent = '0';
        }
    }

    function openCartModal() {
        renderCartModal();
        document.getElementById('cartModal').classList.add('active');
    }

    function renderCartModal() {
        const body = document.getElementById('cartBody');
        if (!body) return;
        
        if (cart.length === 0) {
            body.innerHTML = `
                <div style="text-align:center;padding:40px 20px;">
                    <div style="font-size:48px;margin-bottom:16px;">🛒</div>
                    <div style="color:var(--text-secondary);">ตะกร้าของคุณยังว่างอยู่</div>
                    <a href="index.php" class="btn btn-sm" style="margin-top:16px;">ไปที่หน้าร้าน</a>
                </div>
            `;
            document.getElementById('cartTotal').textContent = '฿0.00';
            return;
        }

        let total = 0;
        let html = '';
        cart.forEach(item => {
            const lineTotal = item.price * item.qty;
            total += lineTotal;
            html += `
                <div class="cart-item" style="display:flex;align-items:center;padding:12px;border-bottom:1px solid var(--border);gap:12px;">
                    <div style="width:50px;height:50px;border-radius:8px;overflow:hidden;background:var(--bg-tertiary);display:flex;align-items:center;justify-content:center;">
                        ${item.image ? `<img src="${item.image}" style="width:100%;height:100%;object-fit:cover;">` : '<span style="font-size:20px;">🎮</span>'}
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:14px;color:var(--text-primary);">${item.name}</div>
                        <div style="font-size:12px;color:var(--accent);">฿${item.price.toLocaleString()}</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;background:var(--bg-primary);padding:4px;border-radius:6px;">
                        <button class="qty-btn" onclick="updateQty(${item.id}, -1)" style="width:26px;height:26px;background:var(--bg-tertiary);border:none;color:var(--text-primary);border-radius:6px;cursor:pointer;font-weight:700;">−</button>
                        <span style="font-size:14px;min-width:20px;text-align:center;font-weight:600;">${item.qty}</span>
                        <button class="qty-btn" onclick="updateQty(${item.id}, 1)" style="width:26px;height:26px;background:var(--bg-tertiary);border:none;color:var(--text-primary);border-radius:6px;cursor:pointer;font-weight:700;">+</button>
                    </div>
                    <div style="font-weight:700;font-size:14px;min-width:80px;text-align:right;">
                        ฿${lineTotal.toLocaleString()}
                    </div>
                    <button onclick="removeFromCart(${item.id})" style="background:none;border:none;color:var(--danger);cursor:pointer;padding:4px;">✕</button>
                </div>
            `;
        });
        body.innerHTML = html;
        document.getElementById('cartTotal').textContent = '฿' + total.toLocaleString('th-TH', {minimumFractionDigits:2});
    }

    function checkout() {
        if (cart.length === 0) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'sales.php';
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'create_sale';
        form.appendChild(actionInput);
        cart.forEach(item => {
            const pid = document.createElement('input');
            pid.type = 'hidden';
            pid.name = 'product_id[]';
            pid.value = item.id;
            form.appendChild(pid);
            const qty = document.createElement('input');
            qty.type = 'hidden';
            qty.name = 'quantity[]';
            qty.value = item.qty;
            form.appendChild(qty);
        });
        document.body.appendChild(form);
        localStorage.removeItem('nournia_cart');
        form.submit();
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('active');
        }
    });
</script>
