FROM node:24-alpine

WORKDIR /app/frontend

# Сначала копируем только манифесты зависимостей — чтобы слой установки кэшировался,
# пока не меняются package.json / package-lock.json
COPY package.json package-lock.json ./
RUN npm install --no-audit --no-fund

# Копируем исходники фронтенда (в dev-режиме compose перекрывает их bind mount'ом,
# а node_modules сохраняется в анонимном volume)
COPY . .

EXPOSE 5173

# Запускаем Vite dev-сервер с --host 0.0.0.0, чтобы он принимал подключения
# извне контейнера (проброс порта 5173 в compose.yaml)
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]
