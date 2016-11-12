<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Intervention\Image\ImageManagerStatic as Image;

trait FileHandler {
	private $named = 'id';                //const
	private $isConfigured = false;

	/**
	 * Set global configuration
	 *
	 * @param void
	 *
	 * @return void
	 */
	private function setConfig() {
		if (!$this->isConfigured) {
			array_walk($this->versions, [ $this, 'setVersionType' ]);


			if (!$this->imageCol) {
				$this->imageCol = 'image';
			}

			$this->thumbPath = buildPath(config($this->thumbPath));

			$this->isConfigured = true;
		}
	}

	/**
	 * Set a version's mode-configuration as an array
	 *
	 * @param mixed $config The Config array of one version
	 *
	 * @return void
	 */
	private function setVersionType($config) {
		$mode = $config[ 3 ];
		if (isset ($mode) && is_string($mode)) {
			$mode = explode('|', $mode);
		}
	}

	/**
	 * Save uploaded file to disk and create file versions
	 *
	 * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file The file instance from upload, with field name!
	 *
	 * @return string Complete filename to uploaded original image
	 */
	public function saveFile($file) {
		$this->setConfig();

		$info = pathinfo($file->getClientOriginalName());
		switch ($this->named) {
			case 'id':
				$filename = buildPath(false, $this->id, $info[ 'extension' ]);
				break;
		}

		$file->move($this->thumbPath, $filename);

		$this->createThumbnails($filename);

		$finalPath = '/'.buildPath($this->thumbPath, $filename);

		$this->{$this->imageCol} = $finalPath;
		$this->save();

		return buildPath($this->thumbPath, $filename);
	}

	public function saveBase64File($file, $ext) {
		$this->setConfig();

		switch ($this->named) {
			case 'id':
				$filename = buildPath(false, $this->id, $ext);
				break;
		}

		switch ($ext) {
			case 'png':
				$content = str_replace('data:image/png;base64,','',$file);
		}

		Image::make($file)->save($this->thumbPath.'/'.$filename);

		$this->createThumbnails($filename);

		$finalPath = '/'.buildPath($this->thumbPath, $filename);

		$this->{$this->imageCol} = $finalPath;
		$this->save();

		return buildPath($this->thumbPath, $filename);
	}

	public function getSmallImageAttribute() {
		if (!$this->attributes[ $this->imageCol ]) {
			return false;
		}
		$this->setConfig();
		$filename = str_replace($this->thumbPath, '', $this->attributes[ $this->imageCol ]);

		return '/'.$this->getVersionFile($filename, 'small');
	}

	public function getMediumImageAttribute() {
		if (!$this->attributes[ $this->imageCol ]) {
			return false;
		}
		$this->setConfig();
		$filename = str_replace($this->thumbPath, '', $this->attributes[ $this->imageCol ]);

		return '/'.buildPath($this->thumbPath, $this->getVersionFilename($filename, 'medium'));
	}

	public function getLargeImageAttribute() {
		if (!$this->attributes[ $this->imageCol ]) {
			return false;
		}
		$this->setConfig();
		$filename = str_replace($this->thumbPath, '', $this->attributes[ $this->imageCol ]);

		return '/'.buildPath($this->thumbPath, $this->getVersionFilename($filename, 'large'));
	}

	/**
	 * Create Thumbnail of file for given Version
	 *
	 * @param string $filename Name of the file to create thumbnails
	 * @param        $version  The version to use - including resize rules, widths and heights
	 */
	private function createThumbnail($filename, $version) {
		$v = $this->versions[ $version ];
		$file = buildPath($this->thumbPath, $filename);

		if ($v[ 1 ] < 0 && $v[ 2 ] < 0) {
			throw new FileException("No diensions found!");
		}

		if (file_exists($file)) {
			$img = Image::make($file);
		} else {
			throw new FileException("File existiert nicht!");
		}

		if ($v[ 1 ] == -1) {
			//only height is set
			$img->heighten($v[ 2 ], function($c) {
				$c->upsize();
			});
		} elseif ($v[ 2 ] == -1) {
			//only width is set
			$img->widen($v[ 1 ], function($c) {
				$c->upsize();
			});
		} else {
			/* @TODO image settings for both dimensions (scale, crop, cover, contain, ...) */
		}

		$img->save($this->getVersionFile($filename, $version));
	}

	/**
	 * Create thumbnails for every configured version
	 *
	 * @param string $filename The filename of the original file
	 *
	 * @return void
	 */
	private function createThumbnails($filename) {
		foreach ($this->versions as $vName => $version) {
			$this->createThumbnail($filename, $vName);
		}
	}

	/**
	 * Gets the filename of a versioned file
	 *
	 * @param $filename The original filename
	 * @param $version  The version to use
	 *
	 * @return string The versioned filename
	 */
	public function getVersionFilename($filename, $version) {
		if (!$filename) {
			return false;
		}
		$suffix = $this->versions[ $version ][ 0 ];

		$fileinfo = pathinfo($filename);

		return buildPath(false, $fileinfo[ 'filename' ].$suffix, $fileinfo[ 'extension' ]);
	}

	/**
	 * Gets the filepath of a versioned file
	 *
	 * @param $filename The original filename
	 * @param $version  The version to use
	 *
	 * @return string The versioned file
	 */
	private function getVersionFile($filename, $version) {
		return buildPath($this->thumbPath, $this->getVersionFilename($filename, $version));
	}

	public function getFilenameAttribute() {
		return pathinfo($this->attributes[ $this->imageCol ], PATHINFO_FILENAME);
	}

	/**
	 * Deletes the associated images for the current record
	 *
	 * @param void
	 *
	 * @return void
	 */
	public function unlink() {
		if ($this->{$this->imageCol}) {
			$this->setConfig();

			@unlink(trim($this->{$this->imageCol}, '/'));

			$filename = str_replace($this->thumbPath, '', $this->{$this->imageCol});

			$filenameBase = pathinfo($filename, PATHINFO_BASENAME);

			foreach ($this->versions as $versionName => $versionParams) {
				@unlink($this->getVersionFile($filenameBase, $versionName));
			}
		}
	}
}