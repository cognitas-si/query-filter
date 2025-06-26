<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @copyright 2025 Cognitas d.o.o.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 */

namespace Cognitas\QueryFilter\Facades;

use Cognitas\QueryFilter\Filters\QueryFilter;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Cognitas\QueryFilter\Outcome\FilterResult applyFilters(\Illuminate\Database\Eloquent\Builder $query, string|array $filter_input)
 *
 * @see \Cognitas\QueryFilter\Filter\QueryFilter
 */
class Filter extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return QueryFilter::class;
    }
}