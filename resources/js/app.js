//

for (const composer of document.querySelectorAll('[data-photo-composer]')) {
    const input = composer.querySelector('[data-photo-input]');
    const preview = composer.querySelector('[data-photo-preview]');
    const image = preview.querySelector('img');
    const error = composer.querySelector('[data-photo-error]');
    let previewUrl;
    const clearPhoto = () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = undefined;
        input.value = '';
        image.removeAttribute('src');
        preview.hidden = true;
        error.hidden = true;
    };
    composer.querySelector('[data-photo-remove]').addEventListener('click', clearPhoto);
    input.addEventListener('change', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        preview.hidden = true;
        error.hidden = true;
        const file = input.files[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            clearPhoto();
            error.textContent = 'Escolha uma foto JPG, PNG ou WebP de até 5 MB.';
            error.hidden = false;
            return;
        }
        previewUrl = URL.createObjectURL(file);
        image.src = previewUrl;
        preview.hidden = false;
    });
}
