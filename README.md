# QueryFilter

[![License: MPL-2.0](https://img.shields.io/badge/License-MPL%202.0-brightgreen.svg)](https://opensource.org/licenses/MPL-2.0)

The QueryFilter package is a Laravel query filtering package that allows you to build dynamic Eloquent filters using request URL parameters. It supports nested conditions, logical operators and sorting.


## Installation

Install via Composer:

```bash
composer require cognitas/query-filter
```

## Usage

Inside your controller, import the `Filter` facade
```php
use Cognitas\QueryFilter\Facades\Filter;
```
and then call `Filter::applyFilters()` with:
* An Eloquent query builder (Model::query())
* The request query parameter that contains the filters (e.g. `filters`)

The `applyFilters` method modifies the provided query builder object and returns a `FilterResult` object containing any errors encountered while parsing or applying filters.


Example:
```php

use App\Models\Post;
use Cognitas\QueryFilter\Facades\Filter;

public function index(Request $request)
{
    $query = Post::query();

    $filterResult = Filter::applyFilters($query, $request->input('filters'));

    return $query->get();
}
```

The `FilterResult` object provides:
* `issues()` &rarr; `FiltersIssue[]`
    
    Returns an array of issues that hold the type, message and field of each issue. 

* `hasErrors()` &rarr; `bool`

    Returns `true` if there were any errors while applying/parsing the filters.

You can call the `applyFilters` method anytime, not just at the beginning. You can start with a static query, like querying all of the logged in user's posts and then filtering those further based on the input query filter. The parsed filters are added to the overall query (appended with the `AND` operator).

## Filter syntax

A filter is defiened as a nested array of base filters combined with logical operators &rarr; AND, OR. Base filters are filters directly applied to some model's attribute.

PHP uses brackets to pass arrays in a URL, therefore if we do: 

```
?test[]=a&test[]=b&test[]=c&...
```

PHP will parse it as an associative array `test`:

```
test => [
    0 => a,
    1 => b,
    2 => c
]
```

Empty brackets get automatically enumareted to keys in the array. But we can define any key we want and we can also add multiple sets of brackets &rarr; **nest** arrays in array.

Inside brackets we therefore use:
* AND/OR
* Numbers
* Nothing &rarr; leave empty

This filter:

```
(... OR ...) AND (... OR ...)
```

can be we written like so:

```
filters[AND][0][OR][0] = ...
&filters[AND][0][OR][1] = ...
&filters[AND][1][OR][0] = ...
&filters[AND][1][OR][1] = ...
```

Let's use the query parameter `filters` for defining our filter. Each such parameter has the syntax:

`filters[LOP][INDEX][LOP][INDEX]....[LOP|INDEX|empty]=base_filter`

where `LOP` is the logical operator `AND`, `OR`, and the base filter has the syntax:

`field:OP:value`

* `field` - field (attribute) to filter by,
* `OP` - operator, one from: `EQ`, `NEQ`, `LIKE`, `NLIKE`, `GT`, `GTE`, `LT`, `LTE`, `IN`, `NIN`
* `value` - the value to filter by. If the operator is `IN` or `NIN` the value(s) must be provided in brackets (`[]`). Strings can be written with a leading and trailing `"`. When using the `LIKE` operator use `%` accordingly. 

With `INDEX` we specify the index or position of the operand in the previous logical expression -> we specify the position of the element in the array. 

If we write 

`filters[AND][0]=base_filter1&filters[AND][1]=base_filter2`

the first operand of the `AND` will be `base_filter1` and the second operand will be `base_filter2`. There can be any number of operands.

### Shorter syntax

We can replace:

    filters[AND][0]=base_filter1
    &filters[AND][1]=base_filter2
    &filters[AND][2]=base_filter3

with:

    filters[AND]=base_filter1;base_filter2;base_filter3

The filters will be split by the semicolon and will be joined by the logical AND operator.

### Filter by related model

To filter by related model, for example filter posts based on the user's (creator's) name, the `base_filter` has this syntax:

`relationship.(field:OP:value*field:OP:value*...)`

* `relationship` - connection between two models
* `field` - the field in the related model
* `OP` - operator, one from: `EQ`, `NEQ`, `LIKE`, `NLIKE`, `GT`, `GTE`, `LT`, `LTE`, `IN`, `NIN`
* `value` - the value to filter by. If the operator is `IN` or `NIN` the value(s) must be provided in brackets (`[]`).

Filters are seperated with the asterisk symbol `*`, and are joined by an `AND` operator.

To filter posts whose creator (user) has the name John, we would write

`filters[AND][0]=user.(name:EQ:John)`

### Sorting

To sort (order) the result we add the query parameter:

`filters[ORDER]=base_order;base_order;...`

Where base_order is of the syntax:

`field:dir`

* `field` - field (attribute) to order by,
* `dir` - direction to order by, asc/desc.


### Whitelisting

Sometimes we want to allow filtering/querying only by specific model attributes or by specific related models.

To enable whitelisting on a model add the interface `FilterWhitelistable` and trait `FilterWhitelist` to it. If a model doesn't have this interface, whitelisting is not enabled on it and filtering can happen on any attribute/relationship.

Define all whitelisted attributes in a `filterableAttributes` array in the model. 

Define all whitelisted relationships in a `filterableRelations` array in the model.

Example:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cognitas\QueryFilter\Contracts\FilterWhitelistable;
use Cognitas\QueryFilter\Traits\FilterWhitelist;

class Post extends Model implements FilterWhitelistable
{
    use FilterWhitelist;

    
    protected array $filterableAttributes = [
        'title',
        'status',
    ];

    protected array $filterableRelations = [
        ...
    ];

    
}
```

### Simple syntax

`filters=base_filter1;base_filter2;...`

gets transformed to 

`filters[AND]=base_filter1;base_filter2;...`

## Configuration

Whitelisting can be globally enabled (default) and disabled. 
Add to `.env`
```
RELATION_WHITELIST=false
COLUMN_WHITELIST=false
```

or publish the config file:
```bash
php artisan vendor:publish --provider="Cognitas\QueryFilter\QueryFilterServiceProvider" --tag="config"

```

## Example queries

Example queries on a sample `posts` endpoint.

* `/posts?filters[AND]=title:EQ:Sample title;views:GT:1000`
* `/posts?filters[AND]=title:EQ:"Sample title";views:GT:1000`
* `/posts?filters[OR]=title:EQ:Sample title1;title:EQ:Sample title2`
* `/posts?filters[AND][0]=title:EQ:Sample title&filters[AND][1]=views:GT:1000`
* `/posts?filters[AND][]=title:EQ:Sample title&filters[AND][]=views:GT:1000`
* `(title = Sample1 AND views > 1000) OR (likes <= 500 AND title = Sample2 AND views > 2000)`

        /posts?filters[OR][0][AND][0]=title:EQ:Sample1
                &filters[OR][0][AND][1]=views:GT:1000
                &filters[OR][1][AND][0]=likes:LTE:500
                &filters[OR][1][AND][1]=title:EQ:Sample2
                &filters[OR][1][AND][2]=views:GT:2000

* The above filter can be written using the short syntax 

        /posts?filters[OR][0][AND]=title:EQ:Sample1;views:GT:1000
                &filters[OR][1][AND]=likes:LTE:500;title:EQ:Sample2;views:GT:2000

* A more complex expression:

        (title = Sample1 AND views < 500)
        OR 
        (
            shares IN [1000,2000,3000]
            AND
            (
                follows < 500
                OR
                views = 10000
                OR
                (title != Sample2 AND views < 3000)
            )

        )
        OR
        (likes = 200 AND shares <= 100)

        and we want to order by views

        /posts?filters[OR][0][AND][0]=title:NEQ:Sample1
                &filters[OR][0][AND][1]=views:LT:500
                &filters[OR][1][AND][0]=shares:IN:[1000,2000.3000]
                &filters[OR][1][AND][1][OR][0]=follows:LT:500
                &filters[OR][1][AND][1][OR][1]=views:EQ:10000
                &filters[OR][1][AND][1][OR][2][AND][0]=title:NEQ:Sample2
                &filters[OR][1][AND][1][OR][2][AND][1]=views:LT:3000 
                &filters[OR][2][AND][0]=likes:EQ:200
                &filters[OR][2][AND][1]=shares:LTE:100
                &filters[ORDER]=title:ASC

* Let's assume each post includes a picture, with its metadata is stored in the `picture` model. The relationship between a post and its image is called `picture`. If we want to query all posts with a title `Sample` OR all posts that have a picture that has a size less than 400 KB, we would write:

        /posts?filters[OR][0]=title:EQ:Sample
            &filters[OR][1]=picture.(size:LT:400)

## License

Mozilla Public License 2.0, see LICENSE for more information.

Copyright @2025 Cognitas d.o.o.