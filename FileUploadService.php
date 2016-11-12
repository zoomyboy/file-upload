<?php

namespace Zoomyboy\FileUpload;

use Zoomyboy\Helpers\ConfigHelper;
use WideImage\WideImage;

class FileUploadService {
	private $config;
	private $model;
	private $file;

	/**
	 * FileUploadService Constructor
	 *
	 * @param array|string $config The configuration from the model
	 * @param string $file The file - relative to saving directory
	 */
	public function __construct($config, $model, $file) {
		$this->config = new ConfigHelper($config);
		$this->model = $model;
		$this->file = $file;
		return $this;
	}

	public function saveFile($fileResource) {
		$fileResource->move($this->getBaseImagePath(), $this->getFilename());
		$this->createThumbnails();

		return buildPath($this->getBaseImagePath(), $this->getFilename());
	}

	/**
	 * Gets Fully Qualified File Path where images will be stored
	 *
	 * @return string
	 */
	private function getBaseImagePath() {
		return base_path(rtrim($this->config->path, '/')) . '/';
	}

	public function getVersionUrl($versionName) {
		foreach($this->config->versions as $version) {
			$version = new ConfigHelper(cssOrArray($version));
			if ($version->name == $versionName) {
				return url(buildPath(str_replace('public', '', $this->config->path), $this->getVersionFilename($version)));
			}
		}
	}

	/**
	 * Gets the Filename of the Image in original size
	 *
	 * @return string
	 */
	private function getFilename() {
		return buildFilename($this->model->id, $this->file);
	}

	/**
	 * Gets name of the file with given version
	 *
	 * @param string|Array $version Version information
	 *
	 * @return string
	 */
	private function getVersionFilename($version) {
		return buildFilename($this->model->id.'_'.$version->name, $this->getFilename());
	}

	/**
	 * Create all Thumbnails of file
	 */
	private function createThumbnails() {
		$file = buildPath($this->getBaseImagePath(), $this->getFilename());

		foreach($this->config->versions as $version) {
			$version = new ConfigHelper(cssOrArray($version));

			$newFile = buildPath($this->getBaseImagePath(), $this->getVersionFilename($version));
			copy($file, $newFile);

			$originalSize = getimagesize($file);
			$svh = $originalSize[0] / $originalSize[1];

			if (!$version->width) {$version->width = getImageWidth($version->height, $svh);}
			if (!$version->height) {$version->height = getImageHeight($version->width, $svh);}

			switch($version->size) {
				case 'contain': $resizeMode = 'inside'; break;
				case 'fill': $resizeMode = 'fill'; break;
				case 'cover': $resizeMode = 'outside'; break;
			}
			WideImage::load($newFile)->resize($version->width, $version->height, $resizeMode, 'any')->saveToFile($newFile);

			if ($version->size == 'cover') {
				WideImage::load($newFile)->crop("center", "middle", $version->width, $version->height)->saveToFile($newFile);
			}
		}
	}

	public function unlinkImages() {
		
	}
}
