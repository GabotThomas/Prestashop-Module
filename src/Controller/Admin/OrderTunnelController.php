<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace DemoGrid\Controller\Admin;

use DemoGrid\Filter\OrderTunnelFilters;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteria;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use DemoGrid\Grid\OrderTunnelGridDefinitionFactory;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Order;




/*Class order view*/
use PrestaShopBundle\Form\Admin\Sell\Order\UpdateOrderStatusType;
use PrestaShop\PrestaShop\Core\Domain\Order\Query\GetOrderForViewing;
use PrestaShop\PrestaShop\Core\Domain\Order\QueryResult\OrderForViewing;
use PrestaShopBundle\Form\Admin\Sell\Order\AddOrderCartRuleType;
use PrestaShopBundle\Form\Admin\Sell\Order\AddProductRowType;
use PrestaShopBundle\Form\Admin\Sell\Order\OrderPaymentType;
use PrestaShopBundle\Form\Admin\Sell\Order\OrderMessageType;
use PrestaShopBundle\Form\Admin\Sell\Order\ChangeOrderAddressType;
use PrestaShopBundle\Form\Admin\Sell\Order\ChangeOrderCurrencyType;
use PrestaShopBundle\Form\Admin\Sell\Order\ChangeOrdersStatusType;
use PrestaShopBundle\Form\Admin\Sell\Customer\PrivateNoteType;
use PrestaShopBundle\Form\Admin\Sell\Order\UpdateOrderShippingType;
use PrestaShopBundle\Form\Admin\Sell\Order\EditProductRowType;
use PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButtonsCollection;

/**
 * Class ProductController manages our custom products page
 */
class OrderTunnelController extends FrameworkBundleAdminController
{

    /**
     * Default number of products per page (in case invalid value is used)
     */
    const DEFAULT_PRODUCTS_NUMBER = 8;

    /**
     * Options used for the number of products per page
     */
    const PRODUCTS_PAGINATION_OPTIONS = [8, 20, 50, 100];


    /**
     * Provides filters functionality
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function searchAction(Request $request)
    {
        $definitionFactory = $this->get('demogrid.grid.order_grid_definition_factory');
        $productDefinition = $definitionFactory->getDefinition();

        $gridFilterFormFactory = $this->get('prestashop.core.grid.filter.form_factory');
        $filtersForm = $gridFilterFormFactory->create($productDefinition);
        $filtersForm->handleRequest($request);

        $filters = [];

        if ($filtersForm->isSubmitted()) {
            $filters = $filtersForm->getData();
        }

        return $this->redirectToRoute('demogrid_admin_index', ['filters' => $filters]);
    }



    /**
     * Show manufacturers listing page.
     *
     * 
     *
     * @param Request $request
     * @param OrderTunnelFilters $manufacturerFilters
     *
     * @return Response
     */

     public function indexAction(Request $request, OrderTunnelFilters $manufacturerFilters) 
    {

        $manufacturerGridFactory = $this->get('demogrid.grid.order_grid_factory');
        $manufacturerGrid = $manufacturerGridFactory->getGrid($manufacturerFilters);

        return $this->render('@Modules/demogrid/views/admin/products.html.twig', [
            'enableSidebar' => true,
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
            'productGrid' => $this->presentGrid($manufacturerGrid),
        ]);
    }
    

    /**
     * 
     *
     *
     * @param Request $request
     *
     * @return Response
     */
    public function orderviewAction(Request $request)
    {
        $orderId = (int)$request->query->get('orderId');
        try {
            /** @var OrderForViewing $orderForViewing */
            $orderForViewing = $this->getQueryBus()->handle(new GetOrderForViewing($orderId));
        } catch (OrderException $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages($e)));

            return $this->redirectToRoute('admin_orders_index');
        }

        $formFactory = $this->get('form.factory');
        $updateOrderStatusForm = $formFactory->createNamed(
            'update_order_status',
            UpdateOrderStatusType::class, [
                'new_order_status_id' => $orderForViewing->getHistory()->getCurrentOrderStatusId(),
            ]
        );
        $updateOrderStatusActionBarForm = $formFactory->createNamed(
            'update_order_status_action_bar',
            UpdateOrderStatusType::class, [
                'new_order_status_id' => $orderForViewing->getHistory()->getCurrentOrderStatusId(),
            ]
        );

        $addOrderCartRuleForm = $this->createForm(AddOrderCartRuleType::class, [], [
            'order_id' => $orderId,
        ]);
        $addOrderPaymentForm = $this->createForm(OrderPaymentType::class, [
            'id_currency' => $orderForViewing->getCurrencyId(),
        ], [
            'id_order' => $orderId,
        ]);

        $orderMessageForm = $this->createForm(OrderMessageType::class, [], [
            'action' => $this->generateUrl('admin_orders_send_message', ['orderId' => $orderId]),
        ]);
        $orderMessageForm->handleRequest($request);

        $changeOrderCurrencyForm = $this->createForm(ChangeOrderCurrencyType::class, [], [
            'current_currency_id' => $orderForViewing->getCurrencyId(),
        ]);

        $changeOrderAddressForm = null;
        $privateNoteForm = null;

        if (null !== $orderForViewing->getCustomer()) {
            $changeOrderAddressForm = $this->createForm(ChangeOrderAddressType::class, [], [
                'customer_id' => $orderForViewing->getCustomer()->getId(),
            ]);

            $privateNoteForm = $this->createForm(PrivateNoteType::class, [
                'note' => $orderForViewing->getCustomer()->getPrivateNote(),
            ]);
        }

        $updateOrderShippingForm = $this->createForm(UpdateOrderShippingType::class, [
            'new_carrier_id' => $orderForViewing->getCarrierId(),
        ], [
            'order_id' => $orderId,
        ]);

        $currencyDataProvider = $this->container->get('prestashop.adapter.data_provider.currency');
        $orderCurrency = $currencyDataProvider->getCurrencyById($orderForViewing->getCurrencyId());

        $addProductRowForm = $this->createForm(AddProductRowType::class, [], [
            'order_id' => $orderId,
            'currency_id' => $orderForViewing->getCurrencyId(),
            'symbol' => $orderCurrency->symbol,
        ]);
        $editProductRowForm = $this->createForm(EditProductRowType::class, [], [
            'order_id' => $orderId,
            'symbol' => $orderCurrency->symbol,
        ]);

        $formBuilder = $this->get('prestashop.core.form.identifiable_object.builder.cancel_product_form_builder');
        $backOfficeOrderButtons = new ActionsBarButtonsCollection();

        try {
            $this->dispatchHook(
                'actionGetAdminOrderButtons', [
                'controller' => $this,
                'id_order' => $orderId,
                'actions_bar_buttons_collection' => $backOfficeOrderButtons,
            ]);

            $cancelProductForm = $formBuilder->getFormFor($orderId);
        } catch (Exception $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages($e)));

            return $this->redirectToRoute('admin_orders_index');
        }

        $this->handleOutOfStockProduct($orderForViewing);

        $merchandiseReturnEnabled = (bool) $this->configuration->get('PS_ORDER_RETURN');

        /** @var OrderSiblingProviderInterface $orderSiblingProvider */
        $orderSiblingProvider = $this->get('prestashop.adapter.order.order_sibling_provider');

        $paginationNum = (int) $this->configuration->get('PS_ORDER_PRODUCTS_NB_PER_PAGE', self::DEFAULT_PRODUCTS_NUMBER);
        $paginationNumOptions = self::PRODUCTS_PAGINATION_OPTIONS;
        if (!in_array($paginationNum, $paginationNumOptions)) {
            $paginationNumOptions[] = $paginationNum;
        }
        sort($paginationNumOptions);

        return $this->render('@Modules/demogrid/views/admin/orderView.html.twig', [
            'showContentHeader' => true,
            'enableSidebar' => true,
            'orderCurrency' => $orderCurrency,
            'meta_title' => $this->trans('Orders', 'Admin.Orderscustomers.Feature'),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
            'orderForViewing' => $orderForViewing,
            'addOrderCartRuleForm' => $addOrderCartRuleForm->createView(),
            'updateOrderStatusForm' => $updateOrderStatusForm->createView(),
            'updateOrderStatusActionBarForm' => $updateOrderStatusActionBarForm->createView(),
            'addOrderPaymentForm' => $addOrderPaymentForm->createView(),
            'changeOrderCurrencyForm' => $changeOrderCurrencyForm->createView(),
            'privateNoteForm' => $privateNoteForm ? $privateNoteForm->createView() : null,
            'updateOrderShippingForm' => $updateOrderShippingForm->createView(),
            'cancelProductForm' => $cancelProductForm->createView(),
            'invoiceManagementIsEnabled' => $orderForViewing->isInvoiceManagementIsEnabled(),
            'changeOrderAddressForm' => $changeOrderAddressForm ? $changeOrderAddressForm->createView() : null,
            'orderMessageForm' => $orderMessageForm->createView(),
            'addProductRowForm' => $addProductRowForm->createView(),
            'editProductRowForm' => $editProductRowForm->createView(),
            'backOfficeOrderButtons' => $backOfficeOrderButtons,
            'merchandiseReturnEnabled' => $merchandiseReturnEnabled,
            'priceSpecification' => $this->getContextLocale()->getPriceSpecification($orderCurrency->iso_code)->toArray(),
            'previousOrderId' => $orderSiblingProvider->getPreviousOrderId($orderId),
            'nextOrderId' => $orderSiblingProvider->getNextOrderId($orderId),
            'paginationNum' => $paginationNum,
            'paginationNumOptions' => $paginationNumOptions,
        ]);
    }






    /**
     * @param OrderForViewing $orderForViewing
     */
    private function handleOutOfStockProduct(OrderForViewing $orderForViewing)
    {
        $isStockManagementEnabled = $this->configuration->get('PS_STOCK_MANAGEMENT');
        if (!$isStockManagementEnabled || $orderForViewing->isDelivered() || $orderForViewing->isShipped()) {
            return;
        }

        foreach ($orderForViewing->getProducts()->getProducts() as $product) {
            if ($product->getAvailableQuantity() <= 0) {
                $this->addFlash(
                    'warning',
                    $this->trans('This product is out of stock:', 'Admin.Orderscustomers.Notification') . ' ' . $product->getName()
                );
            }
        }
    }
    

}


