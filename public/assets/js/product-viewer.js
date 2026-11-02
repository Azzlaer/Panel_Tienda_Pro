(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const viewer = document.querySelector('[data-product-viewer]');
        if (!viewer) return;

        const main = viewer.querySelector('[data-viewer-main]');
        const stage = viewer.querySelector('[data-viewer-stage]');
        const counter = viewer.querySelector('[data-viewer-counter]');
        const label = viewer.querySelector('[data-viewer-label]');
        const thumbnails = Array.from(viewer.querySelectorAll('[data-viewer-index]'));
        const lightbox = document.querySelector('[data-viewer-lightbox]');
        const lightboxImage = lightbox ? lightbox.querySelector('[data-lightbox-image]') : null;
        const lightboxInfo = lightbox ? lightbox.querySelector('[data-lightbox-info]') : null;
        let current = 0;

        function select(index) {
            if (!thumbnails.length) return;
            current = (index + thumbnails.length) % thumbnails.length;
            const thumb = thumbnails[current];
            main.style.opacity = '0';
            window.setTimeout(function () {
                main.src = thumb.dataset.viewerSrc;
                main.alt = thumb.dataset.viewerLabel || 'Imagen del artículo';
                label.textContent = thumb.dataset.viewerLabel || ('Imagen ' + (current + 1));
                counter.textContent = (current + 1) + ' / ' + thumbnails.length;
                thumbnails.forEach(function (item, position) {
                    item.classList.toggle('active', position === current);
                    item.setAttribute('aria-current', position === current ? 'true' : 'false');
                });
                main.style.opacity = '1';
            }, 90);
        }

        thumbnails.forEach(function (thumb, index) {
            thumb.addEventListener('click', function () { select(index); });
        });

        viewer.querySelector('[data-viewer-prev]')?.addEventListener('click', function () { select(current - 1); });
        viewer.querySelector('[data-viewer-next]')?.addEventListener('click', function () { select(current + 1); });

        viewer.querySelectorAll('[data-fit]').forEach(function (button) {
            button.addEventListener('click', function () {
                const fit = button.dataset.fit;
                stage.classList.remove('fit-contain', 'fit-cover', 'fit-fill', 'fit-width', 'fit-height');
                stage.classList.add('fit-' + fit);
                viewer.querySelectorAll('[data-fit]').forEach(function (item) {
                    item.classList.toggle('active', item === button);
                });
            });
        });

        function openLightbox() {
            if (!lightbox || !lightboxImage) return;
            const thumb = thumbnails[current];
            lightboxImage.src = thumb.dataset.viewerSrc;
            lightboxImage.alt = thumb.dataset.viewerLabel || 'Imagen ampliada';
            lightboxInfo.textContent = (thumb.dataset.viewerLabel || 'Imagen') + ' · ' + (current + 1) + ' / ' + thumbnails.length;
            lightbox.hidden = false;
            document.body.classList.add('viewer-open');
        }

        function updateLightbox(index) {
            select(index);
            window.setTimeout(openLightbox, 110);
        }

        viewer.querySelector('[data-viewer-fullscreen]')?.addEventListener('click', openLightbox);
        main.addEventListener('dblclick', openLightbox);

        lightbox?.querySelector('[data-lightbox-close]')?.addEventListener('click', function () {
            lightbox.hidden = true;
            document.body.classList.remove('viewer-open');
        });
        lightbox?.querySelector('[data-lightbox-prev]')?.addEventListener('click', function () { updateLightbox(current - 1); });
        lightbox?.querySelector('[data-lightbox-next]')?.addEventListener('click', function () { updateLightbox(current + 1); });
        lightbox?.addEventListener('click', function (event) {
            if (event.target === lightbox) {
                lightbox.hidden = true;
                document.body.classList.remove('viewer-open');
            }
        });

        document.addEventListener('keydown', function (event) {
            const isOpen = lightbox && !lightbox.hidden;
            if (event.key === 'Escape' && isOpen) {
                lightbox.hidden = true;
                document.body.classList.remove('viewer-open');
            } else if (event.key === 'ArrowLeft') {
                isOpen ? updateLightbox(current - 1) : select(current - 1);
            } else if (event.key === 'ArrowRight') {
                isOpen ? updateLightbox(current + 1) : select(current + 1);
            }
        });

        select(0);
    });
})();
