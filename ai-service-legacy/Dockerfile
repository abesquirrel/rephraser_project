FROM python:3.9-slim

WORKDIR /app

# Install system dependencies (build-essential for sbert/numpy if needed)
RUN apt-get update && apt-get install -y \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements first for cache
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Install mysql-connector for DB access
RUN pip install mysql-connector-python

# Copy app code
COPY . .

# Expose Flask port
EXPOSE 5001

# Run the app
CMD ["python", "app.py"]
