const CACHE_NAME = 'vaijunto-v4'; // Bump version
const ASSETS_TO_CACHE = [
  './',
  './index.php',
  './manifest.json'
];

// Instalação: Cache inicial (Apenas ativos locais críticos)
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('SW: Pre-caching assets');
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

// Ativação: Limpar caches antigos
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(keyList.map((key) => {
        if (key !== CACHE_NAME) return caches.delete(key);
      }));
    })
  );
});

// Fetch: Interceptar requisições (Network-First com Fallback para Cache)
self.addEventListener('fetch', (event) => {
  // Ignorar requisições não-GET e APIs
  if (event.request.method !== 'GET' || event.request.url.includes('/api/')) {
    return;
  }

  const requestUrl = new URL(event.request.url);

  // Regra Exclusiva para Imagens (Stale-While-Revalidate)
  if (requestUrl.pathname.includes('/assets/media/uploads/')) {
    event.respondWith(
      caches.open('vaijunto-images-v1').then(cache => {
        return cache.match(event.request).then(cachedResponse => {
          const fetchPromise = fetch(event.request).then(networkResponse => {
            if (networkResponse.status === 200) {
              cache.put(event.request, networkResponse.clone());
            }
            return networkResponse;
          }).catch(() => { }); // Ignora erro de rede se estiver offline

          return cachedResponse || fetchPromise;
        });
      })
    );
    return;
  }

  // Regra Padrão para o restante do App
  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        return caches.match(event.request);
      })
  );
});
