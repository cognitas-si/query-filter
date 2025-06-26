<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @copyright 2025 Cognitas d.o.o.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 */

namespace Cognitas\QueryFilter;

use Cognitas\QueryFilter\Filters\FilterUtils;
use Cognitas\QueryFilter\Filters\QueryFilter;
use Illuminate\Support\ServiceProvider;

class QueryFilterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(QueryFilter::class, fn() => new QueryFilter(new FilterUtils()));

        $this->mergeConfigFrom(__DIR__.'/../config/query-filter.php', 'query-filter');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/query-filter.php' => $this->app->configPath('query-filter.php')], 'config');
    }
}
