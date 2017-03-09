<?php
/**
 * Represents an oembed object.  Basically populated from oembed so the front end has quick access to properties.
 */
class EmbedObject extends DataObject {

	static $db = array(
		'SourceURL' => 'Varchar(255)',
		'Title' => 'Varchar(255)',
		'Type' => 'Varchar(255)',
		'Version' => 'Float',

		'Width' => 'Int',
		'Height' => 'Int',

		'ThumbnailURL' => 'Varchar(355)',
		'ThumbnailWidth' => 'Int',
		'ThumbnailHeight' => 'Int',

		'ProviderURL' => 'Varchar(255)',
		'ProviderName' => 'Varchar(255)',

		'AuthorURL' => 'Varchar(255)',
		'AuthorName' => 'Varchar(255)',

		'EmbedHTML' => 'HTMLText',
		'HTML' => 'HTMLText',
		'URL' => 'Varchar(355)',
		'Origin' => 'Varchar(355)',
		'WebPage' => 'Varchar(355)'
	);

	public $updateOnSave = false;

	public $sourceExists = false;

	function sourceExists() {
		return ($this->ID != 0 || $this->sourceExists);
	}

	function updateFromURL($sourceURL = null, $options = []) {
		if ($this->SourceURL) {
			$sourceURL = $this->SourceURL;
		}
		$info = Oembed::get_oembed_from_url($sourceURL, false, $options);

		$this->updateFromObject($info);
	}

	function updateFromObject(Oembed_Result $info) {

		if ($info && $info->exists()) {
			$this->sourceExists = true;

			$this->Title = $info->title;
			$this->Type = $info->type;

			$this->Width = $info->width;
			$this->Height = $info->height;

			$this->ThumbnailURL = $info->thumbnail_url;
			$this->ThumbnailWidth = $info->thumbnail_width;
			$this->ThumbnailHeight = $info->thumbnail_height;

			$this->ProviderURL = $info->provider_url;
			$this->ProviderName = $info->provider_name;


			$this->AuthorURL = $info->author_url;
			$this->AuthorName = $info->author_name;


			$this->EmbedHTML = $info->forTemplate();
			$this->HTML = $info->html;
			$this->URL = $info->url;
			$this->Origin = $info->origin;
			$this->WebPage = $info->web_page;

		} else {
			$this->sourceExists = false;
		}




	}

	/**
	 * Return the object's properties as an array
	 * @return array
	 */
	function toArray() {
		if ($this->ID == 0) {
			return array();
		} else {

			$array = $this->toMap();
			unset($array['Created']);
			unset($array['Modified']);
			unset($array['ClassName']);
			unset($array['RecordClassName']);
			unset($array['ID']);
			unset($array['SourceURL']);

			return $array;
		}



	}

	function onBeforeWrite() {
		parent::onBeforeWrite();

		if ($this->updateOnSave === true) {
			$this->updateFromURL($this->SourceURL);
			$this->updateOnSave = false;
		}

	}


	function forTemplate() {
		if ($this->Type) {
			return $this->renderWith($this->ClassName.'_'.$this->Type);
		}
		return false;
	}

	/**
	 * This is used for making videos responsive.  It uses the video's actual dimensions to calculate the height needed for it's aspect ratio (when using this technique: http://alistapart.com/article/creating-intrinsic-ratios-for-video)
	 * @return string 	Percentage for use in CSS
	 */
	function getAspectRatioHeight() {
		return ($this->Height / $this->Width) * 100 . '%';
	}

}
