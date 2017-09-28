<?php
/**
 * Created by PhpStorm.
 * User: kien
 * Date: 9/27/17
 * Time: 4:47 PM
 */


namespace SM\Customer\Model\ResourceModel;

use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Framework\Exception\AlreadyExistsException;

class Customer extends \Magento\Customer\Model\ResourceModel\Customer
{
    /**
     * Load customer by email or telephone
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param string $email
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByEmail(\Magento\Customer\Model\Customer $customer, $email)
    {
        $connection = $this->getConnection();
        $bind = ['customer_email' => $email];
        $select = $connection->select()->from(
            $this->getEntityTable(),
            [$this->getEntityIdField()]
        )->where(
            'email = :customer_email'
        )->orWhere(
            'telephone = :customer_email' // <~ customize
        );

        if ($customer->getSharingConfig()->isWebsiteScope()) {
            if (!$customer->hasData('website_id')) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('A customer website ID must be specified when using the website scope.')
                );
            }
            $bind['website_id'] = (int)$customer->getWebsiteId();
            $select->where('website_id = :website_id');
        }

        $customerId = $connection->fetchOne($select, $bind);
        if ($customerId) {
            $this->load($customer, $customerId);
        } else {
            $customer->setData([]);
        }

        return $this;
    }

    /**
     * Validate email and telephone before save new user
     * @param \Magento\Framework\DataObject $customer
     * @return $this
     * @throws AlreadyExistsException
     * @throws ValidatorException
     */
    protected function _beforeSave(\Magento\Framework\DataObject $customer)
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        if ($customer->getStoreId() === null) {
            $customer->setStoreId($this->storeManager->getStore()->getId());
        }
        $customer->getGroupId();

        parent::_beforeSave($customer);

        if (!$customer->getEmail()) {
            throw new ValidatorException(__('Please enter a customer email.'));
        }

        $result = $this->checkPhone($customer);
        if ($result) {
            throw new AlreadyExistsException(
                __('A customer with the same phone number already exists in an associated website.')
            );
        }

        $result = $this->checkEmail($customer);
        if ($result) {
            throw new AlreadyExistsException(
                __('A customer with the same email already exists in an associated website.')
            );
        }

        // set confirmation key logic
        if ($customer->getForceConfirmed() || $customer->getPasswordHash() == '') {
            $customer->setConfirmation(null);
        } elseif (!$customer->getId() && $customer->isConfirmationRequired()) {
            $customer->setConfirmation($customer->getRandomConfirmationKey());
        }
        // remove customer confirmation key from database, if empty
        if (!$customer->getConfirmation()) {
            $customer->setConfirmation(null);
        }

        $this->_validate($customer);

        return $this;
    }

    /**
     * validate customer by phone number
     * @param $customer
     * @return string
     */
    public function checkPhone($customer){
        $connection = $this->getConnection();
        $bind = ['telephone' => $customer->getTelephone()];

        $select = $connection->select()->from(
            $this->getEntityTable(),
            [$this->getEntityIdField()]
        )->where(
            'telephone = :telephone'
        );
        return $connection->fetchOne($select, $bind);
    }

    /**
     * validate by email
     * @param $customer
     * @return string
     */
    public function checkEmail($customer){
        $connection = $this->getConnection();
        $bind = ['email' => $customer->getEmail()];

        $select = $connection->select()->from(
            $this->getEntityTable(),
            [$this->getEntityIdField()]
        )->where(
            'email = :email'
        );
        if ($customer->getSharingConfig()->isWebsiteScope()) {
            $bind['website_id'] = (int)$customer->getWebsiteId();
            $select->where('website_id = :website_id');
        }
        if ($customer->getId()) {
            $bind['entity_id'] = (int)$customer->getId();
            $select->where('entity_id != :entity_id');
        }
        return $connection->fetchOne($select, $bind);
    }
}