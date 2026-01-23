self.addEventListener('install', (e) => {
    self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
    // Apenas para cumprir o requisito do PWA
});