const CACHE_NAME = 'quizzify-v2';
const urlsToCache = [
    './',
    '../index.php',
    'app.js',
    'tada.mp3',
    'smice.mp4',
    'Video_Generation_Successful.mp4',
    'splash.png',
    'logo.png',
    'bg1.jpg',
    'bg2.jpg',
    'original1.png',
    'original2.png',
    'pineapple1.png',
    'pineapple2.png',
    'guarana1.png',
    'guarana2.png',
    'social_proof_mockup.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700&display=swap'
];

self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache).catch(err => {
                    console.warn('Failed to cache some assets during install:', err);
                });
            })
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                return fetch(event.request).then(
                    response => {
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });
                        return response;
                    }
                );
            })
    );
});
