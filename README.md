# Coin Collection Backend API

A Symfony 7.4 REST API backend for the Coin Collection application.

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Composer

**For Docker:**
- Docker & Docker Compose

## Quick Start with Docker

```bash
# Build and start all containers
docker compose up -d --build

# Generate JWT keys
make jwt-keys

# Run database migrations
make migrate

# Application is now running at http://localhost:8080
```

### Docker Commands

```bash
make build      # Build containers
make up         # Start containers
make down       # Stop containers
make logs       # View logs
make shell      # Access PHP container shell
make db-shell   # Access MySQL shell
make migrate    # Run migrations
make jwt-keys   # Generate JWT keys
make fresh      # Full setup (build + jwt + migrate)
```

### Docker Services

| Service | Port | Description |
|---------|------|-------------|
| nginx | 8080 | Web server |
| php | 9000 | PHP-FPM (internal) |
| database | 3306 | MySQL |

---

## Manual Setup (without Docker)

### 1. Install Dependencies

```bash
composer install
```

### 2. Configure Environment

Copy `.env` to `.env.local` and configure your database:

```bash
cp .env .env.local
```

Edit `.env.local`:

```env
DATABASE_URL="mysql://username:password@127.0.0.1:3306/coin_collection?serverVersion=8.0&charset=utf8mb4"
JWT_PASSPHRASE=your_secure_passphrase
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### 3. Generate JWT Keys

If you need to regenerate JWT keys:

```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:your_passphrase
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:your_passphrase
```

Make sure `JWT_PASSPHRASE` in your `.env.local` matches the passphrase used above.

### 4. Create Database

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Run Development Server

```bash
symfony server:start
# or
php -S localhost:8000 -t public
```

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/auth/register` | Register new user | No |
| POST | `/api/auth/login` | Login and get JWT token | No |
| GET | `/api/auth/me` | Get current user info | Yes |

#### Register

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password123"}'
```

#### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "user@example.com", "password": "password123"}'
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### Coins

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/coins` | List all coins | No |
| GET | `/api/coins/{id}` | Get coin by ID | No |
| POST | `/api/coins` | Create new coin | Yes |
| PUT/PATCH | `/api/coins/{id}` | Update coin | Yes |
| DELETE | `/api/coins/{id}` | Delete coin | Yes |

#### List Coins

```bash
curl http://localhost:8000/api/coins
```

#### Create Coin

```bash
curl -X POST http://localhost:8000/api/coins \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "name": "Lithuanian 2 Euro Commemorative",
    "year": 2024,
    "denomination": "2 EUR",
    "metal": "Bimetallic",
    "weight_grams": 8.5,
    "diameter_mm": 25.75,
    "mintage": 500000
  }'
```

### User Collection

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/collection` | Get user's collection | Yes |
| POST | `/api/collection` | Add coin to collection | Yes |
| PUT/PATCH | `/api/collection/{coinId}` | Update collection entry | Yes |
| DELETE | `/api/collection/{coinId}` | Remove from collection | Yes |

#### Get Collection

```bash
curl http://localhost:8000/api/collection \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Add to Collection

```bash
curl -X POST http://localhost:8000/api/collection \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "coin_id": "uuid-of-coin",
    "acquired_date": "2024-01-15",
    "condition": "UNC",
    "notes": "Purchased from local dealer"
  }'
```

### Profile

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/profile` | Get user profile | Yes |
| PUT/PATCH | `/api/profile` | Update profile | Yes |

### File Upload

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/upload/coin-image/{coinId}` | Upload coin image | Yes |

```bash
curl -X POST http://localhost:8000/api/upload/coin-image/UUID \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "image=@/path/to/image.jpg"
```

## Data Models

### Coin

```json
{
  "id": "uuid",
  "name": "string",
  "description": "string|null",
  "year": "int|null",
  "denomination": "string|null",
  "metal": "string|null",
  "weight_grams": "float|null",
  "diameter_mm": "float|null",
  "mintage": "int|null",
  "image_url": "string|null",
  "external_id": "string|null",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### User Collection Entry

```json
{
  "id": "uuid",
  "user_id": "uuid",
  "coin_id": "uuid",
  "acquired_date": "date|null",
  "condition": "string|null",
  "notes": "string|null",
  "custom_image_url": "string|null",
  "created_at": "datetime",
  "coins": { /* Coin object */ }
}
```

## Frontend Integration

To connect your frontend to this backend instead of Supabase:

1. Update your API client to use JWT authentication
2. Replace Supabase calls with REST API calls
3. Store the JWT token in localStorage after login
4. Include `Authorization: Bearer <token>` header in authenticated requests

## Development

### Clear Cache

```bash
php bin/console cache:clear
```

### Validate Schema

```bash
php bin/console doctrine:schema:validate
```

### Create New Migration

```bash
php bin/console doctrine:migrations:diff
```
