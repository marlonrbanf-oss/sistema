const CACHE_NAME = 'nova-arte-v2';

// Cache de arquivos que raramente mudam (Assets e Manifests)
const PRE_CACHE = [
    'images/logo_app.png',
    'css/bootstrap.css',
    'manifest_aluno.json',
    'manifest_adm.json',
    'manifest_terminal.json'
];

// --- INSTALAÇÃO: Salva arquivos essenciais ---
self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('Arquivos pré-cacheados com sucesso.');
            return cache.addAll(PRE_CACHE);
        })
    );
    self.skipWaiting();
});

// --- ATIVAÇÃO: Limpa caches antigos ---
self.addEventListener('activate', (event) => {
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

// --- FETCH: Estratégia de Rede com Fallback para Cache ---
self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});

// --- PUSH: Recebe as notificações do Servidor (Check-in, Técnicas, Graduação) ---
self.addEventListener('push', function (event) {
    let data = {
        title: 'Nova Arte BJJ',
        body: 'Você recebeu uma nova atualização!',
        url: 'login.php'
    };

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: 'images/logo_app.png', // Ícone que aparece na notificação
        badge: 'images/logo_app.png', // Ícone pequeno na barra de status
        vibrate: [200, 100, 200],
        data: {
            url: data.url
        },
        actions: [
            { action: 'open', title: 'Ver Agora' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// --- CLIQUE: O que acontece quando o aluno toca na notificação ---
self.addEventListener('notificationclick', function (event) {
    event.notification.close(); // Fecha o balão

    // Define para onde o aluno será levado (ex: página de técnicas ou perfil)
    const urlParaAbrir = event.notification.data.url || 'login.php';

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(windowClients => {
            // Se o app já estiver aberto, foca nele
            for (let client of windowClients) {
                if (client.url === urlParaAbrir && 'focus' in client) {
                    return client.focus();
                }
            }
            // Se não estiver aberto, abre uma nova janela
            if (clients.openWindow) {
                return clients.openWindow(urlParaAbrir);
            }
        })
    );
});