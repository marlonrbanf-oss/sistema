const CACHE_NAME = 'nova-arte-v1';

// Arquivos básicos para o App abrir mesmo se a rede oscilar
const PRE_CACHE = [
    'images/logo_app.png',
    'css/bootstrap.css'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(PRE_CACHE))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Responde com o cache, se houver, ou busca na rede
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});