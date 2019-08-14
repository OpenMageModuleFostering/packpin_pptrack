<?php
class Packpin_Pptrack_Block_Adminhtml_Tracks_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setDefaultSort('ship_date');
        $this->setId('pptrack_tracks_grid');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
//        $this->setUseAjax(true);
    }

    protected function _getCollectionClass()
    {
        // This is the model we are using for the grid
        return 'pptrack/track_collection';
    }

    protected function _prepareCollection()
    {
        // Get and set our collection for the grid
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $collection->join(
            array('a' => 'sales/order_grid'),
            'main_table.order_id=a.entity_id',
            array('shipping_name')
        );
        $collection->join(
            array('b' => 'sales/shipment_track'),
            'main_table.shipment_id=b.entity_id'
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('pptrack');

        // Add the columns that should appear in the grid

        $this->addColumn('code',
            array(
                'header'=> $this->__('Tracking Code #'),
                'index' => 'code'
            )
        );
        $this->addColumn('carrier_name',
            array(
                'header'=> $this->__('Carrier'),
                'index' => 'carrier_name'
            )
        );
        $this->addColumn('status',
            array(
                'header'=> $this->__('Status'),
                'index' => 'status'
            )
        );
        $this->addColumn('created_at', array(
            'header' => $this->__('Created at'),
            'type' => 'datetime',
            'index' => 'created_at',
        ));
        $this->addColumn('order_id',
            array(
                'header'=> $this->__('Order #'),
                'index' => 'order_id'
            )
        );
        $this->addColumn('shipping_name',
            array(
                'header'=> $this->__('Ship to name'),
                'index' => 'shipping_name'
            )
        );

//        $this->addColumn('total_qty', array(
//            'header' => $this->__('Total Qty'),
//            'index' => 'total_qty',
//            'type'  => 'number',
//        ));

//        $this->addColumn('action',
//            array(
//                'header'    => Mage::helper('pptrack')->__('Action'),
//                'width'     => '50px',
//                'type'      => 'action',
//                'getter'     => 'getId',
//                'actions'   => array(
//                    array(
//                        'caption' => Mage::helper('pptrack')>__('View/Edit'),
//                        'url'     => array('base'=>'*/*/edit'),
//                        'field'   => 'id',
//                        'data-column' => 'action',
//                    )
//                ),
//                'filter'    => false,
//                'sortable'  => false,
//                'index'     => 'stores',
//                'is_system' => true,
//            ));


        $this->addExportType('*/*/exportTracksCsv', $helper->__('CSV'));
        $this->addExportType('*/*/exportTracksExcel', $helper->__('Excel XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
//        $this->setMassactionIdField('entity_id');
//        $this->getMassactionBlock()->setFormFieldName('order_ids');
//        $this->getMassactionBlock()->setUseSelectAll(false);
//
//        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/cancel')) {
//            $this->getMassactionBlock()->addItem('cancel_order', array(
//                'label'=> Mage::helper('sales')->__('Cancel'),
//                'url'  => $this->getUrl('*/sales_order/massCancel'),
//            ));
//        }

        return $this;
    }

    public function getRowUrl($row)
    {
        // This is where our row data will link to
        return $this->getUrl('*/sales_shipment/view/', array('shipment_id' => $row->getParentId()));
    }
}