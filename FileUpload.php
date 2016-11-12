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
		$service = new FileUploadService($this->fileUpload, $this, $filename);
		return $service->getVersionUrl($versionName);
	}

	/**
	 * Deletes the associated images for the current record
	 *
	 * @param void
	 *
	 * @return void
	 */
	public function unlinkImages() {
		if (isset($this->{$this->imageColumn}) && $this->{$this->imageColumn} != '') {
			$this->setConfig();

			@unlink(base_path($this->{$this->imageCol}));

			$filename = str_replace($this->imagePath, '', $this->{$this->imageCol});

			$filenameBase = pathinfo($filename, PATHINFO_BASENAME);

			foreach ($this->versions as $versionName => $versionParams) {
				@unlink($this->getVersionFile($filenameBase, $versionName));
			}
		}
	}
}
