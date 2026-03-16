<?php

namespace happycog\craftmcp\tools;

use craft\db\Query;
use craft\db\Table;
use craft\services\UserPermissions;

class GetAvailablePermissions
{
    public function __construct(
        protected UserPermissions $userPermissions,
    ) {
    }

    /**
     * List all known Craft permissions plus any custom permission names that have been stored.
     *
     * The response preserves Craft's grouped permission structure for registered permissions and also
     * returns flat lists of all permission names and custom-only names for discovery workflows.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $permissionGroups = $this->userPermissions->getAllPermissions();
        $registeredPermissionNames = [];
        $flattenedPermissions = [];
        $formattedGroups = [];

        foreach ($permissionGroups as $group) {
            $heading = $group['heading'] ?? null;
            $permissions = $group['permissions'] ?? [];

            if (!is_string($heading) || !is_array($permissions)) {
                continue;
            }

            $formattedGroups[] = [
                'heading' => $heading,
                'permissions' => $this->formatPermissions($permissions, $heading, $registeredPermissionNames, $flattenedPermissions),
            ];
        }

        $storedPermissionNames = (new Query())
            ->select(['name'])
            ->from(Table::USERPERMISSIONS)
            ->orderBy(['name' => SORT_ASC])
            ->column();

        $storedPermissionNames = array_values(array_filter($storedPermissionNames, 'is_string'));
        $registeredPermissionNames = array_values(array_unique($registeredPermissionNames));
        $customPermissionNames = array_values(array_diff($storedPermissionNames, $registeredPermissionNames));
        $allPermissionNames = array_values(array_unique(array_merge($registeredPermissionNames, $storedPermissionNames)));
        $customPermissions = array_map(fn(string $name) => [
            'name' => $name,
            'label' => $name,
            'info' => null,
            'warning' => null,
            'groupHeading' => 'Custom Permissions',
            'isCustom' => true,
        ], $customPermissionNames);

        sort($customPermissionNames);
        sort($allPermissionNames);

        return [
            'groups' => $formattedGroups,
            'allPermissions' => $flattenedPermissions,
            'allPermissionNames' => $allPermissionNames,
            'customPermissions' => $customPermissions,
            'customPermissionNames' => $customPermissionNames,
        ];
    }

    /**
     * @param array<string, mixed> $permissions
     * @param string $groupHeading
     * @param string[] $registeredPermissionNames
     * @param array<int, array<string, mixed>> $flattenedPermissions
     * @return array<int, array<string, mixed>>
     */
    private function formatPermissions(
        array $permissions,
        string $groupHeading,
        array &$registeredPermissionNames,
        array &$flattenedPermissions,
    ): array
    {
        $formatted = [];

        foreach ($permissions as $name => $config) {
            if (!is_string($name) || !is_array($config)) {
                continue;
            }

            $normalizedName = strtolower($name);
            $registeredPermissionNames[] = $normalizedName;
            $nested = $config['nested'] ?? [];
            $label = is_string($config['label'] ?? null) ? $config['label'] : $name;
            $info = is_string($config['info'] ?? null) ? $config['info'] : null;
            $warning = is_string($config['warning'] ?? null) ? $config['warning'] : null;

            $formattedPermission = [
                'name' => $normalizedName,
                'label' => $label,
                'info' => $info,
                'warning' => $warning,
                'nested' => is_array($nested) ? $this->formatPermissions($nested, $groupHeading, $registeredPermissionNames, $flattenedPermissions) : [],
            ];

            $formatted[] = $formattedPermission;
            $flattenedPermissions[] = [
                'name' => $normalizedName,
                'label' => $label,
                'info' => $info,
                'warning' => $warning,
                'groupHeading' => $groupHeading,
                'isCustom' => false,
            ];
        }

        return $formatted;
    }
}
