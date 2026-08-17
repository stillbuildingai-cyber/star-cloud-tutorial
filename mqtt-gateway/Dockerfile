# Stage 1: Build
FROM golang:1.23-alpine AS builder

WORKDIR /app

# 複製依賴檔案
COPY go.mod ./
# 註：如果沒有 go.sum，build 時會自動處理或報錯，我們先建立 basic
RUN go mod download || true

COPY . .
RUN CGO_ENABLED=0 go build -o gateway .

# Stage 2: Run
FROM alpine:3.20

# 安裝 tzdata 以支援時區設定
RUN apk add --no-cache tzdata

WORKDIR /root/
COPY --from=builder /app/gateway .

# 運行
CMD ["./gateway"]
