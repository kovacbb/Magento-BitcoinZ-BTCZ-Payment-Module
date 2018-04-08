<?php

namespace Kovach\Btcz\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

	public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
	{
		$installer = $setup;
		$installer->startSetup();
		if (!$installer->tableExists('btcz')) {
			$table = $installer->getConnection()->newTable(
				$installer->getTable('btcz')
			)
				->addColumn(
					'id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					[
						'identity' => true,
						'nullable' => false,
						'primary'  => true,
						'unsigned' => true,
					],
					'Id'
				)
				->addColumn(
					'increment_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable => false'],
					'Increment Id'
				)
				->addColumn(
					'url_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'Url Id'
				)
				->addColumn(
					'processed',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					11,
					[],
					'Processed'
				)
				->addColumn(
					'created_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
					'Created At'
				)
				->setComment('Btcz Table');
			$installer->getConnection()->createTable($table);

			/*
			$installer->getConnection()->addIndex(
				$installer->getTable('btcz'),
				$setup->getIdxName(
					$installer->getTable('btcz'),
					['increment_id', 'url_id', 'processed'],
					\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
				),
				['increment_id', 'url_id', 'processed'],
				\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
			);
			*/
		};
		$installer->getConnection()->createTable($table);
		
		if (!$installer->tableExists('btcz_pingback')) {
			$table = $installer->getConnection()->newTable(
				$installer->getTable('btcz_pingback')
			)
				->addColumn(
					'id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					[
						'identity' => true,
						'nullable' => false,
						'primary'  => true,
						'unsigned' => true,
					],
					'Id'
				)
				->addColumn(
					'json',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					1000,
					['nullable => yes'],
					'JSON'
				)
				->addColumn(
					'created_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
					'Created At'
				)
				->setComment('Btcz Pingback Table');
			$installer->getConnection()->createTable($table);

			/*
			$installer->getConnection()->addIndex(
				$installer->getTable('btcz'),
				$setup->getIdxName(
					$installer->getTable('btcz'),
					['increment_id', 'url_id', 'processed'],
					\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
				),
				['increment_id', 'url_id', 'processed'],
				\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
			);
			*/
		}
		$installer->endSetup();
	}
}