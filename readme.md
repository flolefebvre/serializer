# Flolefebvre Serializer

[![Packagist Version](https://img.shields.io/packagist/v/flolefebvre/serializer?label=packagist)](https://packagist.org/packages/flolefebvre/serializer)
[![Downloads](https://img.shields.io/packagist/dt/flolefebvre/serializer)](https://packagist.org/packages/flolefebvre/serializer)
[![License](https://img.shields.io/packagist/l/flolefebvre/serializer)](LICENSE)
[![CI](https://github.com/flolefebvre/serializer/actions/workflows/tests.yml/badge.svg)](https://github.com/flolefebvre/serializer/actions/workflows/tests.yml)

A **zero‑boilerplate**, attribute‑driven serializer / DTO helper for **Laravel 10 & 11**  
_Built for PHP ≥ 8.2 — lighter than 20 KB._

---

## Table of Contents

- [Why another serializer?](#why-another-serializer)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Advanced usage](#advanced-usage)
  - [Custom TypeCast](#custom-typecast)
  - [Arrays of DTOs](#arrays-of-dtos)
- [Performance & Limitations](#performance--limitations)
- [Contributing](#contributing)
- [License](#license)

---

## Why another serializer?

`flolefebvre/serializer` focuses on **simplicity and strictness**:

- DTOs are _just_ classes extending [`Serializable`](src/Serializable.php).
- Validation occurs **before** the object exists — no invalid state can persist.
- No generated code or heavy reflection caches; your build stays ultra‑fast.

---

## Features

|                                       |                                             |
| ------------------------------------- | ------------------------------------------- |
| ✅ Constructor‑promoted DTOs only     | ✅ Automatic validation via `#[Rule]`       |
| ✅ Pure attribute configuration       | ✅ Typed arrays (`#[ArrayType]`)            |
| ✅ Laravel DI & Request auto‑binding  | ✅ Custom type‑casts (`TypeCast` interface) |
| ✅ Eloquent JSON cast (single / list) | ✅ `JsonResponse` ready to return           |

---

## Requirements

- PHP **8.2+**
- Laravel **10 / 11**
- `ext-json`

---

## Installation

```bash
composer require flolefebvre/serializer
```

---

## Quick start

Create a DTO:

```php
<?php

namespace App\DTO;

use Flolefebvre\Serializer\Serializable;
use Flolefebvre\Serializer\Attributes\Rule;

class PostData extends Serializable
{
    public function __construct(
        public string $title,
        #[Rule('nullable|string|max:280')]
        public ?string $excerpt,
        #[Rule('required|url')]
        public string $url,
    ) {}
}
```

Bind it in a route:

```php
use App\DTO\PostData;
use Illuminate\Support\Facades\Route;

Route::post('/posts', function (PostData $data) {
    // $data is a fully validated DTO.
    // ...
    return $data; // => JsonResponse 201
});
```

Try it:

```bash
curl -X POST http://localhost/api/posts      -H "Content-Type: application/json"      -d '{"title":"Hello","excerpt":null,"url":"https://example.com"}'
```

Response:

```json
{
  "title": "Hello",
  "excerpt": null,
  "url": "https://example.com",
  "_type": "App\\DTO\\PostData"
}
```

---

## Advanced usage

### Custom TypeCast

```php
use Ramsey\Uuid\UuidInterface;
use Flolefebvre\Serializer\Attributes\CastTypeWith;
use Flolefebvre\Serializer\Casts\TypeCast;
use Ramsey\Uuid\Uuid;

class UuidCast implements TypeCast
{
    // How the value will appear once serialized
    public string $serializedType = 'uuid';

    public function serialize(mixed $value): mixed
    {
        return (string) $value;
    }

    public function unserialize(mixed $value): mixed
    {
        return Uuid::fromString($value);
    }
}

class OrderData extends Serializable
{
    public function __construct(
        #[CastTypeWith(UuidCast::class)]
        public UuidInterface $id,
    ) {}
}
```

### Arrays of DTOs

```php
use Flolefebvre\Serializer\Attributes\ArrayType;

class BlogData extends Serializable
{
    public function __construct(
        #[ArrayType(CommentData::class)]
        public array $comments,
    ) {}
}
```

---

## Performance & Limitations

| Topic                    | Notes                                                                                                    |
| ------------------------ | -------------------------------------------------------------------------------------------------------- |
| Reflection               | Metadata is rebuilt on every call → enable OPcache and/or add a static cache for high‑traffic endpoints. |
| Union/Intersection Types | Not yet supported — contributions welcome.                                                               |
| Polymorphism             | The `_type` field is required for subclasses during deserialization.                                     |

---

## Contributing

1. Fork & clone
2. `composer install && composer test`
3. Create your feature branch (`git checkout -b feature/my-feature`)
4. Push and open a PR
5. Ensure tests pass and follow PSR‑12.

---

## License

Released under the MIT License — see [LICENSE](LICENSE).
