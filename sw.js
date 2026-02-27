const CACHE_NAME = 'vaijunto-v3'; // Bump version
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

  // 1. Regra para Imagens de Usuários/Carros (Cache Dinâmico: Stale-While-Revalidate)
  if (requestUrl.pathname.includes('/assets/media/uploads/')) {
      event.respondWith(
          caches.open('vaijunto-dynamic-images-v1').then(cache => {
              return cache.match(event.request).then(cachedResponse => {
                  // Vai na rede buscar a imagem para atualizar o cache
                  const fetchPromise = fetch(event.request).then(networkResponse => {
                      // Salva uma cópia atualizada no cache dinâmico de mídias
                      if (networkResponse && networkResponse.status === 200) {
                          cache.put(event.request, networkResponse.clone());
                      }
                      return networkResponse;
                  }).catch(() => {
                      // Offline: Silencioso, usará o cache fallback se existir
                  });
                  
                  // Se existir no cache, retorna IMEDIATAMENTE (Stale).
                  // Se não, retorna a Promise de buscar na rede (network).
                  return cachedResponse || fetchPromise;
              });
          })
      );
      return; // Interrompe o fluxo para não cair na regra geral abaixo
  }

  // 2. Regra Geral (Network-First Fallback)
  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Se a resposta for válida, coloca no cache
        if (networkResponse && networkResponse.status === 200) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        // Se falhar a rede, tenta o cache
        return caches.match(event.request);
      })
  );
});
