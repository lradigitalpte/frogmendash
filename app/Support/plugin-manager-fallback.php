<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;

if (! class_exists('Webkul\\PluginManager\\PackageServiceProvider')) {
    class_alias(ServiceProvider::class, 'Webkul\\PluginManager\\PackageServiceProvider');
}

if (! class_exists('Webkul\\PluginManager\\Package')) {
    // Minimal no-op fallback used only during constrained build environments.
    class Package
    {
        public function name(string $name): self
        {
            return $this;
        }

        public function hasTranslations(): self
        {
            return $this;
        }

        public function hasViews(): self
        {
            return $this;
        }

        public function hasMigrations(array $migrations = []): self
        {
            return $this;
        }

        public function runsMigrations(): self
        {
            return $this;
        }

        public function hasSeeder(string $seeder): self
        {
            return $this;
        }

        public function hasInstallCommand(callable $callback): self
        {
            return $this;
        }

        public function hasUninstallCommand(callable $callback): self
        {
            return $this;
        }

        public function icon(string $icon): self
        {
            return $this;
        }
    }

    class_alias(Package::class, 'Webkul\\PluginManager\\Package');
}
