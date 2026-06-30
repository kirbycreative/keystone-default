<?php

namespace Keystone\Toolkit\Support;

use Keystone\Toolkit\Models\AppModel;

/**
 * Resolves the public model alias the client sends (the juice model's tableName)
 * to a concrete AppModel class, using the whitelist in config/models.php. This is
 * the security boundary for the generic model API: unlisted classes are unreachable.
 */
class ModelRegistry
{
    /**
     * @return array<string, class-string>
     */
    public static function aliases(): array
    {
        return (array) config('keystone.models.aliases', []);
    }

    /**
     * Resolve an alias to its registered class, or null when not whitelisted.
     *
     * @return class-string<AppModel>|null
     */
    public static function resolve(string $alias): ?string
    {
        $class = static::aliases()[$alias] ?? null;

        if ($class === null || ! class_exists($class) || ! is_subclass_of($class, AppModel::class)) {
            return null;
        }

        return $class;
    }

    /**
     * The alias a class is registered under, or null when it is not whitelisted.
     */
    public static function aliasFor(string $class): ?string
    {
        $alias = array_search($class, static::aliases(), true);

        return $alias === false ? null : $alias;
    }
}
