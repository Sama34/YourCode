<?php
/**
 * Wildcard Helper Classes - External PHP Module Wrapper
 * interface
 */

declare(strict_types=1);

/**
 * standard interface for external PHP modules
 *
 * can be used to wrap any external PHP module for secure loading,
 * validation and execution of its functions
 */
interface ExternalModuleInterface010000
{
	public function load($module);
	public function run($function_name, $args = '');
	public function remove();
}