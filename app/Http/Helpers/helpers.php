<?php

if (!function_exists('stage')) {

    /**
     * Stage — the page composition.
     *
     * Single source of truth for which components sit in which region, and the content each one
     * renders with. The same staged state is read two ways:
     *   - Staging: the layout iterates the regions and @includes each component with its content.
     *   - Production: the deploy writer reads the regions and bakes static @include lines.
     *
     * Stage owns regions + content only. Document concerns (title, body class, head assets, app
     * data) belong to page(). Content-editing mutators are intentionally absent for now — they will
     * arrive with the in-site and admin-form editors; today the stage is hydrated (apply/component)
     * and read.
     */
    class Stage
    {
        /** Canonical region render order. Unknown placements are appended after these. */
        const REGIONS = ['preheader', 'header', 'sidebar', 'content', 'footer'];

        /** @var array<string, list<array{component: string, content: array}>> */
        private $_components = [];

        static $instance;

        static function getInstance()
        {
            if (!isset(self::$instance)) {
                self::$instance = new Stage();
            }
            return self::$instance;
        }

        public function __construct() {}

        /**
         * Hydrate the stage from a config array, e.g. a saved staging draft.
         *
         * @param array{components?: list<array{placement: string, component: string, content?: array}>} $config
         */
        public function apply($config = [])
        {
            if (isset($config['components'])) {
                foreach ($config['components'] as $component) {
                    $this->component(
                        $component['placement'],
                        $component['component'],
                        $component['content'] ?? []
                    );
                }
            }

            return $this;
        }

        /**
         * Place a component in a region with the content it renders with.
         */
        public function component($placement, $component, $content = [])
        {
            if (!isset($this->_components[$placement])) {
                $this->_components[$placement] = [];
            }

            $this->_components[$placement][] = [
                'component' => $component,
                'content' => $content ?? [],
            ];

            return $this;
        }

        /**
         * Components for a region as a list, or — when no region is given — every region grouped
         * and ordered by REGIONS (unknown placements appended). Always returns arrays, never null,
         * so callers can @foreach without guarding.
         *
         * @return list<array{component: string, content: array}>|array<string, list<array{component: string, content: array}>>
         */
        public function getComponents($placement = null)
        {
            if ($placement !== null) {
                return $this->_components[$placement] ?? [];
            }

            $ordered = [];

            foreach (self::REGIONS as $region) {
                if (!empty($this->_components[$region])) {
                    $ordered[$region] = $this->_components[$region];
                }
            }

            foreach ($this->_components as $region => $entries) {
                if (!in_array($region, self::REGIONS, true) && !empty($entries)) {
                    $ordered[$region] = $entries;
                }
            }

            return $ordered;
        }

        /**
         * A single region's components, or a named component within a region. Null when absent.
         *
         * @return array{component: string, content: array}|list<array{component: string, content: array}>|null
         */
        public function getComponent($placement = null, $name = null)
        {
            if ($placement === null) {
                return $this->getComponents();
            }

            $entries = $this->_components[$placement] ?? null;

            if ($entries === null) {
                return null;
            }

            if (!empty($name)) {
                foreach ($entries as $entry) {
                    if ($entry['component'] === $name) {
                        return $entry;
                    }
                }
                return null;
            }

            return $entries;
        }

        /**
         * Whether anything is staged: at all, in a region, or a named component in a region.
         */
        public function hasComponent($placement = null, $name = null)
        {
            if ($placement === null) {
                return count($this->_components) > 0;
            }

            if (empty($this->_components[$placement])) {
                return false;
            }

            if (!empty($name)) {
                foreach ($this->_components[$placement] as $entry) {
                    if ($entry['component'] === $name) {
                        return true;
                    }
                }
                return false;
            }

            return true;
        }

        /**
         * Ordered list of region keys that currently hold components — the iteration order shared
         * by the staging layout and the production writer.
         *
         * @return list<string>
         */
        public function regions()
        {
            return array_keys($this->getComponents());
        }
    }


    function stage()
    {
        return Stage::getInstance();
    }
}
