<?php
/*
 * Wildcard Helper Classes
 * PortableObject Class Structure
 */

declare(strict_types=1);

/**
 * provides a standard interface for object import/export
 */
interface PortableObjectInterface010000
{
	public function export($options = '');
	public function import($xml);
	public function buildRow();
}