<?php

namespace TCG\Voyager\Classes;

use TCG\Voyager\Facades\Voyager;

class Layout implements \JsonSerializable
{
    public $name;
    public $type = 'view';
    public $default_sort_field;
    public $global_search = '';
    public $formfields = [];

    protected $bread;

    public function __construct($layout, $bread)
    {
        $this->bread = $bread;

        foreach ($layout as $key => $data) {
            if ($key == 'formfields') {
                $this->parseFormfields($data);
            } else {
                $this->{$key} = $data;
            }
        }
    }

    private function parseFormfields($data)
    {
        $this->formfields = collect();

        foreach ($data ?? [] as $formfield) {
            $formfield_class = clone Voyager::getFormfield($formfield->type);
            if (!$formfield_class) {
                Voyager::flashMessage('Formfield "'.$formfield->type.'" couldn\'t be found.', 'debug');
                continue;
            }
            foreach ($formfield as $f_key => $f_value) {
                if ($f_key == 'options') {
                    $formfield_class->options = array_merge($formfield_class->options, (array) $f_value);
                } else {
                    $formfield_class->{$f_key} = $f_value;
                }
            }

            if ($formfield_class->isValid()) {
                $this->formfields->push($formfield_class);
            }
        }
    }

    public function getDefaultSortField()
    {
        return $this->default_sort_field ?? $this->bread->getModel()->getKeyName();
    }

    public function getSearchableFields()
    {
        return $this->formfields->where('options.searchable', true);
    }

    public function isValid()
    {
        return !empty($this->name);
    }

    public function jsonSerialize()
    {
        if ($this->type == 'list') {
            return [
                'name'               => $this->name,
                'type'               => $this->type,
                'formfields'         => $this->formfields,
                'default_sort_field' => $this->default_sort_field,
                'global_search'      => $this->global_search,
            ];
        }

        return [
            'name'               => $this->name,
            'type'               => $this->type,
            'formfields'         => $this->formfields,
            'default_sort_field' => $this->default_sort_field,
        ];
    }
}