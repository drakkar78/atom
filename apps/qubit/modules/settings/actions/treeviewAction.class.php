<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

class SettingsTreeviewAction extends DefaultEditAction
{
    // Arrays not allowed in class constants
    public static $NAMES = [
        'type',
        'showBrowseHierarchyPage',
        'allowFullWidthTreeviewCollapse',
        'ioSort',
        'showIdentifier',
        'showLevelOfDescription',
        'showDates',
        'fullItemsPerPage',
    ];

    public function execute($request)
    {
        parent::execute($request);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();

                QubitCache::getInstance()->removePattern('settings:i18n:*');

                $this->getUser()->setFlash('notice', $this->i18n->__('Treeview settings saved.'));

                $this->redirect(['module' => 'settings', 'action' => 'treeview']);
            }
        }
    }

    protected function earlyExecute()
    {
        $this->i18n = sfContext::getInstance()->i18n;
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'type':
                $this->typeSetting = QubitSetting::getByName('treeview_type');
                $default = 'sidebar';
                $options = [
                    'sidebar' => $this->i18n->__('Sidebar'),
                    'fullWidth' => $this->i18n->__('Full width'),
                ];

                $this->addSettingRadioButtonsField($this->typeSetting, $name, $default, $options);

                break;

            case 'showBrowseHierarchyPage':
                $this->showBrowseHierarchyPageSetting = QubitSetting::getByName('treeview_show_browse_hierarchy_page');
                $default = 'no';
                $options = [
                    'no' => $this->i18n->__('No'),
                    'yes' => $this->i18n->__('Yes'),
                ];

                $this->addSettingRadioButtonsField($this->showBrowseHierarchyPageSetting, $name, $default, $options);

                break;

            case 'allowFullWidthTreeviewCollapse':
                $this->allowFullWidthTreeviewCollapse = QubitSetting::getByName('treeview_allow_full_width_collapse');
                $default = 'no';
                $options = [
                    'no' => $this->i18n->__('No'),
                    'yes' => $this->i18n->__('Yes'),
                ];

                $this->addSettingRadioButtonsField($this->allowFullWidthTreeviewCollapse, $name, $default, $options);

                break;

            case 'ioSort':
                $this->ioSortSetting = QubitSetting::getByName('sort_treeview_informationobject');
                $default = 'none';
                $options = [
                    'none' => $this->i18n->__('Manual'),
                    'title' => $this->i18n->__('Title'),
                    'identifierTitle' => $this->i18n->__('Identifier - Title'),
                ];

                $this->addSettingRadioButtonsField($this->ioSortSetting, $name, $default, $options);

                break;

            case 'showIdentifier':
                $this->showIdentifierSetting = QubitSetting::getByName('treeview_show_identifier');
                $default = 'no';
                $options = [
                    'no' => $this->i18n->__('No'),
                    'identifier' => $this->i18n->__('Identifier'),
                    'referenceCode' => $this->i18n->__('Inherit reference code'),
                ];

                $this->addSettingRadioButtonsField($this->showIdentifierSetting, $name, $default, $options);

                break;

            case 'showLevelOfDescription':
                $this->showLevelOfDescriptionSetting = QubitSetting::getByName('treeview_show_level_of_description');
                $default = 'yes';
                $options = [
                    'no' => $this->i18n->__('No'),
                    'yes' => $this->i18n->__('Yes'),
                ];

                $this->addSettingRadioButtonsField($this->showLevelOfDescriptionSetting, $name, $default, $options);

                break;

            case 'showDates':
                $this->showDatesSetting = QubitSetting::getByName('treeview_show_dates');
                $default = 'no';
                $options = [
                    'no' => $this->i18n->__('No'),
                    'yes' => $this->i18n->__('Yes'),
                ];

                $this->addSettingRadioButtonsField($this->showDatesSetting, $name, $default, $options);

                break;

            case 'fullItemsPerPage':
                $this->fullItemsPerPageSetting = QubitSetting::getByName('treeview_full_items_per_page');

                $default = 50;
                if (isset($this->fullItemsPerPageSetting)) {
                    $default = $this->fullItemsPerPageSetting->getValue(['sourceCulture' => true]);
                }
                $this->form->setDefault($name, $default);

                $this->form->setValidator($name, new sfValidatorInteger([
                    'min' => 10,
                    'max' => sfConfig::get('app_treeview_items_per_page_max', 10000),
                ]));
                $this->form->setWidget($name, new sfWidgetFormInput());

                break;
        }
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            case 'type':
                $this->createOrUpdateSetting($this->typeSetting, 'treeview_type', $field->getValue());

                break;

            case 'showBrowseHierarchyPage':
                $this->createOrUpdateSetting($this->showBrowseHierarchyPageSetting, 'treeview_show_browse_hierarchy_page', $field->getValue());

                break;

            case 'allowFullWidthTreeviewCollapse':
                $this->createOrUpdateSetting($this->allowFullWidthTreeviewCollapse, 'treeview_allow_full_width_collapse', $field->getValue());

                break;

            case 'ioSort':
                $this->createOrUpdateSetting($this->ioSortSetting, 'sort_treeview_informationobject', $field->getValue());

                break;

            case 'showIdentifier':
                $this->createOrUpdateSetting($this->showIdentifierSetting, 'treeview_show_identifier', $field->getValue());

                break;

            case 'showLevelOfDescription':
                $this->createOrUpdateSetting($this->showLevelOfDescriptionSetting, 'treeview_show_level_of_description', $field->getValue());

                break;

            case 'showDates':
                $this->createOrUpdateSetting($this->showDatesSetting, 'treeview_show_dates', $field->getValue());

                break;

            case 'fullItemsPerPage':
                $this->createOrUpdateSetting($this->fullItemsPerPageSetting, 'treeview_full_items_per_page', $field->getValue());

                break;
        }
    }

    private function addSettingRadioButtonsField($setting, $fieldName, $default, $options)
    {
        if (isset($setting)) {
            $default = $setting->getValue(['sourceCulture' => true]);
        }

        $this->form->setDefault($fieldName, $default);
        $this->form->setValidator($fieldName, new sfValidatorString(['required' => false]));
        $this->form->setWidget($fieldName, new sfWidgetFormSelectRadio(['choices' => $options], ['class' => 'radio']));
    }

    private function createOrUpdateSetting($setting, $name, $value)
    {
        if (!isset($setting)) {
            $setting = new QubitSetting();
            $setting->name = $name;
            $setting->sourceCulture = sfConfig::get('sf_default_culture');
            $setting->culture = sfConfig::get('sf_default_culture');
        }

        $setting->setValue($value, ['sourceCulture' => true]);
        $setting->save();
    }
}
