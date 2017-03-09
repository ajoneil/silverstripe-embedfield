<?php
/**
 * The form field used for creating EmbedObjects.  Basically you enter a URL and it fetches the oEmbed data from it and stores it in an EmbedObject.
 */
class EmbedField extends FormField {
	private $embedType = false;		// video, rich, link, photo
	private $oembedOptions = [];


	private static $allowed_actions = array(
		'update'
	);

	protected $object;

	/**
	 *
	 * @param string $name
	 * @param string $title
	 * @param string $value
	 */
	public function __construct($name, $title = null, $value = null) {
		parent::__construct($name, $title, $value);
	}

	/**
	 * Restrict what type of embed object
	 * @param string   The embed type (false (any), video, rich, link or photo)
	 */
	public function setEmbedType($type = false) {
		$this->embedType = $type;
	}

	public function setOembedOptions($options) {
		$this->oembedOptions = $options;
	}

	public function FieldHolder($properties = array()) {
		Requirements::javascript('embedfield/javascript/EmbedField.js');
		Requirements::css('embedfield/css/EmbedField.css');

		if (!$this->object || $this->object->ID == 0) {
			$this->object = EmbedObject::create();
		}

		$properties['ThumbnailURL'] = 'framework/images/spacer.gif';
		$properties['ThumbnailTitle'] = '';
		$properties['ShowThumbnail'] = false;

		$properties['SourceURL'] = TextField::create($this->getName() . '[sourceurl]', '', $this->object->SourceURL);
		$properties['SourceURL']->setAttribute('data-update-url', $this->Link('update'));
		$properties['SourceURL']->setAttribute('placeholder', 'http://');



		if ($this->object->ThumbnailURL) {
			$properties['ThumbnailURL'] = $this->object->ThumbnailURL;
			$properties['ThumbnailTitle'] = $this->object->Title;
			$properties['ShowThumbnail'] = true;
		}

		$field = parent::FieldHolder($properties);
		return $field;
	}


	public function Type() {
		return 'embed text';
	}

	public function setValue($value) {

		if ($value instanceof EmbedObject) {
			$this->object = $value;
			parent::setValue($object->ID);
		}
		$this->object = EmbedObject::get()->byID($value);
		parent::setValue($value);
	}


	public function saveInto(DataObjectInterface $record) {
		$val = $this->Value();		// array[sourceurl],[data] (as json)

		$name = $this->getName();
		$sourceURL = $val['sourceurl'];

		$existingID = (int)$record->$name;


		$originalObject = EmbedObject::get()->byID($existingID);
		if (!strlen($sourceURL)) {
			$record->$name = 0;
			if ($originalObject) {
				$originalObject->delete();
			}
			return;
		}

		if ($originalObject && $originalObject->SourceURL == $sourceURL) {
			// nothing has changed
			$object = $originalObject;
		} else {
			$existing = EmbedObject::get()->filter('SourceURL', $sourceURL)->first();
			if ($existing) {
				// save URL as an existing object
				$object = clone $existing;
				$object->ID = 0;
				$object->sourceExists = true;
			} else {
				// brand new source
				$object = EmbedObject::create();
				$object->SourceURL = $sourceURL;
				$object->updateFromURL($sourceURL, $this->oembedOptions);
			}
		}

		// delete the original object
		if ($originalObject && $originalObject->ID != $object->ID) {
			$originalObject->delete();
		}

		// write the new object
		if ($object->ID == 0) {
			$object->write();

		}
		$this->object = $object;

		$record->$name = $this->object->ID;
	}

	/**
	 * This is called by the javascript
	 */
	public function update(SS_HTTPRequest $request) {
		if (!SecurityToken::inst()->checkRequest($request)) {
			return '';
		}
		$sourceURL = $request->postVar('URL');

		if (strlen($sourceURL)) {

			$existingID = $this->Value();
			$originalObject = EmbedObject::get()->byID($existingID);


			if ($originalObject && $originalObject->SourceURL == $sourceURL) {
				// nothing has changed
				$object = $originalObject;
			} else {
				$existing = EmbedObject::get()->filter('SourceURL', $sourceURL)->first();
				if ($existing) {
					// save URL as an existing object
					$object = clone $existing;
					$object->ID = 0;
					$object->sourceExists = true;
				} else {
					// brand new source
					$object = EmbedObject::create();
					$object->SourceURL = $sourceURL;
					$object->updateFromURL();
				}
			}

			if ($object && $object->sourceExists()) {

				if ($this->embedType && $this->embedType != $object->Type) {
					return Convert::array2json(array(
						'status' => 'invalidurl',
						'message' => '<a href="'.$sourceURL.'" target="_blank">' . $sourceURL . '</a> is not a valid source type.',
						'data' => array()
					));
				}

				return Convert::array2json(array(
					'status' => 'success',
					'message' => '',
					'data' => array(
						'ThumbnailURL' => $object->ThumbnailURL,
						'Width' => $object->Width,
						'Height' => $object->Height,
						'Title' => $object->Title
					)
				));

			} else {
				return Convert::array2json(array(
					'status' => 'invalidurl',
					'message' => '<a href="'.$sourceURL.'" target="_blank">' . $sourceURL . '</a> is not a valid embed source.',
					'data' => array()
				));
			}
		}else{

			return Convert::array2json(array(
				'status' => 'nourl',
				'message' => '',
				'data' => array()
			));

		}

	}

}
