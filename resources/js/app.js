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

for (const feed of document.querySelectorAll('[data-infinite-feed]')) {
    const items = feed.querySelector('[data-feed-items]');
    const sentinel = feed.querySelector('[data-feed-sentinel]');
    const status = feed.querySelector('[data-feed-status]');
    const next = feed.querySelector('[data-feed-next]');
    if (!next) continue;
    let loading = false;
    let automatic = true;
    let observer;

    const loadMore = async () => {
        if (loading || !next.hasAttribute('href')) return;
        const url = new URL(next.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        loading = true;
        feed.setAttribute('aria-busy', 'true');
        next.setAttribute('aria-disabled', 'true');
        status.textContent = 'Carregando publicações…';
        const abort = new AbortController();
        const timeout = setTimeout(() => abort.abort(), 20000);
        try {
            const response = await fetch(url, {
                headers: { 'X-Kanaa-Feed': 'posts', Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
                signal: abort.signal,
            });
            if (!response.ok || response.redirected) throw new Error('Não foi possível carregar.');
            const data = await response.json();
            if (typeof data.html !== 'string' || (data.next !== null && typeof data.next !== 'string')) throw new Error('Resposta inválida.');
            if (data.next && new URL(data.next, url).origin !== window.location.origin) throw new Error('Destino inválido.');
            const template = document.createElement('template');
            template.innerHTML = data.html;
            const posts = [...template.content.children].filter(post => post.matches('article.post-card[id]') && !document.getElementById(post.id));
            items.append(...posts);
            automatic = true;
            if (data.next) {
                next.href = data.next;
                status.textContent = `${posts.length} publicações carregadas.`;
                observer?.unobserve(sentinel);
                requestAnimationFrame(() => observer?.observe(sentinel));
            } else {
                next.removeAttribute('href');
                next.hidden = true;
                status.textContent = 'Você chegou ao fim das publicações.';
                observer?.disconnect();
            }
        } catch {
            automatic = false;
            observer?.disconnect();
            status.textContent = 'Não foi possível carregar as publicações. Use o botão para tentar novamente.';
            next.textContent = 'Tentar novamente';
        } finally {
            clearTimeout(timeout);
            loading = false;
            feed.setAttribute('aria-busy', 'false');
            next.removeAttribute('aria-disabled');
        }
    };
    next.addEventListener('click', event => {
        event.preventDefault();
        next.textContent = 'Carregar mais';
        loadMore();
    });
    if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver(entries => {
            if (automatic && entries.some(entry => entry.isIntersecting)) loadMore();
        }, { rootMargin: '240px' });
        observer.observe(sentinel);
    }
}
