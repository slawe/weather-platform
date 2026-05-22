FROM mcr.microsoft.com/dotnet/sdk:9.0

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    curl \
    ca-certificates \
 && rm -rf /var/lib/apt/lists/*

CMD ["bash", "-lc", "tail -f /dev/null"]
