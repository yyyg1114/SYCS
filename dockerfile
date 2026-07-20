# --- Stage 1: Base & Install Dependencies ---
FROM oven/bun:1-alpine AS base
WORKDIR /app

# パッケージ定義ファイルをコピーして依存関係をキャッシュ
COPY package.json bun.lockb* ./
RUN bun install --frozen-lockfile

# --- Stage 2: Build ---
FROM base AS builder
WORKDIR /app
COPY . .
# Nuxtのプロダクションビルド (.output が生成される)
RUN bun run build

# --- Stage 3: Runner ---
FROM oven/bun:1-alpine AS runner
WORKDIR /app

ENV NODE_ENV=production
ENV HOST=0.0.0.0
ENV PORT=3000

# ビルド成果物(.output)だけを軽量なイメージにコピー
COPY --from=builder /app/.output ./.output

EXPOSE 3000

# NitroサーバーをBunで起動
CMD ["bun", "run", ".output/server/index.mjs"]