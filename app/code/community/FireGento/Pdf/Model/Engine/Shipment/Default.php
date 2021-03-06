<?php
/**
 * This file is part of the FIREGENTO project.
 *
 * FireGento_GermanSetup is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * PHP version 5
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
/**
 * Shipment model rewrite.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Model_Engine_Shipment_Default extends FireGento_Pdf_Model_Engine_Abstract
{

    public function __construct()
    {
        parent::__construct();
        $this->setMode('shipment');
    }

    /**
     * Return PDF document
     *
     * @param  array $shipments
     * @return Zend_Pdf
     */
    public function getPdf($shipments = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('shipment');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);

        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        // pagecounter is 0 at the beginning, because it is incremented in newPage()
        $this->pagecounter = 0;

        foreach ($shipments as $shipment) {
            if ($shipment->getStoreId()) {
                Mage::app()->getLocale()->emulate($shipment->getStoreId());
                Mage::app()->setCurrentStore($shipment->getStoreId());
            }
            $page = $this->newPage();

            $order = $shipment->getOrder();

            /* add logo */
            $this->insertLogo($page, $shipment->getStore());

            // Add shipping address
            $this->y = 692;
            $this->insertShippingAddress($page, $order);

            /* add sender address */
            $this->y = 705;
            $this->_insertSenderAddessBar($page);

            /* add header */
            $this->y = 592;
            $this->insertHeader($page, $order, $shipment);

            /* add table header */
            // make sure that item table does not overlap heading
            if ($this->y > 575) {
                $this->y = 575;
            }
            $this->_setFontRegular($page, 9);
            $this->insertTableHeader($page);

            $this->y -= 20;

            $position = 0;

            foreach ($shipment->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }

                if ($this->y < 50 || (Mage::getStoreConfig('sales_pdf/firegento_pdf/show_footer') == 1 && $this->y < 100)) {
                    $page = $this->newPage(array());
                }

                $position++;
                $page = $this->_drawItem($item, $page, $order, $position);
            }

            /* add note */
            $page = $this->_insertNote($page, $order, $shipment);

            // Add footer
            $this->_addFooter($page, $shipment->getStore());
        }

        $this->_afterGetPdf();

        return $pdf;
    }

    protected function insertTableHeader(&$page)
    {
        $page->setFillColor($this->colors['grey1']);
        $page->setLineColor($this->colors['grey1']);
        $page->setLineWidth(1);
        $page->drawRectangle($this->margin['left'], $this->y, $this->margin['right'] - 10, $this->y - 15);

        $page->setFillColor($this->colors['black']);
        $font = $this->_setFontRegular($page, 9);

        $this->y -= 11;
        $page->drawText(Mage::helper('firegento_pdf')->__('No.'),            $this->margin['left'],       $this->y, $this->encoding);
        $page->drawText(Mage::helper('firegento_pdf')->__('Description'),    $this->margin['left'] + 105, $this->y, $this->encoding);

        $page->drawText(Mage::helper('firegento_pdf')->__('Qty'),         $this->margin['left'] + 450, $this->y, $this->encoding);
    }

    protected function insertShippingAddress(&$page, $order)
    {
        $this->_setFontRegular($page, 9);

        $billing = $this->_formatAddress($order->getShippingAddress()->format('pdf'));

        foreach ($billing as $line) {
            $page->drawText(trim(strip_tags($line)), $this->margin['left'], $this->y, $this->encoding);
            $this->Ln(12);
        }
    }

    /**
     * Initialize renderer process.
     *
     * @param string $type
     * @return void
     */
    protected function _initRenderer($type)
    {
        parent::_initRenderer($type);

        $this->_renderers['default'] = array(
            'model' => 'firegento_pdf/items_shipment_default',
            'renderer' => null
        );
        $this->_renderers['bundle'] = array(
            'model' => 'firegento_pdf/items_shipment_bundle',
            'renderer' => null
        );
    }

}
