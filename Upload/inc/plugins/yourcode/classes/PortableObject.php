<?php
/**
 * portable object definition
 *
 * @category  MyBB Plugins
 * @package   YourCode
 * @author    Mark Vincent <admin@rantcentralforums.com>
 * @copyright 2012-2014 Mark Vincent
 * @license   http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link      https://github.com/WildcardSearch/YourCode
 * @since     1.1
 */

/**
 * provides a standard interface for object import/export
 */
interface PortableObjectInterface
{
	public function export($options = '');
	public function import($xml);
	public function buildRow();
}

/**
 * provides functionality to import and export any StorableObject as an
 * XML file and to output a row to be included in a collection exported by
 * an outside function
 */
abstract class PortableObject extends StorableObject implements PortableObjectInterface
{
	/**
	 * provides export functionality for any StorableObject
	 *
	 * @param  array basic export options
	 * @return void
	 */
	public function export($options = '')
	{
		if ($this->tableName &&
			$this->id) {
			$row = $this->buildRow();
			$id = (int) $this->id;

			if ($row) {
				$name = $this->getCleanIdentifier();
				$defaultValues = array(
					"charset" => 'UTF-8',
					"version" => '1.0',
					"website" => 'http://www.rantcentralforums.com',
					"filename" => "{$this->tableName}_{$name}-backup.xml",
				);

				// try to get MyBB default charset
				global $lang;
				if (isset($lang->settings['charset'])) {
					$defaultValues['charset'] = $lang->settings['charset'];
				}

				if (is_array($options) &&
					!empty($options)) {
					foreach ($defaultValues as $key => $value) {
						if (!isset($options[$key]) ||
							!$options[$key]) {
							$options[$key] = $value;
						}
					}
				} else {
					$options = $defaultValues;
				}

				$xml = <<<EOF
<?xml version="1.0" encoding="{$options['charset']}"?>
<{$this->tableName} version="{$options['version']}" xmlns="{$options['website']}">
<{$this->tableName}_{$id}>
{$row}	</{$this->tableName}_{$id}>
</{$this->table_name}>
EOF;
				// send out headers (opens a save dialogue)
				header("Content-Disposition: attachment; filename={$options['filename']}");
				header('Content-Type: application/xml');
				header('Content-Length: ' . strlen($xml));
				header('Pragma: no-cache');
				header('Expires: 0');
				echo $xml;
			}
		}
		return false;
	}

	/**
	 * import an object from XML
	 *
	 * @param  string the contents of the XML file to be imported
	 * @return bool true on success, false on fail
	*/
	public function import($xml)
	{
		if ($xml) {
			require_once MYBB_ROOT . 'inc/class_xml.php';
			$parser = new XMLParser($xml);
			$tree = $parser->get_tree();

			// only doing a single YourCode backup, fail if multi detected
			if (is_array($tree) &&
				is_array($tree[$this->table_name]) &&
				is_array($tree[$this->table_name]['attributes']) &&
				isset($tree[$this->table_name]['attributes']['contains']) &&
				$tree[$this->table_name]['attributes']['contains'] == 'single') {
				foreach ($tree[$this->table_name] as $property => $this_entry) {
					// skip the info
					if (in_array($property, array('tag', 'value', 'attributes'))) {
						continue;
					}

					// if there is data
					if (is_array($this_entry) &&
						!empty($this_entry)) {
						foreach ($this_entry as $key => $value) {
							// skip the info
							if (in_array($key, array('tag', 'value'))) {
								continue;
							}

							// get the field name from the array key
							$key_array = explode('-', $key);
							$newkey = $key_array[0];

							// is it a valid property name for this object?
							if (property_exists($this, $newkey)) {
								// then store it
								$this->$newkey = $value['value'];
							}
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * build a single row of XML markup for this object
	 *
	 * @return string|bool the XML markup or false on fail
	 */
	public function buildRow()
	{
		// object must have been saved (it exists in the db) in order to be exported
		if ($this->tableName &&
			$this->id) {
			$row = '';
			$id = (int) $this->id;
			foreach ($this as $property => $value) {
				// skip inherited properties
				if (in_array($property, array('id', 'valid', 'data', 'tableName', 'active'))) {
					continue;
				}
				$row .= <<<EOF
	<{$property}-{$id}><![CDATA[{$value}]]></{$property}-{$id}>

EOF;
			}
			return $row;
		}
		return false;
	}

	/**
	 * returns the name, title or ID (first available-- in that order) to
	 * be used as a unique identifier
	 *
	 * @access private
	 * @return string the identifier
	 */
	private function getCleanIdentifier()
	{
		if (property_exists($this, 'name') &&
			trim($this->name)) {
			$name = $this->name;
		} else if (property_exists($this, 'title') &&
				   trim($this->title)) {
			$name = $this->title;
		}

		// using a string, clean it
		if ($name) {
			// clean and return
			$find = array(
				"#(\s)+#s",
				"#[^\w_]#is"
			);
			$replace = array(
				'_',
				''
			);
			return preg_replace($find, $replace, strtolower(trim($name)));
		}
		// no name or title, return ID (all storables have an ID)
		return (int) $this->id;
	}
}

?>
