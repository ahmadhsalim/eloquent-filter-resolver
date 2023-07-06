# Query filter resolver for Laravel

## Features

- Operators
    - contains - ilike
    - eq - equals
    - ne - not equals
    - gt - greater than
    - lt - less than
    - gte - greater than or equal to
    - lte - less than or equal to
    - in - in
    - notIn - not in
- Nested bracets
    - Supports enclosing multiple conditions using nested brackets. Eg: `eq(status, active) or (gte(created_at, 2023-06-01) and lt(created_at, 2023-07-01)))`

## Installation

Require this package with composer using the following command:

```bash
composer require ahmadhsalim/eloquent-filter-resolver
```

## Basic Usage

```php
use App\Http\Controllers\Controller;
use App\Models\User;
use Salim\FilterResolver\EloquentFilterResolver;

Class UserController extends Controller
{
    public function index()
    {
        $query = User->query();
        $filter = 'contains(name, "john") and eq(status, active)'

        EloquentFilterResolver::resolve($query, $filter);

        return $query->paginate();
    }
}

```
