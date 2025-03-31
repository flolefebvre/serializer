# Flolefebvre Serializer

A simplified and high-performance data serialization library for Laravel inspired by Spatie Data. With this library, you can easily define data transfer objects (DTOs) with automatic validation, seamless integration in controllers, and flexible Eloquent casting.

## Features

- **Easy DTO Definition:** Extend `Flopefebvre\Serializer\Serializable` to create your own data classes.
- **Automatic Validation:** When injected into controllers (e.g., during a create operation), the DTO is automatically validated.
- **Inertia Integration:** Return your DTO directly as data in an `Inertia::render` response.
- **Flexible Eloquent Casting:** Store data as JSON in your database:
  - Use `"value" => Post::class` for a single DTO.
  - Use `"value" => Post::class . ':list'` for an array of DTOs.
- **Dynamic Instantiation:** Create DTO instances using `Post::from()` with an array, model, or any data source, as long as the values match.
- **Custom Validation Rules:** Annotate properties with attributes like `#[Rule('my rule')]` for fine-grained validation.
- **Array Type Specification:** Use `#[ArrayType('my type')]` to enforce element types in arrays.

## Installation

Install via Composer:

```bash
composer require flolefebvre/serializer
```

## Usage

### 1. Define a Data Class

Create a DTO by extending `Flopefebvre\Serializer\Serializable`. For example, a `Post` class representing a blog post:

```php
<?php

namespace App\Data;

use Flolefebvre\Serializer\Serializable;
use Flolefebvre\Serializer\Attributes\Rule;
use Flolefebvre\Serializer\Attributes\ArrayType;

class Post extends Serializable
{
    #[Rule('min:3')]
    public string $title;

    public ?string $content = null;

    #[ArrayType('string')]
    public array $tags = [];
}
```

### 2. Controller Injection and Automatic Validation

Leverage Laravel's dependency injection to automatically validate incoming data:

```php
<?php

namespace App\Http\Controllers;

use App\Data\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(Post $post)
    {
        // The $post DTO is automatically validated.
        // Process the validated data.
    }
}
```

### 3. Return DTO with Inertia

Use your DTO directly in Inertia responses:

```php
<?php

namespace App\Http\Controllers;

use App\Data\Post;
use Inertia\Inertia;

class PostController extends Controller
{
    public function show(Post $post)
    {
        return Inertia::render('Post/Show', $post);
    }
}
```

### 4. Eloquent Casting for JSON Storage

Use the provided cast class to store your DTOs as JSON in the database:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Flolefebvre\Serializer\Casts\SerializableCast;
use App\Data\Post;

class Blog extends Model
{
    public function casts() {
        return [
            // For a single DTO instance:
            'post' => SerializableCast::class . ':' . Post::class,

            // For a list of DTOs:
            'posts' => SerializableCast::class . ':' . Post::class . ':list',
        ];
    }
}
```

### 5. Creating DTOs from Various Data Sources

Instantiate your DTO using the `from` method with an array, model, or any compatible data source:

```php
use App\Data\Post;

// From an array
$post = Post::from([
    'title'   => 'My First Post',
    'content' => 'Hello, world!',
    'tags'    => ['laravel', 'php']
]);

// Alternatively, from a model or other data source if field names match.
```

## License

This project is open-sourced software licensed under the [MIT License](LICENSE).

Explore the tests in the repository for more detailed usage examples. Enjoy building with Flolefebvre Serializer!
