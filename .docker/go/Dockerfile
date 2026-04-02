FROM golang:1.24-bookworm

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    curl \
    ca-certificates \
 && rm -rf /var/lib/apt/lists/*

CMD ["sh", "-lc", "tail -f /dev/null"]