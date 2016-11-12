<?php

namespace Zoomyboy\FileUpload;

use Zoomyboy\Helpers\ConfigHelper;

class FileUploadService {
	private $config;
	private $model;

	/**
	 * FileUploadService Constructor
	 *
	 * @param array|string $config The configuration from the model
	 * @param string $file The file - relative to saving directory
	 */
	public function __construct($config, $model) {
		$this->config = new ConfigHelper($config);
		$this->model = $model;
		return $this;
	}

	public function saveFile($file) {
		$info = pathinfo($file->getClientOriginalName());

		$file->move($this->getBaseImagePath(), $this->getFilename($file));
		$this->createThumbnails();

		return buildPath($this->getBaseImagePath(), $this->getFilename($file));
	}

	public static function getImageVersionFileUrl($config, $model, $versionName) {
		$config = new ConfigHelper($config);
		$file = $model->{$config->path};
		return url(buildPath($config->path, buildFilename($model->id.'_'.$versionName, $file)));
	}

	/**
	 * Gets Fully Qualified File Path where images will be stored
	 *
	 * @return string
	 */
	private function getBaseImagePath() {
		return base_path(rtrim($this->config->path, '/')) . '/';
	}

	/**
	 * Gets the Filename of the Image in original size
	 *
	 * @return string
	 */
	private function getFilename($file) {
		return buildFilename($this->model->id, $file->getClientOriginalName());
	}

	/**
	 * Gets name of the file with given version
	 *
	 * @param Zoomyboy\Helpers\ConfigHelper $version Version information
	 *
	 * @return string
	 */
	private function getVersionFilename($version) {
		return getFilename($this->model->id.'_'.$version->name, $this->getFilename($file));
	}

	/**
	 * Create all Thumbnails of file
	 */
	private function createThumbnails() {
		$file = buildPath($this->getBaseImagePath(), $this->getFilename());

		foreach($this->config->versions as $version) {
			$newFile = buildPath($this->getBaseImagePath(), $this->getVersionFilename($version));
			copy($file, $newFile);
			$img = WideImage::load($newFile);

			$originalSize = getimagesize($file);
			$svh = $originalSize[0] / $originalSize[1];

			if (!$version->width) {$version->width = getWidth($version->height, $svh);}
			if (!$version->height) {$version->height = getHeight($version->width, $svh);}

			switch($version->size) {
				case 'contain': $resizeMode = 'inside'; break;
				case 'fill': $resizeMode = 'fill'; break;
				case 'cover': $resizeMode = 'outside'; break;
			}
			$img->resize($width, $height, $resizeMode, 'any');

			if ($version->size == 'cover') {
				$img->crop("center", "middle", $width, $height);
			}

			$img->saveToPath($newFile);
		}
	}

	public function unlinkImages() {
		
	}
}
