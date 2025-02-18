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

class ObjectImportSelectAction extends DefaultEditAction
{
    // Arrays not allowed in class constants
    public static $NAMES = [
        'repos',
        'collection',
    ];

    public function execute($request)
    {
        parent::execute($request);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();

                $this->doBackgroundImport($request);

                $this->setTemplate('importResults');
            }
        } else {
            $this->response->addJavaScript('checkReposFilter', 'last');

            // Check parameter
            if (isset($request->type)) {
                $this->type = $request->type;
            }

            switch ($this->type) {
                case 'csv':
                    $this->title = $this->context->i18n->__('Import CSV');

                break;

                case 'xml':
                    $this->title = $this->context->i18n->__('Import XML');

                break;

                default:
                    $this->redirect(['module' => 'object', 'action' => 'importSelect', 'type' => 'xml']);

                break;
            }
        }
    }

    protected function earlyExecute()
    {
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        if (isset($this->getRoute()->resource)) {
            $this->resource = $this->getRoute()->resource;

            $this->form->setDefault('parent', $this->context->routing->generate(null, [$this->resource]));
            $this->form->setValidator('parent', new sfValidatorString());
            $this->form->setWidget('parent', new sfWidgetFormInputHidden());
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'repos':
                // Get list of repositories
                $criteria = new Criteria();
                // Do source culture fallback
                $criteria = QubitCultureFallback::addFallbackCriteria($criteria, 'QubitActor');
                // Ignore root repository
                $criteria->add(QubitActor::ID, QubitRepository::ROOT_ID, Criteria::NOT_EQUAL);
                $criteria->addAscendingOrderByColumn('authorized_form_of_name');
                $cache = QubitCache::getInstance();
                $cacheKey = 'file-import:list-of-repositories:'.$this->context->user->getCulture();

                if ($cache->has($cacheKey)) {
                    $choices = $cache->get($cacheKey);
                } else {
                    $choices = [];
                    $choices[null] = null;
                    foreach (QubitRepository::get($criteria) as $repository) {
                        $choices[$repository->slug] = $repository->__toString();
                    }
                    $cache->set($cacheKey, $choices, 3600);
                }
                $this->form->setValidator($name, new sfValidatorChoice(['choices' => array_keys($choices)]));
                $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => $choices]));

                break;

            case 'collection':
                $this->form->setValidator($name, new sfValidatorString());
                $choices = [];

                if (
                    isset($this->getParameters['collection']) && ctype_digit($this->getParameters['collection'])
                    && null !== $collection = QubitInformationObject::getById($this->getParameters['collection'])
                ) {
                    sfContext::getInstance()->getConfiguration()->loadHelpers(['Url']);
                    $collectionUrl = url_for($collection);
                    $this->form->setDefault($name, $collectionUrl);

                    $choices[$collectionUrl] = $collection;
                }
                $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => $choices]));

                break;

            default:
                return parent::addField($name);
        }
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            case 'repos':
                $this->repositorySlug = $this->request->getPostParameter('repos');

                break;

            case 'collection':
                $url = $this->request->getPostParameter('collection');
                if (!empty($url)) {
                    $parts = explode('/', $url);
                    $this->collectionSlug = end($parts);
                }

                break;
        }
    }

    /**
     * Launch the file import background job and return.
     *
     * @param $request data
     */
    protected function doBackgroundImport($request)
    {
        $file = $request->getFiles('file');

        // Import type, CSV or XML?
        $importType = $request->getParameter('importType', 'xml');

        // We will use this later to redirect users back to the importSelect page
        if (isset($this->getRoute()->resource)) {
            $importSelectRoute = [$this->getRoute()->resource, 'module' => 'object', 'action' => 'importSelect', 'type' => $importType];
        } else {
            $importSelectRoute = ['module' => 'object', 'action' => 'importSelect', 'type' => $importType];
        }

        // Move uploaded file to new location to pass off to background arFileImportJob.
        try {
            $file = Qubit::moveUploadFile($file);
        } catch (sfException $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
            $this->redirect($importSelectRoute);
        }

        // Redirect user if they are attempting to upload an invalid CSV file
        if ('csv' == $importType && !$this->checkForValidCsvFile($request, $file['tmp_name'])) {
            $errorMessage = $this->context->i18n->__('Not a CSV file (or CSV columns not recognized).');
            $this->context->user->setFlash('error', $errorMessage);
            $this->redirect($importSelectRoute);
        }

        // if we got here without a file upload, go to file selection
        if (0 == count($file) || empty($file['tmp_name'])) {
            $this->redirect($importSelectRoute);
        }

        $options = [
            'index' => ('on' == $request->getParameter('noIndex')) ? false : true,
            'doCsvTransform' => ('on' == $request->getParameter('doCsvTransform')) ? true : false,
            'skip-unmatched' => ('on' == $request->getParameter('skipUnmatched')) ? true : false,
            'skip-matched' => ('on' == $request->getParameter('skipMatched')) ? true : false,
            'parentId' => (isset($this->getRoute()->resource) ? $this->getRoute()->resource->id : null),
            'objectType' => $request->getParameter('objectType'),
            // Choose import type based on importType parameter
            // This decision used to be based in the file extension but some users
            // experienced problems when the extension was omitted
            'importType' => $importType,
            'update' => $request->getParameter('updateType'),
            'repositorySlug' => $this->repositorySlug,
            'collectionSlug' => $this->collectionSlug,
            'file' => $file,
        ];

        try {
            $job = QubitJob::runJob('arFileImportJob', $options);

            $this->getUser()->setFlash('notice', $this->context->i18n->__('Import file initiated. Check %1%job %2%%3% to view the status of the import.', [
                '%1%' => sprintf('<a class="alert-link" href="%s">', $this->context->routing->generate(null, ['module' => 'jobs', 'action' => 'report', 'id' => $job->id])),
                '%2%' => $job->id,
                '%3%' => '</a>',
            ]), ['persist' => false]);
        } catch (sfException $e) {
            $this->context->user->setFlash('error', $e->getMessage());
            $this->redirect($importSelectRoute);
        }
    }

    private function checkForValidCsvFile($request, $fileName)
    {
        $importOjectClassNames = [
            'informationObject' => 'QubitInformationObject',
            'accession' => 'QubitAccession',
            'authorityRecord' => 'QubitActor',
            'event' => 'QubitEvent',
            'repository' => 'QubitRepository',
            'authorityRecordRelationship' => 'QubitRelation-actor',
        ];

        $className = $importOjectClassNames[$request->getParameter('objectType')];

        return $this->countValidColumnsInCsvFileForExportType($fileName, $className);
    }

    private function countValidColumnsInCsvFileForExportType($fileName, $className)
    {
        $validColumnCount = 0;

        $exportTypeConfig = QubitFlatfileExport::loadResourceConfigFile($className.'.yml', $className);

        // Get first row of possible CSV file
        $fh = fopen($fileName, 'rb');
        $firstCsvRow = fgetcsv($fh, 60000);

        // Count valid columns found
        foreach ($firstCsvRow as $column) {
            if (in_array($column, $exportTypeConfig['columnNames'])) {
                ++$validColumnCount;
            }
        }

        return $validColumnCount;
    }
}
