import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig, loadEnv } from 'vite'

// https://vite.dev/config/
export default defineConfig(({ command, mode }) => {
  // Порт nginx берём из корневого .env (NGINX_PORT), чтобы dev-прокси
  // совпадал с docker-compose. По умолчанию — 8080.
  // Внутри Docker-контейнера проксируем на сервис nginx по имени (VITE_API_TARGET=http://nginx:80).
  const rootEnv = loadEnv(mode, '..', '')
  const apiTarget =
    process.env.VITE_API_TARGET || `http://127.0.0.1:${rootEnv.NGINX_PORT || '8080'}`

  return {
    // Прод: файлы собираются в /dist/ (nginx раздаёт их оттуда).
    // Dev: приложение остаётся в корне — http://localhost:5173/
    base: command === 'build' ? '/dist/' : '/',
    plugins: [react(), tailwindcss()],
    server: {
      port: 5173,
      proxy: {
        '/api': {
          target: apiTarget,
          changeOrigin: true,
        },
      },
    },
    build: {
      // Сборка в отдельный каталог: рядом лежат index.php и health.php,
      // которые мы не должны затирать.
      outDir: '../app/public/dist',
      emptyOutDir: true,
    },
  }
})
