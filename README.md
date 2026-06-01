# ecomapi

Laravel 12 e-commerce JSON API. Public endpoints serve a storefront; admin endpoints are guarded by Sanctum tokens.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Default admin: `admin@site.com` / `admin123`.

## Endpoints

| Method | Path                          | Auth          | Purpose                |
| ------ | ----------------------------- | ------------- | ---------------------- |
| GET    | /api/settings                 | public        | Site settings          |
| GET    | /api/sliders                  | public        | Active sliders         |
| GET    | /api/categories               | public        | Category list          |
| GET    | /api/categories/{category}    | public        | Category detail        |
| GET    | /api/products                 | public        | Product list (paged)   |
| GET    | /api/products/{product}       | public        | Product detail         |
| POST   | /api/admin/login              | public        | Issue sanctum token    |
| POST   | /api/admin/logout             | auth:sanctum  | Revoke current token   |
| GET    | /api/admin/me                 | auth:sanctum  | Current admin          |
| GET    | /api/admin/settings           | auth:sanctum  | Read settings          |
| PUT    | /api/admin/settings           | auth:sanctum  | Update settings        |
| GET    | /api/admin/sliders            | auth:sanctum  | List sliders           |
| POST   | /api/admin/sliders            | auth:sanctum  | Create slider          |
| GET    | /api/admin/sliders/{slider}   | auth:sanctum  | Show slider            |
| PUT    | /api/admin/sliders/{slider}   | auth:sanctum  | Update slider          |
| DELETE | /api/admin/sliders/{slider}   | auth:sanctum  | Delete slider          |
| GET    | /api/admin/categories         | auth:sanctum  | List categories        |
| POST   | /api/admin/categories         | auth:sanctum  | Create category        |
| GET    | /api/admin/categories/{id}    | auth:sanctum  | Show category          |
| PUT    | /api/admin/categories/{id}    | auth:sanctum  | Update category        |
| DELETE | /api/admin/categories/{id}    | auth:sanctum  | Delete category        |
| GET    | /api/admin/products           | auth:sanctum  | List products          |
| POST   | /api/admin/products           | auth:sanctum  | Create product         |
| GET    | /api/admin/products/{product} | auth:sanctum  | Show product           |
| PUT    | /api/admin/products/{product} | auth:sanctum  | Update product         |
| DELETE | /api/admin/products/{product} | auth:sanctum  | Delete product         |
