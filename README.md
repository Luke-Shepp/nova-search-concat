## Laravel Nova Concat Search

Allows searching for Resources by a concatenation of multiple fields in Laravel Nova.

### Example

An example use case is where a table contains `first_name` and `last_name`, and the Nova user would like to search by full name.

Example table;

| first_name | last_name | email        | ... |
|------------|-----------|--------------|-----|
| John       | Doe       | john@doe.com | ... |

Using standard Nova search, the  term `John Doe` will not match this record as neither first name or last name match.

By adding a sub-array of column names into the `$search` property of a Resource, this package allows searching on concatenated database fields:

```php
class User extends Resource
{
    use Shepp\NovaConcatSearch\Traits\SearchesOnConcatColumns;

    public static $search = [
        ['first_name', 'last_name'], // <----
        'email',
    ];

    // ...
}
```

This is effectively appending the following to the search query;
```sql
WHERE
    # ...
    OR CONACT(first_name, ' ', last_name) LIKE '%John Doe%'
```

Using the example above, this will allow the term `John Doe` to match the sample record.
