<?php

namespace Zoomyboy\FileUpload;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Intervention\Image\ImageManagerStatic as Image;

trait FileUpload {
	/**
	 * @var string $imageColumn Column Name in the model where to save the image path.
	 *                          Should be of type 'string' or 'text'.
	 *                          Should be a fillable attribute or a accessor (setter)
	 */
	private $imageColumn = 'image';

	/**
	 * Save uploaded file to disk and create file versions
	 *
	 * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file The file instance from upload, with field name!
	 *
	 * @return string Complete filename to uploaded original image
	 */
	public function saveFile($file) {
		$service = new FileUploadService($this->fileUpload, $this, $file);
		$path = $service->saveFile();

		$this->{$this->imageColumn} = $path;
		$this->save();

		return $path; 
	}

	public function imageVersionUrl($versionName) {
		return FileUploadService::getImageVersionFileUrl($this->fileUpload, $this, $versionName);
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
