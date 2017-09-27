<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace SM\Customer\Model\Data;

class Customer extends \Magento\Customer\Model\Data\Customer
{
    /**
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->_get('telephone');
    }

    /**
     * @param $telephone
     * @return $this
     */
    public function setTelephone($telephone)
    {
        return $this->setData('telephone', $telephone);
    }
}
