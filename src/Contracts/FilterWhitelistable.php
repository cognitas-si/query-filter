<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @copyright 2025 Cognitas d.o.o.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 */

namespace Cognitas\QueryFilter\Contracts;

interface FilterWhitelistable
{
    /**
     * Return filterable relations of the model.
     */
    public function getFilterableRelations(): array;

    /**
     * Return filterable attributes of the model.
     */
    public function getFilterableAttributes(): array;
}
