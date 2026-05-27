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
