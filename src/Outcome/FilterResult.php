<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @copyright 2025 Cognitas d.o.o.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 */

namespace Cognitas\QueryFilter\Outcome;

class FilterResult
{

    protected array $issues = [];

    /**
     * Adds a FilterIssue to the array
     * 
     * @param \Cognitas\QueryFilter\Outcome\FilterIssue $issue
     * @return void
     */
    public function addIssue(FilterIssue $issue)
    {
        $this->issues[] = $issue;
    }

    /**
     * Returns all FilterIssues
     * 
     * @return array
     */
    public function issues(): array
    {
        return $this->issues;
    }

    /**
     * Checks if the FilterIssues array is non empty
     * 
     * @return bool
     */
    public function hasErrors(): bool
    {
        return count($this->issues)>0;
    }
}
