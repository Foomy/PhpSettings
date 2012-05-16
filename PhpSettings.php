<?php

/**
 * PhpSettings
 *
 * This class provides methods to save entire objects in
 * configuration files. It is based upon Zend_Config.
 *
 * @package	PhpSettings
 * @author	Sascha Schneider <foomy.code@arcor.de>
 *
 * @uses	Zend_Config_Ini
 * @uses	Zend_Config_Writer_Ini
 * @uses	Zend_Config_Xml
 * @uses	Zend_Config_Writer_xml
 *
 * Zend_Config is written by Zend Technologies USA Inc. (http://www.zend.com)
 */

defined('PHP_SETTINGS_PATH')
	|| define('PHP_SETTINGS_PATH', realpath(dirname(__FILE__)));

set_include_path(implode(PATH_SEPARATOR, array(
	get_include_path(),
	PHP_SETTINGS_PATH
)));

require_once 'fb.php';

require_once 'Zend/Config/Ini.php';
require_once 'Zend/Config/Writer/Ini.php';
require_once 'Zend/Config/Xml.php';
require_once 'Zend/Config/Writer/Xml.php';
require_once 'Zend/Config/Exception.php';


class PhpSettings
{
	const SAVE_AS_INI = 'ini';
	const SAVE_AS_XML = 'xml';

	const ERR_NO_FILE		= 'No configfile loaded! Use loadFile() to load a file.';
	const ERR_NO_EXT		= 'Unable to determine file extension.';
	const ERR_NO_OBJECTS	= 'There are no objects in the register.';

	private $_objRegister	= array();
	private $_filenames		= array();
	private $_config		= null;
	private $_writer		= null;
	private $_mode			= null;
	private $_savePath		= '';

	/**
	 * API constructor.
	 *
	 * @param	$file		Optional! Name of the file to be laoded.
	 * @param	$configType	Optional! Type of the config to be read or saved.
	 * @return	PhpSettings
	 */
	public function __construct($file = '', $configType = self::SAVE_AS_INI)
	{
		switch ($configType) {
			case self::SAVE_AS_INI:
				$this->_mode = self::SAVE_AS_INI;
				$this->initConfigIni($file);
				break;

			case self::SAVE_AS_XML:
				$this->_mode = self::SAVE_AS_XML;
				$this->initConfigXml($file);
				break;

			default:
				$this->_mode = self::SAVE_AS_INI;
				$this->initConfigIni($file);
		}// switch
	}

	/**
	 * Adds an object to the object register.
	 *
	 * @param	Object $object
	 * @return	void
	 */
	public function addObject($object)
	{
		if ( null !== $object && is_object($object) ) {
			$this->_objRegister[] = $object;
		}
	}

	/**
	 * Adds several objects to the object register.
	 *
	 * @param	Array $objects
	 * @return	void
	 */
	public function addObjects(Array $objects)
	{
		foreach ($objects as $object) {
			$this->addObject($object);
		}
	}

	/**
	 * Sets the filename for the configuration file in which the object
	 * will be stored.
	 *
	 * @param	string $filename
	 */
	public function addFilename($filename)
	{
		$this->filename = $filename;
	}

	/**
	 * Sets several filenames, if more than one objects should be stored.
	 *
	 * @param	array $filenames
	 */
	public function addFilenames(Array $filenames)
	{
		foreach ($filenames as $filename) {
			$this->addFilename($filename);
		}
	}

	/**
	 * Returns the read configuration
	 * as an object.
	 *
	 * @param	void
	 * @return	Zend_Config_Ini | Zend_Config_Xml
	 */
	public function getConfigAsObject()
	{
		if ( null !== $this->_config ) {
			return $this->_config;
		} else {
			throw new Exception(self::ERR_NO_FILE_LOADED);
		}
	}

	/**
	 * Returns the read configuration
	 * as an array.
	 *
	 * @param	void
	 * @return	array
	 */
	public function getConfigAsArray()
	{
		return $this->_config->toArray();
	}

	/**
	 * Sets the path for new object to be saved.
	 * 
	 * @param	string $path
	 */
	public function setSavePath($path) {
		if (! empty($path)) {
			$this->savePath = $path;
		}
	}
	
	/**
	 * Prepares the the config writer for writing an existing file.
	 *
	 * @param	string $file
	 * @return	void
	 */
	public function loadFile($file)
	{
		switch ($this->extractFileExtension($file)) {
			case self::SAVE_AS_INI:
				$this->initConfigIni($file);
				break;

			case self::SAVE_AS_XML:
				$this->initConfigXml($file);
				break;

			default:
				throw new Exception(self::ERR_NO_EXT);
		}// switch
	}

	/**
	 * Writes the configuration to the file.
	 *
	 * @param	void
	 * @return	void
	 *
	 * @throws	Exception
	 */
	public function save()
	{
		if ( empty($this->_objRegister) ) {
			throw new Exception(self::ERR_NO_OBJECTS);
		}

		$this->processObjects($this->_objRegister);

		$this->_writer->write();
	}

	/**
	 * This Method is used by the constructor to
	 * prepare the config writer and file for
	 * writing ini files.
	 *
	 * @todo	Try to merge initConfigIni() and initConfigXml()
	 *
	 * @param	string $file
	 * @return	void
	 */
	protected function initConfigIni($file)
	{
		
		/*
		 * Prepare existing config file for read and edit,
		 *
		 */
		if ( ! empty($file) ) {
			$this->file = $file;
			$this->_config = new Zend_Config_Ini($file, null, array(
				'skipExtends' => true,
				'allowModifications' => true
			));
		}

		/*
		 * Prepare writer for createing new .ini files.
		 */
		$this->_writer = new Zend_Config_Writer_Ini();

		if ( null !== $this->_config ) {
			$this->_writer->setConfig($this->_config);
		}

		if ( ! empty($file) ) {
			$this->_writer->setFilename($file);
		}
	}

	/**
	 * This Method is used by the constructor to
	 * prepare the config writer and file for
	 * writing xml files.
	 *
	 * @todo	Try to merge initConfigIni() and initConfigXml()
	 *
	 * @param	string $file
	 * @return	void
	 */
	protected function initConfigXml($file)
	{
		/*
		 * Prepare existing config file for read and edit
		 */
		if ( ! empty($file) ) {
			$this->_config = new Zend_Config_Xml($file, null, array(
				'skipExtends' => true,
				'allowModifications' => true
			));
		}

		/*
		 * Prepare writer for createing new .xml files.
		 */
		$this->_writer = new Zend_Config_Writer_Xml();

		if ( null !== $this->_config ) {
			$this->_writer->setConfig($this->_config);
		}

		if ( ! empty($file) ) {
			$this->_writer->setFilename($file);
		}

	}

	/**
	 * Extracts the file extension of a given filepath.
	 *
	 * @param	string $filepath
	 * @return	string $extension
	 */
	protected function extractFileExtension($filepath)
	{
		$fileArray = array_reverse(explode('/', $filepath));
		list($name, $extension) = explode('.', $fileArray[0]);

		return $extension;
	}

	/**
	 * Processes rekursively the objects in the object register
	 *
	 * @param	void
	 * @return	void
	 */
	protected function processObjects(Array $objects)
	{
		foreach ($objects as $object) {
			if (is_object($object)) {
				$this->processObject($object);
			}
		}
	}

	/**
	 * Processes one Object and push it to the config,
	 * in order to prepare it for saving.
	 *
	 * @todo	Dynamically find the right getter for each property.
	 *
	 * @param	Mixed $object
	 */
	protected function processObject($object, $i = 0)
	{
		/*
		 * Identify object name, in order to create
		 * a section in the INI file.
		 * 
		 * @todo: Each object should create a new file named equal to the object.
		 */
		if (method_exists($object, 'getId')) {
			$section = '[' . get_class($object) . '_' . $object->getId() . ']';
		} else {
			$section = '[' . get_class($object) . '_' . ++$i . ']';
		}

		/*
		 * Use reflection class in order to analyse the object.
		 */
		$reflect = $this->getReflection($object);
		
		$methods = $this->extractMethodNames($reflect);
		$propertys = $this->extractPropertys($reflect);
	}
	
	protected function extractMethodNames(ReflectionClass $reflect) {
		$methods = array();
		foreach ($reflect->getMethods() as $methodInfo) {
			if ('__construct' !== $methodInfo->name) {
				$methods[] = $methodInfo->name;
			}
		}
		return $methods;
	}
	
	protected function extractPropertys(ReflectionClass $reflect, $methods = array()) {
		foreach ($reflect->getProperties() as $property) {
			$property = substr($property->name, 1);
			$getterName = 'get' . ucfirst($property);

			if (in_array($getterName, $methods)) {
				$value = $object->$getterName();
			}

			if (is_array($value)) {
				$this->processObjects($value);
			} elseif (is_object($value)) {
				$this->processObject($value);
			} else {
				$keyValuePair = $property . ' = ' . $value . PHP_EOL;
			}
		}
	}
	
	protected function getReflection($object, ReflectionClass $reflect = null) {
		if (null === $reflect) {
			$reflect = new ReflectionClass($object);
		}
		return $reflect;
	} 
}

/**
 *  "Wenn wir heute noch was vermasseln koennen, sagt mir bescheid!"
 *  (James T. Kirk, Star Trek VI - Das unendeckte Land)
 */