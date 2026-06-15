import './bootstrap';
import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';

// Alpine is bundled by Livewire 3 and started for us — do not import it here
// or it conflicts with Livewire and stops registering components.

function initPhotoswipe() {
    document.querySelectorAll('.pswp-gallery:not([data-pswp-init])').forEach((el) => {
        el.setAttribute('data-pswp-init', '1');
        const lightbox = new PhotoSwipeLightbox({
            gallery: el,
            children: 'a.pswp-link',
            pswpModule: () => import('photoswipe'),
            bgOpacity: 0.92,
            showHideAnimationType: 'fade',
        });
        lightbox.init();
    });
}

document.addEventListener('DOMContentLoaded', initPhotoswipe);
document.addEventListener('livewire:navigated', initPhotoswipe);

// --- Add to cart (AJAX) ----------------------------------------------------
// Progressive enhancement for any `form[data-cart-add]` (product page +
// product cards in grids). Falls back to a normal POST when JS is disabled.
function notify(message, type = 'success') {
    window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
}

document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-cart-add]');
    if (!form) return;
    e.preventDefault();

    // Instant feedback for required options; the server validates too.
    const missing = [];
    form.querySelectorAll('fieldset.cart-option[data-option-required="1"]').forEach((fs) => {
        if (!fs.querySelector('input[type="radio"]:checked')) {
            missing.push(fs.dataset.optionLabel || '');
            fs.classList.add('ring-2', 'ring-red-300', 'ring-offset-2', 'rounded-xl');
            setTimeout(() => fs.classList.remove('ring-2', 'ring-red-300', 'ring-offset-2', 'rounded-xl'), 2000);
        }
    });
    if (missing.length) {
        notify('გთხოვთ აირჩიოთ: ' + missing.filter(Boolean).join(', '), 'error');
        return;
    }

    const btn = form.querySelector('[type="submit"]');
    if (btn) btn.disabled = true;

    fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
    })
        .then(async (res) => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                notify(firstError || data.message || 'დამატება ვერ მოხერხდა', 'error');
                return;
            }
            window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count } }));
            notify(data.message || 'პროდუქცია დაემატა კალათში', 'success');
        })
        .catch(() => notify('ქსელის შეცდომა. სცადეთ თავიდან.', 'error'))
        .finally(() => { if (btn) btn.disabled = false; });
});
