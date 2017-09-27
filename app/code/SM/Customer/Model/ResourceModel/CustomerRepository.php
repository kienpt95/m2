<?php
/**
 * Created by PhpStorm.
 * User: kien
 * Date: 9/27/17
 * Time: 3:30 PM
 */

namespace SM\Customer\Model\ResourceModel;


class CustomerRepository extends \Magento\Customer\Model\ResourceModel\CustomerRepository
{
    /**
     * {@inheritdoc}
     */
    public function save(\Magento\Customer\Api\Data\CustomerInterface $customer, $passwordHash = null)
    {
        $prevCustomerData = null;
        $prevCustomerDataArr = null;

        if ($customer->getId()) {
            $prevCustomerData = $this->getById($customer->getId());
            $prevCustomerDataArr = $prevCustomerData->__toArray();
        }

        /** @var $customer \Magento\Customer\Model\Data\Customer */
        $customerArr = $customer->__toArray();
        $customer = $this->imageProcessor->save(
            $customer,
            'customer',
            $prevCustomerData
        );

        $origAddresses = $customer->getAddresses();
        $customer->setAddresses([]);
        $customerData = $this->extensibleDataObjectConverter->toNestedArray(
            $customer,
            [],
            \SM\Customer\Api\Data\CustomerInterface::class
        );

        $customer->setAddresses($origAddresses);
        $customerModel = $this->customerFactory->create(['data' => $customerData]);
        $storeId = $customerModel->getStoreId();

        if ($storeId === null) {
            $customerModel->setStoreId($this->storeManager->getStore()->getId());
        }

        $customerModel->setId($customer->getId());

        // Need to use attribute set or future updates can cause data loss
        if (!$customerModel->getAttributeSetId()) {
            $customerModel->setAttributeSetId(
                \Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER
            );
        }
        // Populate model with secure data
        $this->populateCustomerModelWithSecureData($customer, $passwordHash, $customerModel);

        // If customer email was changed, reset RpToken info
        if ($prevCustomerData
            && $prevCustomerData->getEmail() !== $customerModel->getEmail()
        ) {
            $customerModel->setRpToken(null);
            $customerModel->setRpTokenCreatedAt(null);
        }

        $this->setDefaultBilling($customerArr, $prevCustomerDataArr, $customerModel);

        $this->setDefaultShipping($customerArr, $prevCustomerDataArr, $customerModel);

        $customerModel->save();
        $this->customerRegistry->push($customerModel);
        $customerId = $customerModel->getId();

        $this->updateAddresses($customer, $customerId);

        $savedCustomer = $this->get($customer->getEmail(), $customer->getWebsiteId());
        $this->eventManager->dispatch(
            'customer_save_after_data_object',
            ['customer_data_object' => $savedCustomer, 'orig_customer_data_object' => $customer]
        );
        return $savedCustomer;
    }

    /**
     * Populate customer model with secure data.
     *
     * @param \Magento\Framework\Api\CustomAttributesDataInterface $customer
     * @param string $passwordHash
     * @param \Magento\Customer\Model\Customer\Interceptor $customerModel
     * @return void
     */
    private function populateCustomerModelWithSecureData(
        \Magento\Framework\Api\CustomAttributesDataInterface $customer,
        $passwordHash,
        $customerModel
    ) {
        if ($customer->getId()) {
            $customerSecure = $this->customerRegistry->retrieveSecureData($customer->getId());
            $customerModel->setRpToken($customerSecure->getRpToken());
            $customerModel->setRpTokenCreatedAt($customerSecure->getRpTokenCreatedAt());
            $customerModel->setPasswordHash($customerSecure->getPasswordHash());
            $customerModel->setFailuresNum($customerSecure->getFailuresNum());
            $customerModel->setFirstFailure($customerSecure->getFirstFailure());
            $customerModel->setLockExpires($customerSecure->getLockExpires());
        } else {
            if ($passwordHash) {
                $customerModel->setPasswordHash($passwordHash);
            }
        }
    }

    /**
     * Update customer addresses.
     *
     * @param \Magento\Framework\Api\CustomAttributesDataInterface $customer
     * @param int $customerId
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    private function updateAddresses(\Magento\Framework\Api\CustomAttributesDataInterface $customer, $customerId)
    {
        if ($customer->getAddresses() !== null) {
            if ($customer->getId()) {
                $existingAddresses = $this->getById($customer->getId())->getAddresses();
                $getIdFunc = function ($address) {
                    return $address->getId();
                };
                $existingAddressIds = array_map($getIdFunc, $existingAddresses);
            } else {
                $existingAddressIds = [];
            }

            $savedAddressIds = [];
            foreach ($customer->getAddresses() as $address) {
                $address->setCustomerId($customerId)
                    ->setRegion($address->getRegion());
                $this->addressRepository->save($address);
                if ($address->getId()) {
                    $savedAddressIds[] = $address->getId();
                }
            }

            $addressIdsToDelete = array_diff($existingAddressIds, $savedAddressIds);
            foreach ($addressIdsToDelete as $addressId) {
                $this->addressRepository->deleteById($addressId);
            }
        }
    }

    /**
     * Set default billing.
     *
     * @param array $customerArr
     * @param array $prevCustomerDataArr
     * @param \Magento\Customer\Model\Customer\Interceptor $customerModel
     * @return void
     */
    private function setDefaultBilling(
        $customerArr,
        $prevCustomerDataArr,
        $customerModel
    ) {
        if (!array_key_exists('default_billing', $customerArr) &&
            null !== $prevCustomerDataArr &&
            array_key_exists('default_billing', $prevCustomerDataArr)
        ) {
            $customerModel->setDefaultBilling($prevCustomerDataArr['default_billing']);
        }
    }

    /**
     * Set default shipping.
     *
     * @param array $customerArr
     * @param array $prevCustomerDataArr
     * @param \Magento\Customer\Model\Customer\Interceptor $customerModel
     * @return void
     */
    private function setDefaultShipping(
        $customerArr,
        $prevCustomerDataArr,
        $customerModel
    ) {
        if (!array_key_exists('default_shipping', $customerArr) &&
            null !== $prevCustomerDataArr &&
            array_key_exists('default_shipping', $prevCustomerDataArr)
        ) {
            $customerModel->setDefaultShipping($prevCustomerDataArr['default_shipping']);
        }
    }
}