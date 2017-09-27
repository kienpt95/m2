<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SM\Customer\Model;

use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\App\RequestInterface;

class CustomerExtractor extends \Magento\Customer\Model\CustomerExtractor
{
    /**
     * @param string $formCode
     * @param RequestInterface $request
     * @param array $attributeValues
     * @return \SM\Customer\Api\Data\CustomerInterface
     */
    public function extract(
        $formCode,
        RequestInterface $request,
        array $attributeValues = []
    ) {
        $customerForm = $this->formFactory->create(
            'customer',
            $formCode,
            $attributeValues
        );

        $customerData = $customerForm->extractData($request);
        $customerData = $customerForm->compactData($customerData);

        $allowedAttributes = $customerForm->getAllowedAttributes();
        $isGroupIdEmpty = isset($allowedAttributes['group_id']);

        $customerDataObject = $this->customerFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $customerDataObject,
            $customerData,
            '\SM\Customer\Api\Data\CustomerInterface'
        );
        $store = $this->storeManager->getStore();
        if ($isGroupIdEmpty) {
            $customerDataObject->setGroupId(
                $this->customerGroupManagement->getDefaultGroup($store->getId())->getId()
            );
        }

        $customerDataObject->setWebsiteId($store->getWebsiteId());
        $customerDataObject->setStoreId($store->getId());

        return $customerDataObject;
    }
}
