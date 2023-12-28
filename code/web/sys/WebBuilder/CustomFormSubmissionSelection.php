<?php

class CustomFormSubmissionSelection extends DataObject {
    public $__table = 'web_builder_custom_form_field_submission';
    public $id;
    public $formSubmissionId;
    public $submissionFieldId;

    static function getObjectStructure($context = ''): array {
        return [
            'id' => [
                'property' => 'id',
                'type' => 'label',
                'label' => 'Id',
                'description' => 'The unique id within the database',
            ],
            'formFieldContent' => [
                'property' => 'formFieldContent',
                'type' => 'text',
                'label' => 'Custom Form',
                'description' => 'The parent custom form',
                'readOnly' => true,
            ],
        ];

    }
    private $_formFieldContent;
    public function __get($name) {
        if ($name == 'formFieldContent') {
            if ($this->_formFieldContent == null) {
                $formFieldContent = new CustomFormField();
                $formFieldContent->id = $this->submissionFieldId;
                if ($formFieldContent->find(true)) {
                    $this->_formFieldContent = $formFieldContent->label;
                } else {
                    $this->_formFieldContent = '';
                }
            }
            return $this->_formFieldContent;
        }
        return parent::__get($name);
    }
}
