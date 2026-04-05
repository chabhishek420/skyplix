# Stage 1: Build the React Admin UI
FROM node:22-alpine AS ui-builder
WORKDIR /app/admin-ui
COPY admin-ui/package*.json ./
RUN npm install
COPY admin-ui/ .
RUN npm run build

# Stage 2: Build the Go Backend
FROM golang:1.25-alpine AS go-builder
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download
COPY . .
# Copy the built UI from Stage 1 into the Go expected static directory
COPY --from=ui-builder /app/admin-ui/dist ./admin-ui/dist
RUN CGO_ENABLED=0 GOOS=linux go build -o zai-tds cmd/zai-tds/main.go

# Stage 3: Final Production Image
FROM gcr.io/distroless/static-debian12
WORKDIR /app
COPY --from=go-builder /app/zai-tds .
COPY db/clickhouse/migrations/ ./db/clickhouse/migrations/
COPY config.yaml .
EXPOSE 8080
USER 65532:65532
ENTRYPOINT ["./zai-tds"]
CMD ["serve"]
