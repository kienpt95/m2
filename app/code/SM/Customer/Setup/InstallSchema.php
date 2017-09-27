<?php
/**
 * Created by PhpStorm.
 * User: kien
 * Date: 9/26/17
 * Time: 11:40 AM
 */

namespace SM\Customer\Setup;


use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $installer->getConnection()->addColumn(
            $installer->getTable('customer_entity'),
            'telephone',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 11,
                'nullable' => true,
                'comment' => 'telephone'
            ]
        );
        $installer->getConnection()->addIndex(
            'customer_entity',
            $installer->getIdxName(
                'customer_entity',
                ['telephone'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['telephone'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
        );
        $installer->endSetup();

    }
}