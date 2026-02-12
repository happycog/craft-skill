<?php

namespace happycog\craftmcp\helpers;

use Craft;
use craft\fields\Matrix;
use craft\models\EntryType;

/**
 * Get entry types from a Matrix field, handling differences between Craft 4 and 5.
 *
 * In Craft 4, Matrix fields have getBlockTypes() which returns block types
 * In Craft 5, Matrix fields have getEntryTypes() which returns entry types
 *
 * @return array<EntryType>
 */
function getMatrixSubTypes(Matrix $field): array
{
    if (\Composer\Semver\Semver::satisfies(Craft::$app->version, '~5.0')) {
        return $field->getEntryTypes();  // @phpstan-ignore-line
    }

    return $field->getBlockTypes();  // @phpstan-ignore-line
}
