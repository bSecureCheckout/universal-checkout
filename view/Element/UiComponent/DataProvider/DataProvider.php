<?php

namespace Bsecure\UniversalCheckout\view\Element\UiComponent\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Framework\App\DeploymentConfig;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * Data Provider name
     *
     * @var string
     */
    protected $name;

    /**
     * Data Provider Primary Identifier name
     *
     * @var string
     */
    protected $primaryFieldName;

    /**
     * Data Provider Request Parameter Identifier name
     *
     * @var string
     */
    protected $requestFieldName;

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * Provider configuration data
     *
     * @var array
     */
    protected $data = [];

    /**
     * @var ReportingInterface
     */
    protected $reporting;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var SearchCriteria
     */
    protected $searchCriteria;
    protected $seller_order_arr;
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        DeploymentConfig $deploymentConfig,
        array $meta = [],
        array $data = []
    ) {
        $this->request = $request;
        $this->filterBuilder = $filterBuilder;
        $this->name = $name;
        $this->primaryFieldName = $primaryFieldName;
        $this->requestFieldName = $requestFieldName;
        $this->reporting = $reporting;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->deploymentConfig = $deploymentConfig;
        $this->meta = $meta;
        $this->data = $data;
        $this->prepareUpdateUrl();
    }
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {

        $dbPrefix = ($this->deploymentConfig->get('db/table_prefix'));
        $mainTable = $dbPrefix . 'sales_order_grid';
        
        if ($searchResult->getMainTable() === $mainTable) {
            $searchResult->addFieldToFilter('status', ['neq' => 'bsecure_draft']);
        }
        
        $arrItems = [];

        $arrItems['items'] = [];
        foreach ($searchResult->getItems() as $item) {
            $itemData = [];
            foreach ($item->getCustomAttributes() as $attribute) {
                $itemData[$attribute->getAttributeCode()] = $attribute->getValue();
            }
            $arrItems['items'][] = $itemData;
            $arrItems['totalRecords'] = $searchResult->getTotalCount();
        }
                return $arrItems;
    }
    public function getSearchCriteria()
    {
        if (!$this->searchCriteria) {
            $this->searchCriteria = $this->searchCriteriaBuilder->create();
            $this->searchCriteria->setRequestName($this->name);
        }
        return $this->searchCriteria;
    }

        /**
         * Get data
         *
         * @return array
         */
    public function getData()
    {
        return $this->searchResultToOutput($this->getSearchResult());
    }

        /**
         * Get config data
         *
         * @return array
         */

        /**
         * Set data
         *
         * @param mixed $config
         * @return void
         */

        /**
         * Returns Search result
         *
         * @return SearchResultInterface
         */

    public function getSearchResult()
    {
        return $this->reporting->search($this->getSearchCriteria());
    }
}
