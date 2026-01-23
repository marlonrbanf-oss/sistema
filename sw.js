const CACHE_NAME = 'nova-arte-v2';

// Cache de arquivos que raramente mudam (Assets)
const PRE_CACHE = [
    'images/logo_app.png',
    'css/bootstrap.css',
    'manifest_aluno.json',
    'manifest_adm.json',
    'manifest_terminal.json'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('Arquivos pré-cacheados com sucesso.');
            return cache.addAll(PRE_CACHE);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // Limpa caches antigos se você mudar o CACHE_NAME
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
    return self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    // Estratégia: Tenta Rede, se falhar (offline), busca no Cache
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});