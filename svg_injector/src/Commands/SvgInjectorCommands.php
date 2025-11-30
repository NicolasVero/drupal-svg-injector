<?php

	namespace Drupal\svg_injector\Commands;

	use Drush\Commands\DrushCommands;
	use Drupal\Core\Cache\Cache;

	/**
	 * Drush commands for Svg Injector module.
	 */
	class SvgInjectorCommands extends DrushCommands {

	/**
	 * Clears the SVG index cache.
	 *
	 * @command svg-injector:cache-remove
	 * @aliases svg-injector:cr, si:cr
	 * @usage svg-injector:cache-remove
	 *   Clears the cached SVG index.
	 */
	public function clearCache() {
		Cache::invalidateTags(['svg_injector:index']);
		$this->logger()->success(dt('SVG cache has been cleared.'));
	}
}
