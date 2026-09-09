(() => {
    if (window.__rcnAssetsLoaded) {
        return;
    }
    window.__rcnAssetsLoaded = true;

    const scripts = document.body.classList.contains('public-page')
        ? ['public.js']
        : ['admin.js', 'notifications.js', 'pwa.js', 'push.js'];
    const currentScriptUrl = document.currentScript ? new URL(document.currentScript.src, document.baseURI) : null;
    const basePath = currentScriptUrl?.pathname.replace(/\/js\/main\.js$/, '') || '';

    scripts.forEach((scriptName) => {
        const script = document.createElement('script');
        const moduleUrl = new URL(`${basePath}/js/${scriptName}`, currentScriptUrl?.origin || document.baseURI);
        if (currentScriptUrl?.search) {
            moduleUrl.search = currentScriptUrl.search;
        }
        script.src = moduleUrl.toString();
        script.defer = false;
        document.body.appendChild(script);
    });
})();
