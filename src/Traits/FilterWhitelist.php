<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @copyright 2025 Cognitas d.o.o.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 */

namespace Cognitas\QueryFilter\Traits;

trait FilterWhitelist
{
    
    /**
     * Returns filterable relations of the model.
     * 
     * @return array
     */
    public function getFilterableRelations(): array 
    {
        return $this->filterableRelations ?? [];
    }

    /**
     * Returns filterable attributes of the model.
     * 
     * @return array
     */
    public function getFilterableAttributes(): array 
    {
        return $this->filterableAttributes ?? [];
    }
}
