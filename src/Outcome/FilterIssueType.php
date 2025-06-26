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

enum FilterIssueType: string
{
    case SYNTAX_ERROR = 'Syntax error';
    case RELATION_WHITELIST_ERROR = 'Relation whitelist error';
    case RELATION_NONEXISTENT = 'Relation nonexistent';
    case ATTRIBUTE_WHITELIST_ERROR = 'Attribute whitelist error';
}