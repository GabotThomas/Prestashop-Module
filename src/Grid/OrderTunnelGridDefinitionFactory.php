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

namespace DemoGrid\Grid;

use PrestaShop\PrestaShop\Core\Grid\Action\GridActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Type\SimpleGridAction;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Hook\HookDispatcherInterface;

/**
 * Class ProductGridDefinitionFactory creates definition for our products grid
 */
final class OrderTunnelGridDefinitionFactory extends AbstractGridDefinitionFactory
{

    /**
     * @var string
     */
    private $resetFiltersUrl;

    /**
     * @var string
     */
    private $redirectUrl;

    /**
     * @param HookDispatcherInterface $hookDispatcher
     * @param string $resetFiltersUrl
     * @param string $redirectUrl
     */
    public function __construct(HookDispatcherInterface $hookDispatcher,$resetFiltersUrl, $redirectUrl)
    {
        parent::__construct($hookDispatcher);
        $this->resetFiltersUrl = $resetFiltersUrl;
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function getId()
    {
        return 'ordertunnel';
    }

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return $this->trans('Orders', [], 'Modules.DemoGrid.Admin');
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumns()
    {
        return (new ColumnCollection())
            ->add((new DataColumn('id_order'))
                ->setName($this->trans('ID', [], 'Modules.DemoGrid.Admin'))
                ->setOptions([
                    'field' => 'id_order',
                ])
            )
            ->add((new DataColumn('reference'))
                ->setName($this->trans('Reference', [], 'Modules.DemoGrid.Admin'))
                ->setOptions([
                    'field' => 'reference',
                ])
            )
            ->add((new ActionColumn('actions'))
                ->setName($this->trans('Actions', [], 'Admin.Actions'))
                ->setOptions([
                    'actions' => $this->getRowActions(),
                ])
            )
        ;
    }

    /**
     * {@inheritdoc}
     *
     * Define filters and associate them with columns.
     * Note that you can add filters that are not associated with any column.
     */
    protected function getFilters()
    {

        return (new FilterCollection())
            ->add((new Filter('id_order', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                ])
                ->setAssociatedColumn('id_order')
            )
            ->add((new Filter('reference', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                ])
                ->setAssociatedColumn('reference')
            )
            
            ->add((new Filter('actions', SearchAndResetType::class))
                ->setAssociatedColumn('actions')
                ->setTypeOptions([
                    'attr' => [
                        'data-url' => $this->resetFiltersUrl,
                        'data-redirect' => $this->redirectUrl,
                    ],
                ])
            )
        ;
    }

    /**
     * Extracted row action definition into separate method.
     */
    private function getRowActions()
    {
        return (new RowActionCollection())
            ->add((new LinkRowAction('edit'))
                ->setName($this->trans('Edit', [], 'Admin.Actions'))
                ->setOptions([
                    'route' => 'demogrid_admin_orderview',
                    'route_param_name' => 'orderId',
                    'route_param_field' => 'id_order',
                ])
                ->setIcon('edit')
            )
        ;
    }
}

