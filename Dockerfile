# Builder stage: install deps + build
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci --legacy-peer-deps
COPY . .
RUN npm run build

# Runner stage: production runtime only
FROM node:20-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
COPY package*.json ./
RUN npm ci --omit=dev --production --legacy-peer-deps
COPY --from=builder /app/lib ./lib
COPY --from=builder /app/server ./server
COPY --from=builder /app/README.md ./README.md
EXPOSE 3000
ENV PORT=3000
CMD ["node", "server/index.js"]
