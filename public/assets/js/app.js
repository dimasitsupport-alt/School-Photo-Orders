(function () {
    'use strict';

    const transparentPixel = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
    let previewPhotoSrc = '';

    document.addEventListener('DOMContentLoaded', function () {
        setupNavToggles();
        setupAlertDismiss();
        setupUploadRows();
        setupAdminSearch();
        setupLazyImages();
        setupPhotoActions();
    });

    function setupNavToggles() {
        document.querySelectorAll('[data-nav-toggle]').forEach(function (button) {
            const selector = button.dataset.target;

            if (!selector) {
                return;
            }

            const target = document.querySelector(selector);

            if (!target) {
                return;
            }

            button.addEventListener('click', function () {
                const isOpen = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', String(!isOpen));
                target.classList.toggle('is-open', !isOpen);
            });
        });
    }

    function setupAlertDismiss() {
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-dismiss-alert]');

            if (!button) {
                return;
            }

            const alert = button.closest('.alert');

            if (alert) {
                alert.remove();
            }
        });
    }

    function setupUploadRows() {
        document.addEventListener('click', function (event) {
            const addButton = event.target.closest('[data-add-row]');
            const removeButton = event.target.closest('[data-remove-row]');

            if (addButton) {
                const rows = document.querySelector('[data-upload-rows]');
                const firstRow = rows ? rows.querySelector('[data-upload-row]') : null;

                if (!rows || !firstRow) {
                    return;
                }

                const clone = firstRow.cloneNode(true);
                clone.querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                });
                rows.appendChild(clone);
            }

            if (removeButton) {
                const row = removeButton.closest('[data-upload-row]');
                const rows = document.querySelectorAll('[data-upload-row]');

                if (row && rows.length > 1) {
                    row.remove();
                } else if (row) {
                    row.querySelectorAll('input').forEach(function (input) {
                        input.value = '';
                    });
                }
            }
        });
    }

    function setupAdminSearch() {
        const form = document.querySelector('[data-admin-search]');

        if (!form) {
            return;
        }

        const input = form.querySelector('input[name="order_no"]');
        const endpoint = form.dataset.endpoint;
        const results = document.querySelector('[data-search-results]');
        const status = document.querySelector('[data-search-status]');
        let timer = null;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            runSearch();
        });

        input.addEventListener('input', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(runSearch, 300);
        });

        function setStatus(message) {
            if (status) {
                status.textContent = message;
            }
        }

        function runSearch() {
            const query = input.value.trim();

            clearResults();

            if (query === '') {
                setStatus('Masukkan nomor pesanan untuk mencari.');
                return;
            }

            setStatus('Mencari...');

            fetch(endpoint + '?order_no=' + encodeURIComponent(query), {
                headers: {
                    Accept: 'application/json'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Search request failed');
                    }

                    return response.json();
                })
                .then(function (payload) {
                    renderSearchResults(payload.orders || []);
                })
                .catch(function () {
                    setStatus('Pencarian gagal. Coba lagi.');
                });
        }

        function clearResults() {
            if (results) {
                results.replaceChildren();
            }
        }

        function renderSearchResults(orders) {
            clearResults();

            if (!results) {
                return;
            }

            if (orders.length === 0) {
                setStatus('Pesanan tidak ditemukan.');
                return;
            }

            setStatus(orders.length + ' pesanan ditemukan.');

            orders.forEach(function (order) {
                const column = document.createElement('div');
                column.className = 'col-12 col-md-6 col-xl-4';

                const card = document.createElement('a');
                card.className = 'order-result';
                card.href = order.detail_url;

                const logo = document.createElement('img');
                logo.src = order.logo_url || transparentPixel;
                logo.alt = 'Logo ' + order.school_name;
                logo.loading = 'lazy';
                logo.decoding = 'async';

                const body = document.createElement('div');
                body.className = 'min-w-0';

                const title = document.createElement('h2');
                title.textContent = order.school_name;

                const address = document.createElement('p');
                address.textContent = order.school_address;

                const meta = document.createElement('div');
                meta.className = 'result-meta';

                const orderNo = createPill('Order', order.order_no);
                const photoCount = createPill('Foto', order.photo_count);

                meta.appendChild(orderNo);
                meta.appendChild(photoCount);
                body.appendChild(title);
                body.appendChild(address);
                body.appendChild(meta);
                card.appendChild(logo);
                card.appendChild(body);
                column.appendChild(card);
                results.appendChild(column);
            });
        }
    }

    function createPill(label, text) {
        const pill = document.createElement('span');
        pill.className = 'result-pill';
        pill.textContent = label + ': ' + text;

        return pill;
    }

    function setupLazyImages() {
        const lazyImages = Array.from(document.querySelectorAll('img[data-src]'));

        if (lazyImages.length === 0) {
            return;
        }

        const loadImage = function (image) {
            image.addEventListener('load', function () {
                image.classList.add('is-loaded');
            }, { once: true });
            image.decoding = 'async';
            image.src = image.dataset.src;
            image.removeAttribute('data-src');
        };

        if (!('IntersectionObserver' in window)) {
            lazyImages.forEach(loadImage);
            return;
        }

        const observer = new IntersectionObserver(function (entries, currentObserver) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                loadImage(entry.target);
                currentObserver.unobserve(entry.target);
            });
        }, {
            rootMargin: '240px 0px',
            threshold: 0.01
        });

        lazyImages.forEach(function (image) {
            observer.observe(image);
        });
    }

    function setupPhotoActions() {
        const modalElement = document.getElementById('photoPreviewModal');
        const previewImage = document.getElementById('previewImage');
        const previewName = document.getElementById('previewStudentName');
        const modalCopyButton = document.querySelector('[data-modal-copy]');
        const modal = modalElement ? createModalController(modalElement) : null;

        document.addEventListener('click', function (event) {
            const previewButton = event.target.closest('[data-preview-photo]');
            const copyButton = event.target.closest('[data-copy-photo]');

            if (copyButton) {
                event.preventDefault();
                copyImage(copyButton.dataset.photoSrc);
                return;
            }

            if (previewButton && modal && previewImage && previewName) {
                previewPhotoSrc = previewButton.dataset.photoSrc;
                const studentName = previewButton.dataset.studentName || 'Foto siswa';
                previewImage.src = previewPhotoSrc;
                previewImage.alt = 'Foto ' + studentName;
                previewName.textContent = studentName;
                modal.show();
            }
        });

        if (modalCopyButton) {
            modalCopyButton.addEventListener('click', function () {
                if (previewPhotoSrc) {
                    copyImage(previewPhotoSrc);
                }
            });
        }
    }

    function createModalController(modalElement) {
        let isOpen = false;

        const show = function () {
            isOpen = true;
            modalElement.hidden = false;
            modalElement.removeAttribute('aria-hidden');
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
        };

        const hide = function () {
            if (!isOpen) {
                return;
            }

            isOpen = false;
            modalElement.classList.remove('show');
            modalElement.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');

            window.setTimeout(function () {
                if (!isOpen) {
                    modalElement.hidden = true;
                }
            }, 120);
        };

        modalElement.querySelectorAll('[data-modal-close]').forEach(function (button) {
            button.addEventListener('click', hide);
        });

        modalElement.addEventListener('click', function (event) {
            if (event.target === modalElement) {
                hide();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen) {
                hide();
            }
        });

        return {
            show: show,
            hide: hide
        };
    }

    async function copyImage(url) {
        if (!url) {
            showToast('File foto tidak ditemukan.', 'danger');
            return;
        }

        if (!navigator.clipboard || !window.ClipboardItem) {
            showToast('Clipboard API tidak tersedia pada browser ini.', 'danger');
            return;
        }

        try {
            const response = await fetch(url, { cache: 'no-store' });

            if (!response.ok) {
                throw new Error('Image request failed');
            }

            const blob = await response.blob();

            try {
                await writeImageBlob(blob);
            } catch (error) {
                if ((blob.type || '').toLowerCase() === 'image/png') {
                    throw error;
                }

                const pngBlob = await convertImageBlobToPng(blob);
                await writeImageBlob(pngBlob, 'image/png');
            }

            showToast('Gambar berhasil dicopy ke clipboard.', 'success');
        } catch (error) {
            showToast('Gagal copy gambar. Gunakan HTTPS atau localhost.', 'danger');
        }
    }

    function writeImageBlob(blob, forcedType) {
        const type = forcedType || blob.type || 'image/png';

        if (ClipboardItem.supports && !ClipboardItem.supports(type)) {
            throw new Error('Clipboard type is not supported');
        }

        const item = new ClipboardItem({
            [type]: blob
        });

        return navigator.clipboard.write([item]);
    }

    function convertImageBlobToPng(blob) {
        return new Promise(function (resolve, reject) {
            const image = new Image();
            const objectUrl = URL.createObjectURL(blob);

            image.onload = function () {
                const canvas = document.createElement('canvas');
                canvas.width = image.naturalWidth;
                canvas.height = image.naturalHeight;

                const context = canvas.getContext('2d');

                if (!context) {
                    URL.revokeObjectURL(objectUrl);
                    reject(new Error('Canvas context unavailable'));
                    return;
                }

                context.drawImage(image, 0, 0);
                URL.revokeObjectURL(objectUrl);
                canvas.toBlob(function (pngBlob) {
                    if (pngBlob) {
                        resolve(pngBlob);
                    } else {
                        reject(new Error('PNG conversion failed'));
                    }
                }, 'image/png');
            };

            image.onerror = function () {
                URL.revokeObjectURL(objectUrl);
                reject(new Error('Image conversion failed'));
            };

            image.src = objectUrl;
        });
    }

    function showToast(message, type) {
        let container = document.querySelector('.toast-container');

        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'app-toast app-toast-' + type;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        const body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast-close';
        close.setAttribute('aria-label', 'Close');
        close.textContent = 'x';

        toast.appendChild(body);
        toast.appendChild(close);
        container.appendChild(toast);

        const removeToast = function () {
            toast.classList.remove('is-visible');
            window.setTimeout(function () {
                toast.remove();
            }, 140);
        };

        close.addEventListener('click', removeToast);
        window.requestAnimationFrame(function () {
            toast.classList.add('is-visible');
        });
        window.setTimeout(removeToast, 2800);
    }
})();
