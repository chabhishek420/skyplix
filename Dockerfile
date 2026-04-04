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
RUN CGO_ENABLED=0 GOOS=linux go build -o migrate-ch cmd/migrate-ch/main.go

# Stage 3: Final Production Image
FROM alpine:3.20
# Add CA certs for outbound HTTPS requests and TZ data for proper time handling
RUN apk --no-cache add ca-certificates tzdata \
    && addgroup -S skyplix && adduser -S skyplix -G skyplix
WORKDIR /app
COPY --from=go-builder /app/zai-tds .
COPY --from=go-builder /app/migrate-ch .
COPY db/clickhouse/migrations/ ./db/clickhouse/migrations/
COPY config.yaml .
RUN chown -R skyplix:skyplix /app
USER skyplix
EXPOSE 8080
CMD ["./zai-tds"]
