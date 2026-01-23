// Este arquivo é necessário para o Chrome habilitar a instalação do PWA
self.addEventListener('install', function (e) {
    e.waitUntil(
        caches.open('nova-arte-v1').then(function (cache) {
            return cache.addAll(['./']);
        })
    );
});

self.addEventListener('fetch', function (event) { });