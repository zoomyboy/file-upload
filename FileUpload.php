<?php

namespace Zoomyboy\FileUpload;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Intervention\Image\ImageManagerStatic as Image;

trait FileUpload {
	/**
	 * Save uploaded file to disk and create file versions
	 *
	 * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file The file instance from upload, with field name!
	 */
	public function saveFile($file) {
		if(!property_exists($this, 'fileUpload')) {throw new FileUploadException("You should provide a configuration to your model!");}

		$filename = $this->id . '.' . $file->getClientOriginalExtension();
		$service = new FileUploadService($this->fileUpload, $this, $filename);
		$service->saveFile($file);

		$this->{$this->imageColumn} = $filename;
		$this->save();
	}

	public function imageVersionUrl($versionName) {
		$filename = $this->{$this->imageColumn};
		if ($filename != '')  {
			$service = new FileUploadService($this->fileUpload, $this, $filename);
			return $service->getVersionUrl($versionName);
		} else {
			return false;
		}
	}

	/**
	 * Deletes the associated images for the current record
	 *
	 * @param void
	 *
	 * @return void
	 */
	public function unlink() {
		if (isset($this->{$this->imageColumn}) && $this->{$this->imageColumn} != '') {
			$service = new FileUploadService($this->fileUpload, $this, $this->{$this->imageColumn});
			$service->unlinkImages();

			$this->{$this->imageColumn} = '';
			$this->save();

			return '[]';
		}
	}

	public function __get($value) {
		if (strstr($value, 'Url') === false) {
			return parent::__get($value);
		} else {
			$service = new FileUploadService($this->fileUpload, $this, $this->{$this->imageColumn});
			$versionName = strtolower(str_replace($this->imageColumn, '', str_replace('Url', '', $value)));
			$version = $service->getVersionFromName($versionName);

			if ($version != null) {
				if ($this->imageVersionUrl($versionName)) {
					return $this->imageVersionUrl($versionName);
				} else {
					return url(str_replace('public', '', $this->fileUpload['default']));
				}
			} else {
				return '';
			}
		}
	}
}
