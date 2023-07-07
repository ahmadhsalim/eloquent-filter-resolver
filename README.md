# Eloquent Filter Resolver

The Eloquent Filter Resolver is a package that allows you to resolve a filter string into an Eloquent query in Laravel. It provides a convenient way to filter Eloquent models based on various conditions and operators.

## Installation

You can install the Eloquent Filter Resolver package via Composer. Run the following command:

```bash
composer require ahmadhsalim/eloquent-filter-resolver
```

## Usage

To use the Eloquent Filter Resolver, follow the example below:

```php
use App\Http\Controllers\Controller;
use App\Models\User;
use Salim\FilterResolver\EloquentFilterResolver;

class UserController extends Controller
{
    public function index()
    {
        $query = User::query();
        $filter = 'contains(name, "john") and eq(status, active)';

        EloquentFilterResolver::resolve($query, $filter);

        return $query->paginate();
    }
}
```

In the above example, we create an Eloquent query for the `User` model and apply a filter using the Eloquent Filter Resolver. The filter string `contains(name, "john") and eq(status, active)` filters the users whose names contain "john" and have an active status.

### Supported Operators

The Eloquent Filter Resolver supports the following operators:

- `contains`: Performs a case-insensitive search using the `LIKE` operator. Uses `ILIKE` for `pgsql` driver. Usage: `contains(field, value)`.
- `eq`: Performs an equality comparison using the `=` operator. Usage: `eq(field, value)`.
- `ne`: Performs a not-equal comparison using the `!=` operator. Usage: `ne(field, value)`.
- `gt`: Performs a greater-than comparison using the `>` operator. Usage: `gt(field, value)`.
- `gte`: Performs a greater-than-or-equal comparison using the `>=` operator. Usage: `gte(field, value)`.
- `lt`: Performs a less-than comparison using the `<` operator. Usage: `lt(field, value)`.
- `lte`: Performs a less-than-or-equal comparison using the `<=` operator. Usage: `lte(field, value)`.
- `in`: Performs an `IN` comparison for multiple values. Values can be separated by `|`. Usage: `in(field, "value1|value2|value3")`.
- `notIn`: Performs a `NOT IN` comparison for multiple values. Values can be separated by `|`. Usage: `notIn(field, "value1|value2|value3")`.

### Value Formats

- Values can be enclosed in double quotes for strings. Example: `eq(name, "John Doe")`.
- Values can have double quotes within the string by escaping them with a backslash (`\`). Example: `eq(name, "John \"The Man\" Doe")`.
- If a value contains commas, brackets, or spaces, it must be enclosed in double quotes. Example: `eq(name, "John Doe, Jr.")`.

### Boolean and Null Values

- When the value is `"true"` or `"false"`, it will be parsed as a boolean value.
- When the value is `"null"`, it will be parsed as `null`.

### Nested Brackets

The Eloquent Filter Resolver supports nested brackets to create complex filter expressions. For example:

```php
$filter = 'eq(name, "John") and (eq(status, "active") or eq(status, "inactive"))';
```
In the above example, the filter condition checks if the name is "John" and the status is either "active" or "inactive".

## Usage with Relations

The Eloquent Filter Resolver also supports filtering on related models using dot notation. For example:

```php
$filter = 'eq(user.role.name, "admin")';
```
In the above example, the filter condition checks if the related `role` of the `user` has a name equal to "admin".

## Usage with Boolean and Null Values

Boolean and null values can be used in filter conditions as follows:

```php
$filter = 'eq(is_active, true) and eq(deleted_at, null)';
```

In the above example, the filter condition checks if the `is_active` field is true and the `deleted_at` field is null.

# Contributing

Contributions to the Eloquent Filter Resolver package are welcome. If you find any issues or want to suggest improvements, please create a GitHub issue

# License

The Eloquent Filter Resolver package is open-source software licensed under the MIT license.

